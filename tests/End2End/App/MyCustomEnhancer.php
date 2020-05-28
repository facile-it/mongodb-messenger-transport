<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\End2End\App;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;

class MyCustomEnhancer implements DocumentEnhancer
{
    public function enhance(BSONDocument $document, Envelope $envelope): void
    {
        $document->foo = 'bar';
    }
}
