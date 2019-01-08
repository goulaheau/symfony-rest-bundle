<?php

namespace Goulaheau\RestBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

abstract class RestException extends \Exception
{
    protected $status = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected $data;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getData()
    {
        return $this->data;
    }
}
