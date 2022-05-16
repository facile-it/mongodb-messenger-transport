<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\End2End\App\KernelWithPhpSerializer;

class E2EWithPHPSerializerTest extends AbstractMongoDbTransportTest
{
    protected static function getKernelClass(): string
    {
        return KernelWithPhpSerializer::class;
    }
}
