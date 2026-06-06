<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Stubs;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;

class NotInstantiableDocumentEnhancer implements DocumentEnhancer
{
    public function __construct(
        private readonly \DateTime $bar,
        private readonly ?\DateTime $foo = null,
    ) {}

    public function enhance(BSONDocument $document, Envelope $envelope): void
    {
        if (! $this->foo instanceof \DateTime) {
            throw new \RuntimeException('To avoid no-op constructor ' . $this->bar->getTimestamp());
        }
    }
}
