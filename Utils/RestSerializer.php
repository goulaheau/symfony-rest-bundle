<?php

namespace Goulaheau\RestBundle\Utils;

use Goulaheau\RestBundle\Entity\RestEntity;
use Goulaheau\RestBundle\Utils\RestParams\Method;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class RestSerializer
{
    /**
     * @var Serializer
     */
    protected $serializer;

    protected $deserializeContext = ['groups' => 'editable'];

    protected $normalizeContext = ['groups' => 'readable'];

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->setNormalizeCircularReferenceHandler();
    }

    /**
     * @param      $data
     * @param null $toEntity
     *
     * @return object
     */
    public function deserialize($data, $entityClass, $toEntity = null)
    {
        $context = $this->setDeserializeContext($this->deserializeContext, $toEntity);

        return $this->serializer->deserialize($data, $entityClass, 'json', $context);
    }

    /**
     * @param mixed $data
     * @param array $groups
     * @param array $attributes
     * @param Method[] $entityMethods
     * @return array|bool|float|int|mixed|string
     */
    public function normalize($data, $attributes = null, $groups = null, $entityMethods = null)
    {
        $context = $this->setNormalizeContext($this->normalizeContext, $attributes, $groups);

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
     * @param $entity
     * @param $entityNormalized
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

            $methodsData[$name] = $entity->{$name}(...$params);
        }

        $entityNormalized[$key] = $methodsData;

        return $entityNormalized;
    }

    protected function setDeserializeContext($context, $toEntity = null)
    {
        if (!$toEntity) {
            return $context;
        }

        $context['object_to_populate'] = $toEntity;

        return $context;
    }

    protected function setNormalizeContext($context, $attributes = null, $groups = null)
    {
        if ($attributes) {
            $context['attributes'] = $attributes;
        }

        if ($groups) {
            $context['groups'] = $groups;
        }

        return $context;
    }

    protected function setNormalizeCircularReferenceHandler()
    {
        $this->normalizeContext['circular_reference_handler'] = function (RestEntity $object) {
            return ['id' => $object->getId()];
        };
    }
}
