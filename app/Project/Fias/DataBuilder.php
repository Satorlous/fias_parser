<?php

namespace Project\Fias;

use App\Models\AdmHierarchy;
use App\Models\Redis\AddrObj;
use App\Models\Redis\AddrObjParsed;
use App\Models\Redis\LevelType;
use App\Project\App;
use Predis\Client;
use Project\Fias\Parsers\File\AddrObjParser;
use Project\Fias\Parsers\File\AdmHierarchyParser;
use Project\Fias\Parsers\File\MunHierarchyParser;

class DataBuilder
{
    private array $arAddrObjects;
    private array $arMunHierarchy;
    private array $arAdmHierarchy;

    private string $sRedisKey;
    private string $sRegionCode;

    private Client $redis;
    private LevelType $levelType;

//    private const COUNTRY_CODE = "0000028023";
    private const COUNTRY_CODE = "0";

    public function __construct(string $sRegionCode)
    {
        $this->sRegionCode = str_pad($sRegionCode, 2, "0", STR_PAD_LEFT);
        $this->levelType   = new LevelType();
    }

    public function build()
    {
        $obAddrRedis     = new AddrObj($this->sRegionCode);
        $arAddrObjectIds = $obAddrRedis->getKeys();
        sort($arAddrObjectIds);
        $this->arAddrObjects  = $obAddrRedis->getAll()->keyBy("OBJECTID")->toArray();

        $this->arAdmHierarchy = AdmHierarchy::where("REGIONCODE", (int) $this->sRegionCode)
            ->whereIn("OBJECTID", $arAddrObjectIds)
            ->get()
            ->keyBy("OBJECTID")
            ->toArray();

        $this->unsetNoParent();
        $this->fillParents();
        $this->fillRegionObjectsParent();
        $this->addRegionTypeData();
        $this->fixChuvashiya();

        $obParsedWriter = new AddrObjParsed((int) $this->sRegionCode);
        foreach ($this->arAddrObjects as $arAddrObject) {
            $obParsedWriter->set($arAddrObject["OBJECTID"], $arAddrObject);
        }
    }

    private function fillRegionObjectsParent(): void
    {
        $arRegionObject                                   = $this->getRegionObject();
        $arRegionObject["PARENTOBJID"]                    = self::COUNTRY_CODE;
        $this->arAddrObjects[$arRegionObject["OBJECTID"]] = $arRegionObject;
    }

    private function fillParents(): void
    {
        foreach ($this->arAddrObjects as $sObjId => $arAddrObj) {
            if (@$arAdmObject = $this->arAdmHierarchy[$sObjId]) {
                @$this->arAddrObjects[$sObjId]["PARENTOBJID"] = (string) $arAdmObject["PARENTOBJID"] ?: "";
            }
        }
    }

    private function unsetNoParent(): void
    {
        $arDiffObjects = $this->getDiffObjects();
        foreach ($arDiffObjects as $sObjectId => $arObject) {
            unset($this->arAddrObjects[$sObjectId]);
        }
    }

    private function fillDiffsParents(): void
    {
        $arRegionObject = $this->getRegionObject();
        $arDiffObjects  = $this->getDiffObjects();
        foreach ($arDiffObjects as $sObjectId => $arObject) {
            $arObject["PARENTOBJID"]         = (string) $arRegionObject["OBJECTID"];
            $arObject["PARENT_FILLED"]       = "1";
            $this->arAddrObjects[$sObjectId] = $arObject;
        }
    }

    public function getDiffObjects(): array
    {
        $arDiffIds = array_diff(array_keys($this->arAddrObjects), array_keys($this->arAdmHierarchy));
        return array_filter($this->arAddrObjects, static function ($arObj) use ($arDiffIds) {
            return in_array((int) $arObj["OBJECTID"], $arDiffIds, true);
        });
    }

    public function getRegionObject(): array
    {
        return current(array_filter($this->arAddrObjects, static fn($arObj) => $arObj["LEVEL"] === '1')) ?: [];
    }

    public function getByLevel(int $iLevel): array
    {
        return array_filter($this->arAddrObjects, static fn($arObj) => (int) $arObj["LEVEL"] === $iLevel);
    }

    private function addRegionTypeData(): void
    {
        foreach ($this->arAddrObjects as $sKey => $arAddrObject) {
            $arType    = $this->levelType->getByLevel($arAddrObject["LEVEL"])
                ->first(fn($el) => $el["SHORTNAME"] === $arAddrObject["TYPENAME"]);
            $sTypeName = $arType["NAME"];

            $this->arAddrObjects[$sKey]["REGIONCODE"]    = $this->sRegionCode;
            $this->arAddrObjects[$sKey]["TYPENAME_FULL"] = $sTypeName;
            $this->arAddrObjects[$sKey]["TYPECODE"]      = $arType["TYPECODE"];
            if (mb_strtolower($sTypeName) === "республика") {
                $this->arAddrObjects[$sKey]["NAME_FULL"] = "$sTypeName " . $arAddrObject["NAME"];
            } else {
                if (mb_strtolower($sTypeName) !== "город") {
                    $this->arAddrObjects[$sKey]["NAME_FULL"] = $arAddrObject["NAME"] . " " . mb_strtolower($sTypeName);
                } else {
                    $this->arAddrObjects[$sKey]["NAME_FULL"] = $arAddrObject["NAME"];
                }
            }
        }
    }

    private function fixChuvashiya()
    {
        if ($this->sRegionCode === "21") {
            $this->arAddrObjects[259389]["NAME"] = "Чувашская";
            $this->arAddrObjects[259389]["TYPENAME"] = "респ.";
            $this->arAddrObjects[259389]["TYPENAME_FULL"] = "Республика";
            $this->arAddrObjects[259389]["NAME_FULL"] = "Чувашская Республика";
        }
    }
}
