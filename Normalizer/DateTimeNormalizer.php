<?php

namespace Goulaheau\RestBundle\Normalizer;

use DateTime;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * Date format
     *
     * @var string
     */
    protected $dateFormat = 'd/m/Y';

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $data instanceof DateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === 'DateTime';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return $object->format($this->dateFormat);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return DateTime::createFromFormat($this->dateFormat, $data);
    }
}
