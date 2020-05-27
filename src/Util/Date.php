<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Util;

use MongoDB\BSON\UTCDateTime;

class Date
{
    public static function toUTC(\DateTimeInterface $date): UTCDateTime
    {
        return new UTCDateTime((int) $date->format('Uv'));
    }
}
