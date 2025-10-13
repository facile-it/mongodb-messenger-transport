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
    private Connection $connection;

    private SerializerInterface $serializer;

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

        if (! array_key_exists('body', $encodedMessage)) {
            throw new \InvalidArgumentException('Encoded message missing required "body" parameter');
        }

        if (! is_string($encodedMessage['body'])) {
            throw new \InvalidArgumentException('Encoded message must be a string, got ' . gettype($encodedMessage['body']));
        }

        $encodedMessage['headers'] ??= [];
        if (! is_array($encodedMessage['headers'])) {
            throw new \InvalidArgumentException('Encoded headers must be an array, got ' . gettype($encodedMessage['headers']));
        }
        $this->assertContainsOnlyStrings($encodedMessage['headers']);

        $id = $this->connection->send($envelope, $encodedMessage['body'], $delay, $encodedMessage['headers']);

        return $envelope->with(new TransportMessageIdStamp($id));
    }

    /**
     * @param mixed[] $headers
     *
     * @phpstan-assert array<string, string> $headers
     */
    private function assertContainsOnlyStrings(array $headers): void
    {
        foreach ($headers as $key => $header) {
            if (! is_string($key)) {
                throw new \InvalidArgumentException('Encoded message headers must have string keys, got ' . gettype($key));
            }

            if (! is_string($header)) {
                throw new \InvalidArgumentException('Encoded message headers must be strings, got ' . gettype($header));
            }
        }
    }
}
