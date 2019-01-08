<?php

namespace Goulaheau\RestBundle\Exception\RestException;

use Goulaheau\RestBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class RestNotFoundException extends RestException
{
    protected $status = Response::HTTP_NOT_FOUND;
}