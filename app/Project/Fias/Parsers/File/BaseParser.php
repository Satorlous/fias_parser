<?php

namespace Project\Fias\Parsers\File;

use App\Project\App;
use DB;
use Illuminate\Database\Eloquent\Model;
use Predis\Client;
use Predis\ClientException;
use RuntimeException;
use SimpleXMLElement;
use UnexpectedValueException;
use XMLReader;

abstract class BaseParser
{
    protected static string $sFilePattern;
    protected static array $arExcludedAttributes = [];
    protected static string $sIndexProperty = "OBJECTID";
    protected static string $sXmlElementName;
    public static string $sRedisKey;
    protected static string $sModelClass;

    protected string $sFileName;

    protected bool $bUseCache = true;

    private const   CACHE_DIR  = "/parsed";
    protected const SOURCE_DIR = "/storage/fias/files";

    protected SimpleXMLElement $obXml;
    protected string $sFolder;

    private Client $oRedis;
    private string $sRegionCode;

    public function __construct(string $sFolder)
    {
        $this->oRedis    = App::getRedis();
        $this->sFolder   = $sFolder;
        $this->sRegionCode = basename($sFolder);
        $sPathPattern    = $sFolder . static::$sFilePattern;
        $this->sFileName = (string) glob($sPathPattern)[0];
        if (!is_file($this->sFileName)) {
            throw new UnexpectedValueException("Не удалось найти файл по шаблону $sPathPattern");
        }
    }

    public function parseToArray()
    {
        /** @var XMLReader $obXmlReader */
        $obXmlReader = XMLReader::open($this->sFileName);
        $arElements  = [];
        while ($obXmlReader->read()) {
            if (
                ($obXmlReader->name === static::$sXmlElementName) &&
                $arElement = $this->makeElement($obXmlReader->readOuterXml())
            ) {
                if (static::$sIndexProperty && $sPropValue = $arElement[static::$sIndexProperty]) {
                    $this->oRedis->hset(
                        static::$sRedisKey,
                        $sPropValue,
                        json_encode($arElement, JSON_UNESCAPED_UNICODE)
                    );
                } else {
                    throw new RuntimeException(
                        "Element doesn't have key " . static::$sIndexProperty
                        . PHP_EOL . json_encode($arElement, JSON_UNESCAPED_UNICODE)
                    );
                }
            }
            unset($arElement);
            gc_collect_cycles();
        }
        $obXmlReader->close();
        return $arElements;
    }

    protected function makeElement(string $sXml): ?array
    {
        $obXml     = new SimpleXMLElement($sXml);
        $arElement = [];
        foreach ($obXml->attributes() as $sKey => $sValue) {
            $arElement[$sKey] = (string) $sValue;
        }
        if (static::isAppropriate($arElement)) {
            return $this->modifyElement($arElement);
        }
        return null;
    }

    protected static function isAppropriate($arElement): bool
    {
        return true;
    }

    protected function modifyElement($arElement)
    {
        return array_filter(
            $arElement,
            static function ($sAttrKey) {
                return !in_array($sAttrKey, static::$arExcludedAttributes, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function getCacheFileName()
    {
        return str_replace(["XML", self::SOURCE_DIR], ["json", self::CACHE_DIR], $this->sFileName);
    }

    public function getContent(): array
    {
        if ($this->bUseCache) {
            $sCacheFile = $this->getCacheFileName();
            if (!is_file($sCacheFile)) {
                $arXml = $this->parseToArray();
                $this->saveToFile($sCacheFile, json_encode($arXml, JSON_UNESCAPED_UNICODE));
                return $arXml;
            }
            return json_decode(file_get_contents($sCacheFile), JSON_OBJECT_AS_ARRAY);
        }
        return $this->parseToArray();
    }

    protected function saveToFile($sPath, $mxContent): void
    {
        $arParts = explode('/', trim($sPath, "/"));
        $sFile   = array_pop($arParts);
        $sDir    = "";
        foreach ($arParts as $sPart) {
            if (!is_dir($sDir .= "/$sPart") && !mkdir($sDir) && !is_dir($sDir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $sDir));
            }
        }
        file_put_contents($sDir . "/" . $sFile, $mxContent);
    }

    public function indexRedis(): void
    {
        /** @var XMLReader $obXmlReader */
        $obXmlReader = XMLReader::open($this->sFileName);
        while ($obXmlReader->read()) {
            if (
                ($obXmlReader->name === static::$sXmlElementName) &&
                $arElement = $this->makeElement($obXmlReader->readOuterXml())
            ) {
                if (static::$sIndexProperty && $sPropValue = $arElement[static::$sIndexProperty]) {
                    $sRedisKey = static::$sRedisKey.":".$this->sRegionCode;
                    $this->oRedis->hset(
                        $sRedisKey,
                        $sPropValue,
                        json_encode($arElement, JSON_UNESCAPED_UNICODE)
                    );
                } else {
                    throw new RuntimeException(
                        "Element doesn't have value at key = " . static::$sIndexProperty
                        . PHP_EOL . json_encode($arElement, JSON_UNESCAPED_UNICODE)
                    );
                }
            }
            unset($arElement);
            gc_collect_cycles();
        }
        $obXmlReader->close();
    }

    public function indexDatabase(): void
    {
        /** @var XMLReader $obXmlReader */
        $obXmlReader = XMLReader::open($this->sFileName);
        $arModels = [];
        $iModelsCounter = 0;
        while ($obXmlReader->read()) {
            if (
                ($obXmlReader->name === static::$sXmlElementName) &&
                $arElement = $this->makeElement($obXmlReader->readOuterXml())
            ) {
                $arModels[] = $arElement;
                if (++$iModelsCounter === 1000) {
                    $this->insertModels($arModels);
                    $arModels = [];
                    $iModelsCounter = 0;
                }
            }
            unset($arElement);
            gc_collect_cycles();
        }
        $obXmlReader->close();
        if ($iModelsCounter !== 0) {
            $this->insertModels($arModels);
        }
    }

    protected function insertModels(array $arModels): bool
    {
        /** @var Model $obModel */
        $obModel = new static::$sModelClass();
        $sTableName = $obModel->getTable();
        return DB::table($sTableName)->insert($arModels);
    }
}
