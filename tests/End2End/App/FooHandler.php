<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

if (PHP_MAJOR_VERSION < 8 || ! class_exists(AsMessageHandler::class)) {
    class FooHandler extends AbstractFooHandler implements MessageHandlerInterface {}
} else {
    #[AsMessageHandler]
    class FooHandler extends AbstractFooHandler {}
}
