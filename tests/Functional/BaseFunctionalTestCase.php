<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Functional;

use Facile\MongoDbMessenger\Tests\End2End\App\Kernel;
use Facile\MongoDbMessenger\Transport\MongoDbTransport;
use Facile\SymfonyFunctionalTestCase\WebTestCase as FacileWebTestCase;
use MongoDB\Collection;
use MongoDB\Database;
use Symfony\Component\Messenger\Envelope;

class BaseFunctionalTestCase extends FacileWebTestCase
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

    protected function getTransport(string $name = 'default'): MongoDbTransport
    {
        $transport = $this->getContainer()->get('messenger.transport.' . $name);
        $this->assertInstanceOf(MongoDbTransport::class, $transport);

        return $transport;
    }

    protected function getOneEnvelope(MongoDbTransport $transport): Envelope
    {
        $envelopes = $transport->get();
        $this->assertIsArray($envelopes);
        $this->assertNotEmpty($envelopes, 'No Envelope found');
        $fetchedEnvelope = current($envelopes);
        $this->assertInstanceOf(Envelope::class, $fetchedEnvelope);

        return $fetchedEnvelope;
    }

    protected function getMessageCollection(string $collectionName = 'messenger_messages'): Collection
    {
        return $this->getMongoDb()->selectCollection($collectionName);
    }
}
