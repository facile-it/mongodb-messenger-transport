<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Functional\Transport;

use Facile\MongoDbMessenger\Stamp\MongoDBSessionStamp;
use Facile\MongoDbMessenger\Tests\Functional\BaseFunctionalTestCase;
use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

class TransactionalMongoDbTransportTest extends BaseFunctionalTestCase
{
    public function testSendWithCommit(): void
    {
        $session = $this->getMongoDb()->getManager()->startSession();
        $envelope = (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session));
        $transport = $this->getTransport();

        $session->startTransaction();
        $envelope = $transport->send($envelope);
        $session->commitTransaction();

        $stamps = $envelope->all();
        $this->assertCount(2, $stamps);
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
        $this->assertNull($fetchedEnvelope->last(MongoDBSessionStamp::class));
    }

    public function testSendWithRollback(): void
    {
        $session = $this->getMongoDb()->getManager()->startSession();
        $envelope = (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session));
        $transport = $this->getTransport();

        $session->startTransaction();
        $envelope = $transport->send($envelope);
        $session->abortTransaction();

        $stamps = $envelope->all();
        $this->assertCount(2, $stamps);
        $this->assertArrayHasKey(TransportMessageIdStamp::class, $stamps);
        $stamps = $stamps[TransportMessageIdStamp::class];
        $this->assertIsArray($stamps);
        $this->assertCount(1, $stamps);
        $stamp = current($stamps);
        $this->assertInstanceOf(TransportMessageIdStamp::class, $stamp);
        $document = $this->getMessageCollection()->findOne(['_id' => $stamp->getId()]);
        $this->assertNull($document);
        $this->checkTransportIsEmpty($transport);
    }

    public function testAllWithCommit(): void
    {
        $session = $this->getMongoDb()->getManager()->startSession();
        $originalEnvelopes = [
            (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session)),
            (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session)),
            (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session)),
        ];
        $transport = $this->getTransport();

        $session->startTransaction();
        foreach ($originalEnvelopes as $envelope) {
            $transport->send($envelope);
        }
        $session->commitTransaction();

        $allEnvelopes = iterator_to_array($transport->all());

        $this->assertCount(3, $allEnvelopes);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $allEnvelopes);
        foreach ($allEnvelopes as $i => $envelope) {
            $this->assertEquals($originalEnvelopes[$i]->getMessage(), $envelope->getMessage());
            $this->assertNull($envelope->last(MongoDBSessionStamp::class));
        }
    }

    public function testAllWithRollback(): void
    {
        $session = $this->getMongoDb()->getManager()->startSession();
        $originalEnvelopes = [
            (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session)),
            (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session)),
            (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session)),
        ];
        $transport = $this->getTransport();

        $session->startTransaction();
        foreach ($originalEnvelopes as $envelope) {
            $transport->send($envelope);
        }
        $session->abortTransaction();

        $allEnvelopes = iterator_to_array($transport->all());

        $this->assertCount(0, $allEnvelopes);
    }

    public function testMixedAllWithRollback(): void
    {
        $session = $this->getMongoDb()->getManager()->startSession();
        $originalEnvelopes = [
            new Envelope(FooMessage::create()),
            (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session)),
            (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session)),
        ];
        $transport = $this->getTransport();

        $session->startTransaction();
        foreach ($originalEnvelopes as $envelope) {
            $transport->send($envelope);
        }
        $session->abortTransaction();

        /** @var Envelope[] $allEnvelopes */
        $allEnvelopes = iterator_to_array($transport->all());

        $this->assertCount(1, $allEnvelopes);
        $this->assertContainsOnlyInstancesOf(Envelope::class, $allEnvelopes);
        $this->assertEquals($originalEnvelopes[0]->getMessage(), $allEnvelopes[0]->getMessage());
        $this->assertNull($allEnvelopes[0]->last(MongoDBSessionStamp::class));
    }

    public function testMessageCountWithCommit(): void
    {
        $session = $this->getMongoDb()->getManager()->startSession();

        $session->startTransaction();

        $envelope = (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session));
        $transport = $this->getTransport();

        $transport->send($envelope);

        $this->assertSame(0, $transport->getMessageCount());

        $transport->send($envelope);

        $this->assertSame(0, $transport->getMessageCount());

        $session->commitTransaction();

        $this->assertSame(2, $transport->getMessageCount());
    }

    public function testMessageCountWithRollback(): void
    {
        $session = $this->getMongoDb()->getManager()->startSession();

        $session->startTransaction();

        $envelope = (new Envelope(FooMessage::create()))->with(new MongoDBSessionStamp($session));
        $transport = $this->getTransport();

        $transport->send($envelope);

        $this->assertSame(0, $transport->getMessageCount());

        $transport->send($envelope);

        $this->assertSame(0, $transport->getMessageCount());

        $session->abortTransaction();

        $this->assertSame(0, $transport->getMessageCount());
    }
}
