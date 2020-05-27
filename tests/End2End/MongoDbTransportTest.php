<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use Facile\MongoDbMessenger\Transport\MongoDbTransport;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\SentToFailureTransportStamp;

class MongoDbTransportTest extends BaseEnd2EndTestCase
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

    public function testMessageCount(): void
    {
        $envelope = new Envelope(new FooMessage());
        $transport = $this->getTransport();

        $transport->send($envelope);

        $this->assertSame(1, $transport->getMessageCount());

        $transport->send($envelope);

        $this->assertSame(2, $transport->getMessageCount());
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
}
