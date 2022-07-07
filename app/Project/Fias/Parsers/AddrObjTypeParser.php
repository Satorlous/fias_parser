<?php

namespace App\Project\Fias\Parsers;

use App\Project\App;
use http\Exception\RuntimeException;
use Predis\Client;
use Project\Fias\Parsers\File\AddrObjParser;
use SimpleXMLElement;

class AddrObjTypeParser
{
    public const  REDIS_KEY = "addr_types";
    private const FILENAME_PATTERN = "AS_ADDR_OBJ_TYPES_*.XML";

    private string $sFileName;
    private Client $redis;

    public const TYPES_DB      = [
        1 => "COUNTRY", //Страна
        2 => "COUNTRY_DISTRICT", //Округ
        3 => "REGION", // Область
        4 => "SUBREGION", // Район области
        5 => "CITY", // Город
        6 => "VILLAGE", // Село
        8 => "CITY_DISTRICT", // Район города
    ];

    public function __construct()
    {
        $_SERVER["DOCUMENT_ROOT"] = $_SERVER["DOCUMENT_ROOT"] ?: "/var/www/html/public";
        $sFiasFolder              = dirname($_SERVER["DOCUMENT_ROOT"]) . "/storage/fias/files/";
        $arSuggestions            = glob($sFiasFolder . self::FILENAME_PATTERN);
        $this->redis              = App::getRedis();
        if ($arSuggestions) {
            $this->sFileName = current($arSuggestions);
        } else {
            throw new RuntimeException("Can't find any file by pattern path " . $sFiasFolder . self::FILENAME_PATTERN);
        }
    }

    public function parseToRedis()
    {
        $sContent = file_get_contents($this->sFileName);
        $obXml    = new SimpleXMLElement($sContent);
        $iCounter = 0;
        foreach ($obXml as $obXmlElement) {
            $arElement = [];
            foreach ($obXmlElement->attributes() as $sKey => $sAttrValue) {
                if ($sKey === "ISACTIVE") {
                    $sAttrValue = (string) $sAttrValue === "true" ? 1 : 0;
                }
                $arElement[$sKey] = (string) $sAttrValue;
            }
            if (in_array($arElement["LEVEL"], AddrObjParser::ALLOWED_LEVELS)) {
                $arElement["TYPECODE"] = $this->suggestTypeCode($arElement);
                $iCounter++;
                $this->redis->hset(
                    self::REDIS_KEY,
                    $arElement["ID"],
                    json_encode($arElement, JSON_UNESCAPED_UNICODE)
                );
            }
        }
        dump($iCounter);
    }

    public function suggestTypeCode($arTypeData)
    {
        if (mb_strtolower($arTypeData["NAME"]) === "город") {
            return self::TYPES_DB[5];
        }
        if ($arTypeData["LEVEL"] === "1") {
            return self::TYPES_DB[3];
        }
        if ($arTypeData["LEVEL"] === "2") {
            if (mb_strtolower($arTypeData["NAME"]) === "поселение") {
                return self::TYPES_DB[8];
            }
            return self::TYPES_DB[4];
        }
        if ($arTypeData["LEVEL"] === "3") {
            return self::TYPES_DB[2];
        }
        if ($arTypeData["LEVEL"] === "4") {
            return self::TYPES_DB[2];
        }
        if ($arTypeData["LEVEL"] === "5") {
            return self::TYPES_DB[6];
        }
        if ($arTypeData["LEVEL"] === "6") {
            return self::TYPES_DB[6];
        }
    }
}