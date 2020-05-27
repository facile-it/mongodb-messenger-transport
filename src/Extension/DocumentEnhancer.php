<?php

namespace Facile\MongoDbMessenger\Extension;

use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;

interface DocumentEnhancer
{
    public function enhance(BSONDocument $document, Envelope $envelope): void;
}
