<?php

namespace App\Models\Redis;

use App\Project\App;
use Illuminate\Support\Collection;
use Predis\Client;
use Project\Fias\Parsers\File\AddrObjParser;

class AddrObjDadata extends AddrObjParsed
{
    public const BASIC_REDIS_KEY = "dadata:addr_obj:";

    public function hmset($arData)
    {
        return $this->redis->hmset($this->getRedisKey(), $arData);
    }

    public static function getByAllRegions(): Collection
    {
        $obCollection = new Collection();
        foreach (Regions::get() as $sRegionCode) {
            $obSelf = new static($sRegionCode);
            $obCollection->push(...$obSelf->getAll());
        }
        return $obCollection;
    }

    public static function removeByKey(string $sRegionCode, string $sKey)
    {
        App::getRedis()->hdel(static::BASIC_REDIS_KEY.$sRegionCode, [$sKey]);
    }
}