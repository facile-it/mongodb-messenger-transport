<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SuppressDeprecationNormalizer extends ObjectNormalizer
{
    /**
     * @param array<string,mixed> $context
     */
    protected function isAllowedAttribute($classOrObject, string $attribute, string $format = null, array $context = [])
    {
        $result = parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);

        if (
            \Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 50200
            && ($classOrObject instanceof RedeliveryStamp || $classOrObject === RedeliveryStamp::class)
            && in_array($attribute, ['exceptionMessage', 'flattenException', 'redeliveredAt'])
        ) {
            return false;
        }

        return $result;
    }
}
