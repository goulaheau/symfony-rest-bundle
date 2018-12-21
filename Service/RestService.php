<?php

namespace Goulaheau\RestBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Goulaheau\RestBundle\Exception\RestException\RestEntityValidationException;
use Goulaheau\RestBundle\Exception\RestException\RestNotFoundException;
use Goulaheau\RestBundle\Repository\RestRepository;
use Goulaheau\RestBundle\Utils\RestParams;
use Goulaheau\RestBundle\Utils\RestSerializer;
use Goulaheau\RestBundle\Utils\RestValidator;

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
    protected $manager;

    /**
     * @var RestParams
     */
    protected $queryParams;

    /**
     * RestService constructor.
     *
     * @param string         $entityClass
     * @param RestRepository $repository
     * @param RestSerializer $serializer
     * @param RestValidator  $validator
     * @param ObjectManager  $manager
     */
    public function __construct(
        string $entityClass,
        RestRepository $repository,
        RestSerializer $serializer,
        RestValidator $validator,
        ObjectManager $manager
    ) {
        $this->entityClass = $entityClass;
        $this->repository = $repository;
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->manager = $manager;
    }

    /**
     * @param RestParams $restParams
     * @return mixed
     */
    public function search(RestParams $restParams)
    {
        if ($restParams->getRepositoryMethod()) {
            return $this->callRepositoryMethod($restParams->getRepositoryMethod());
        }

        $mode = $restParams->getMode();
        $sorts = $restParams->getSorts();
        $pager = $restParams->getPager();
        $joins = $restParams->getJoins();
        $conditions = $restParams->getConditions();

        $queryBuilder = $this->repository->createQueryBuilder('o');
        $queryBuilder->select('o');

        foreach ($conditions as $condition) {
            $property = $condition->getProperty();
            $operator = $condition->getOperator();
            $value = $condition->getValue();
            $parameter = $condition->getParameter();

            if (!$this->hasPrefix($property)) {
                $property = 'o.' . $property;
            }

            $predicate = $queryBuilder->expr()->$operator($property, ':' . $parameter);
            $queryBuilder->{$mode . 'Where'}($predicate);
            $queryBuilder->setParameter($parameter, $value);
        }

        foreach ($sorts as $sort) {
            $property = $sort->getProperty();
            $order = $sort->getOrder();

            if (!$this->hasPrefix($property)) {
                $property = 'o.' . $property;
            }

            $queryBuilder->orderBy($property, $order);
        }

        foreach ($joins as $join) {
            $joinFunction = $join->getType() . 'Join';
            $path = $join->getPath();
            $name = $join->getName();

            $queryBuilder->$joinFunction($path, $name);
        }

        if ($pager) {
            $queryBuilder->setMaxResults($pager->getLimit());
            $queryBuilder->setFirstResult($pager->getOffset());
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $id
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

        $this->manager->persist($entity);
        $this->manager->flush();

        return $entity;
    }

    /**
     * @param      $entity
     * @param null $id
     * @param bool $isDeserialized
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

        $this->manager->flush();

        return $entity;
    }

    /**
     * @param $id
     * @throws RestNotFoundException
     */
    public function delete($id)
    {
        $entity = $this->get($id);

        $this->manager->remove($entity);
        $this->manager->flush();
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

    /**
     * @param $entity
     * @return array
     */
    protected function validate($entity)
    {
        return $this->validator->validate($entity);
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

    protected function hasPrefix($value)
    {
        return strpos($value, '.') !== false;
    }
}
