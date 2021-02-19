<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use Facile\MongoDbMessenger\Util\RedeliveryStampExtractor;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class LastErrorMessageEnhancer implements DocumentEnhancer
{
    public function enhance(BSONDocument $document, Envelope $envelope): void
    {
        if (class_exists(ErrorDetailsStamp::class)) {
            $lastRedeliveryStamp = $envelope->last(RedeliveryStamp::class);
            $lastErrorStamp = $envelope->last(ErrorDetailsStamp::class);
            if (! $lastErrorStamp instanceof ErrorDetailsStamp) {
                return;
            }

            $exceptionMessage = $lastErrorStamp->getExceptionMessage();
        } else {
            $lastRedeliveryStamp = RedeliveryStampExtractor::getLastWithException($envelope);

            if (null === $lastRedeliveryStamp) {
                return;
            }

            $exceptionMessage = $lastRedeliveryStamp->getExceptionMessage();
        }

        if ($lastRedeliveryStamp instanceof RedeliveryStamp) {
            $document->lastErrorAt = new UTCDateTime($lastRedeliveryStamp->getRedeliveredAt());
            $document->retryCount = $lastRedeliveryStamp->getRetryCount();
        }

        $document->lastErrorMessage = $exceptionMessage;
    }
}
