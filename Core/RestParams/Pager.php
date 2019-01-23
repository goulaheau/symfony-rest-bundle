<?php

namespace Goulaheau\RestBundle\Core\RestParams;

class Pager
{
    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * Pager constructor.
     *
     * @param int|null $page
     * @param int|null $perPage
     */
    public function __construct($page = null, $perPage = null)
    {
        $page = $page > 0 ? $page : 1;
        $perPage = $perPage > 0 ? $perPage : 25;

        $this->setLimit($perPage);
        $this->setOffset($perPage * ($page - 1));
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit > 0 ? (int) $limit : 25;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     *
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset >= 0 ? (int) $offset : 0;

        return $this;
    }
}
