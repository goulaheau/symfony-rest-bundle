<?php

namespace Goulaheau\RestBundle\Exception\RestException;

use Goulaheau\RestBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class RestEntityValidationException extends RestException
{
    protected $status = Response::HTTP_UNPROCESSABLE_ENTITY;
}