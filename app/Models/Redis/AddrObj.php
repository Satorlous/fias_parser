<?php

namespace App\Models\Redis;

use App\Project\App;
use Illuminate\Support\Collection;
use Predis\Client;

class AddrObj
{
    public const BASIC_REDIS_KEY = "addr_obj:";

    private string $sRegionCode;

    private Client $redis;

    public function __construct(int $iRegionCode)
    {
        $this->redis = App::getRedis();
        $this->setRegion($iRegionCode);
    }

    public function setRegion(int $iRegionCode): void
    {
        $this->sRegionCode = str_pad($iRegionCode, 2, "0", STR_PAD_LEFT);
    }

    public function getRedisKey(): string
    {
        return self::BASIC_REDIS_KEY . $this->sRegionCode;
    }

    public function getAll(): Collection
    {
        $arData = $this->redis->hgetall($this->getRedisKey());
        return collect($arData)->sortKeys()->map(fn($el) => json_decode($el, JSON_OBJECT_AS_ARRAY));
    }

    public function getById(int $iObjectId): array
    {
        return json_decode($this->redis->hget($this->getRedisKey(), $iObjectId), JSON_OBJECT_AS_ARRAY) ?? [];
    }

    public function getKeys(): array
    {
        return $this->redis->hkeys($this->getRedisKey());
    }
}