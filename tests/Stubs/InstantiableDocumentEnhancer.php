<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Stubs;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;

class InstantiableDocumentEnhancer implements DocumentEnhancer
{
    public function __construct(private readonly ?\DateTime $foo = null) {}

    public function enhance(BSONDocument $document, Envelope $envelope): void {}

    public function getFoo(): ?\DateTime
    {
        return $this->foo;
    }
}
