<?php

namespace Goulaheau\RestBundle\Service;

use Goulaheau\RestBundle\Repository\RestRepository;
use Goulaheau\RestBundle\Utils\RestQueryParams;

abstract class RestService
{
    /**
     * @var RestRepository
     */
    protected $repository;

    /**
     * @var RestQueryParams
     */
    protected $queryParams;

    public function __construct(RestRepository $repository)
    {
        $this->repository = $repository;
    }

    public function search(RestQueryParams $queryParams)
    {
        if ($queryParams->repositoryFunctions) {
            return $this->callRepositoryFunction($queryParams);
        }

        return $this->repository->findAll();
    }

    public function callRepositoryFunction(RestQueryParams $queryParams)
    {
        $function = $queryParams->repositoryFunctions[0]['function'];
        $parameters = $queryParams->repositoryFunctions[0]['parameters'];

        return $this->repository->$function(...$parameters);
    }
}