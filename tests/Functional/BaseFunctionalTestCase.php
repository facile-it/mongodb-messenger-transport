<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Functional;

use Facile\MongoDbMessenger\Transport\Connection;
use Facile\MongoDbMessenger\Transport\MongoDbTransport;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class BaseFunctionalTestCase extends TestCase
{
    /** @var string */
    private $host = 'mongo';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getMongoDb()->drop();
    }

    protected function getMongoDb(): Database
    {
        if ($hostOverride = getenv('MONGO_HOST')) {
            $this->host = $hostOverride;
        }

        $database = new Database(new Manager('mongodb://root:rootPass@' . $this->host), 'test');
        $this->assertInstanceOf(Database::class, $database);

        return $database;
    }

    protected function getTransport(string $queueName = 'default'): MongoDbTransport
    {
        $collection = $this->getMongoDb()->selectCollection('messenger_messages');

        return new MongoDbTransport(
            new Connection($collection, $queueName, 3_600),
            new PhpSerializer()
        );
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
