<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SuppressDeprecationNormalizer extends ObjectNormalizer
{
    /**
     * @param string|object $classOrObject
     * @param string $attribute
     * @param string|null $format
     * @param array<string,mixed> $context
     */
    protected function isAllowedAttribute($classOrObject, $attribute, $format = null, array $context = []): bool
    {
        if ($classOrObject instanceof RedeliveryStamp || $classOrObject === RedeliveryStamp::class) {
            if (
                \Symfony\Component\HttpKernel\Kernel::VERSION_ID <= 4_04_00
                && $attribute === 'flattenException'
            ) {
                return false;
            }

            if (
                \Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 5_02_00
                && is_string($attribute)
                && in_array($attribute, ['exceptionMessage', 'flattenException', 'redeliveredAt'])
            ) {
                return false;
            }
        }

        return parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);
    }
}
