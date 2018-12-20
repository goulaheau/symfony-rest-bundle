<?php

namespace Goulaheau\RestBundle\Utils;

class RestParams
{
    protected $attributes = [];

    protected $groups = [];

    protected $entityFunctions = [];

    protected $repositoryFunction = [];

    protected $sorts = [];

    protected $pager = [];

    protected $mode = 'and';

    protected $conditions = [];


    public function __construct($queryParams = [])
    {
        if (isset($queryParams['_a'])) {
            $this->setAttributes($queryParams['_a']);
        }

        if (isset($queryParams['_g'])) {
            $this->setGroups($queryParams['_g']);
        }

        if (isset($queryParams['_ef'])) {
            $functions = $queryParams['_ef'];
            $params = isset($queryParams['_efp']) && is_array($queryParams['_efp']) ? $queryParams['_efp'] : [];
            $this->setEntityFunctions($functions, $params);
        }

        if (isset($queryParams['_rf'])) {
            $function = $queryParams['_rf'];
            $params = isset($queryParams['_rfp']) && is_array($queryParams['_rfp']) ? $queryParams['_rfp'] : [];
            $this->setRepositoryFunction($function, $params);
        }

        if (isset($queryParams['_s'])) {
            $this->setSorts($queryParams['_s']);
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
     * @return self
     */
    public function setAttributes($attributes): self
    {
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
     * @return self
     */
    public function setGroups($groups): self
    {
        if (is_array($groups)) {
            $this->groups = $groups;

            return $this;
        }

        $this->groups = explode(',', $groups);

        return $this;
    }

    /**
     * @return array
     */
    public function getEntityFunctions(): array
    {
        return $this->entityFunctions;
    }

    /**
     * @param string|array $functions
     * @return self
     */
    public function setEntityFunctions($functions, $params = []): self
    {
        if (is_array($functions)) {
            $this->entityFunctions = $functions;

            return $this;
        }

        $functions = explode(',', $functions);

        foreach ($functions as $key => $function) {
            $functions[$key] = [
                'function' => $function,
                'params' => isset($params[$key]) ? $params[$key] : [],
            ];
        }

        $this->entityFunctions = $functions;

        return $this;
    }

    /**
     * @return array
     */
    public function getRepositoryFunction(): array
    {
        return $this->repositoryFunction;
    }

    /**
     * @param       $function
     * @param array $params
     * @return self
     */
    public function setRepositoryFunction($function, $params = []): self
    {
        $this->repositoryFunction = [
            'function' => $function,
            'params' => $params,
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function getSorts(): array
    {
        return $this->sorts;
    }

    /**
     * @param string|array $sorts
     * @return self
     */
    public function setSorts($sorts): self
    {
        if (is_array($sorts)) {
            $this->sorts = $sorts;

            return $this;
        }

        $sorts = explode(',', $sorts);

        foreach ($sorts as $sort) {
            $order = 'asc';

            if (strpos($sort, '-') === 0) {
                $order = 'desc';
                $sort = substr($sort, 1);
            }

            $this->sorts[$sort] = $order;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getPager(): array
    {
        return $this->pager;
    }

    /**
     * @param array $pager
     * @return RestParams
     */
    public function setPager(array $pager): RestParams
    {
        $this->pager = $pager;

        return $this;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     * @return RestParams
     */
    public function setMode(string $mode): RestParams
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @param array $conditions
     * @return RestParams
     */
    public function setConditions(array $conditions): RestParams
    {
        $this->conditions = $conditions;

        return $this;
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
