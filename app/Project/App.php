<?php

namespace App\Project;

use Predis\Client;

class App
{
    private static Client $oRedis;

    public static function getRedis(): Client
    {
        return self::$oRedis ?? (self::$oRedis = new Client(["host" => "redis", "serialize" => "json"]));
    }
}
