<?php

namespace Goulaheau\RestBundle\Core;

use Goulaheau\RestBundle\Core\RestParams\Method;
use Goulaheau\RestBundle\Entity\RestEntity;
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

        return $this->serializer->denormalize($data, $entityClass, null, $context);
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

        $methodsData = [];

        foreach ($entityMethods as $entityMethod) {
            $name = $entityMethod->getName();
            $params = $entityMethod->getParams();

            $methodsData[$name] = $entity->$name(...$params);
        }

        $entityNormalized[$key] = $methodsData;

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
            $context[AbstractObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] = function (RestEntity $object) {
                return [
                    'id' => $object->getId(),
                ];
            };
        }

        return $context;
    }
}
