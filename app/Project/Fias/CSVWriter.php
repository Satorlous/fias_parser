<?php

namespace App\Project\Fias;

use League\Csv\CannotInsertRecord;
use League\Csv\Writer;

class CSVWriter
{
    private const FILEPATH = "/storage/fias/import.csv";

    private const HEADER = [
        "CODE" => "OBJECTID",
        "PARENT_CODE" => "PARENTOBJID",
        "TYPE_CODE" => "TYPECODE",
        "NAME.RU.NAME" => "NAME_FULL",
        "EXT.FIAS" => "OBJECTGUID"
    ];

    private Writer $writer;

    /**
     * @throws CannotInsertRecord
     */
    public function __construct()
    {
        $sRoot        = dirname(__DIR__, 3);
        $this->writer = Writer::createFromPath($sRoot . self::FILEPATH, "w+");
        $this->writer->insertOne(array_keys(self::HEADER));
    }

    public function insertAll($arObjects)
    {
        $arFiltered = [];
        foreach ($arObjects as $arObject) {
            $arFilteredObject = [];
            foreach (self::HEADER as $sKey) {
                $arFilteredObject[] = $arObject[$sKey] ?? "";
            }
            $arFiltered[] = $arFilteredObject;
        }
        $this->writer->insertAll($arFiltered);
    }
}