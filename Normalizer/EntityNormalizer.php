<?php

namespace Goulaheau\RestBundle\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Entity normalizer
 */
class EntityNormalizer extends ObjectNormalizer
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * Entity normalizer
     *
     * @param ObjectManager              $manager
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
        parent::__construct($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor);

        $this->manager = $manager;
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return strpos($type, 'App\\Entity\\') === 0 &&
            (is_numeric($data) || is_string($data) || $this->isAnArrayOfIds($data));
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $class = str_replace('[]', '', $class);

        if (!is_array($data)) {
            return $this->manager->find($class, $data) ?? new $class();
        }

        $entities = new ArrayCollection();

        foreach ($data as $id) {
            $entity = $this->manager->find($class, $id);

            if ($entity) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * @param $array
     *
     * @return bool
     */
    protected function isAnArrayOfIds($array)
    {
        if (!is_array($array)) {
            return false;
        }

        $i = 0;
        foreach ($array as $key => $value) {
            if ($i !== $key || !is_int($value)) {
                return false;
            }

            ++$i;
        }

        return true;
    }
}
