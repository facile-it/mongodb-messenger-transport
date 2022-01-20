<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Transport;

use Facile\MongoDbMessenger\Document\QueueDocument;
use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use Facile\MongoDbMessenger\Repository\CollectionRepository;
#use MongoDB\Driver\Cursor;
#use MongoDB\Driver\WriteConcern;
#use MongoDB\Model\BSONDocument;
#use MongoDB\Operation\FindOneAndUpdate;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use DateTime;

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

    public function __construct(CollectionRepository $collection, string $queueName, int $redeliverTimeout)
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

    /**
     * @throws TransportException
     */
    public function get(): ?QueueDocument
    {
        # $options = $this->getWriteOptions();
        # $options['returnDocument'] = FindOneAndUpdate::RETURN_DOCUMENT_AFTER;
        # $options['sort'] = [
        #     'availableAt' => 1,
        # ];
        # $options = $this->setTypeMapOption($options);

        $updateStatement = [
            '$set' => [
                'deliveredTo' => $this->uniqueId,
                'deliveredAt' => new DateTime(),
            ],
        ];

        try {
            $updatedDocument = $this->collection->findOneAndUpdate(
                $this->createAvailableMessagesQuery(),
                $updateStatement
                #$options
            );
        } catch (\Throwable $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        if (! $updatedDocument instanceof QueueDocument) {
            return null;
        }

        if ($updatedDocument->deliveredTo !== $this->uniqueId) {
            // concurrency issue - some other consumer got to this message while we were updating it
            //return null;
        }

        return $updatedDocument;
    }

    /**
     * @return array<string, mixed>
     */
    private function createAvailableMessagesQuery(): array
    {
        $now = new DateTime();
        $redeliverLimit = (clone $now)->modify(sprintf('-%d seconds', $this->redeliverTimeout));

        return [
            '$or' => [
                ['deliveredAt' => null],
                ['deliveredAt' => [
                    '$lt' => $redeliverLimit,
                ]],
            ],
            'availableAt' => ['$lte' => $now],
            'queueName' => $this->queueName,
        ];
    }

    /**
     * @param int $delay The delay in milliseconds
     *
     * @throws TransportException
     *
     * @return string The inserted id
     */
    public function send(Envelope $envelope, string $body, int $delay = 0): string
    {
        $now = new DateTime();
        $availableAt = (clone $now)->modify(sprintf('+%d milliseconds', $delay));

        $document = new QueueDocument();

        foreach ($this->documentEnhancers as $documentEnhancer) {
            $documentEnhancer->enhance($document, $envelope);
        }

        $document->body = $body;
        $document->queueName = $this->queueName;
        $document->createdAt = $now;
        $document->availableAt = $availableAt;

        try {
            $insertResult = $this->collection->insertOne($document);
        } catch (\Throwable $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return $insertResult->getInsertedId();
    }

    /**
     * @param string $id The ID of the message to ack; the corresponding document will be removed from the collection
     *
     * @throws TransportException
     *
     * @return bool Returns true if the document has been deleted
     */
    public function ack(string $id): bool
    {
        try {
            $deleteResult = $this->collection->deleteOne(['id' => $id]);
        } catch (\Throwable $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return is_null($deleteResult);
    }

    /**
     * @param string $id The ID of the message to ack; the corresponding document will be removed from the collection
     *
     * @throws TransportException
     *
     * @return bool Returns true if the document has been deleted
     */
    public function reject(string $id): bool
    {
        try {
            $deleteResult = $this->collection->deleteOne(['id' => $id]);
        } catch (\Throwable $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return true;
    }

    public function getMessageCount(): int
    {
        return $this->collection->count(
            $this->createAvailableMessagesQuery()
        );
    }

    /**
     * @throws Exception
     */
    public function find(string $id): ?QueueDocument
    {
        return $this->collection->findOne(['id' => $id]);
    }

    /**
     * @return Cursor<QueueDocument>
     */
    public function findAll(int $limit = null): Cursor
    {
        $options = [];
        if ($limit !== null) {
            $options['limit'] = $limit;
        }

        return $this->findBy($this->createAvailableMessagesQuery(), $options);
    }

    /**
     * @param array<string, mixed>|object $filters
     * @param array<string, mixed> $options
     *
     * @return Collection<QueueDocument>
     */
    public function findBy($filters, array $options): Collection
    {
        return $this->collection->find($filters, $this->setTypeMapOption($options));
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
        $this->collection->deleteMany(['queueName' => $this->queueName], []);
    }

    /**
     * This method will create the collection and add a compound index
     * including the queueName, availableAt and deliveredAt fields.
     */
    public function setup(): void
    {
        // $this->collection->createIndex([
        //     'availableAt' => 1,
        //     'queueName' => 1,
        //     'deliveredAt' => 1,
        // ], [
        //     'name' => 'facile-it_messenger_index',
        // ]);
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

    /**
     * @param array<string, mixed> $readOptions
     *
     * @return array<string, mixed>
     */
    private function setTypeMapOption(array $readOptions = []): array
    {
        $readOptions['typeMap'] = [
            'root' => BSONDocument::class,
        ];

        return $readOptions;
    }
}
