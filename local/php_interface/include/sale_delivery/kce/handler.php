<?
namespace Sale\Handlers\Delivery;

use \Bitrix\Main\Loader;
use \Bitrix\Iblock\IblockTable;
use \Bitrix\Sale\Shipment;
use \Bitrix\Sale\Delivery\CalculationResult;
use \Bitrix\Sale\Delivery\Services\Base;
use \Bitrix\Sale\Location\LocationTable;
use \Jamilco\Delivery\Location;

class KceHandler extends Base
{
    protected static $isCalculatePriceImmediately = true;
    protected static $whetherAdminExtraServicesShow = true;

    public function __construct(array $initParams)
    {
        parent::__construct($initParams);
    }

    public static function getClassTitle()
    {
        return 'KCE Delivery';
    }

    public static function getClassDescription()
    {
        return 'Доставка KCE';
    }

    protected function calculateConcrete(Shipment $shipment = null)
    {
        $result = new CalculationResult();

        // default price
        $result->setDeliveryPrice(roundEx($this->config["MAIN"]["DEFAULT_PRICE"], SALE_VALUE_PRECISION));

        $arLoc = Location::getCurrentLocation();
        $locationId = $arLoc['ID'];
        if (defined('ADMIN_SECTION')) {
            $order = $shipment->getCollection()->getOrder();
            $props = $order->getPropertyCollection();
            $locationProp = $props->getDeliveryLocation();
            $locationCode = $locationProp->getValue();
            $locationId = self::getLocationID($locationCode);
        }

        $info = $this->getDeliveryInfo($locationId);
        $result->setDeliveryPrice(roundEx($info['PRICE'], SALE_VALUE_PRECISION));
        if ($info['PERIOD']) $result->setPeriodDescription($info['PERIOD']);

        return $result;
    }

    public function isCompatible(Shipment $shipment = null)
    {
        $arLoc = Location::getCurrentLocation();
        $locationId = $arLoc['ID'];
        if (defined('ADMIN_SECTION')) {
            $order = $shipment->getCollection()->getOrder();
            $props = $order->getPropertyCollection();
            $locationProp = $props->getDeliveryLocation();
            $locationCode = $locationProp->getValue();
            $locationId = self::getLocationID($locationCode);
        }

        return self::checkLocationInSections($locationId);
    }

    static protected function checkLocationInSections($locationId = 0)
    {
        $arItem = self::getElementByLocationID($locationId);
        if ($arItem['ID']) return true;

        return false;
    }

    protected function getDeliveryInfo($locationId = 0)
    {
        $arElement = self::getElementByLocationID($locationId);

        $arOut = ['PRICE' => $arElement['PROPERTY_VALUE_VALUE'], 'PERIOD' => $arElement['PROPERTY_TERMS_VALUE']];

        return $arOut;
    }

    public function getElementByLocationID($locationId = 0, $selectAdditionalFields = [])
    {
        $selectFields = ['ID', 'PROPERTY_value', 'PROPERTY_terms'];
        if (!empty($selectAdditionalFields)) {
            $selectFields = array_merge($selectFields, $selectAdditionalFields);
        }
        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID'             => IBLOCK_TARIFS_KCE,
                '=PROPERTY_LOCATION_ID' => $locationId,
            ],
            false,
            ['nTopCount' => 1],
            $selectFields
        );
        if ($arItem = $el->Fetch()) {
            return $arItem;
        } else {
            // получим родительские локации
            $arPath = self::getLocationPath($locationId, true);
            $regionName = self::checkRegionName(array_pop($arPath));

            $el = \CIblockElement::GetList(
                [
                    'property_terms' => 'desc',
                    'property_value' => 'desc',
                ],
                [
                    'IBLOCK_ID'               => IBLOCK_TARIFS_KCE,
                    'PROPERTY_address_region' => [
                        $regionName,
                        '%'.$regionName,
                        $regionName.'%',
                    ],
                ],
                false,
                ['nTopCount' => 1],
                $selectFields
            );
            if ($arItem = $el->Fetch()) {
                return $arItem;
            }
        }

        return false;
    }

    protected static function getLocationPath($locationId = 0, $getNames = false)
    {
        $arResult = [];
        $loc = LocationTable::getPathToNode(
            $locationId,
            [
                'filter' => [
                    '!ID'               => $locationId,
                    '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    '>DEPTH_LEVEL'      => 1,
                ],
                'select' => [
                    'ID',
                    'NAME_RU' => 'NAME.NAME',
                ]
            ]
        );
        while ($arLoc = $loc->Fetch()) {
            $arResult[] = ($getNames) ? $arLoc['NAME_RU'] : $arLoc['ID'];
        }

        $arResult = array_reverse($arResult);

        return $arResult;
    }

    /**
     * @param string $locationCode
     *
     * @return int
     */
    protected static function getLocationID($locationCode = '')
    {
        if (strlen($locationCode) <= 0) return false;

        static $result = [];

        if (!isset($result[$locationCode])) {

            $dbRes = LocationTable::getList(
                [
                    'filter' => [
                        'CODE'              => $locationCode,
                        '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    ],
                    'select' => ['ID']
                ]
            );

            if ($rec = $dbRes->fetch()) {
                $result[$locationCode] = $rec['ID'];
            }
        }

        return $result[$locationCode];
    }

    protected function getConfigStructure()
    {
        Loader::includeModule('jamilco.delivery');

        return [
            "MAIN" => [
                "TITLE"       => 'Настройки',
                "DESCRIPTION" => 'Настройки',
                "ITEMS"       => [
                    "DEFAULT_PRICE" => [
                        "TYPE"     => "NUMBER",
                        "NAME"     => "Стоимость доставки по умолчанию",
                        "DEFAULT"  => "600",
                        "REQUIRED" => true,
                    ],
                ]
            ]
        ];
    }

    /**
     * список всех инфоблоков
     * @return array
     */
    protected function getIblocksList()
    {
        Loader::includeModule('iblock');
        $arResult = [];
        $res = IblockTable::getList(
            [
                'order'  => ['IBLOCK_TYPE_ID' => 'ASC', 'SORT' => 'ASC', 'ID' => 'ASC'],
                'filter' => []
            ]
        );
        while ($arIblock = $res->Fetch()) {
            $arResult[$arIblock['ID']] = $arIblock['ID'].'. '.$arIblock['NAME'];
        }

        return $arResult;
    }

    public function isCalculatePriceImmediately()
    {
        return self::$isCalculatePriceImmediately;
    }

    public static function whetherAdminExtraServicesShow()
    {
        return self::$whetherAdminExtraServicesShow;
    }

    /**
     * обновляет привязку тарифов к Местоположениям
     *
     * @param bool|false $skipAll - сброс всех привязок
     */
    static public function checkLocationID($showProgress = false, $skipAll = false, $count = 0)
    {
        $arData = [];
        $arLog = [];

        $loc = LocationTable::getList(
            [
                'filter' => [
                    '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    'TYPE.CODE'         => 'REGION',
                ],
                'select' => [
                    '*',
                    'NAME_RU'   => 'NAME.NAME',
                    'TYPE_CODE' => 'TYPE.CODE',
                ]
            ]
        );
        while ($arLoc = $loc->Fetch()) {
            $arData['REGIONS'][$arLoc['ID']] = self::checkRegionName($arLoc['NAME_RU']);
        }

        $arData['EXCHANGE'] = [];

        $arExchange = [
            'москва'          => 'московская',
            'санкт-петербург' => 'ленинградская',
        ];

        $loc = LocationTable::getList(
            [
                'order'  => ['ID' => 'ASC'],
                'filter' => [
                    '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    //'=PARENT.NAME.LANGUAGE_ID'        => LANGUAGE_ID,
                    //'=PARENT.PARENT.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    'TYPE.CODE'         => ['CITY', 'VILLAGE'],
                ],
                'select' => [
                    'ID',
                    'CODE',
                    'NAME_RU'    => 'NAME.NAME',
                    'TYPE_CODE'  => 'TYPE.CODE',
                    //'PARENT_NAME'        => 'PARENT.NAME.NAME',
                    'PARENT_ID',
                    //'PARENT_PARENT_NAME' => 'PARENT.PARENT.NAME.NAME',
                    'PARENT_ID2' => 'PARENT.PARENT.ID',
                    'PARENT_ID3' => 'PARENT.PARENT.PARENT.ID',
                    'PARENT_ID4' => 'PARENT.PARENT.PARENT.PARENT.ID',
                ]
            ]
        );
        while ($arLoc = $loc->Fetch()) {
            $name = self::checkLocationName($arLoc['NAME_RU']);

            $parentId = self::getParentID($arLoc, $arData['REGIONS'], $arData['EXCHANGE']);
            if (!$parentId) {
                if (array_key_exists($name, $arExchange)) {
                    foreach ($arData['REGIONS'] as $regionId => $regionName) {
                        if ($regionName == $arExchange[$name]) {
                            $parentId = $regionId;
                            $arData['EXCHANGE'][$arLoc['ID']] = $parentId;
                            break;
                        }
                    }
                }
            }

            // не перезаписываем
            if (!array_key_exists($name, $arData['LOCATIONS'][$parentId])) $arData['LOCATIONS'][$parentId][$name] = $arLoc['ID'];
        }

        $arFilter = [
            'IBLOCK_ID'                => IBLOCK_TARIFS_KCE,
            'ACTIVE'                   => 'Y',
            '!PROPERTY_address_region' => false,
            'PROPERTY_LOCATION_ID'     => [false, 0],
            'PROPERTY_NOT_FOUND'       => false,
        ];
        if ($skipAll) unset($arFilter['PROPERTY_LOCATION_ID']);
        $el = \CIblockElement::GetList(
            ['ID' => 'ASC'],
            $arFilter,
            false,
            ['nTopCount' => ($count > 0) ? (int)$count : 100000],
            [
                'ID',
                'NAME',
                'PROPERTY_address_region',
            ]
        );
        $progress = new \Jamilco\Main\Progress($el->SelectedRowsCount());
        while ($arOne = $el->Fetch()) {
            $arOne['NAME'] = ToLower($arOne['NAME']);
            $region = self::checkRegionName($arOne['PROPERTY_ADDRESS_REGION_VALUE']);
            $name = self::checkLocationName($arOne['NAME']);
            $arData['ITEMS'][$region][$name] = $arOne['ID'];
            if ($arOne['NAME'] != $name) $arData['ITEMS'][$region][$name] = $arOne['ID'];
        }

        foreach ($arData['ITEMS'] as $regionName => $arCities) {

            $regionId = false;
            foreach ($arData['REGIONS'] as $locId => $locName) {
                if ($locName == $regionName) $regionId = $locId;
            }

            foreach ($arCities as $cityName => $itemId) {
                if ($showProgress) $progress->step();

                $locId = $arData['LOCATIONS'][$regionId][$cityName];
                if ($locId) {
                    \CIBlockElement::SetPropertyValuesEx($itemId, IBLOCK_TARIFS_KCE, ['LOCATION_ID' => $locId]);
                    $arLog['ADD']++;
                } else {
                    \CIBlockElement::SetPropertyValuesEx($itemId, IBLOCK_TARIFS_KCE, ['NOT_FOUND' => 'Y']);
                    $arLog['NO_FOUND'][$regionName][] = $cityName;
                }
            }
        }

        $arLog['OST'] = \CIblockElement::GetList([], $arFilter, true);

        return $arLog;
    }

    public function getParentID($arLoc = [], $arRegions = [], $arExchange = [])
    {
        $parentId = $arLoc['PARENT_ID'];
        if (!array_key_exists($parentId, $arRegions) && !array_key_exists($parentId, $arExchange)) $parentId = $arLoc['PARENT_ID2'];
        if (!array_key_exists($parentId, $arRegions) && !array_key_exists($parentId, $arExchange)) $parentId = $arLoc['PARENT_ID3'];
        if (!array_key_exists($parentId, $arRegions) && !array_key_exists($parentId, $arExchange)) $parentId = $arLoc['PARENT_ID4'];
        if (!array_key_exists($parentId, $arRegions) && !array_key_exists($parentId, $arExchange)) $parentId = 0;

        if (array_key_exists($parentId, $arExchange)) {
            $parentId = $arExchange[$parentId];
        }

        return $parentId;
    }

    static public function checkLocationName($name = '')
    {
        $name = ToLower($name);
        $name = str_replace(
            [
                'днт ',
                'сдт ',
                'снт ',
                'пгск ',
                'гк ',
                'ст ',
                'ст. ',
                ' местечко',
                ' микрорайон',
                ' мост',
                ' м',
                ' сельсовет',
                ' снт',
                ' село',
                ' железнодорожная станция',
                ' станция',
                ' станица',
                ' слобода',
                ' с',
                ' деревня',
                ' дачный посёлок',
                ' д',
                ' территория',
                ' тер',
                ' посёлок городского типа',
                ' пгт',
                ' рабочий посёлок',
                ' рп',
                ' поселок',
                ' посёлок',
                ' почтовое отделение',
                ' плес',
                ' хутор',
                ' х',
                ' населённый пункт',
                ' нп',
                ' аул курортный',
                ' аул',
                ' аал',
                ' жилрайон',
            ],
            '',
            $name
        );

        if (substr_count($name, '(')) {
            $name = explode('(', $name);
            $name = array_shift($name);
        }

        $name = str_replace('ё', 'е', $name);

        $name = trim($name);

        return $name;
    }

    static public function checkRegionName($regionName = '')
    {
        $regionName = ToLower($regionName);
        $regionName = str_replace(
            [
                'автономный округ - югра',
                'автономный округ — югра',
                'автономный округ',
                'автономная область',
                'автономная обл',
                ' область',
                'республика',
                ' край',
                ' обл',
                ' респ',
                ' ао',
                '— алания',
                '- алания',
                '-алания',
                '*',
                'город ',
            ],
            '',
            $regionName
        );

        $regionName = str_replace(
            [
                '(якутия)',
            ],
            [
                '/якутия/',
            ],
            $regionName
        );
        $regionName = trim($regionName);

        return $regionName;
    }
}

