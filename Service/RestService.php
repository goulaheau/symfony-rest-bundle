<?php

namespace Goulaheau\RestBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Goulaheau\RestBundle\Core\RestParams;
use Goulaheau\RestBundle\Core\RestSerializer;
use Goulaheau\RestBundle\Core\RestValidator;
use Goulaheau\RestBundle\Exception\RestException\RestEntityValidationException;
use Goulaheau\RestBundle\Exception\RestException\RestNotFoundException;
use Goulaheau\RestBundle\Repository\RestRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
            if ($returnAll) {
                $restParams->setPager(null);
            }

            return $this->callRepositoryMethod($restParams);
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
        if (!$entity) {
            throw new BadRequestHttpException();
        }

        if (!$isDeserialized) {
            $entity = $this->denormalize($entity);
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
     *
     * @return mixed
     * @throws RestNotFoundException
     * @throws RestEntityValidationException
     */
    public function update($entity, $id = null, $isDeserialized = false)
    {
        if (!$entity) {
            throw new BadRequestHttpException();
        }

        if ($id && !$isDeserialized) {
            $toEntity = $this->get($id);
        }

        if (isset($toEntity)) {
            $this->denormalize($entity, $toEntity);
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
     * @param RestParams        $restParams
     * @param RestParams\Method $repositoryMethod
     *
     * @return mixed
     */
    public function callRepositoryMethod($restParams)
    {
        $repositoryMethod = $restParams->getRepositoryMethod();

        return $this->repository->{$repositoryMethod->getName()}($restParams, ...$repositoryMethod->getParams());
    }

    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    public function setManager($manager)
    {
        $this->manager = $manager;
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
        return $this->serializer->denormalize($data, $this->entityClass, $toEntity);
    }
}
