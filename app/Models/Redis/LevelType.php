<?php

namespace App\Models\Redis;

use App\Project\App;
use Illuminate\Support\Collection;
use Predis\Client;

class LevelType
{
    public const BASIC_REDIS_KEY = "addr_types";

    private Client $redis;

    public function __construct()
    {
        $this->redis = App::getRedis();
    }

    public function getRedisKey(): string
    {
        return self::BASIC_REDIS_KEY;
    }

    public function getAll(): Collection
    {
        $arData = $this->redis->hgetall($this->getRedisKey());
        return collect($arData)->sortKeys()->map(fn($el) => json_decode($el, JSON_OBJECT_AS_ARRAY));
    }

    public function getByLevel(int $iLevel): Collection
    {
        return $this->getAll()->filter(fn($el) => $el["LEVEL"] === (string) $iLevel);
    }

    public function getByShortname(string $sShortName): Collection
    {
        return $this->getAll()->filter(fn($el) => $el["SHORTNAME"] === $sShortName);
    }

    public function getKeys(): array
    {
        return $this->redis->hkeys($this->getRedisKey());
    }
}