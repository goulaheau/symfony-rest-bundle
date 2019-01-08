<?php

namespace Goulaheau\RestBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Goulaheau\RestBundle\Core\RestParams\Condition;
use Goulaheau\RestBundle\Core\RestParams\Join;
use Goulaheau\RestBundle\Core\RestParams\Pager;
use Goulaheau\RestBundle\Core\RestParams\Sort;

abstract class RestRepository extends ServiceEntityRepository
{
   public function __construct(ManagerRegistry $registry, $entityClass)
   {
       parent::__construct($registry, $entityClass);
   }

    /**
     * @param Condition[] $conditions
     * @param Sort[]      $sorts
     * @param Join[]      $joins
     * @param Pager       $pager
     * @param string      $mode
     *
     * @return mixed
     */
    public function search($conditions = [], $sorts = [], $joins = [], $pager = null, $mode = 'and')
    {
        $queryBuilder = $this->createQueryBuilder('o');
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

    protected function hasPrefix($value)
    {
        return strpos($value, '.') !== false;
    }
}
