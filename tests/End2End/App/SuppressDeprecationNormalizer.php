<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;

if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 6_03_00) {
    class SuppressDeprecationNormalizer extends ObjectNormalizerDecorator
    {
        /**
         * @return array<string, bool>
         */
        public function getSupportedTypes(?string $format): array
        {
            return [
                FlattenException::class => true,
                RedeliveryStamp::class => true,
            ];
        }
    }
} else {
    class SuppressDeprecationNormalizer extends ObjectNormalizerDecorator implements CacheableSupportsMethodInterface
    {
        public function hasCacheableSupportsMethod(): bool
        {
            return $this->objectNormalizer->hasCacheableSupportsMethod();
        }
    }
}
