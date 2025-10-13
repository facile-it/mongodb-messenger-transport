<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Stubs;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;

class NotInstantiableDocumentEnhancer implements DocumentEnhancer
{
    private \DateTime $bar;

    private ?\DateTime $foo;

    public function __construct(\DateTime $bar, ?\DateTime $foo = null)
    {
        $this->bar = $bar;
        $this->foo = $foo;
    }

    public function enhance(BSONDocument $document, Envelope $envelope): void
    {
        if (! $this->foo instanceof \DateTime) {
            throw new \RuntimeException('To avoid no-op constructor ' . $this->bar->getTimestamp());
        }
    }
}
