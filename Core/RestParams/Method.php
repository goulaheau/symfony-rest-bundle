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
     * @var array
     */
    protected $subEntities = [];

    public function __construct($name = null, $params = null)
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
}
