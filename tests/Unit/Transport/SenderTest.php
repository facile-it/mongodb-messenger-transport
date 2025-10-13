<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Transport;

use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testSend(): void
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
            Argument::withEntry('headers', new BSONDocument($headers)),
            Argument::withEntry('createdAt', Argument::type(UTCDateTime::class)),
            Argument::withEntry('availableAt', Argument::type(UTCDateTime::class)),
            Argument::that(static fn(BSONDocument $document): bool => $document->createdAt == $document->availableAt)
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

    #[DataProvider('invalidSerializedHeadersProvider')]
    public function testHeadersAreValidated(mixed $invalidHeaders, string $expectedError): void
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->encode(Argument::cetera())
            ->willReturn([
                'body' => '{serialized: "body"}',
                'headers' => $invalidHeaders,
            ]);

        $sender = new Sender(
            new Connection($this->prophesize(Collection::class)->reveal(), 'queueName', 0),
            $serializer->reveal()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedError);

        $sender->send(new Envelope(new \stdClass()));
    }

    /**
     * @return array{mixed, string}[]
     */
    public static function invalidSerializedHeadersProvider(): array
    {
        return [
            ['directString', 'Encoded headers must be an array, got string'],
            [[123 => 'non-string key'], 'Encoded message headers must have string keys, got int'],
            [['test' => false], 'Encoded message headers must be strings, got bool'],
            [['test1' => 'string', 'test2' => 0.1], 'Encoded message headers must be strings, got double'],
        ];
    }
}
