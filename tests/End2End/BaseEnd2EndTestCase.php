<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\End2End\App\Kernel;
use Facile\SymfonyFunctionalTestCase\WebTestCase as FacileWebTestCase;
use MongoDB\Database;

class BaseEnd2EndTestCase extends FacileWebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->getMongoDb()->drop();
    }

    protected function getMongoDb(): Database
    {
        $database = $this->getContainer()->get('mongo.connection.test_default');
        $this->assertInstanceOf(Database::class, $database);

        return $database;
    }
}
