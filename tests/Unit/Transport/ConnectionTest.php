<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Transport;

use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use Facile\MongoDbMessenger\Transport\Connection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use MongoDB\InsertOneResult;
use MongoDB\Model\BSONDocument;
use MongoDB\Operation\FindOneAndUpdate;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;

class ConnectionTest extends TestCase
{
    use ProphecyTrait;

    public function testGet(): void
    {
        $collection = $this->prophesize(Collection::class);
        $now = new \DateTimeImmutable();
        $connection = new Connection($collection->reveal(), 'foobar', 100);
        $document = $this->mockUpdatedDocumentDeliveredTo($connection->getUniqueId());

        $expectedFilter = Argument::allOf(
            Argument::withEntry('$or', Argument::allOf(
                Argument::withEntry(0, ['deliveredAt' => null]),
                Argument::withEntry(1, Argument::withEntry(
                    'deliveredAt',
                    Argument::withEntry('$lt', $this->argumentIsUTCDateTimeInSeconds($now, -100))
                ))
            )),
            Argument::withEntry('availableAt', Argument::withEntry('$lte', Argument::type(UTCDateTime::class))),
            Argument::withEntry('queueName', 'foobar')
        );

        $expectedUpdateStatement = Argument::allOf(
            Argument::withEntry('$set', Argument::allOf(
                Argument::withEntry('deliveredTo', $connection->getUniqueId()),
                Argument::withEntry('deliveredAt', $this->argumentIsUTCDateTimeInSeconds($now, 0))
            ))
        );

        $expectedOptions = Argument::allOf(
            Argument::withEntry('writeConcern', Argument::allOf(
                Argument::type(WriteConcern::class),
                Argument::which('getW', WriteConcern::MAJORITY)
            )),
            Argument::withEntry('returnDocument', FindOneAndUpdate::RETURN_DOCUMENT_AFTER),
            Argument::withEntry('sort', ['availableAt' => 1])
        );

        $collection->findOneAndUpdate($expectedFilter, $expectedUpdateStatement, $expectedOptions)
            ->shouldBeCalledOnce()
            ->willReturn($document);

        $this->assertSame($document, $connection->get());
    }

    public function testGetWrapsMongoExceptions(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->findOneAndUpdate(Argument::cetera())
            ->willThrow(new \Exception('Foo bar baz'));

        $connection = new Connection($collection->reveal(), 'queueName', 100);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Foo bar baz');
        $this->expectExceptionCode(0);

        $connection->get();
    }

    public function testGetWithEmptyCollection(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->findOneAndUpdate(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $connection = new Connection($collection->reveal(), 'default', 3_600);

        $this->assertNull($connection->get());
    }

    public function testGetWithUnmatchedDeliveredAt(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->findOneAndUpdate(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn($this->mockUpdatedDocumentDeliveredTo('someoneElse'));
        $connection = new Connection($collection->reveal(), 'default', 3_600);

        $this->assertNull($connection->get());
    }

    public function testSend(): void
    {
        $insertOneResult = $this->prophesize(InsertOneResult::class);
        $objectId = new ObjectId();
        $insertOneResult->getInsertedId()
            ->willReturn($objectId);
        $collection = $this->prophesize(Collection::class);
        $inOneSecondFromNow = [
            Argument::type(UTCDateTime::class),
        ];
        $expectedDocument = Argument::allOf(
            Argument::type(BSONDocument::class),
            Argument::withEntry('body', 'serializedEnvelope'),
            Argument::withEntry('queueName', 'foobar'),
            Argument::withEntry('createdAt', Argument::allOf(...$inOneSecondFromNow)),
            Argument::withEntry('availableAt', Argument::allOf(...$inOneSecondFromNow))
        );
        $expectedOptions = Argument::allOf(
            Argument::withEntry('writeConcern', Argument::allOf(
                Argument::type(WriteConcern::class),
                Argument::which('getW', WriteConcern::MAJORITY)
            ))
        );
        $collection->insertOne($expectedDocument, $expectedOptions)
            ->shouldBeCalledOnce()
            ->willReturn($insertOneResult->reveal());

        $connection = new Connection($collection->reveal(), 'foobar', 3_600);

        $this->assertSame($objectId, $connection->send(new Envelope(FooMessage::create()), 'serializedEnvelope'));
    }

    public function testSendWrapsMongoExceptions(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->insertOne(Argument::cetera())
            ->willThrow(new \Exception('Foo bar baz'));

        $connection = new Connection($collection->reveal(), 'queueName', 100);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Foo bar baz');
        $this->expectExceptionCode(0);

        $connection->send(new Envelope(FooMessage::create()), 'body', 0);
    }

    public function testAckWrapsMongoExceptions(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->deleteOne(Argument::cetera())
            ->willThrow(new \Exception('Foo bar baz'));

        $connection = new Connection($collection->reveal(), 'queueName', 100);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Foo bar baz');
        $this->expectExceptionCode(0);

        $connection->ack((new ObjectId())->__toString());
    }

    public function testRejectWrapsMongoExceptions(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->deleteOne(Argument::cetera())
            ->willThrow(new \Exception('Foo bar baz'));

        $connection = new Connection($collection->reveal(), 'queueName', 100);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Foo bar baz');
        $this->expectExceptionCode(0);

        $connection->reject((new ObjectId())->__toString());
    }

    private function mockUpdatedDocumentDeliveredTo(string $deliveredTo): BSONDocument
    {
        $document = new BSONDocument();
        $document->deliveredTo = $deliveredTo;

        return $document;
    }

    private function argumentIsUTCDateTimeInSeconds(\DateTimeImmutable $reference, int $secondsModifier): Argument\Token\LogicalAndToken
    {
        return Argument::allOf(
            Argument::type(UTCDateTime::class),
            Argument::that(function (UTCDateTime $val) use ($reference, $secondsModifier) {
                $lowerBound = $reference->modify($secondsModifier . ' seconds');
                $this->assertUTCDateTimeIsBetween($lowerBound, $lowerBound->modify('+1 seconds'), $val);

                return true;
            })
        );
    }

    private function assertUTCDateTimeIsBetween(
        \DateTimeImmutable $lowerBound,
        \DateTimeImmutable $higherBound,
        UTCDateTime $val
    ): void {
        $this->assertGreaterThanOrEqual(new UTCDateTime($lowerBound), $val);
        $this->assertLessThan(new UTCDateTime($higherBound), $val);
    }
}
