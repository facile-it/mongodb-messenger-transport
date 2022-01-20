<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Extension;

#use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Facile\MongoDbMessenger\Document\QueueDocument;

interface DocumentEnhancer
{
    public function enhance(QueueDocument $document, Envelope $envelope): void;
}
