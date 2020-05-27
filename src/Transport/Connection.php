<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Transport;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use Facile\MongoDbMessenger\Util\Date;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Exception\RuntimeException as DriverException;
use MongoDB\Driver\WriteConcern;
use MongoDB\Model\BSONDocument;
use MongoDB\Operation\FindOneAndUpdate;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;

/**
 * @internal
 */
final class Connection
{
    /** @var Collection */
    private $collection;

    /** @var string */
    private $queueName;

    /** @var int */
    private $redeliverTimeout;

    /** @var DocumentEnhancer[] */
    private $documentEnhancers = [];

    /** @var string */
    private $uniqueId;

    public function __construct(Collection $collection, string $queueName, int $redeliverTimeout)
    {
        $this->collection = $collection;
        $this->queueName = $queueName;
        $this->redeliverTimeout = $redeliverTimeout;
        $this->uniqueId = uniqid('consumer_', true);
    }

    public function addDocumentEnhancer(DocumentEnhancer $enhancer): void
    {
        $this->documentEnhancers[] = $enhancer;
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    public function get(): ?BSONDocument
    {
        $options = $this->getWriteOptions();
        $options['returnDocument'] = FindOneAndUpdate::RETURN_DOCUMENT_AFTER;
        $options['sort'] = [
            'availableAt' => 1,
        ];

        $updateStatement = [
            '$set' => [
                'deliveredTo' => $this->uniqueId,
                'deliveredAt' => new UTCDateTime(),
            ],
        ];

        $updatedDocument = $this->collection->findOneAndUpdate($this->createAvailableMessagesQuery(), $updateStatement, $options);

        if (! $updatedDocument instanceof BSONDocument) {
            return null;
        }

        if ($updatedDocument->deliveredTo !== $this->uniqueId) {
            // concurrency issue - some other consumer got to this message while we were updating it
            return null;
        }

        return $updatedDocument;
    }

    /**
     * @param array<string, mixed> $headers
     * @param int $delay The delay in milliseconds
     *
     * @throws TransportException
     *
     * @return ObjectId The inserted id
     */
    public function send(Envelope $envelope, string $body, array $headers, int $delay = 0): ObjectId
    {
        $now = new \DateTime();
        $availableAt = (clone $now)->modify(sprintf('+%d seconds', $delay / 1000));

        $document = new BSONDocument();

        foreach ($this->documentEnhancers as $documentEnhancer) {
            $documentEnhancer->enhance($document, $envelope);
        }

        $document->body = $body;
        $document->headers = $headers;
        $document->queueName = $this->queueName;
        $document->createdAt = Date::toUTC($now);
        $document->availableAt = Date::toUTC($availableAt);

        try {
            $insertResult = $this->collection->insertOne($document, $this->getWriteOptions());
        } catch (DriverException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return $insertResult->getInsertedId();
    }

    /**
     * @param string $id The ID of the message to ack; the corresponding document will be removed from the collection
     *
     * @throws DriverException
     *
     * @return bool Returns true if the document has been deleted
     */
    public function ack(string $id): bool
    {
        $deleteResult = $this->collection->deleteOne(['_id' => new ObjectId($id)], $this->getWriteOptions());

        return $deleteResult->getDeletedCount() > 0;
    }

    /**
     * @param string $id The ID of the message to ack; the corresponding document will be removed from the collection
     *
     * @throws DriverException
     *
     * @return bool Returns true if the document has been deleted
     */
    public function reject(string $id): bool
    {
        $deleteResult = $this->collection->deleteOne(['_id' => new ObjectId($id)], $this->getWriteOptions());

        return $deleteResult->getDeletedCount() > 0;
    }

    public function getMessageCount(): int
    {
        return $this->collection->count(
            $this->createAvailableMessagesQuery()
        );
    }

    /**
     * @throws DriverException
     */
    public function find(string $id): ?BSONDocument
    {
        return $this->collection->findOne(['_id' => new ObjectId($id)]);
    }

    /**
     * @return Cursor<BSONDocument>
     */
    public function findAll(int $limit = null): Cursor
    {
        $options = [];
        if ($limit !== null) {
            $options['limit'] = $limit;
        }

        return $this->findBy([], $options);
    }

    /**
     * @param array<string, mixed>|object $filters
     * @param array<string, mixed> $options
     *
     * @return Cursor<BSONDocument>
     */
    public function findBy($filters, array $options): Cursor
    {
        return $this->collection->find($filters, $options);
    }

    /**
     * @param array<string, mixed>|object $filters
     * @param array<string, mixed> $options
     */
    public function countBy($filters, array $options): int
    {
        return $this->collection->count($filters, $options);
    }

    public function deleteAll(): void
    {
        $this->collection->deleteMany([], []);
    }

    /**
     * This method will create the collection and add a compound index
     * including the queueName, availableAt and deliveredAt fields.
     */
    public function setup(): void
    {
        $this->collection->createIndex([
            'queueName' => 1,
            'availableAt' => 1,
            'deliveredAt' => 1,
        ], [
            'name' => 'facile-it_messenger_index',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createAvailableMessagesQuery(): array
    {
        $now = new \DateTime();
        $redeliverLimit = (clone $now)->modify(sprintf('-%d seconds', $this->redeliverTimeout));

        return [
            '$or' => [
                ['deliveredAt' => null],
                ['deliveredAt' => [
                    '$lt' => Date::toUTC($redeliverLimit),
                ]],
            ],
            'availableAt' => ['$lte' => Date::toUTC($now)],
            'queueName' => $this->queueName,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getWriteOptions(): array
    {
        return [
            'writeConcern' => new WriteConcern(WriteConcern::MAJORITY),
        ];
    }
}
