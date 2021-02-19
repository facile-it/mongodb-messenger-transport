<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer\FirstErrorMessageEnhancer;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class FirstErrorMessageEnhancerTest extends DocumentEnhancerTestCase
{
    public function testEnhance(): void
    {
        if (class_exists(ErrorDetailsStamp::class)) {
            // Symfony 5.2+
            $stamps = [
                $stamp = new RedeliveryStamp(456),
                new ErrorDetailsStamp(\Exception::class, 500, 'Foo Bar'),
                new RedeliveryStamp(789),
                new ErrorDetailsStamp(\Exception::class, 500, 'Baz'),
            ];
        } else {
            $stamps = [
                $stamp = new RedeliveryStamp(456, 'Foo Bar'),
                new RedeliveryStamp(789, 'Baz'),
            ];
        }

        $document = new BSONDocument();
        $envelope = new Envelope(new class() {
        }, $stamps);

        (new FirstErrorMessageEnhancer())->enhance($document, $envelope);

        $this->assertPropertyEquals($stamp->getRedeliveredAt(), $document, 'firstErrorAt');
        $this->assertPropertyEquals('Foo Bar', $document, 'firstErrorMessage');
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
