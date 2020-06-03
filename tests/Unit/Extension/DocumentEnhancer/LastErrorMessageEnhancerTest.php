<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer\LastErrorMessageEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class LastErrorMessageEnhancerTest extends DocumentEnhancerTestCase
{
    public function testEnhance(): void
    {
        $document = new BSONDocument();
        $stampToBeIgnored = new RedeliveryStamp(456, 'Baz');
        $stamp = new RedeliveryStamp(789, 'Foo Bar');
        $envelope = new Envelope(new class() {
        }, [$stampToBeIgnored, $stamp]);

        (new LastErrorMessageEnhancer())->enhance($document, $envelope);

        $this->assertPropertyEquals($stamp->getRedeliveredAt(), $document, 'lastErrorAt');
        $this->assertPropertyEquals($stamp->getExceptionMessage(), $document, 'lastErrorMessage');
        $this->assertPropertyEquals($stamp->getRetryCount(), $document, 'retryCount');
    }

    public function testEnhanceWithNoRedeliveryStamp(): void
    {
        $document = new BSONDocument();
        $envelope = new Envelope(new class() {
        });

        (new LastErrorMessageEnhancer())->enhance($document, $envelope);

        $this->assertPropertyDoesNotExist('lastErrorAt', $document);
        $this->assertPropertyDoesNotExist('lastErrorMessage', $document);
        $this->assertPropertyDoesNotExist('retryCount', $document);
    }
}
