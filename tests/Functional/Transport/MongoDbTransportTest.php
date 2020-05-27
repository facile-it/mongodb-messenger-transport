<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Functional\Transport;

use Facile\MongoDbMessenger\Tests\Functional\BaseFunctionalTestCase;
use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use Symfony\Component\Messenger\Envelope;

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

    public function testMessageCount(): void
    {
        $envelope = new Envelope(new FooMessage());
        $transport = $this->getTransport();

        $transport->send($envelope);

        $this->assertSame(1, $transport->getMessageCount());

        $transport->send($envelope);

        $this->assertSame(2, $transport->getMessageCount());
    }
}
