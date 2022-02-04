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
        $result = parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);

        if (
            \Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 50200
            && ($classOrObject instanceof RedeliveryStamp || $classOrObject === RedeliveryStamp::class)
            && is_string($attribute)
            && in_array($attribute, ['exceptionMessage', 'flattenException', 'redeliveredAt'])
        ) {
            return false;
        }

        return $result;
    }
}
