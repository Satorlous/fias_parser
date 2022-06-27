<?php

namespace Project\Fias\Parsers;

use UnexpectedValueException;

class RegionsParser
{
    private string $sFiasFolder;
    private array $arRegionsPaths;

    public function __construct()
    {
        $_SERVER["DOCUMENT_ROOT"] = $_SERVER["DOCUMENT_ROOT"] ?: "/var/www/html/public";
        $this->sFiasFolder = dirname($_SERVER["DOCUMENT_ROOT"]) . "/storage/fias/files/";
    }

    /**
     * @return string[]
     */
    public function getRegionsPaths(): array
    {
        if (isset($this->arRegionsPaths)) {
            return $this->arRegionsPaths;
        }
        $this->arRegionsPaths = array_map(
                static fn($sPath) => $sPath . "/",
                array_filter(
                    glob($this->sFiasFolder . "*"),
                    "is_dir"
                )
            ) ?? [];
        return $this->arRegionsPaths;
    }

    /**
     * @param string $sRegionCode
     *
     * @return string
     */
    public function getRegionFolderByCode(string $sRegionCode): string
    {
        $sRegionCode = str_pad($sRegionCode, 2, "0", STR_PAD_LEFT);
        $arRegions = $this->getRegionsPaths();
        $arFiltered = array_filter(
            $arRegions,
            fn($sRegionPath) => $sRegionPath === $this->sFiasFolder . $sRegionCode . "/"
        );
        if (!count($arFiltered)) {
            throw new UnexpectedValueException("Указанный регион не найден");
        }
        return current($arFiltered);
    }
}
