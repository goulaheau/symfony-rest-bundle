<?php

namespace Goulaheau\RestBundle\Core;

use Goulaheau\RestBundle\Core\RestParams\Method;
use Goulaheau\RestBundle\Entity\RestEntity;
use Goulaheau\RestBundle\Normalizer\EntityNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class RestSerializer
{
    /**
     * @var Serializer
     */
    protected $serializer;

    protected $denormalizeContext = [AbstractObjectNormalizer::GROUPS => 'editable'];

    protected $normalizeContext = [AbstractObjectNormalizer::GROUPS => 'readable'];

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed           $data
     * @param string          $entityClass
     * @param callable | null $factory
     * @param null            $toEntity
     *
     * @return object
     */
    public function denormalize($data, $entityClass, $factory = null, $toEntity = null)
    {
        $context = $this->getDenormalizeContext($this->denormalizeContext, $toEntity);

        if ($factory) {
            $entityClass = $entityClass::$factory($data);
        }

        $entity = $this->serializer->denormalize($data, $entityClass, null, $context);

        // TODO: Ameliorer ce mechanisme
        EntityNormalizer::$isFirstCall = true;

        return $entity;
    }

    /**
     * @param mixed    $data
     * @param array    $groups
     * @param array    $attributes
     * @param Method[] $entityMethods
     *
     * @return array|bool|float|int|mixed|string
     */
    public function normalize($data, $attributes = null, $groups = null, $entityMethods = null)
    {
        $context = $this->getNormalizeContext($this->normalizeContext, $attributes, $groups);

        $dataNormalized = $this->serializer->normalize($data, null, $context);

        if ($entityMethods && count($entityMethods) > 0) {
            $dataNormalized = $this->mergeEntitiesMethods($data, $dataNormalized, $entityMethods);
        }

        return $dataNormalized;
    }

    protected function mergeEntitiesMethods($data, $dataNormalized, $entityMethods)
    {
        if (!is_array($data)) {
            return $this->mergeEntityMethods($data, $dataNormalized, $entityMethods);
        }

        foreach ($data as $index => $entity) {
            if (!isset($dataNormalized[$index])) {
                continue;
            }

            $dataNormalized[$index] = $this->mergeEntityMethods($entity, $dataNormalized[$index], $entityMethods);
        }

        return $dataNormalized;
    }

    /**
     * @param          $entity
     * @param          $entityNormalized
     * @param Method[] $entityMethods
     *
     * @return array
     */
    protected function mergeEntityMethods($entity, $entityNormalized, $entityMethods, $key = '_entityMethods')
    {
        if (!is_array($entityNormalized)) {
            return $entityNormalized;
        }

        foreach ($entityMethods as $entityMethod) {
            $methodEntity = $entity;
            $name = $entityMethod->getName();
            $params = $entityMethod->getParams();
            $attributes = $entityMethod->getAttributes();
            $subEntities = $entityMethod->getSubEntities();

            foreach ($subEntities as $subEntity) {
                $methodEntity = $methodEntity->{"get${subEntity}"}();
            }

            if (count($subEntities) === 0) {
                if (!isset($entityNormalized[$key])) {
                    $entityNormalized[$key] = [];
                }

                $data = $methodEntity->$name(...$params);

                if ($attributes) {
                    $data = $this->serializer->normalize(
                        $data,
                        null,
                        $this->getNormalizeContext($this->normalizeContext, $attributes)
                    );
                }

                $entityNormalized[$key][$name] = $data;
                continue;
            }

            foreach ($subEntities as $i => $subEntity) {
                if (!isset($entityNormalized[$subEntity])) {
                    $entityNormalized[$subEntity] = [];
                }

                $subEntityNormalized = &$entityNormalized[$subEntity];

                if ($i === count($subEntities) - 1) {
                    if (!isset($subEntityNormalized[$key])) {
                        $subEntityNormalized[$key] = [];
                    }

                    $data = $methodEntity->$name(...$params);

                    if ($attributes) {
                        $data = $this->serializer->normalize(
                            $data,
                            null,
                            $this->getNormalizeContext($this->normalizeContext, $attributes)
                        );
                    }

                    $subEntityNormalized[$key][$name] = $data;
                }
            }
        }

        return $entityNormalized;
    }

    protected function getDenormalizeContext($context, $toEntity = null)
    {
        if (!isset($context[AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT])) {
            $context[AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT] = true;
        }

        if (!$toEntity) {
            return $context;
        }

        $context[AbstractObjectNormalizer::OBJECT_TO_POPULATE] = $toEntity;

        return $context;
    }

    protected function getNormalizeContext($context, $attributes = null, $groups = null)
    {
        if ($attributes) {
            $context[AbstractObjectNormalizer::ATTRIBUTES] = $attributes;
        }

        if ($groups) {
            $context[AbstractObjectNormalizer::GROUPS] = $groups;
        }

        if (!isset($context[AbstractObjectNormalizer::CIRCULAR_REFERENCE_HANDLER])) {
            $context[AbstractObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] = function (
                RestEntity $object,
                string $format = null,
                array $context = []
            ) {
                if (
                    isset($context['attributes']) &&
                    is_array($context['attributes']) &&
                    count($context['attributes']) > 1
                ) {
                    return $this->serializer->normalize($object, null, $context);
                }

                return ['id' => $object->getId()];
            };
        }

        return $context;
    }
}
