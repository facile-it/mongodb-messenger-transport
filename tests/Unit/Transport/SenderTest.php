<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Transport;

use Facile\MongoDbMessenger\Transport\Connection;
use Facile\MongoDbMessenger\Transport\Sender;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\InsertOneResult;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class SenderTest extends TestCase
{
    use ProphecyTrait;

    public function testHeadersAreSent(): void
    {
        $headers = ['header' => 'headerValue'];
        $body = '{this: "is the body"}';
        $serializer = $this->prophesize(SerializerInterface::class);
        $collection = $this->prophesize(Collection::class);
        $insertOneResult = $this->prophesize(InsertOneResult::class);

        $serializer->encode(Argument::cetera())
            ->willReturn([
                'body' => $body,
                'headers' => $headers,
            ]);

        $collection->insertOne(Argument::allOf(
            Argument::type(BSONDocument::class),
            Argument::withEntry('body', $body),
            Argument::withEntry('headers', new BSONDocument($headers))
        ), Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn($insertOneResult->reveal())
        ;

        $insertOneResult->getInsertedId()
            ->willReturn(new ObjectId());

        $sender = new Sender(
            new Connection($collection->reveal(), 'queueName', 0),
            $serializer->reveal()
        );

        $sender->send(new Envelope(new \stdClass()));
    }
}
