<?php

namespace Goulaheau\RestBundle\Utils\RestParams;

class Sort
{
    /**
     * @var string
     */
    protected $property;

    /**
     * @var string
     */
    protected $order;

    /**
     * Sort constructor.
     *
     * @param string|null $property
     * @param string|null $order
     */
    public function __construct($property = null, $order = null)
    {
        if (!$property) {
            return;
        }

        $this->setProperty($property);
        $this->setOrder($order);
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param string $property
     *
     * @return self
     */
    public function setProperty($property)
    {
        $this->property = $property;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $order
     *
     * @return self
     */
    public function setOrder($order)
    {
        $order = in_array($order, ['ASC', 'DESC']) ? $order : 'ASC';

        $this->order = $order;

        return $this;
    }
}