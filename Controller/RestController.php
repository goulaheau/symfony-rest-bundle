<?php

namespace Goulaheau\RestBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Goulaheau\RestBundle\Repository\RestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RestController
 *
 * @package Goulaheau\RestBundle\Controller
 *
 * @Rout
 */
abstract class RestController extends AbstractController
{
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var RestRepository
     */
    protected $repository;

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
     * RestController constructor.
     *
     * @param string                 $entityClass
     * @param RestRepository         $repository
     * @param EntityManagerInterface $manager
     * @param ValidatorInterface     $validator
     * @param SerializerInterface    $serializer
     */
    public function __construct(
        string $entityClass,
        RestRepository $repository,
        EntityManagerInterface $manager,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        $this->entityClass = $entityClass;
        $this->repository = $repository;
        $this->manager = $manager;
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    /**
     * @Route("", methods={"GET"})
     */
    public function listEntities(): ?JsonResponse
    {
        $entities = $this->repository->findAll();

        $entities = $this->normalize($entities);

        return $this->json($entities);
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
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
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
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->manager->flush();

        $entity = $this->validate($entity);

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

    /**
     * @param $entity
     *
     * @return array
     */
    protected function validate($entity)
    {
        $errors = $this->validator->validate($entity);

        $dataErrors = [];

        /** @var ConstraintViolation $error */
        foreach ($errors as $error) {
            if (!isset($dataErrors[$error->getPropertyPath()])) {
                $dataErrors[$error->getPropertyPath()] = [];
            }

            $dataErrors[$error->getPropertyPath()][] = $error->getMessage();
        }

        return $dataErrors;
    }

    /**
     * @param $data
     *
     * @return array|bool|float|int|mixed|string
     */
    protected function normalize($data)
    {
        // TODO: use attributes from query

        $context = [
            // 'attributes' => [],
            'groups' => 'read',
        ];

        return $this->serializer->normalize($data, null, $context);
    }

    /**
     * @param      $data
     * @param null $toEntity
     *
     * @return object
     */
    protected function deserialize($data, $toEntity = null)
    {
        $context = ['groups' => 'update'];

        if ($toEntity) {
            $context['object_to_populate'] = $toEntity;
        }

        return $this->serializer->deserialize($data, $this->entityClass, 'json', $context);
    }
}
