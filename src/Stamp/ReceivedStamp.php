<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Stamp;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

final class ReceivedStamp implements NonSendableStampInterface
{
    public function __construct(private readonly string $id) {}

    public function getId(): string
    {
        return $this->id;
    }
}
