<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Transport;

#use MongoDB\BSON\ObjectId;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\SetupableTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class MongoDbUnresettableTransport implements TransportInterface, SetupableTransportInterface, MessageCountAwareInterface, ListableReceiverInterface
{
    /** @var Connection */
    private $connection;

    /** @var SerializerInterface */
    private $serializer;

    /** @var Receiver */
    private $receiver;

    /** @var Sender */
    private $sender;

    public function __construct(Connection $connection, SerializerInterface $serializer)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     *
     * @return array{0?: Envelope}
     */
    public function get(): iterable
    {
        return $this->getReceiver()->get();
    }

    public function ack(Envelope $envelope): void
    {
        $this->getReceiver()->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        $this->getReceiver()->reject($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        return $this->getSender()->send($envelope);
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<Envelope>
     */
    public function all(int $limit = null): iterable
    {
        return $this->getReceiver()->all($limit);
    }

    /**
     * A method to obtain messages filtered in the same way as \MongoDB\Collection::find
     *
     * @param array<string, mixed>|object $filters
     * @param array<string, mixed> $options
     *
     * @return \Generator<Envelope>
     */
    public function findBy($filters = [], array $options = []): \Generator
    {
        yield from $this->getReceiver()->findBy($filters, $options);
    }

    /**
     * A method to obtain a message count filtered in the same way as \MongoDB\Collection::count
     *
     * @param array<string, mixed>|object $filters
     * @param array<string, mixed> $options
     */
    public function countBy($filters = [], array $options = []): int
    {
        return $this->getReceiver()->countBy($filters, $options);
    }

    /**
     * @param string $id
     */
    public function find($id): ?Envelope
    {
        return $this->getReceiver()->find($id);
    }

    public function getMessageCount(): int
    {
        return $this->getReceiver()->getMessageCount();
    }

    public function reset(): void
    {
        $this->connection->deleteAll();
    }

    public function setup(): void
    {
        $this->connection->setup();
    }

    private function getReceiver(): Receiver
    {
        if ($this->receiver === null) {
            $this->receiver = new Receiver($this->connection, $this->serializer);
        }

        return $this->receiver;
    }

    private function getSender(): Sender
    {
        if ($this->sender === null) {
            $this->sender = new Sender($this->connection, $this->serializer);
        }

        return $this->sender;
    }
}
