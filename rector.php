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
    ->withComposerBased(
        phpunit: true
    )
    ->withImportNames(
        importShortClasses: false,
        removeUnusedImports: true
    )
    ->withPreparedSets(
        deadCode: true, 
        codeQuality: true,
        typeDeclarations: true
    )
    ->withSkip([
        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__ . '/tests/End2End/AbstractMongoDbTransportTestCase.php',
        ],
    ]);
