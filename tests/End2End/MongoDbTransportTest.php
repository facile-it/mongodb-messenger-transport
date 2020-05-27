<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End;

use Facile\MongoDbMessenger\Tests\Functional\BaseFunctionalTestCase;
use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
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
