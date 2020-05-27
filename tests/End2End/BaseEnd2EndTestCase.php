<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\End2End\App\Kernel;
use Facile\SymfonyFunctionalTestCase\WebTestCase as FacileWebTestCase;
use MongoDB\Database;
use Symfony\Component\Console\Tester\CommandTester;

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

    protected function runMessengerConsume(string $transport = 'default', int $messageCount = 1): CommandTester
    {
        return $this->runCommand('messenger:consume', [
            'receivers' => [$transport],
            '--limit' => $messageCount,
            '--time-limit' => 1 * $messageCount,
            '-vv' => true,
        ]);
    }
}
