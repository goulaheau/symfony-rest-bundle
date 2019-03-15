<?php

namespace Goulaheau\RestBundle\Core\RestParams;

class Method
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var string
     */
    protected $attributes;

    /**
     * @var array
     */
    protected $subEntities = [];

    public function __construct($name = null, $params = null, $attributes = null)
    {
        if (!$name) {
            return;
        }

        $subEntities = explode('.', $name);
        if ($subEntities > 1) {
            $name = $subEntities[count($subEntities) - 1];
            unset($subEntities[count($subEntities) - 1]);
            $this->setSubEntities($subEntities);
        }

        $this->setName($name);
        $this->setParams($params);
        $this->setAttributes($attributes);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     *
     * @return self
     */
    public function setParams($params)
    {
        $this->params = is_array($params) ? $params : [];

        return $this;
    }

    /**
     * @return string
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $attributes
     *
     * @return Method
     */
    public function setAttributes($attributes)
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
    public function getSubEntities()
    {
        return $this->subEntities;
    }

    /**
     * @param array $subEntities
     *
     * @return self
     */
    public function setSubEntities($subEntities)
    {
        $this->subEntities = $subEntities;

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
