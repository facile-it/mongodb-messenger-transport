<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Util;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

/**
 * @see \Symfony\Component\Messenger\Command\AbstractFailedMessagesCommand::getLastRedeliveryStampWithException
 */
class RedeliveryStampExtractor
{
    public static function getFirstWithException(Envelope $envelope): ?RedeliveryStamp
    {
        self::checkDeprecation();

        /** @var RedeliveryStamp $stamp */
        foreach ($envelope->all(RedeliveryStamp::class) as $stamp) {
            if (null !== $stamp->getExceptionMessage()) {
                return $stamp;
            }
        }

        return null;
    }

    public static function getLastWithException(Envelope $envelope): ?RedeliveryStamp
    {
        self::checkDeprecation();

        /** @var RedeliveryStamp $stamp */
        foreach (array_reverse($envelope->all(RedeliveryStamp::class)) as $stamp) {
            if (null !== $stamp->getExceptionMessage()) {
                return $stamp;
            }
        }

        return null;
    }

    private static function checkDeprecation(): void
    {
        if (class_exists(ErrorDetailsStamp::class)) {
            trigger_deprecation(
                'symfony/messenger',
                '5.2',
                'using RedeliveryStamp::getExceptionMessage is deprecated; use ErrorDetailsStamp instead, which is now added to failed messages to retain information about the failures'
            );
        }
    }
}
