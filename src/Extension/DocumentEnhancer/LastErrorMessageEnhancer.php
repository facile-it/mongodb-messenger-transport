<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use Facile\MongoDbMessenger\Util\RedeliveryStampExtractor;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;

class LastErrorMessageEnhancer implements DocumentEnhancer
{
    public function enhance(BSONDocument $document, Envelope $envelope): void
    {
        $lastRedeliveryStamp = RedeliveryStampExtractor::getLastWithException($envelope);

        if (null === $lastRedeliveryStamp) {
            return;
        }

        $document->lastErrorAt = new UTCDateTime($lastRedeliveryStamp->getRedeliveredAt());
        $document->lastErrorMessage = $lastRedeliveryStamp->getExceptionMessage();
        $document->retryCount = $lastRedeliveryStamp->getRetryCount();
    }
}
