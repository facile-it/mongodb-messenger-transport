<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Extension\DocumentEnhancer;

use Facile\MongoDbMessenger\Util\Date;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\TestCase;

abstract class DocumentEnhancerTestCase extends TestCase
{
    /**
     * @param mixed $expected
     */
    protected function assertPropertyEquals($expected, BSONDocument $document, string $propertyName): void
    {
        $this->assertTrue(property_exists($document, $propertyName), 'Property missing: ' . $propertyName);

        if ($expected instanceof \DateTimeInterface) {
            $this->assertEquals(Date::toUTC($expected), $document->$propertyName);
        } else {
            $this->assertSame($expected, $document->$propertyName);
        }
    }

    protected function assertPropertyDoesNotExist(string $propertyName, BSONDocument $document): void
    {
        $this->assertFalse(property_exists($document, $propertyName), 'Property is present on document: ' . $propertyName);
    }
}
