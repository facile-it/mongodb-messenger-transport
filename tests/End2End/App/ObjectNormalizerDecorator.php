<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract class ObjectNormalizerDecorator implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    protected ObjectNormalizer $objectNormalizer;

    public function __construct()
    {
        $this->objectNormalizer = new ObjectNormalizer();
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = [])
    {
        // inside RedeliveryStamp
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'exceptionMessage';
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'flattenException';
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'redeliveredAt';
        // inside FlattenException
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'dataRepresentation';

        return $this->objectNormalizer->denormalize(...func_get_args());
    }

    public function supportsDenormalization($data, string $type, ?string $format = null): bool
    {
        return class_exists($type)
            && in_array($type, [
                RedeliveryStamp::class,
                FlattenException::class,
            ]);
    }

    public function normalize($object, ?string $format = null, array $context = [])
    {
        // inside RedeliveryStamp
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'exceptionMessage';
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'flattenException';

        return $this->objectNormalizer->normalize(...func_get_args());
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof FlattenException
            || $data instanceof RedeliveryStamp
        ;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->objectNormalizer->setSerializer(...func_get_args());
    }
}
