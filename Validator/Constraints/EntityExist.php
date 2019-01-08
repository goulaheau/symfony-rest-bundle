<?php

namespace Goulaheau\RestBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EntityExist extends Constraint
{
    public $message = '{{ class }}.{{ property }}.entity-exist';

    public function validatedBy()
    {
        return \get_class($this) . 'Validator';
    }
}
