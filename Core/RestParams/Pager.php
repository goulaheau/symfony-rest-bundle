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
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     *
     * @return self
     */
    public function setLimit($limit): self
    {
        $this->limit = $limit > 0 ? $limit : 25;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     *
     * @return self
     */
    public function setOffset($offset): self
    {
        $this->offset = $offset >= 0 ? $offset : 0;

        return $this;
    }
}
