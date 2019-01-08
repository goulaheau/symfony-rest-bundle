<?php

namespace Goulaheau\RestBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Goulaheau\RestBundle\Core\Utils;
use Goulaheau\RestBundle\Entity\RestEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntityExistValidator extends ConstraintValidator
{
    protected $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param RestEntity $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $valueClass = $this->manager->getClassMetadata(get_class($value))->name;
        $repository = $this->manager->getRepository($valueClass);

        if (!$value->getId() || !$repository->find($value->getId())) {
            $this->context->addViolation($constraint->message, [
                '{{ class }}' => Utils::classNameToLowerCase($this->context->getClassName()),
                '{{ property }}' => $this->context->getPropertyPath(),
            ]);
        }
    }
}
