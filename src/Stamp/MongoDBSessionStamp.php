<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Stamp;

use MongoDB\Driver\Session;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

final class MongoDBSessionStamp implements NonSendableStampInterface
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function getSession(): Session
    {
        return $this->session;
    }
}
