<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class Sender implements SenderInterface
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

    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);

        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);
        $delay = null !== $delayStamp ? $delayStamp->getDelay() : 0;

        $id = $this->connection->send($envelope, $encodedMessage['body'], $delay, $encodedMessage['headers'] ?? []);

        return $envelope->with(new TransportMessageIdStamp($id));
    }
}
