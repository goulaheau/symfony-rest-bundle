<?php

namespace Goulaheau\RestBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Goulaheau\RestBundle\Service\RestService;
use Goulaheau\RestBundle\Utils\RestQueryParams;
use Goulaheau\RestBundle\Utils\RestSerializer;
use Goulaheau\RestBundle\Utils\RestValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RestController
 *
 * @package Goulaheau\RestBundle\Controller
 */
abstract class RestController extends AbstractController
{
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var RestService
     */
    protected $service;

    /**
     * @var EntityManagerInterface
     */
    protected $manager;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var RestQueryParams
     */
    protected $queryParams;


    public function __construct(
        string $entityClass,
        RestService $service,
        ObjectManager $manager,
        RestValidator $validator,
        RestSerializer $serializer
    ) {
        $this->entityClass = $entityClass;
        $this->service = $service;
        $this->manager = $manager;
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    /**
     * @Route("", methods={"GET"})
     */
    public function listEntities(Request $request): ?JsonResponse
    {
        $this->queryParams = new RestQueryParams($request->query->all());

        $entities = $this->service->search($this->queryParams);

        $entitiesFunctions = $this->getEntitiesFunctions($entities);
        $repositoriesFunctions = $this->getRepositoriesFunctions();

        $entities = $this->normalize($entities);
        $entities = $this->mergeEntitiesFunctions($entities, $entitiesFunctions);

        $json = [
            '_entities' => $entities,
            '_repositoryFunctions' => $repositoriesFunctions,
        ];

        return $this->json($json);
    }

    protected function getRepositoriesFunctions()
    {
        if (!$this->queryParams->repositoryFunctions) {
            return null;
        }

        $data = [];

        foreach ($this->queryParams->repositoryFunctions as $repositoryFunction) {
            $function = $repositoryFunction['function'];
            $parameters = $repositoryFunction['parameters'];

            $data[$function] = $this->repository->$function(...$parameters);
        }

        return $data;
    }

    protected function mergeEntitiesFunctions($entities, $entitiesFunctions)
    {
        if ($entitiesFunctions) {
            foreach ($entities as $key => $entity) {
                if (isset($entitiesFunctions[$key])) {
                    $entities[$key]['_entityFunctions'] = $entitiesFunctions[$key];
                }
            }
        }

        return $entities;
    }

    protected function getEntitiesFunctions($entities)
    {
        if (!$this->queryParams->entityFunctions) {
            return null;
        }

        $data = [];
        foreach ($entities as $entity) {
            $entityData = [];

            foreach ($this->queryParams->entityFunctions as $entityFunction) {
                $function = $entityFunction['function'];
                $parameters = $entityFunction['parameters'];

                try {
                    $entityData[$function] = $entity->$function(...$parameters);
                } catch (\Exception $e) {
                }
            }

            $data[] = $entityData;
        }

        return $data;
    }

    /**
     * @Route("/{id}", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getEntity(string $id): ?JsonResponse
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }

        $entity = $this->normalize($entity);

        return $this->json($entity, Response::HTTP_CREATED);
    }

    /**
     * @Route("", methods={"POST"})
     */
    public function createEntity(Request $request): ?JsonResponse
    {
        $entity = $this->deserialize($request->getContent());

        $errors = $this->validate($entity);

        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->manager->persist($entity);
        $this->manager->flush();

        $entity = $this->normalize($entity);

        return $this->json($entity, Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function updateEntity(string $id, Request $request): ?JsonResponse
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }

        $this->deserialize($request->getContent(), $entity);

        $errors = $this->validate($entity);

        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->manager->flush();

        $entity = $this->normalize($entity);

        return $this->json($entity);
    }

    /**
     * @Route("/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function deleteEntity(string $id): ?JsonResponse
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        $this->manager->remove($entity);
        $this->manager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function validate($entity)
    {
        return $this->validator->validate($entity);
    }

    protected function deserialize($data, $toEntity = null)
    {
        return $this->serializer->deserialize($data, $this->entityClass, $toEntity);
    }

    protected function normalize($data)
    {
        return $this->serializer->normalize($data, $this->queryParams);
    }
}
