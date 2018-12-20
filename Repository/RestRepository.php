<?php

namespace Goulaheau\RestBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

abstract class RestRepository extends ServiceEntityRepository
{
    public function __construct(string $entityClass, ManagerRegistry $registry)
    {
        parent::__construct($registry, $entityClass);
    }
}
