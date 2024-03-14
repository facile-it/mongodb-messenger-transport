<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Functional\Transport;

use Facile\MongoDbMessenger\Tests\Functional\BaseFunctionalTestCase;
use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use MongoDB\Model\CollectionInfo;
use MongoDB\Model\IndexInfo;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

class MongoDbTransportTest extends BaseFunctionalTestCase
{
    public function testSendAndGet(): void
    {
        $envelope = new Envelope(FooMessage::create());
        $transport = $this->getTransport();

        $envelope = $transport->send($envelope);

        $stamps = $envelope->all();
        $this->assertCount(1, $stamps);
        $this->assertArrayHasKey(TransportMessageIdStamp::class, $stamps);
        $stamps = $stamps[TransportMessageIdStamp::class];
        $this->assertIsArray($stamps);
        $this->assertCount(1, $stamps);
        $stamp = current($stamps);
        $this->assertInstanceOf(TransportMessageIdStamp::class, $stamp);
        $document = $this->getMessageCollection()->findOne(['_id' => $stamp->getId()]);
        $this->assertInstanceOf(BSONDocument::class, $document);

        $fetchedEnvelope = $this->getOneEnvelope($transport);

        $this->assertEquals($envelope->getMessage(), $fetchedEnvelope->getMessage());
    }

    public function testAck(): void
    {
        $envelope = new Envelope(FooMessage::create());
        $transport = $this->getTransport();

        $envelope = $transport->send($envelope);

        $stamp = $envelope->last(TransportMessageIdStamp::class);
        $this->assertInstanceOf(TransportMessageIdStamp::class, $stamp);
        $document = $this->getMessageCollection()->findOne(['_id' => $stamp->getId()]);
        $this->assertInstanceOf(BSONDocument::class, $document);

        $receivedEnvelope = $this->getOneEnvelope($transport);
        $transport->ack($receivedEnvelope);

        $document = $this->getMessageCollection()->findOne(['_id' => $stamp->getId()]);
        $this->assertNull($document);
    }

    public function testReject(): void
    {
        $envelope = new Envelope(FooMessage::create());
        $transport = $this->getTransport();

        $envelope = $transport->send($envelope);

        $stamp = $envelope->last(TransportMessageIdStamp::class);
        $this->assertInstanceOf(TransportMessageIdStamp::class, $stamp);
        $document = $this->getMessageCollection()->findOne(['_id' => $stamp->getId()]);
        $this->assertInstanceOf(BSONDocument::class, $document);

        $receivedEnvelope = $this->getOneEnvelope($transport);
        $transport->reject($receivedEnvelope);

        $document = $this->getMessageCollection()->findOne(['_id' => $stamp->getId()]);
        $this->assertNull($document);
    }

    public function testAll(): void
    {
        $originalEnvelopes = [
            new Envelope(FooMessage::create()),
            new Envelope(FooMessage::create()),
            new Envelope(FooMessage::create()),
        ];
        $transport = $this->getTransport();
        foreach ($originalEnvelopes as $envelope) {
            $transport->send($envelope);
        }

        $allEnvelopes = iterator_to_array($transport->all());

        $this->assertCount(3, $allEnvelopes);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $allEnvelopes);
        foreach ($allEnvelopes as $i => $envelope) {
            $this->assertEquals($originalEnvelopes[$i]->getMessage(), $envelope->getMessage());
        }
    }

    public function testAllReturnsOnlyAvailableMessages(): void
    {
        $transport = $this->getTransport();

        $transport->send(new Envelope(FooMessage::create()));
        $transport->send(new Envelope(FooMessage::create(), [new DelayStamp(1_000_000)]));
        $transport->send(new Envelope(FooMessage::create()));
        $lockedEnvelope = $this->getOneEnvelope($transport);

        $allAvailableEnvelopes = iterator_to_array($transport->all());

        $this->assertCount(1, $allAvailableEnvelopes);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $allAvailableEnvelopes);
        foreach ($allAvailableEnvelopes as $i => $envelope) {
            $this->assertNotEquals($lockedEnvelope->getMessage(), $envelope->getMessage());
        }
    }

    public function testAllRespectsLimit(): void
    {
        $envelope = new Envelope(FooMessage::create());
        $transport = $this->getTransport();
        $count = 3;
        do {
            $transport->send($envelope);
        } while (--$count);

        $allEnvelopes = iterator_to_array($transport->all(1));

        $this->assertCount(1, $allEnvelopes);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $allEnvelopes);
        $this->assertEquals($envelope->getMessage(), $allEnvelopes[0]->getMessage());
    }

    public function testFindByRespectsFiltersAndOptions(): void
    {
        $firstAvailableMessage = FooMessage::create();
        $secondMessage = FooMessage::create();
        $transport = $this->getTransport();
        $transport->send((new Envelope($secondMessage))->with(new DelayStamp(10_000)));
        $transport->send(new Envelope($firstAvailableMessage));

        $result = iterator_to_array($transport->findBy(['body' => ['$regex' => $secondMessage->getData()]], []));

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $result);
        $this->assertEquals($secondMessage, $result[0]->getMessage());

        $result = iterator_to_array($transport->findBy([], ['sort' => ['availableAt' => 1]]));

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $result);
        $this->assertEquals($firstAvailableMessage, $result[0]->getMessage());
    }

    public function testCountByRespectsFiltersAndOptions(): void
    {
        $firstMessage = FooMessage::create();
        $transport = $this->getTransport();
        $transport->send((new Envelope(FooMessage::create()))->with(new DelayStamp(10_000)));
        $transport->send(new Envelope($firstMessage));

        $result = $transport->countBy(['body' => ['$regex' => $firstMessage->getData()]]);

        $this->assertSame(1, $result);

        $result = $transport->countBy(['body' => ['$regex' => 'test-data-']], ['limit' => 1]);

        $this->assertSame(1, $result);

        $result = $transport->countBy(['body' => ['$regex' => 'test-data-']], ['limit' => 999]);

        $this->assertSame(2, $result);
    }

    public function testFind(): void
    {
        $message = FooMessage::create();
        $transport = $this->getTransport();
        $transport->send(new Envelope(FooMessage::create()));
        $stamp = $transport->send(new Envelope($message))
            ->last(TransportMessageIdStamp::class);
        $this->assertInstanceOf(TransportMessageIdStamp::class, $stamp);

        $envelope = $transport->find($stamp->getId());

        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEquals($message, $envelope->getMessage());
    }

    public function testFindWithNonexistentId(): void
    {
        $transport = $this->getTransport();
        $transport->send(new Envelope(FooMessage::create()));

        $this->assertNull($transport->find(new ObjectId()));
    }

    public function testMessageCount(): void
    {
        $envelope = new Envelope(FooMessage::create());
        $transport = $this->getTransport();

        $transport->send($envelope);

        $this->assertSame(1, $transport->getMessageCount());

        $transport->send($envelope);

        $this->assertSame(2, $transport->getMessageCount());
    }

    public function testReset(): void
    {
        $transport1 = $this->getTransport();
        $transport2 = $this->getTransport('retryable');
        $envelope = new Envelope(FooMessage::create());

        $transport1->send($envelope);
        $transport2->send($envelope);

        $this->assertSame(1, $transport1->getMessageCount());
        $this->assertSame(1, $transport2->getMessageCount());

        $transport1->reset();

        $this->assertSame(0, $transport1->getMessageCount());
        $this->assertSame(1, $transport2->getMessageCount());
    }

    public function testSetup(): void
    {
        $database = $this->getMongoDb();
        $collectionName = 'messenger_messages';
        $database->dropCollection($collectionName);

        $this->getTransport()->setup();

        $collections = iterator_to_array($database->listCollections());

        $this->assertCount(1, $collections);
        $collectionInfo = current($collections);
        $this->assertInstanceOf(CollectionInfo::class, $collectionInfo);
        $this->assertSame($collectionName, $collectionInfo->getName());

        $collection = $this->getMessageCollection($collectionName);
        $indexes = iterator_to_array($collection->listIndexes());
        $this->assertCount(2, $indexes);
        $index = $indexes[1];
        $this->assertInstanceOf(IndexInfo::class, $index);
        $this->assertSame('facile-it_messenger_index', $index->getName());
        $this->assertFalse($index->isUnique());
        $this->assertEquals(
            [
                'availableAt' => 1,
                'queueName' => 1,
                'deliveredAt' => 1,
            ],
            $index->getKey()
        );
    }
}
