<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\DependencyInjection;

use Facile\MongoDbMessenger\Transport\TransportFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @internal
 */
class MongoDbMessengerExtension extends Extension
{
    public function getAlias(): string
    {
        return 'facile_mongo_db_messenger';
    }

    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $transportFactory = new Definition(TransportFactory::class);
        $transportFactory->addTag('messenger.transport_factory');
        $transportFactory->addArgument(new Reference('service_container'));

        $container->setDefinition(TransportFactory::class, $transportFactory);
    }
}
