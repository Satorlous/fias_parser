<?php

namespace App\Models\Redis;

use App\Project\App;
use Illuminate\Support\Collection;
use Predis\Client;

class AddrObjParsed
{
    public const BASIC_REDIS_KEY = "parsed:addr_obj:";

    protected string $sRegionCode;

    protected Client $redis;

    public function __construct(int $iRegionCode)
    {
        $this->redis = App::getRedis();
        $this->setRegion($iRegionCode);
    }

    public function setRegion(int $iRegionCode): void
    {
        $this->sRegionCode = str_pad($iRegionCode, 2, "0", STR_PAD_LEFT);
    }

    public function set(string $sKey, array $arData): int
    {
        return $this->redis->hset($this->getRedisKey(), $sKey, json_encode($arData, JSON_UNESCAPED_UNICODE));
    }

    protected function getRedisKey(): string
    {
        return static::BASIC_REDIS_KEY . $this->sRegionCode;
    }

    public function getAll(): Collection
    {
        $arData = $this->redis->hgetall($this->getRedisKey());
        return $this->decode(collect($arData));
    }

    public function getByLevel(int $iLevel): Collection
    {
        return  $this->getAll()->filter(fn($el) => (int) $el["LEVEL"] === $iLevel);
    }

    protected function decode(Collection $obCollection): Collection
    {
        return $obCollection->sortKeys()->map(fn($el) => json_decode($el, JSON_OBJECT_AS_ARRAY));
    }

    public function getById(int $iObjectId): array
    {
        return json_decode($this->redis->hget($this->getRedisKey(), $iObjectId), JSON_OBJECT_AS_ARRAY) ?? [];
    }

    public function getKeys(): array
    {
        return $this->redis->hkeys($this->getRedisKey());
    }

    public function getHasChildren()
    {
        return $this->getAll()->filter(fn($el) => $el["HAS_CHILDREN"] === "1");
    }
}