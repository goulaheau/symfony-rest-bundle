<?php

namespace Goulaheau\RestBundle\Controller;

use Goulaheau\RestBundle\Exception\RestException;
use Goulaheau\RestBundle\Service\RestService;
use Goulaheau\RestBundle\Utils\RestParams;
use Goulaheau\RestBundle\Utils\RestSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @var RestSerializer
     */
    protected $serializer;

    /**
     * @var RestParams
     */
    protected $restParams;

    public function __construct(string $entityClass, RestService $service, RestSerializer $serializer)
    {
        $this->entityClass = $entityClass;
        $this->service = $service;
        $this->serializer = $serializer;
    }

    /**
     * @Route("", methods={"GET"})
     */
    public function searchEntities(Request $request): ?JsonResponse
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entities = $this->service->search($this->restParams);
            $entities = $this->normalize($entities);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entities);
    }

    /**
     * @Route("/{id}", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getEntity(string $id, Request $request): ?JsonResponse
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entity = $this->service->get($id);
            $entity = $this->normalize($entity);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entity, Response::HTTP_CREATED);
    }

    /**
     * @Route("", methods={"POST"})
     */
    public function createEntity(Request $request): ?JsonResponse
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entity = $this->service->create($request->getContent());
            $entity = $this->normalize($entity);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entity, Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function updateEntity(string $id, Request $request): ?JsonResponse
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entity = $this->service->update($request->getContent(), $id);
            $entity = $this->normalize($entity);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entity);
    }

    /**
     * @Route("/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function deleteEntity(string $id): ?JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param \Exception $exception
     * @return JsonResponse
     */
    protected function exceptionHandler(\Exception $exception)
    {
        switch (true) {
            case $exception instanceof RestException:
                return $this->json($exception->getData(), $exception->getStatus());
            default:
                return $this->json($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param      $data
     * @param null $toEntity
     * @return object
     */
    protected function deserialize($data, $toEntity = null)
    {
        return $this->serializer->deserialize($data, $this->entityClass, $toEntity);
    }

    /**
     * @param $data
     * @return array|bool|float|int|mixed|string
     */
    protected function normalize($data)
    {
        return $this->serializer->normalize(
            $data,
            $this->restParams->getAttributes(),
            $this->restParams->getGroups(),
            $this->restParams->getEntityMethods()
        );
    }
}
