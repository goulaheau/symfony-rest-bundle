<?php

namespace Goulaheau\RestBundle\Core;

use Doctrine\Common\Collections\ArrayCollection;
use Goulaheau\RestBundle\Core\RestParams\Condition;
use Goulaheau\RestBundle\Core\RestParams\Join;
use Goulaheau\RestBundle\Core\RestParams\Method;
use Goulaheau\RestBundle\Core\RestParams\Pager;
use Goulaheau\RestBundle\Core\RestParams\Sort;

class RestParams
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $groups = [];

    /**
     * @var ArrayCollection|Method[]
     */
    protected $entityMethods;

    /**
     * @var Method
     */
    protected $repositoryMethod;

    /**
     * @var Pager
     */
    protected $pager;

    /**
     * @var ArrayCollection|Sort[]
     */
    protected $sorts;

    /**
     * @var ArrayCollection|Join[]
     */
    protected $joins;

    /**
     * @var string
     */
    protected $mode = 'and';

    /**
     * @var ArrayCollection|Condition[]
     */
    protected $conditions;

    /**
     * RestParams constructor.
     *
     * @param array $queryParams
     */
    public function __construct($queryParams = [])
    {
        $this->entityMethods = new ArrayCollection();
        $this->sorts = new ArrayCollection();
        $this->joins = new ArrayCollection();
        $this->conditions = new ArrayCollection();

        foreach ($queryParams as $key => $value) {
            switch ($key) {
                case '_a':
                    $this->setAttributes($value);
                    break;

                case '_g':
                    $this->setGroups($value);
                    break;

                case '_em':
                    $methods = explode(',', $value);
                    $params = isset($queryParams['_emp']) && is_array($queryParams['_emp']) ? $queryParams['_emp'] : [];

                    foreach ($methods as $index => $name) {
                        $this->addEntityMethod(new Method($name, $params[$index] ?? null));
                    }

                    break;

                case '_emp':
                    break;

                case '_rm':
                    $this->setRepositoryMethod(new Method($value, $queryParams['_rmp'] ?? null));
                    break;

                case '_rmp':
                    break;

                case '_p':
                    $this->setPager(new Pager($value, $queryParams['_pp'] ?? null));
                    break;

                case '_pp':
                    break;

                case '_s':
                    $sortParts = explode(',', $value);

                    foreach ($sortParts as $part) {
                        $property = $part;
                        $order = 'ASC';

                        if (strpos($property, '-') === 0) {
                            $property = substr($property, 1);
                            $order = 'DESC';
                        }

                        $this->addSort(new Sort($property, $order));
                    }

                    break;

                case '_j':
                    $joinParts = explode(',', $value);

                    foreach ($joinParts as $joinType) {
                        $joinType = explode('-', $joinType);

                        if (in_array(count($joinType), [1, 2])) {
                            $this->addJoin(new Join($joinType[0], $joinType[1] ?? null));
                        }
                    }

                    break;

                case '_m':
                    $this->setMode($value);
                    break;

                default:
                    $propertyOperator = explode('-', $key);

                    if (in_array(count($propertyOperator), [1, 2])) {
                        $this->addCondition(new Condition($propertyOperator[0], $value, $propertyOperator[1] ?? null));
                    }
            }
        }
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string|array $attributes
     *
     * @return self
     */
    public function setAttributes($attributes): self
    {
        if ($attributes === null) {
            $this->attributes = [];

            return $this;
        }

        if (is_array($attributes)) {
            $this->attributes = $attributes;

            return $this;
        }

        $attributes = explode(',', $attributes);
        $attributes = $this->relationsToArray($attributes);

        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param string|array $groups
     *
     * @return self
     */
    public function setGroups($groups): self
    {
        if ($groups === null) {
            $this->groups = [];

            return $this;
        }

        if (is_array($groups)) {
            $this->groups = $groups;

            return $this;
        }

        $this->groups = explode(',', $groups);

        return $this;
    }

    /**
     * @return ArrayCollection|Method[]
     */
    public function getEntityMethods()
    {
        return $this->entityMethods;
    }

    /**
     * @param ArrayCollection|Method[] $entityMethods
     *
     * @return RestParams
     */
    public function setEntityMethods($entityMethods)
    {
        $this->entityMethods = $entityMethods;

        return $this;
    }

    /**
     * @param Method $entityMethod
     *
     * @return self
     */
    public function addEntityMethod($entityMethod)
    {
        if (!$this->entityMethods->contains($entityMethod)) {
            $this->entityMethods[] = $entityMethod;
        }

        return $this;
    }

    /**
     * @param Method $entityMethod
     *
     * @return self
     */
    public function removeEntityMethod($entityMethod)
    {
        if ($this->entityMethods->contains($entityMethod)) {
            $this->entityMethods->removeElement($entityMethod);
        }

        return $this;
    }

    /**
     * @return Method
     */
    public function getRepositoryMethod()
    {
        return $this->repositoryMethod;
    }

    /**
     * @param Method $repositoryMethod
     *
     * @return self
     */
    public function setRepositoryMethod($repositoryMethod)
    {
        $this->repositoryMethod = $repositoryMethod;

        return $this;
    }

    /**
     * @return Pager
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * @param Pager $pager
     *
     * @return self
     */
    public function setPager($pager)
    {
        $this->pager = $pager;

        return $this;
    }

    /**
     * @return ArrayCollection|Sort[]
     */
    public function getSorts()
    {
        return $this->sorts;
    }

    /**
     * @param ArrayCollection|Sort[] $sorts
     *
     * @return RestParams
     */
    public function setSorts($sorts)
    {
        $this->sorts = $sorts;

        return $this;
    }

    /**
     * @param Sort $sort
     *
     * @return self
     */
    public function addSort($sort)
    {
        if (!$this->sorts->contains($sort)) {
            $this->sorts[] = $sort;
        }

        return $this;
    }

    /**
     * @param Sort $sort
     *
     * @return self
     */
    public function removeSort($sort)
    {
        if ($this->sorts->contains($sort)) {
            $this->sorts->removeElement($sort);
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param null|string $mode
     *
     * @return RestParams
     */
    public function setMode($mode = 'and')
    {
        $mode = in_array($mode, ['and', 'or']) ? $mode : 'and';

        $this->mode = $mode;

        return $this;
    }

    /**
     * @return ArrayCollection|Join[]
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * @param ArrayCollection|Join[] $joins
     *
     * @return RestParams
     */
    public function setJoins($joins)
    {
        $this->joins = $joins;

        return $this;
    }

    /**
     * @param Join $join
     *
     * @return self
     */
    public function addJoin($join)
    {
        if (!$this->joins->contains($join)) {
            $this->joins[] = $join;
        }

        return $this;
    }

    /**
     * @param Join $join
     *
     * @return self
     */
    public function removeJoin($join)
    {
        if ($this->joins->contains($join)) {
            $this->joins->removeElement($join);
        }

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
     * @return RestParams
     */
    public function setConditions($conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @param Condition $condition
     *
     * @return self
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
     * @return self
     */
    public function removeCondition($condition)
    {
        if ($this->conditions->contains($condition)) {
            $this->conditions->removeElement($condition);
        }

        return $this;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function hasPrefix($value)
    {
        return strpos($value, '.') !== false;
    }

    protected function relationsToArray($attributes)
    {
        $relations = [];

        foreach ($attributes as $key => $value) {
            if (!strpos($value, '.')) {
                continue;
            }

            $this->dotStringToArray($relations, $value);

            unset($attributes[$key]);
        }

        $attributes = array_values($attributes);

        return array_merge($attributes, $relations);
    }

    protected function dotStringToArray(&$array, $value)
    {
        $keys = explode('.', $value);

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $array = &$array[];
                continue;
            }

            $array = &$array[$key];
        }

        $array = $keys[count($keys) - 1];
    }
}
