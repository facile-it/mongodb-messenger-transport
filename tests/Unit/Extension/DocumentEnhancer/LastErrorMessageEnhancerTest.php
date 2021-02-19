<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer\LastErrorMessageEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class LastErrorMessageEnhancerTest extends DocumentEnhancerTestCase
{
    public function testEnhance(): void
    {
        if (class_exists(ErrorDetailsStamp::class)) {
            // Symfony 5.2+
            $stamps = [
                new RedeliveryStamp(456),
                new ErrorDetailsStamp(\Exception::class, 500, 'Baz'),
                $stamp = new RedeliveryStamp(789),
                new ErrorDetailsStamp(\Exception::class, 500, 'Foo Bar'),
            ];
        } else {
            $stamps = [
                new RedeliveryStamp(456, 'Baz'),
                $stamp = new RedeliveryStamp(789, 'Foo Bar'),
            ];
        }

        $document = new BSONDocument();
        $envelope = new Envelope(new class() {
        }, $stamps);

        (new LastErrorMessageEnhancer())->enhance($document, $envelope);

        $this->assertPropertyEquals($stamp->getRedeliveredAt(), $document, 'lastErrorAt');
        $this->assertPropertyEquals('Foo Bar', $document, 'lastErrorMessage');
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
