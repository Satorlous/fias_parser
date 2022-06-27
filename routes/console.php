<?php

use App\Http\Controllers\MainController;
use App\Models\Redis\AddrObjDadata;
use App\Models\Redis\AddrObjParsed;
use App\Models\Redis\LevelType;
use App\Models\Redis\Regions;
use App\Project\Fias\CSVWriter;
use App\Project\Fias\DadataKeys;
use App\Project\Fias\Parsers\AddrObjTypeParser;
use Dadata\DadataClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Project\Fias\Parsers\File\AddrObjParser;
use Project\Fias\Parsers\File\AdmHierarchyParser;
use Project\Fias\Parsers\RegionsParser;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('fias:build', function () {
    $mainController = new MainController();
    $mainController->buildData();
});

Artisan::command('fias:writecsv', function () {
    $obCsvWriter = new CSVWriter();
    foreach (Regions::get() as $sRegionCode) {
        $obAddrObjRedis = new AddrObjParsed($sRegionCode);
        $arObjects      = $obAddrObjRedis->getAll()->toArray();
        $obCsvWriter->insertAll($arObjects);
        dump($sRegionCode . " - WRITED");
    }
});

Artisan::command('fias:writejson', function () {
    $arAllObjects = [];
    foreach (Regions::get() as $sRegionCode) {
        $obAddrObjRedis = new AddrObjParsed($sRegionCode);
        $arObjects      = $obAddrObjRedis->getAll()->toArray();
        $arAllObjects[] = $arObjects;
        dump($sRegionCode . " - is added");
    }
    $arAllObjects = array_merge([], ...$arAllObjects);
    $sFilename    = dirname(__DIR__, 1) . "/storage/fias/objects.json";
    file_put_contents($sFilename, json_encode($arAllObjects, JSON_UNESCAPED_UNICODE));
    dump("finish");
});

Artisan::command('fias:types:writejson', function () {
    $arTypes   = (new LevelType())->getAll()->toArray();
    $sFilename = dirname(__DIR__, 1) . "/storage/fias/types.json";
    file_put_contents($sFilename, json_encode($arTypes, JSON_UNESCAPED_UNICODE));
    dump("finish");
});

Artisan::command('index:addr', function () {
    $oRegionParser = new RegionsParser();
    foreach ($oRegionParser->getRegionsPaths() as $sRegion) {
        $obAddrParser = new AddrObjParser($sRegion);
        $obAddrParser->indexRedis();
        dump("$sRegion - OK");
    }
});

Artisan::command('index:adm', function () {
    $oRegionParser = new RegionsParser();
    foreach ($oRegionParser->getRegionsPaths() as $sRegion) {
        $obAddrParser = new AdmHierarchyParser($sRegion);
        $obAddrParser->indexDatabase();
        dump("$sRegion - OK");
    }
});

Artisan::command('index:levels', function () {
    $obLevelsParser = new AddrObjTypeParser();
    $obLevelsParser->parseToRedis();
});

Artisan::command('index:children', function () {
    foreach (Regions::get() as $sRegionCode) {
        $obRepo = new AddrObjParsed($sRegionCode);
        $obAll  = $obRepo->getAll();
        foreach ($obAll as $arItem) {
            $arChildren             = $obAll->filter(fn($el) => $el["PARENTOBJID"] === $arItem["OBJECTID"]);
            $arItem["HAS_CHILDREN"] = (string) (int) $arChildren->isNotEmpty();
            $obRepo->set($arItem["OBJECTID"], $arItem);
        }
    }
});

Artisan::command('parse:dadata', function () {
    $obLocations = AddrObjDadata::getByAllRegions();
    dd($obLocations->count());
    $arApiKeys         = array_values(DadataKeys::KEYS);
    $i                 = 0;
    $arDadataResponses = [];
    foreach ($obLocations->chunk(10000) as $obChunk) {
        $obDadata = new DadataClient($arApiKeys[$i]["API"], null);
        foreach ($obChunk->chunk(30) as $obSecChunks) {
            foreach ($obSecChunks as $arLocation) {
                $arResponse                                 = $obDadata->findById(
                    "address",
                    $arLocation["OBJECTGUID"]
                )[0];
                $arDadataResponses[$arLocation["OBJECTID"]] = $arResponse;
                AddrObjDadata::removeByKey($arLocation["REGIONCODE"], $arLocation["OBJECTID"]);
            }
            sleep(1);
        }
        dump("$i - 10000 - OK");
        if (++$i === 9) {
            break;
        }
    }
    file_put_contents(
        "/var/www/html/storage/fias/dadata_1.json",
        json_encode($arDadataResponses, JSON_UNESCAPED_UNICODE)
    );
});

Artisan::command('parse:chunk', function () {
    $obLocations = AddrObjDadata::getByAllRegions();
    $i           = 1;
    foreach ($obLocations->chunk(9900) as $obChunk) {
        $arFiases = $obChunk->map(fn($el) => $el["OBJECTGUID"])->values()->toArray();
        file_put_contents(
            "/var/www/html/storage/fias/chunks/dadata_$i.json",
            json_encode($arFiases, JSON_UNESCAPED_UNICODE)
        );
        $i++;
    }
});
