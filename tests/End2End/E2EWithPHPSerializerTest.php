<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\End2End\App\KernelWithPhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class E2EWithPHPSerializerTest extends AbstractMongoDbTransportTest
{
    protected static function getKernelClass(): string
    {
        return KernelWithPhpSerializer::class;
    }

    protected function assertSerializerIsTheExpectedKind(SerializerInterface $serializer): void
    {
        $this->assertInstanceOf(PhpSerializer::class, $serializer);
    }
}
