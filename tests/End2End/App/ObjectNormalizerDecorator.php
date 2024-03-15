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

    /**
     * @param mixed $data Data to restore
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed>&array{ignored_attributes?: string[]} $context
     *
     * @return mixed
     */
    protected function doDenormalize($data, $type, $format = null, array $context = [])
    {
        // inside RedeliveryStamp
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'exceptionMessage';
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'flattenException';
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'redeliveredAt';
        // inside FlattenException
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'dataRepresentation';

        return $this->objectNormalizer->denormalize(...func_get_args());
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return class_exists($type)
            && in_array($type, [
                RedeliveryStamp::class,
                FlattenException::class,
            ]);
    }

    /**
     * @param mixed $object
     * @param string|null $format
     * @param array<string, mixed>&array{ignored_attributes?: string[]} $context
     *
     * @return mixed[]|string|int|float|bool|\ArrayObject|null
     */
    protected function doNormalize($object, $format = null, array $context = [])
    {
        // inside RedeliveryStamp
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'exceptionMessage';
        $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = 'flattenException';

        return $this->objectNormalizer->normalize(...func_get_args());
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @param array<string, mixed> $context
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof FlattenException
            || $data instanceof RedeliveryStamp
        ;
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->objectNormalizer->setSerializer($serializer);
    }
}
