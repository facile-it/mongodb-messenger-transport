<?php

namespace Facile\MongoDbMessenger\Repository;

use Facile\MongoDbMessenger\Document\QueueDocument;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class CollectionRepository extends ServiceDocumentRepository
{
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QueueDocument::class);
        $this->documentManager = $this->getDocumentManager();
    }

    public function insertOne(QueueDocument $document): QueueDocument
    {
        $this->documentManager->persist($document);
        $this->documentManager->flush();

        return $document;
    }

    public function findOne(array $param): ?QueueDocument
    {
        return $this->findOneBy($param);
    }

    public function count(array $queryFind): int
    {
        $query = $this->documentManager->createQueryBuilder(QueueDocument::class);
        $query = $this->makeQuery($query, $queryFind);
        $documents =  $query->getQuery()->execute();

        return $documents->count();
    }


    public function deleteOne(array $params): ?QueueDocument
    {
        $document = $this->findOneBy($params);

        if (!$document) {
            return null;
        }

        $this->documentManager->remove($document);
        $this->documentManager->flush();

        return $document;
    }

    public function findOneAndUpdate(array $queryFind, array $dataUpdate): ?QueueDocument
    {
        $query = $this->documentManager->createQueryBuilder(QueueDocument::class);

        $query = $this->makeQuery($query, $queryFind);

        $documents =  $query->getQuery()->execute();

        $document = $documents->current();

        if (!$document) {
            return null;
        }

        $document->fromArray($dataUpdate['$set']);

        return $this->insertOne($document);
    }

    protected function makeQuery($query, $conditions)
    {
        foreach ($conditions as $name => $value) {
            if ($name === '$or') {
                $query = $this->makeQueryOr($query, $value);
                continue;
            }

            $query = $query->field($name)->equals($value);
        }

        return $query;
    }

    protected function makeQueryOr($query, array $conditions)
    {
        foreach ($conditions as $name => $value) {
            $query->addOr($query->expr()->field($name)->equals($value));
        }

        return $query;
    }
}