<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Webmozart\Assert\Assert;

class FirstErrorMessageEnhancer implements DocumentEnhancer
{
    public function enhance(BSONDocument $document, Envelope $envelope): void
    {
        $firstRedeliveryStamp = $this->getFirst(RedeliveryStamp::class, $envelope);
        $firstErrorStamp = $this->getFirst(ErrorDetailsStamp::class, $envelope);
        if (! $firstErrorStamp instanceof StampInterface) {
            return;
        }

        $exceptionMessage = $firstErrorStamp->getExceptionMessage();

        Assert::isInstanceOf($firstRedeliveryStamp, RedeliveryStamp::class);
        $document->firstErrorAt = new UTCDateTime($firstRedeliveryStamp->getRedeliveredAt());
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
            return $stamp;
        }

        return null;
    }
}
