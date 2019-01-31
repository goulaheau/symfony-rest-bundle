<?php

namespace Goulaheau\RestBundle\Normalizer;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class EntityNormalizer extends ObjectNormalizer
{
    /**
     * @var ObjectManager
     */
    protected $manager;
    /**
     * @var PropertyTypeExtractorInterface
     */
    protected $propertyTypeExtractor;

    /**
     * @var bool
     */
    protected $isFirstCall = true;

    /**
     * @param ObjectManager                       $em
     * @param ClassMetadataFactoryInterface|null  $classMetadataFactory
     * @param NameConverterInterface|null         $nameConverter
     * @param PropertyAccessorInterface|null      $propertyAccessor
     * @param PropertyTypeExtractorInterface|null $propertyTypeExtractor
     */
    public function __construct(
        ObjectManager $manager,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        ?NameConverterInterface $nameConverter = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?PropertyTypeExtractorInterface $propertyTypeExtractor = null
    ) {
        parent::__construct(
            $classMetadataFactory,
            $nameConverter,
            $propertyAccessor,
            $propertyTypeExtractor
        );

        $this->manager = $manager;
        $this->propertyTypeExtractor = $propertyTypeExtractor;
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if ($this->isFirstCall) {
            $this->isFirstCall = false;
            return parent::denormalize($data, $class, $format, $context);
        }

        if ($class === 'DateTime' && is_string($data)) {
            return trim($data) === '' ? null : \DateTime::createFromFormat('d/m/Y', $data);
        } elseif (
            strpos($class, 'App\\Entity\\') === 0 &&
            strpos($class, '[]') === false &&
            (is_numeric($data) || is_string($data) || is_array($data))
        ) {
            if (is_array($data)) {
                $data = $data['id'] ?? $data;
            }

            return $this->manager->find($class, $data);
        } elseif (
            strpos($class, 'App\\Entity\\') === 0 &&
            strpos($class, '[]') !== false &&
            is_array($data)
        ) {
            foreach ($data as &$datum) {
                $datum = $data['id'] ?? $datum;
            }

            return $this->manager
                ->getRepository(str_replace('[]', '', $class))
                ->findBy(['id' => $data]);
        } else {
            return parent::denormalize($data, $class, $format, $context);
        }
    }

    protected function setAttributeValue(
        $object,
        $attribute,
        $value,
        $format = null,
        array $context = []
    ) {
        $types = $this->propertyTypeExtractor->getTypes(get_class($object), $attribute);

        if ($types) {
            foreach ($types as $type) {
                if ($type->isNullable() && is_string($value) && trim($value) === '') {
                    $value = null;
                }
            }
        }

        parent::setAttributeValue($object, $attribute, $value);
    }
}
