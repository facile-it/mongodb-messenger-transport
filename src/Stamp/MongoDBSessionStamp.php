<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Stamp;

use MongoDB\Driver\Session;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

final class MongoDBSessionStamp implements NonSendableStampInterface
{
    public function __construct(
        private readonly Session $session,
    ) {}

    public function getSession(): Session
    {
        return $this->session;
    }
}
