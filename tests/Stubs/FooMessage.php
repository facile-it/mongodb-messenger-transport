<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Stubs;

class FooMessage
{
    /** @var string */
    private $data;

    /** @var bool */
    private $shouldFail;

    public function __construct(bool $shouldFail = false, string $data = null)
    {
        $this->data = $data ?? uniqid('test-data-', true);
        $this->shouldFail = $shouldFail;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function shouldFail(): bool
    {
        return $this->shouldFail;
    }
}
