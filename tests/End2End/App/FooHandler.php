<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Facile\MongoDbMessenger\Tests\Stubs\FooMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class FooHandler implements MessageHandlerInterface
{
    public function __invoke(FooMessage $message): void
    {
        if ($message->shouldFail()) {
            throw new \RuntimeException('Failing on purpose');
        }
    }
}
