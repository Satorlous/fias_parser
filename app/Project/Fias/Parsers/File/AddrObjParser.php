<?php

namespace Project\Fias\Parsers\File;

class AddrObjParser extends BaseParser
{
    public const ALLOWED_LEVELS = ["1", "2", "3", "4", "5", "6"];

    protected static string $sXmlElementName = "OBJECT";
    protected static string $sFilePattern = "AS_ADDR_OBJ_*.XML";
    public static string $sRedisKey = "addr_obj";

    protected static array $arExcludedAttributes = [
        "PREVID",
        "NEXTID",
        "STARTDATE",
        "UPDATEDATE",
        "ENDDATE",
        "ISACTUAL",
        "ISACTIVE",
    ];

    protected static function isAppropriate($arElement): bool
    {
        return $arElement["ISACTIVE"] === "1" &&
            $arElement["ISACTUAL"] === "1" &&
            in_array($arElement["LEVEL"], static::ALLOWED_LEVELS, true);
    }
}
