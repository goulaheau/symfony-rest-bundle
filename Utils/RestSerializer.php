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

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param      $data
     * @param null $toEntity
     *
     * @return object
     */
    protected function deserialize($data, $entityClass, $toEntity = null)
    {
        $context = ['groups' => 'update'];

        if ($toEntity) {
            $context['object_to_populate'] = $toEntity;
        }

        return $this->serializer->deserialize($data, $entityClass, 'json', $context);
    }

    /**
     * @param                 $data
     * @param RestQueryParams $queryParams
     * @return array|bool|float|int|mixed|string
     */
    public function normalize($data, RestQueryParams $queryParams)
    {
        $context = [
            'circular_reference_handler' => function (RestEntity $object) {
                return $object->getId();
            },
            'groups' => 'read',
        ];

        if ($queryParams) {
            if ($queryParams->groups) {
                $context['groups'] = $queryParams->groups;
            }

            if ($queryParams->attributes) {
                $context['attributes'] = $queryParams->attributes;
            }
        }

        return $this->serializer->normalize($data, null, $context);
    }
}