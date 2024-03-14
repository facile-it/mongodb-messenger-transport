<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

if (class_exists(AsMessageHandler::class)) {
    #[AsMessageHandler]
    class FooHandler extends AbstractFooHandler {}
} else {
    class FooHandler extends AbstractFooHandler implements MessageHandlerInterface {}
}
