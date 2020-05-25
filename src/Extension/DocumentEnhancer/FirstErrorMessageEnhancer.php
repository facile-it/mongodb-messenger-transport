<?php

namespace Facile\MongoDbMessenger\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use Facile\MongoDbMessenger\Util\Date;
use Facile\MongoDbMessenger\Util\RedeliveryStampExtractor;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;

class FirstErrorMessageEnhancer implements DocumentEnhancer
{
    public function enhance(BSONDocument $document, Envelope $envelope): void
    {
        $firstRedeliveryStamp = RedeliveryStampExtractor::getFirstWithException($envelope);
        
        if (null === $firstRedeliveryStamp) {
            return;
        }

        $document->firstErrorAt = Date::toUTC($firstRedeliveryStamp->getRedeliveredAt());
        $document->firstErrorMessage = $firstRedeliveryStamp->getExceptionMessage();
    }
}
