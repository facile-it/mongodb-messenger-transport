<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Stubs;

class FooMessage
{
    public function __construct(
        private readonly string $data,
        private readonly bool $shouldFail
    ) {}

    public static function create(): self
    {
        return new self(uniqid('test-data-', true), false);
    }

    public static function createFailing(): self
    {
        return new self(uniqid('test-data-', true), true);
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
