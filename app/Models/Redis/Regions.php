<?php

namespace App\Models\Redis;

use App\Project\App;
use DB;

class Regions
{

    public const REDIS_KEY = "regions";

    public static function index()
    {
        $arRegions = DB::table("adm_h")
            ->select("REGIONCODE")
            ->distinct()
            ->get()
            ->map(fn ($el) => str_pad($el->REGIONCODE, 2, "0", STR_PAD_LEFT))
            ->toArray();
        App::getRedis()->del(self::REDIS_KEY);
        App::getRedis()->sadd(self::REDIS_KEY, $arRegions);
    }

    public static function get()
    {
        return collect(App::getRedis()->smembers(self::REDIS_KEY))->sort()->values()->toArray();
    }
}