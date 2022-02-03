<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\End2End\App\KernelWithJsonSerializer;

class E2EWithJsonSerializerTest extends AbstractMongoDbTransportTest
{
    protected static function getKernelClass(): string
    {
        return KernelWithJsonSerializer::class;
    }
}
