<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\End2End\App\KernelWithJsonSerializer;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class E2EWithJsonSerializerTest extends AbstractMongoDbTransportTest
{
    protected static function getKernelClass(): string
    {
        return KernelWithJsonSerializer::class;
    }

    protected function assertSerializerIsTheExpectedKind(SerializerInterface $serializer): void
    {
        $this->assertInstanceOf(Serializer::class, $serializer);
    }
}
