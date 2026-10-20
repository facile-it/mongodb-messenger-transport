<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger;

use Facile\MongoDbMessenger\DependencyInjection\MongoDbMessengerExtension;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FacileMongoDbMessengerBundle extends Bundle
{
    protected function createContainerExtension(): Extension
    {
        return new MongoDbMessengerExtension();
    }
}
