<?php

namespace Project\Fias\Parsers\File;

class MunHierarchyParser extends BaseParser
{
    protected static string $sFilePattern = "AS_MUN_HIERARCHY_*.XML";
    protected static string $sXmlElementName = "ITEM";

    protected static array $arExcludedAttributes = [
        "PREVID",
        "NEXTID",
        "STARTDATE",
        "UPDATEDATE",
        "ENDDATE",
        "ISACTUAL",
        "ISACTIVE",
    ];

    protected function modifyElement($arElement): array
    {
        $arMunElement = parent::modifyElement($arElement);
        $arMunElement["PATH"] = explode(".", $arMunElement["PATH"]);
        return $arMunElement;
    }
}