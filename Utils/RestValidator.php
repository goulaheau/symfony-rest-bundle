<?php

namespace Goulaheau\RestBundle\Utils;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RestValidator
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param $entity
     *
     * @return array
     */
    protected function validate($entity)
    {
        $errors = $this->validator->validate($entity);

        $dataErrors = [];

        /** @var ConstraintViolation $error */
        foreach ($errors as $error) {
            if (!isset($dataErrors[$error->getPropertyPath()])) {
                $dataErrors[$error->getPropertyPath()] = [];
            }

            $dataErrors[$error->getPropertyPath()][] = $error->getMessage();
        }

        return $dataErrors;
    }
}