<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Transport;

use Facile\MongoDbMessenger\Stamp\ReceivedStamp;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\RuntimeException as DriverException;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class Receiver implements ReceiverInterface, MessageCountAwareInterface, ListableReceiverInterface
{
    /** @var Connection */
    private $connection;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(Connection $connection, SerializerInterface $serializer)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * @return array{0?: Envelope}
     */
    public function get(): iterable
    {
        $document = $this->connection->get();

        if ($document === null) {
            return [];
        }

        return [$this->createEnvelope($document)];
    }

    public function ack(Envelope $envelope): void
    {
        $stamp = $envelope->last(ReceivedStamp::class);

        if (! $stamp instanceof ReceivedStamp) {
            throw new \LogicException('Unable to retrieve ReceivedStamp on the envelope');
        }

        try {
            $this->connection->ack($stamp->getId());
        } catch (DriverException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    public function reject(Envelope $envelope): void
    {
        $stamp = $envelope->last(ReceivedStamp::class);

        if (! $stamp instanceof ReceivedStamp) {
            throw new \LogicException('Unable to retrieve ReceivedStamp on the envelope');
        }

        try {
            $this->connection->ack($stamp->getId());
        } catch (DriverException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return \Generator<Envelope>
     */
    public function all(int $limit = null): iterable
    {
        foreach ($this->connection->findAll($limit) as $document) {
            yield $this->createEnvelope($document);
        }
    }

    /**
     * @param array<string, mixed>|object $filters
     * @param array<string, mixed> $options
     *
     * @return \Generator<Envelope>
     */
    public function findBy($filters, array $options): \Generator
    {
        foreach ($this->connection->findBy($filters, $options) as $document) {
            yield $this->createEnvelope($document);
        }
    }

    /**
     * @param array<string, mixed>|object $filters
     * @param array<string, mixed> $options
     */
    public function countBy($filters, array $options): int
    {
        return $this->connection->countBy($filters, $options);
    }

    /**
     * @param string|ObjectId $id
     */
    public function find($id): ?Envelope
    {
        $document = $this->connection->find((string) $id);

        if ($document === null) {
            return null;
        }

        return $this->createEnvelope($document);
    }

    public function getMessageCount(): int
    {
        return $this->connection->getMessageCount();
    }

    private function createEnvelope(BSONDocument $document): Envelope
    {
        $documentID = (string) $document->_id;

        try {
            $envelope = $this->serializer->decode([
                'body' => $document->body,
                'headers' => $document->headers,
            ]);
        } catch (MessageDecodingFailedException $exception) {
            $this->connection->reject($documentID);

            throw $exception;
        }

        return $envelope->with(
            new ReceivedStamp($documentID),
            new TransportMessageIdStamp($documentID)
        );
    }
}
