<?php

namespace App\Project;

use Illuminate\Support\Collection;
use Predis\Client;

class App
{
    private static Client $oRedis;

    public static function getRedis(): Client
    {
        return self::$oRedis ?? (self::$oRedis = new Client(["host" => "redis", "serialize" => "json"]));
    }

    public static function getJsonCollection($sFilename): Collection
    {
        $sContent = file_get_contents($sFilename);
        return collect(json_decode($sContent, 1));
    }

    public static function saveToFile(string $sFileName, array $arContent): bool|int
    {
        $sJson = json_encode($arContent, JSON_UNESCAPED_UNICODE);
        return file_put_contents($sFileName, $sJson);
    }
}
