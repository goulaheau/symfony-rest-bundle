<?php

namespace Goulaheau\RestBundle\Controller;

use Exception;
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
     * @var RestService
     */
    protected $service;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var RestParams
     */
    protected $restParams;

    public function __construct($service, $entityClass)
    {
        $this->service = $service;
        $this->entityClass = $entityClass;
    }

    /**
     * @return RestSerializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param RestSerializer $serializer
     *
     * @return self
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return self
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return RestService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param RestService $service
     *
     * @return self
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     *
     * @return self
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return RestParams
     */
    public function getRestParams()
    {
        return $this->restParams;
    }

    /**
     * @param RestParams $restParams
     *
     * @return self
     */
    public function setRestParams($restParams)
    {
        $this->restParams = $restParams;

        return $this;
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

            $headers = ['X-Rest-Total' => $this->getTotal($entities)];
        } catch (Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entities, Response::HTTP_OK, $headers);
    }

    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function getEntity($id, Request $request)
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entity = $this->service->get($id);
            $entity = $this->normalize($entity);
        } catch (Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entity);
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
        } catch (Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entity, Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     */
    public function updateEntity($id, Request $request)
    {
        try {
            $this->restParams = new RestParams($request->query->all());

            $entity = $this->service->update($request->request->all(), $id);
            $entity = $this->normalize($entity);
        } catch (Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json($entity);
    }

    /**
     * @Route("/{id}", methods={"DELETE"})
     */
    public function deleteEntity($id)
    {
        try {
            $this->service->delete($id);
        } catch (Exception $exception) {
            return $this->exceptionHandler($exception);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Exception $exception
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    protected function exceptionHandler($exception)
    {
        switch (true) {
            case $exception instanceof RestException:
                $this->logger->notice($exception->getMessage(), $exception->getTrace());
                return $this->json($exception->getData(), $exception->getStatus());
            default:
                // TODO: Throw exception only in dev mode.
                throw $exception;
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

        return in_array($entitiesNumber, [0, $limit], true)
            ? count($this->service->search($this->restParams, true))
            : $offset + $entitiesNumber;
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
