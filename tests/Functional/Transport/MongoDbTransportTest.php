<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Functional\Transport;

use Facile\MongoDbMessenger\Tests\Functional\BaseFunctionalTestCase;
use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

class MongoDbTransportTest extends BaseFunctionalTestCase
{
    public function testSendAndGet(): void
    {
        $envelope = new Envelope(new FooMessage());
        $transport = $this->getTransport();

        $transport->send($envelope);

        $fetchedEnvelope = $this->getOneEnvelope($transport);
        $this->assertEquals($envelope->getMessage(), $fetchedEnvelope->getMessage());
    }

    public function testAck(): void
    {
        $envelope = new Envelope(new FooMessage());
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
        $envelope = new Envelope(new FooMessage());
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
            new Envelope(new FooMessage()),
            new Envelope(new FooMessage()),
            new Envelope(new FooMessage()),
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

    public function testAllRespectsLimit(): void
    {
        $envelope = new Envelope(new FooMessage());
        $transport = $this->getTransport();
        $count = 3;
        do {
            $transport->send($envelope);
        } while (--$count);

        $allEnvelopes = iterator_to_array($transport->all(1));

        $this->assertCount(1, $allEnvelopes);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $allEnvelopes);
        $this->assertEquals($envelope->getMessage(), current($allEnvelopes)->getMessage());
    }

    public function testFindByRespectsFiltersAndOptions(): void
    {
        $firstAvailableMessage = new FooMessage();
        $secondMessage = new FooMessage();
        $transport = $this->getTransport();
        $transport->send((new Envelope($secondMessage))->with(new DelayStamp(10000)));
        $transport->send(new Envelope($firstAvailableMessage));

        $result = iterator_to_array($transport->findBy(['body' => ['$regex' => $secondMessage->getData()]], []));

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $result);
        $this->assertEquals($secondMessage, current($result)->getMessage());

        $result = iterator_to_array($transport->findBy([], ['sort' => ['availableAt' => 1]]));

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $result);
        $this->assertEquals($firstAvailableMessage, current($result)->getMessage());
    }

    public function testCountByRespectsFiltersAndOptions(): void
    {
        $firstMessage = new FooMessage();
        $transport = $this->getTransport();
        $transport->send((new Envelope(new FooMessage()))->with(new DelayStamp(10000)));
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
        $firstMessage = new FooMessage();
        $transport = $this->getTransport();
        $transport->send((new Envelope(new FooMessage()))->with(new DelayStamp(10000)));
        $transport->send(new Envelope($firstMessage));

        $result = $transport->countBy(['body' => ['$regex' => $firstMessage->getData()]]);

        $this->assertSame(1, $result);

        $result = $transport->countBy(['body' => ['$regex' => 'test-data-']], ['limit' => 1]);

        $this->assertSame(1, $result);

        $result = $transport->countBy(['body' => ['$regex' => 'test-data-']], ['limit' => 999]);

        $this->assertSame(2, $result);
    }

    public function testMessageCount(): void
    {
        $envelope = new Envelope(new FooMessage());
        $transport = $this->getTransport();

        $transport->send($envelope);

        $this->assertSame(1, $transport->getMessageCount());

        $transport->send($envelope);

        $this->assertSame(2, $transport->getMessageCount());
    }

    protected function getMessageCollection(string $collectionName = 'messenger_messages'): Collection
    {
        return $this->getMongoDb()->selectCollection($collectionName);
    }
}