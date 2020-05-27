<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Stamp;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

final class ReceivedStamp implements NonSendableStampInterface
{
    /** @var string */
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
