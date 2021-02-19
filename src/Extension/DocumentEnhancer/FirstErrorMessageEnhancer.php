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
use Symfony\Component\Messenger\Stamp\StampInterface;

class FirstErrorMessageEnhancer implements DocumentEnhancer
{
    public function enhance(BSONDocument $document, Envelope $envelope): void
    {
        if (class_exists(ErrorDetailsStamp::class)) {
            $firstRedeliveryStamp = $this->getFirst(RedeliveryStamp::class, $envelope);
            $firstErrorStamp = $this->getFirst(ErrorDetailsStamp::class, $envelope);
            if (null === $firstErrorStamp) {
                return;
            }

            $exceptionMessage = $firstErrorStamp->getExceptionMessage();
        } else {
            $firstRedeliveryStamp = RedeliveryStampExtractor::getFirstWithException($envelope);

            if (null == $firstRedeliveryStamp) {
                return;
            }

            $exceptionMessage = $firstRedeliveryStamp->getExceptionMessage();
        }

        if ($firstRedeliveryStamp) {
            $document->firstErrorAt = new UTCDateTime($firstRedeliveryStamp->getRedeliveredAt());
        }
        $document->firstErrorMessage = $exceptionMessage;
    }

    /**
     * @template T of StampInterface
     *
     * @param class-string<T> $stampName
     *
     * @return T|null
     */
    private function getFirst(string $stampName, Envelope $envelope): ?StampInterface
    {
        foreach ($envelope->all($stampName) as $stamp) {
            if ($stamp instanceof $stampName) {
                return $stamp;
            }
        }

        return null;
    }
}
