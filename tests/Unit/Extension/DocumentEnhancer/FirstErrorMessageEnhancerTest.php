<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer\FirstErrorMessageEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class FirstErrorMessageEnhancerTest extends DocumentEnhancerTestCase
{
    public function testEnhance(): void
    {
        $document = new BSONDocument();
        $stamp = new RedeliveryStamp(456, 'Foo Bar');
        $stampToBeIgnored = new RedeliveryStamp(789, 'Baz');
        $envelope = new Envelope(new class() {
        }, [$stamp, $stampToBeIgnored]);

        (new FirstErrorMessageEnhancer())->enhance($document, $envelope);

        $this->assertPropertyEquals($stamp->getRedeliveredAt(), $document, 'firstErrorAt');
        $this->assertPropertyEquals($stamp->getExceptionMessage(), $document, 'firstErrorMessage');
        $this->assertPropertyDoesNotExist('retryCount', $document);
    }

    public function testEnhanceWithNoRedeliveryStamp(): void
    {
        $document = new BSONDocument();
        $envelope = new Envelope(new class() {
        });

        (new FirstErrorMessageEnhancer())->enhance($document, $envelope);

        $this->assertPropertyDoesNotExist('firstErrorAt', $document);
        $this->assertPropertyDoesNotExist('firstErrorMessage', $document);
        $this->assertPropertyDoesNotExist('retryCount', $document);
    }
}
