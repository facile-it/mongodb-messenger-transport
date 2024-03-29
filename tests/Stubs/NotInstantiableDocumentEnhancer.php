<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Stubs;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;

class NotInstantiableDocumentEnhancer implements DocumentEnhancer
{
    public function __construct(\DateTime $foo = null, \DateTime $bar) {}

    public function enhance(BSONDocument $document, Envelope $envelope): void {}
}
