<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Symfony\Component\Config\Loader\LoaderInterface;

class KernelWithJsonSerializer extends Kernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        $loader->load(__DIR__ . '/json_serializer.yaml');
    }
}
