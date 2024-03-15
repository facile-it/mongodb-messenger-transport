<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 6_03_00) {
    require_once __DIR__ . '/SuppressDeprecationNormalizer.php8.php';
} else {
    require_once __DIR__ . '/SuppressDeprecationNormalizer.php7.php';
}
