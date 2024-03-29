<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;

abstract class AbstractFooHandler
{
    public const ERROR_MESSAGE = 'Failing on purpose';

    public function __invoke(FooMessage $message): void
    {
        if ($message->getShouldFail()) {
            throw new \RuntimeException(self::ERROR_MESSAGE);
        }
    }
}
