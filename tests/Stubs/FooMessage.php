<?php

namespace Facile\MongoDbMessenger\Tests\Stubs;

class FooMessage
{
    /** @var string */
    private $data;

    public function __construct()
    {
        $this->data = uniqid('test-data-');
    }

    public function getData(): string
    {
        return $this->data;
    }
}
