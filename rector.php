<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withImportNames(true, true, false, true)
    ->withPreparedSets(true, true, false, true)
    ->withSkip([
        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__ . '/tests/End2End/AbstractMongoDbTransportTest.php',
        ],
    ]);
