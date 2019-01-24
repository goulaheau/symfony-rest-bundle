<?php

namespace Goulaheau\RestBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
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
        $queryBuilder = $this->createQueryBuilder('o')->select('o');

        $this->queryBuilderConditions($queryBuilder, $conditions, $mode);
        $this->queryBuilderSorts($queryBuilder, $sorts);
        $this->queryBuilderJoins($queryBuilder, $joins);
        $this->queryBuilderPager($queryBuilder, $pager);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Condition[]  $conditions
     * @param string       $mode
     */
    protected function queryBuilderConditions($queryBuilder, $conditions, $mode)
    {
        foreach ($this->getPredicates($queryBuilder, $conditions) as $predicate) {
            $queryBuilder->{$mode . 'Where'}($predicate);
        }

        $this->queryBuilderParameters($queryBuilder, $conditions);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Condition[]  $conditions
     *
     * @return array
     */
    protected function getPredicates($queryBuilder, $conditions)
    {
        $predicates = [];

        foreach ($conditions as $condition) {
            $predicates[] = $condition->getPredicate($queryBuilder);
        }

        return $predicates;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Condition[] $conditions
     */
    protected function queryBuilderParameters($queryBuilder, $conditions)
    {
        foreach ($conditions as $condition) {
            $queryBuilder->setParameter($condition->getParameter(), $condition->getValue());
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Sort[]       $sorts
     */
    protected function queryBuilderSorts($queryBuilder, $sorts)
    {
        foreach ($sorts as $sort) {
            $property = $sort->getProperty();
            $order = $sort->getOrder();

            $queryBuilder->orderBy($property, $order);
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Join[]       $joins
     */
    protected function queryBuilderJoins($queryBuilder, $joins)
    {
        foreach ($joins as $join) {
            $joinFunction = $join->getType() . 'Join';
            $path = $join->getPath();
            $name = $join->getName();

            $queryBuilder->$joinFunction($path, $name);
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Pager|null   $pager
     */
    protected function queryBuilderPager($queryBuilder, $pager)
    {
        if ($pager) {
            $queryBuilder->setMaxResults($pager->getLimit());
            $queryBuilder->setFirstResult($pager->getOffset());
        }
    }
}
