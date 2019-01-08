<?php

namespace Goulaheau\RestBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Goulaheau\RestBundle\Exception\RestException\RestEntityValidationException;
use Goulaheau\RestBundle\Exception\RestException\RestNotFoundException;
use Goulaheau\RestBundle\Repository\RestRepository;
use Goulaheau\RestBundle\Core\RestParams;
use Goulaheau\RestBundle\Core\RestSerializer;
use Goulaheau\RestBundle\Core\RestValidator;

abstract class RestService
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
     * @var RestSerializer
     */
    protected $serializer;

    /**
     * @var RestValidator
     */
    protected $validator;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var RestParams
     */
    protected $queryParams;

    /**
     * RestService constructor.
     *
     * @param string         $entityClass
     * @param RestRepository $repository
     */
    public function __construct(string $entityClass, RestRepository $repository)
    {
        $this->entityClass = $entityClass;
        $this->repository = $repository;
    }

    /**
     * @param RestParams $restParams
     * @param bool       $returnAll
     *
     * @return mixed
     */
    public function search(RestParams $restParams, $returnAll = false)
    {
        if ($restParams->getRepositoryMethod()) {
            return $this->callRepositoryMethod($restParams->getRepositoryMethod());
        }

        return $this->repository->search(
            $restParams->getConditions(),
            $restParams->getSorts(),
            $restParams->getJoins(),
            $returnAll ? null : $restParams->getPager(),
            $restParams->getMode()
        );
    }

    /**
     * @param $id
     *
     * @return object
     * @throws RestNotFoundException
     */
    public function get($id)
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw new RestNotFoundException();
        }

        return $entity;
    }

    /**
     * @param      $entity
     * @param bool $isDeserialized
     *
     * @return object
     * @throws RestEntityValidationException
     */
    public function create($entity, $isDeserialized = false)
    {
        if (!$isDeserialized) {
            $entity = $this->deserialize($entity);
        }

        $errors = $this->validate($entity);

        if ($errors) {
            throw new RestEntityValidationException($errors);
        }

        $this->objectManager->persist($entity);
        $this->objectManager->flush();

        return $entity;
    }

    /**
     * @param      $entity
     * @param null $id
     * @param bool $isDeserialized
     *
     * @return mixed
     * @throws RestNotFoundException
     * @throws RestEntityValidationException
     */
    public function update($entity, $id = null, $isDeserialized = false)
    {
        if ($id && !$isDeserialized) {
            $toEntity = $this->get($id);
        }

        if (isset($toEntity)) {
            $this->deserialize($entity, $toEntity);
        }

        $errors = $this->validate($entity);

        if ($errors) {
            throw new RestEntityValidationException($errors);
        }

        $this->objectManager->flush();

        return $entity;
    }

    /**
     * @param $id
     *
     * @throws RestNotFoundException
     */
    public function delete($id)
    {
        $entity = $this->get($id);

        $this->objectManager->remove($entity);
        $this->objectManager->flush();
    }

    /**
     * @param RestParams\Method $repositoryMethod
     *
     * @return mixed
     */
    public function callRepositoryMethod($repositoryMethod)
    {
        return $this->repository->{$repositoryMethod->getName()}(...$repositoryMethod->getParams());
    }

    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    public function setObjectManager($objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param $entity
     *
     * @return array
     */
    protected function validate($entity)
    {
        return $this->validator->validate($entity);
    }

    /**
     * @param      $data
     * @param null $toEntity
     *
     * @return object
     */
    protected function deserialize($data, $toEntity = null)
    {
        return $this->serializer->deserialize($data, $this->entityClass, $toEntity);
    }
}