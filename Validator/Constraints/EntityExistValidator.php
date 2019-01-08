<?php

namespace Goulaheau\RestBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Goulaheau\RestBundle\Core\Utils;
use Goulaheau\RestBundle\Entity\RestEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityExistValidator extends ConstraintValidator
{
    protected $manager;
    protected $translator;

    public function __construct(EntityManagerInterface $manager, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->translator = $translator;
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
            $className = Utils::classNameToLowerCase($this->context->getClassName());
            $property = $this->context->getPropertyPath();

            $this->context->addViolation("$className.$property.entity-exist");
        }
    }
}
