<?php

namespace Goulaheau\RestBundle\Core\RestParams;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;

class Expression
{
    /**
     * @var string
     */
    protected $mode;

    /**
     * @var ArrayCollection | Condition[]
     */
    protected $conditions;

    /**
     * @var ArrayCollection | Expression[]
     */
    protected $expressions;

    /**
     * @param array $expression
     */
    public function __construct($expression = [])
    {
        $this->conditions = new ArrayCollection();
        $this->expressions = new ArrayCollection();

        $ret = $this->arrayToExpression($expression);
        $this->setMode($ret['mode']);
        $this->setConditions($ret['conditions']);
        $this->setExpressions($ret['expressions']);
    }

    protected function arrayToExpression($array)
    {
        $mode = 'and';
        $conditions = new ArrayCollection();
        $expressions = new ArrayCollection();

        foreach ($array as $key => $value) {
            if (!in_array($key, ['_a', '_o'], true)) {
                $propertyOperator = explode('-', $key);

                if (!in_array(count($propertyOperator), [1, 2])) {
                    $conditions[] = new Condition($propertyOperator[0], $value, $propertyOperator[1] ?? null);
                }
            } else {
                $mode = $key === '_a' ? 'and' : 'or';
                $expressionData = $this->arrayToExpression($value);
                $expressions[] = (new Expression())
                    ->setMode($expressionData['mode'])
                    ->setConditions($expressionData['conditions'])
                    ->setExpressions($expressionData['expressions']);
            }
        }

        return [
            'mode' => $mode,
            'conditions' => $conditions,
            'expressions' => $expressions,
        ];
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     *
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return ArrayCollection|Condition[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param ArrayCollection|Condition[] $conditions
     *
     * @return $this
     */
    public function setConditions($conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @param Condition $condition
     *
     * @return $this
     */
    public function addCondition($condition)
    {
        if (!$this->conditions->contains($condition)) {
            $this->conditions[] = $condition;
        }

        return $this;
    }

    /**
     * @param Condition $condition
     *
     * @return $this
     */
    public function removeCondition($condition)
    {
        if ($this->conditions->contains($condition)) {
            $this->conditions->removeElement($condition);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|Expression[]
     */
    public function getExpressions()
    {
        return $this->expressions;
    }

    /**
     * @param ArrayCollection|Expression[] $expressions
     *
     * @return $this
     */
    public function setExpressions($expressions)
    {
        $this->expressions = $expressions;

        return $this;
    }

    /**
     * @param Expression $expression
     *
     * @return $this
     */
    public function addExpression($expression)
    {
        if (!$this->expressions->contains($expression)) {
            $this->expressions[] = $expression;
        }

        return $this;
    }

    /**
     * @param Expression $expression
     *
     * @return $this
     */
    public function removeExpression($expression)
    {
        if ($this->expressions->contains($expression)) {
            $this->expressions->removeElement($expression);
        }

        return $this;
    }

    public function getPredicate(QueryBuilder $queryBuilder)
    {
        $conditionsPredicates = [];
        foreach ($this->getConditions() as $condition) {
            $conditionsPredicates[] = $condition->getPredicate($queryBuilder);
        }

        $expressionsPredicates = [];
        foreach ($this->getExpressions() as $expression) {
            $expressionsPredicates[] = $expression->getPredicate($queryBuilder);
        }

        return $queryBuilder->expr()->{"{$this->getMode()}X"}(...$conditionsPredicates, ...$expressionsPredicates);
    }
}
