<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Stubs;

class FooMessage
{
    /** @var string */
    private $data;

    /** @var bool */
    private $shouldFail;

    public static function create(): self
    {
        return new self(uniqid('test-data-', true), false);
    }

    public static function createFailing(): self
    {
        return new self(uniqid('test-data-', true), true);
    }

    public function __construct(string $data, bool $shouldFail)
    {
        $this->data = $data;
        $this->shouldFail = $shouldFail;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getShouldFail(): bool
    {
        return $this->shouldFail;
    }
}
