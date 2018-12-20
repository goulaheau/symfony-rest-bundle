<?php

namespace Goulaheau\RestBundle\Utils;

use Goulaheau\RestBundle\Entity\RestEntity;
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
     * @param                 $data
     * @param RestParams      $restParams
     * @return array|bool|float|int|mixed|string
     */
    public function normalize($data, RestParams $restParams = null)
    {
        $context = $this->setNormalizeContext($this->normalizeContext, $restParams);

        $dataNormalized = $this->serializer->normalize($data, null, $context);
        $dataNormalized = $this->mergeEntitiesFunctions($data, $dataNormalized, $restParams);

        return $dataNormalized;
    }

    protected function mergeEntitiesFunctions($data, $dataNormalized, RestParams $restParams)
    {
        if (!$restParams || !$restParams->getEntityFunctions()) {
            return $dataNormalized;
        }

        if (!is_array($data)) {
            return $this->mergeEntityFunctions($data, $dataNormalized, $restParams->getEntityFunctions());
        }

        foreach ($data as $index => $entity) {
            if (!isset($dataNormalized[$index])) {
                continue;
            }

            $dataNormalized[$index] = $this->mergeEntityFunctions(
                $entity,
                $dataNormalized[$index],
                $restParams->getEntityFunctions()
            );
        }

        return $dataNormalized;
    }

    protected function mergeEntityFunctions($entity, $entityNormalized, $entityFunctions)
    {
        if (!is_array($entityNormalized)) {
            return $entityNormalized;
        }

        $entityNormalized['_entityFunctions'] = [];

        foreach ($entityFunctions as $entityFunction) {
            $function = $entityFunction['function'];
            $params = $entityFunction['params'];

            $entityNormalized['_entityFunctions'][$function] = $entity->$function(...$params);
        }

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

    protected function setNormalizeContext($context, RestParams $restParams = null)
    {
        if (!$restParams) {
            return $context;
        }

        if ($restParams->getGroups()) {
            $context['groups'] = $restParams->getGroups();
        }

        if ($restParams->getAttributes()) {
            $context['attributes'] = $restParams->getAttributes();
        }

        return $context;
    }

    protected function setNormalizeCircularReferenceHandler()
    {
        $this->normalizeContext['circular_reference_handler'] = function (RestEntity $object) {
            return $object->getId();
        };
    }
}
