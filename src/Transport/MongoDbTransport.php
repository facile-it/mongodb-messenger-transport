<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Transport;

use Symfony\Contracts\Service\ResetInterface;

class MongoDbTransport extends MongoDbUnresettableTransport implements ResetInterface {}
