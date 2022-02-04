<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Facile\MongoDbBundle\FacileMongoDbBundle;
use Facile\MongoDbMessenger\FacileMongoDbMessengerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new FacileMongoDbBundle(),
            new FacileMongoDbMessengerBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/framework.yaml');
        $loader->load(__DIR__ . '/facile_it_mongodb.yaml');
        $loader->load(__DIR__ . '/messenger.yaml');

        if (BaseKernel::VERSION_ID >= 50400) {
            $loader->load(__DIR__ . '/deprecations.yaml');
        }
    }
}
