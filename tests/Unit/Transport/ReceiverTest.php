<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Transport;

use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use Facile\MongoDbMessenger\Transport\Connection;
use Facile\MongoDbMessenger\Transport\Receiver;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class ReceiverTest extends TestCase
{
    public function testGetRejectsIfDecodingFails(): void
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $collection = $this->prophesize(Collection::class);
        $connection = new Connection($collection->reveal(), 'queueName', 0);
        $receiver = new Receiver($connection, $serializer->reveal());
        $document = new BSONDocument();
        $document->_id = new ObjectId();
        $document->deliveredTo = $connection->getUniqueId();

        $collection->findOneAndUpdate(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn($document);
        $serializer->decode(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow(MessageDecodingFailedException::class);
        $collection->deleteOne(Argument::withEntry('_id', $document->_id->__toString()), Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(DeleteResult::class)->reveal());

        $this->expectException(MessageDecodingFailedException::class);

        $receiver->get();
    }

    public function testAckWithoutReceivedStamp(): void
    {
        $receiver = $this->createReceiver();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to retrieve ReceivedStamp on the envelope');

        $receiver->ack(new Envelope(new FooMessage()));
    }

    public function testRejectWithoutReceivedStamp(): void
    {
        $receiver = $this->createReceiver();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to retrieve ReceivedStamp on the envelope');

        $receiver->reject(new Envelope(new FooMessage()));
    }

    private function createReceiver(): Receiver
    {
        return new Receiver(
            new Connection(
                $this->prophesize(Collection::class)->reveal(),
                'queueName',
                0
            ),
            $this->prophesize(SerializerInterface::class)->reveal()
        );
    }
}
