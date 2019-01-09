<?php

namespace Goulaheau\RestBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EntityExist extends Constraint
{
    public $message = 'This entity does not exist.';
}
