<?php

namespace App\Http\Controllers;

use App\Models\AdmHierarchy;
use App\Models\Redis\AddrObj;
use App\Models\Redis\AddrObjParsed;
use App\Models\Redis\LevelType;
use App\Models\Redis\Regions;
use App\Project\App;
use App\Project\Fias\CSVWriter;
use App\Project\Fias\Parsers\AddrObjTypeParser;
use Dadata\CleanClient;
use Dadata\DadataClient;
use App\Project\Fias\DadataKeys;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;
use JetBrains\PhpStorm\NoReturn;
use Predis\Client;
use Project\Fias\DataBuilder;
use Project\Fias\Parsers\File\AddrObjParser;
use Project\Fias\Parsers\File\AdmHierarchyParser;
use Project\Fias\Parsers\RegionsParser;

class MainController extends BaseController
{

    #[NoReturn] public function main(): void
    {
        $arAll = AddrObjParsed::getByAllRegions();
        dd($arAll->filter(fn ($el) => $el["OBJECTGUID"] === '11399f9f-5f51-408f-be99-1a5d5c981a52'));
        $arHasChildren = [];
        foreach (Regions::get() as $sRegionCode) {
            $obRepo = new AddrObjParsed($sRegionCode);
            $obHasChildren = $obRepo->getHasChildren();
            array_push($arHasChildren, ...$obHasChildren->toArray());
        }
        dd(collect($arHasChildren));
    }

    #[NoReturn] public function writeCsv(): void
    {
        $obCsvWriter = new CSVWriter();
        foreach (Regions::get() as $sRegionCode) {
            $obAddrObjRedis = new AddrObjParsed($sRegionCode);
            $arObjects      = $obAddrObjRedis->getAll()->toArray();
            $obCsvWriter->insertAll($arObjects);
            dump($sRegionCode . " - WRITED");
        }
    }

    #[NoReturn] public function buildData(): void
    {
        set_time_limit(0);
        foreach (Regions::get() as $sRegionCode) {
            $obDataBuilder = new DataBuilder($sRegionCode);
            $obDataBuilder->build();
            dump($sRegionCode . " - OK");
        }
    }

    #[NoReturn] public function associateTypes(): void
    {
        $obLevels = new LevelType();
        $arLevels = $obLevels->getAll();
//        $arLevels = $obLevels->getByLevel(4);
        dd($arLevels);
    }
}
