<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use Facile\MongoDbMessenger\Transport\MongoDbTransport;
use Symfony\Component\Messenger\Envelope;

class MongoDbTransportTest extends BaseEnd2EndTestCase
{
    public function testSend(): void
    {
        $envelope = new Envelope(new FooMessage());

        $transport = $this->getTransport();

        $transport->send($envelope);

        $this->assertSame(1, $transport->getMessageCount());
        $envelopes = $transport->get();
        $this->assertIsArray($envelopes);
        $this->assertNotEmpty($envelopes);
        $fetchedEnvelope = current($envelopes);
        $this->assertInstanceOf(Envelope::class, $fetchedEnvelope);
        /* @var Envelope $fetchedEnvelope */
        $this->assertEquals($envelope->getMessage(), $fetchedEnvelope->getMessage());
    }

    protected function getTransport(string $name = 'default'): MongoDbTransport
    {
        $transport = $this->getContainer()->get('messenger.transport.' . $name);
        $this->assertInstanceOf(MongoDbTransport::class, $transport);

        return $transport;
    }
}
