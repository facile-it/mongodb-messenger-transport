<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger;

use Facile\MongoDbMessenger\Transport\TransportFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FacileMongoDbMessengerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $transportFactory = new Definition(TransportFactory::class);
        $transportFactory->addTag('messenger.transport_factory');
        $transportFactory->addArgument(new Reference('service_container'));

        $container->setDefinition(TransportFactory::class, $transportFactory);
    }
}
