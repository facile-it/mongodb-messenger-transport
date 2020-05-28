<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\End2End\App\FooHandler;
use Facile\MongoDbMessenger\Tests\Functional\BaseFunctionalTestCase;
use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use MongoDB\Model\BSONDocument;
use MongoDB\Model\CollectionInfo;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\SentToFailureTransportStamp;

class MongoDbTransportTest extends BaseFunctionalTestCase
{
    public function testAck(): void
    {
        $envelope = new Envelope(new FooMessage());
        $transport = $this->getTransport();

        $transport->send($envelope);
        $this->runMessengerConsume();

        $this->assertEmpty($transport->get());
        $this->assertEmpty($this->getTransport('failed')->get());
    }

    public function testReject(): void
    {
        $envelope = new Envelope(new FooMessage(true));
        $transport = $this->getTransport();

        $transport->send($envelope);
        $this->runMessengerConsume();

        $this->assertEmpty($transport->get());
        $fetchedEnvelope = $this->getOneEnvelope($this->getTransport('failed'));
        $this->assertEquals($envelope->getMessage(), $fetchedEnvelope->getMessage());
        $this->assertInstanceOf(SentToFailureTransportStamp::class, $fetchedEnvelope->last(SentToFailureTransportStamp::class));
    }

    public function testSetup(): void
    {
        $database = $this->getMongoDb();
        $database->drop();

        $this->runCommand('messenger:setup-transports');

        $collections = iterator_to_array($database->listCollections());
        $this->assertCount(1, $collections);
        $collectionInfo = current($collections);
        $this->assertInstanceOf(CollectionInfo::class, $collectionInfo);
        $this->assertSame('messenger_messages', $collectionInfo->getName());
    }

    public function testDocumentEnhancers(): void
    {
        $envelope = new Envelope(new FooMessage(true));

        $this->getTransport()->send($envelope);
        $this->runMessengerConsume();

        $documents = iterator_to_array($this->getMessageCollection()->find());
        $this->assertCount(1, $documents);
        $this->assertContainsOnlyInstancesOf(BSONDocument::class, $documents);
        foreach ($documents as $document) {
            $this->assertTrue(property_exists($document, 'queueName'));
            $this->assertSame('failed', $document->queueName);
            $this->assertTrue(property_exists($document, 'foo'));
            $this->assertSame('bar', $document->foo);
            $this->assertTrue(property_exists($document, 'lastErrorMessage'));
            $this->assertSame(FooHandler::ERROR_MESSAGE, $document->lastErrorMessage);
        }
    }

    private function runMessengerConsume(string $transport = 'default', int $messageCount = 1): CommandTester
    {
        return $this->runCommand('messenger:consume', [
            'receivers' => [$transport],
            '--limit' => $messageCount,
            '--time-limit' => 1 * $messageCount,
            '-vv' => true,
        ]);
    }
}
