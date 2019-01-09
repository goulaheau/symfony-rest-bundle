<?php

namespace Goulaheau\RestBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntityExistValidator extends ConstraintValidator
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param mixed      $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $valueClass = $this->manager->getClassMetadata(get_class($value))->name;
        $repository = $this->manager->getRepository($valueClass);

        if (!$value->getId() || !$repository->find($value->getId())) {
            $this->context->addViolation($constraint->message);
        }
    }
}
