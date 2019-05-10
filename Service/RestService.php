<?php

namespace Goulaheau\RestBundle\Service;

use DMS\Filter\Filter;
use Doctrine\Common\Persistence\ObjectManager;
use Goulaheau\RestBundle\Core\RestParams;
use Goulaheau\RestBundle\Core\RestSerializer;
use Goulaheau\RestBundle\Core\RestValidator;
use Goulaheau\RestBundle\Exception\RestException\RestBadRequestException;
use Goulaheau\RestBundle\Exception\RestException\RestEntityValidationException;
use Goulaheau\RestBundle\Exception\RestException\RestNotFoundException;
use Goulaheau\RestBundle\Repository\RestRepository;

abstract class RestService
{
    /**
     * @var RestRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var callable | null
     */
    protected $factory;

    /**
     * @var RestSerializer
     */
    protected $serializer;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var RestValidator
     */
    protected $validator;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var RestParams
     */
    protected $queryParams;

    /**
     * @param RestRepository $repository
     * @param string         $entityClass
     * @param callable       $factory
     */
    public function __construct(RestRepository $repository, $entityClass, $factory = null)
    {
        $this->repository = $repository;
        $this->entityClass = $entityClass;
        $this->factory = $factory;
    }

    /**
     * @return RestRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param RestRepository $repository
     *
     * @return self
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

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
     * @return callable | null
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param callable | null $factory
     *
     * @return self
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;

        return $this;
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
     * @return Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param Filter $filter
     *
     * @return self
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return RestValidator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * @param RestValidator $validator
     *
     * @return self
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * @return ObjectManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param ObjectManager $manager
     *
     * @return self
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @return RestParams
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @param RestParams $queryParams
     *
     * @return self
     */
    public function setQueryParams($queryParams)
    {
        $this->queryParams = $queryParams;

        return $this;
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
            return $this->callRepositoryMethod($restParams, $returnAll);
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
     *
     * @throws RestEntityValidationException
     * @throws RestBadRequestException
     */
    public function create($entity, $isDeserialized = false)
    {
        if (!$entity) {
            throw new RestBadRequestException();
        }

        if (!$isDeserialized) {
            $entity = $this->denormalize($entity);
        }

        $errors = $this->filterAndValidate($entity);

        if ($errors) {
            throw new RestEntityValidationException($errors);
        }

        $this->manager->persist($entity);
        $this->manager->flush();

        return $entity;
    }

    /**
     * @param      $entity
     * @param null $id
     * @param bool $isDeserialized
     *
     * @return mixed
     *
     * @throws RestNotFoundException
     * @throws RestEntityValidationException
     * @throws RestBadRequestException
     */
    public function update($entity, $id = null, $isDeserialized = false)
    {
        if (!$entity) {
            throw new RestBadRequestException();
        }

        if ($id && !$isDeserialized) {
            $toEntity = $this->get($id);
        }

        if (isset($toEntity)) {
            $entity = $this->denormalize($entity, $toEntity);
        }

        $errors = $this->filterAndValidate($entity);

        if ($errors) {
            throw new RestEntityValidationException($errors);
        }

        $this->manager->flush();

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

        $this->manager->remove($entity);
        $this->manager->flush();
    }

    /**
     * @param RestParams $restParams
     * @param bool       $returnAll
     *
     * @return mixed
     */
    public function callRepositoryMethod($restParams, $returnAll = false)
    {
        $repositoryMethod = $restParams->getRepositoryMethod();

        return $this->repository->{$repositoryMethod->getName()}(
            $restParams->getConditions(),
            $restParams->getSorts(),
            $restParams->getJoins(),
            $returnAll ? null : $restParams->getPager(),
            $restParams->getMode(),
            ...$repositoryMethod->getParams()
        );
    }

    protected function filterAndValidate($entity)
    {
        return $this->filter($entity)->validate($entity);
    }

    protected function filter($entity)
    {
        $this->filter->filterEntity($entity);

        return $this;
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
    protected function denormalize($data, $toEntity = null)
    {
        return $this->serializer->denormalize($data, $this->entityClass, $this->factory, $toEntity);
    }
}
