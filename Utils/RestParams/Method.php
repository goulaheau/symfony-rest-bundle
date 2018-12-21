<?php

namespace Goulaheau\RestBundle\Utils\RestParams;

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

    public function __construct($name = null, $params = null)
    {
        if (!$name) {
            return;
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
}