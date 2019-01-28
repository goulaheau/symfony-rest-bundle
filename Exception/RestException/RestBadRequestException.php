<?php

namespace Goulaheau\RestBundle\Exception\RestException;

use Goulaheau\RestBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;

class RestBadRequestException extends RestException
{
    protected $status = Response::HTTP_BAD_REQUEST;
}
