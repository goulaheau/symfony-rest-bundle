<?php

namespace Goulaheau\RestBundle\Core\RestParams;

class Join
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    public function __construct($join = null, $type = null)
    {
        if (!$join) {
            return;
        }

        $parts = explode('.', $join);

        if (count($parts) === 1) {
            $this->setName($join);
            $this->setPath('o.' . $join);
        } else {
            $this->setName($parts[count($parts) - 1]);
            $this->setPath($join);
        }

        switch ($type) {
            case 'l':
                $this->setType('left');
                break;
            case 'i':
            default:
                $this->setType('inner');
        }
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = in_array($type, ['left', 'inner']) ? $type : 'inner';

        return $this;
    }
}
