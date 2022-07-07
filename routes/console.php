<?php

use App\Project\App as MyApp;
use App\Models\Redis\AddrObjParsed;
use App\Models\Redis\LevelType;
use App\Models\Redis\Regions;
use App\Project\DadataTools;
use App\Project\Fias\CSVWriter;
use App\Project\Fias\Parsers\AddrObjTypeParser;
use Illuminate\Support\Facades\Artisan;
use League\Csv\Reader;
use Project\Fias\DataBuilder;
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

Artisan::command('fias:build', function () {
    set_time_limit(0);
    foreach (Regions::get() as $sRegionCode) {
        $obDataBuilder = new DataBuilder($sRegionCode);
        $obDataBuilder->build();
        dump($sRegionCode . " - OK");
    }
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

Artisan::command("unparsed", function () {
    $arParsedFiases   = [];
    $sRootDir         = env("DOCKER_ROOT_DIR");
    $obAddrCollection = MyApp::getJsonCollection($sRootDir . "/storage/fias/objects.json");
    foreach (glob($sRootDir . "/storage/results/dadata_*.json") as $sParsedFileName) {
        $arDadataParsedKeys = MyApp::getJsonCollection($sParsedFileName)
            ->filter(fn($el) => empty($el["value"]))
            ->keys()
            ->toArray();
        $arParsedFiases     = array_merge([], $arParsedFiases, $arDadataParsedKeys);
    }
    $obNeedToParse    = $obAddrCollection
        ->filter(fn($el) => !in_array($el["OBJECTGUID"], $arParsedFiases))
        ->map(fn($el) => $el["OBJECTGUID"])
        ->values();
    $sNeedToParseJson = $obNeedToParse->toJson(JSON_UNESCAPED_UNICODE);
    file_put_contents($sRootDir . "/storage/needtoparse/needtoparse.json", $sNeedToParseJson);
    $this->info("File saved");
});

Artisan::command('parse:rebound', function () {
    $sRootDir             = env("DOCKER_ROOT_DIR");
    $sNeedToParseFilename = $sRootDir . "/storage/needtoparse/needtoparse.json";
    $obCollection         = MyApp::getJsonCollection($sNeedToParseFilename);
    $i                    = 1;
    foreach ($obCollection->chunk(9900) as $obChunk) {
        MyApp::saveToFile("$sRootDir/storage/chunks/dadata_$i.json", $obChunk->toArray());
        $i++;
    }
});

Artisan::command('parse:chunk {number}', function ($number) {
    $sRootDir        = env("DOCKER_ROOT_DIR");
    $arFiles         = glob("$sRootDir/storage/chunks/dadata_$number.json");
    $sPath           = current($arFiles);
    $sResultsDir     = "$sRootDir/storage/results/";
    $sResultFileName = $sResultsDir . "dadata_a_$number.json";
    $obFiases        = MyApp::getJsonCollection($sPath);
    $obDadata        = new DadataTools();
    $arResponses     = [];
    $i               = 1;
    foreach ($obFiases->chunk(25) as $arFiases) {
        foreach ($arFiases as $sFias) {
            @$arResponse = current($obDadata->getElementsById($sFias)["suggestions"]);
            @$arResponses[$sFias] = $arResponse;
        }
        dump($i);
        $i++;
        sleep(1);
    }
    MyApp::saveToFile($sResultFileName, $arResponses);
});

Artisan::command("parse:join", function () {
    $arRequiredFields = [
        "postal_code",
        "fias_id",
        "kladr_id",
        "geo_lat",
        "geo_lon",
        "timezone"
    ];
    $arParsedObjects  = [];
    $sRootDir         = env("DOCKER_ROOT_DIR");
    foreach (glob($sRootDir . "/storage/results/dadata_*.json") as $sParsedFileName) {
        $arDadataObjects   = MyApp::getJsonCollection($sParsedFileName);
        $arParsedObjects[] = $arDadataObjects->toArray();
    }
    $obParsedCollection         = collect(array_merge([], ...$arParsedObjects))->filter();
    $obDadataCollectionRequired = $obParsedCollection
        ->map(fn($el) => [
            "value" => $el["value"],
            ...collect($el["data"])->filter(fn($value, $key) => in_array($key, $arRequiredFields, 1))
        ]);
    MyApp::saveToFile($sRootDir . "/storage/dadata_responses_full.json", $obParsedCollection->toArray());
    MyApp::saveToFile($sRootDir . "/storage/dadata_responses_required.json", $obDadataCollectionRequired->toArray());
    $this->info("File saved!");
});
