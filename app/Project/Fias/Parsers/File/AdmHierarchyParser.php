<?php

namespace Project\Fias\Parsers\File;

use App\Models\AdmHierarchy;
use RuntimeException;
use XMLReader;

class AdmHierarchyParser extends BaseParser
{
    protected static string $sFilePattern    = "AS_ADM_HIERARCHY_*.XML";
    protected static string $sXmlElementName = "ITEM";
    public static string    $sRedisKey       = "adm_h";
    protected static string $sModelClass = AdmHierarchy::class;

    protected static array $arExcludedAttributes = [
        "ID",
        "CHANGEID",
        "AREACODE",
        "CITYCODE",
        "PLACECODE",
        "PLANCODE",
        "STREETCODE",
        "PREVID",
        "NEXTID",
        "STARTDATE",
        "UPDATEDATE",
        "ENDDATE",
        "ISACTUAL",
        "ISACTIVE",
        "PATH"
    ];

    protected static function isAppropriate($arElement): bool
    {
        return $arElement["ISACTIVE"] === "1";
    }
}
