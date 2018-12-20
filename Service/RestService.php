<?php

namespace Goulaheau\RestBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Goulaheau\RestBundle\Entity\RestEntity;
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
        if ($restParams->getRepositoryFunction()) {
            return $this->callRepositoryFunction($restParams->getRepositoryFunction());
        }

        return $this->repository->findAll();
    }

    /**
     * @param $id
     * @return RestEntity
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
     * @param $repositoryFunction
     * @return mixed
     */
    public function callRepositoryFunction($repositoryFunction)
    {
        $function = $repositoryFunction['function'];
        $parameters = $repositoryFunction['params'];

        return $this->repository->$function(...$parameters);
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
}
