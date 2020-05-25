<?php

namespace Facile\MongoDbMessenger\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use Facile\MongoDbMessenger\Util\Date;
use Facile\MongoDbMessenger\Util\RedeliveryStampExtractor;
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

        $document->lastErrorAt = Date::toUTC($lastRedeliveryStamp->getRedeliveredAt());
        $document->lastErrorMessage = $lastRedeliveryStamp->getExceptionMessage();
        $document->retryCount = $lastRedeliveryStamp->getRetryCount();
    }
}
