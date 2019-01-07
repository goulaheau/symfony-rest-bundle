<?php

namespace Goulaheau\RestBundle\Core\RestParams;

class Condition
{
    /**
     * @var string
     */
    protected $property;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var string|array
     */
    protected $value;

    /**
     * @var string
     */
    protected $parameter;

    /**
     * Sort constructor.
     *
     * @param string|null $property
     * @param string|null $order
     */
    public function __construct($property = null, $value = null, $operator = null)
    {
        if (!$property || !$value) {
            return;
        }

        if (in_array($operator, ['in', 'notIn'])) {
            $value = explode(',', $value);
        }

        $property = str_replace('_', '.', $property);

        $this->setProperty($property);
        $this->setOperator($operator);
        $this->setValue($value);
        $this->setParameter();
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
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     *
     * @return self
     */
    public function setOperator($operator)
    {
        $this->operator = $operator ?? 'eq';

        return $this;
    }

    /**
     * @return array|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param array|string $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * @param string $parameter
     *
     * @return self
     */
    public function setParameter($parameter = null)
    {
        $this->parameter = $parameter ?? self::generateRandomString();

        return $this;
    }

    /**
     * @return string
     */
    public static function generateRandomString()
    {
        return 'p' . substr(str_shuffle(MD5(microtime())), 0, 10);
    }
}
