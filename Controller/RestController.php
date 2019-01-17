<?php

namespace Goulaheau\RestBundle\Controller;

use Goulaheau\RestBundle\Exception\RestException;
use Goulaheau\RestBundle\Service\RestService;
use Goulaheau\RestBundle\Core\RestParams;
use Goulaheau\RestBundle\Core\RestSerializer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

abstract class RestController extends AbstractController
{
    /**
     * @var RestSerializer
     */
    protected $serializer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var RestService
     */
    protected $service;

    /**
     * @var RestParams
     */
    protected $restParams;

    public function __construct($entityClass, $service)
    {
        $this->entityClass = $entityClass;
        $this->service = $service;
    }

    /**
     * @Route("", methods={"GET"})
     */
    public function searchEntities(Request $request)
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entities = $this->service->search($this->restParams);
            $entities = $this->normalize($entities);

            $total = $this->getTotal($entities);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entities, 200, ['X-Rest-Total' => $total]);
    }

    /**
     * @Route("/{id}", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getEntity($id, Request $request)
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
    public function createEntity(Request $request)
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entity = $this->service->create($request->request->all());
            $entity = $this->normalize($entity);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entity, Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function updateEntity($id, Request $request)
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entity = $this->service->update($request->request->all(), $id);
            $entity = $this->normalize($entity);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entity);
    }

    /**
     * @Route("/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function deleteEntity($id)
    {
        try {
            $this->service->delete($id);
        } catch (\Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \Exception $exception
     * @return JsonResponse
     */
    protected function exceptionHandler($exception)
    {
        switch (true) {
            case $exception instanceof RestException:
                $this->logger->notice($exception->getMessage(), $exception->getTrace());
                return $this->json($exception->getData(), $exception->getStatus());
            default:
                $this->logger->error($exception->getMessage(), $exception->getTrace());
                return $this->json($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array $entities
     *
     * @return int
     */
    protected function getTotal($entities)
    {
        $pager = $this->restParams->getPager();
        $entitiesNumber = count($entities);

        if (!$pager) {
            return $entitiesNumber;
        }

        $limit = $pager->getLimit();
        $offset = $pager->getOffset();

        if ($entitiesNumber === 0 && $offset === 0) {
            return 0;
        }

        return in_array($entitiesNumber, [0, $limit], true) === 0
            ? count($this->service->search($this->restParams, true))
            : $offset + $entitiesNumber;
    }

    /**
     * @param      $data
     * @param null $toEntity
     * @return object
     */
    protected function denormalize($data, $toEntity = null)
    {
        return $this->serializer->denormalize($data, $this->entityClass, $toEntity);
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
