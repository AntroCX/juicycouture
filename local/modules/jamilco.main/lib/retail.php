<?php
namespace Jamilco\Main;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Sale\Location\LocationTable;
use \Jamilco\Omni\Channel;

class Retail
{
    const DEFAULT_CITY_ID = 19;
    const PRE_ORDER_PROP = 'PRE_ORDER';
    const FILTER_TO_OFFER = 'Y';

    /**
     * @param array $arFilter - массив фильтр
     * @param bool  $sale     - страница /sale/
     */
    public static function getItemFilter(&$arFilter = [], $sale = false)
    {
        $arItemFilter = [
            'LOGIC'                  => 'OR',
            '!PROPERTY_DELIVERY_CAN' => false,
        ];
        if (self::PRE_ORDER_PROP) {
            if (self::FILTER_TO_OFFER == 'Y') {
                $arItemFilter['!PROPERTY_CML2_LINK.PROPERTY_'.self::PRE_ORDER_PROP] = false;
            } else {
                $arItemFilter['!PROPERTY_'.self::PRE_ORDER_PROP] = false;
            }
        }

        $cityStoreName = \Jamilco\Main\Retail::getStoreName();
        if ($cityStoreName) {
            // если в текущем городе есть РМ, то добавляем фильтр по наличию в нем
            $arOneFilter = ['LOGIC' => 'AND', 'PROPERTY_RETAIL_CITIES' => $cityStoreName];
            $arItemFilter[] = $arOneFilter;
        } else {
            // если в текущем городе РМ нет, то выводим только товары с основного склада
        }

        if (self::FILTER_TO_OFFER == 'Y') {
            $arFilter['OFFERS'][] = $arItemFilter;
        } else {
            $arFilter[] = $arItemFilter;
        }
    }

    /**
     * @return bool|string
     */
    public static function getStoreName($getId = false)
    {
        Loader::includeModule('sale');

        // получим список складов и определим, в каких городах они присутствуют
        $storeCities = self::getCityStores();

        $arLoc = self::getCurrentLocationName();
        $locationName = $arLoc['NAME_RU'];
        if (array_key_exists($locationName, $storeCities)) return ($getId) ? $arLoc['ID'] : $locationName;

        return 'others'; // все города, в которых нет РМ получают одно и то же
    }

    /**
     * возвращает название выбранного пользователем города
     *
     * @return bool|string
     */
    public static function getCurrentLocationName()
    {
        Loader::includeModule('sale');

        if (class_exists('\Jamilco\Delivery\Location') && 0) {
            $arLoc = \Jamilco\Delivery\Location::getCurrentLocation();

            return $arLoc;
        } else {
            $locationId = self::DEFAULT_CITY_ID;
            if ((int)$_COOKIE['city_id'] > 0) {
                $locationId = (int)$_COOKIE['city_id'];
            }

            $loc = LocationTable::getList(
                [
                    'filter' => [
                        '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                        'ID'                => $locationId,
                    ],
                    'select' => [
                        '*',
                        'NAME_RU'   => 'NAME.NAME',
                        'TYPE_CODE' => 'TYPE.CODE',
                    ],
                    'limit'  => 1,
                ]
            );
            if ($arMainLoc = $loc->Fetch()) {
                return $arMainLoc;
            }
        }

        return false;
    }

    /**
     * получим список складов и определим, в каких городах они присутствуют
     * @return array
     */
    public static function getCityStores($addId = false, $clearCache = false)
    {
        Loader::includeModule('catalog');
        Loader::includeModule('sale');

        $storeCities = [];
        $cache = Cache::createInstance();
        $cacheTime = ($clearCache) ? 0 : 86400 * 30;
        if ($cache->initCache($cacheTime, "cityStores")) {
            $storeCities = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $st = \CCatalogStore::GetList([], ['ACTIVE' => 'Y']);
            while ($arStore = $st->Fetch()) {
                $arData = explode(',', $arStore['TITLE']);
                TrimArr($arData, true);
                if (count($arData) >= 3) {
                    $shopName = array_shift($arData);
                    $shopCity = array_shift($arData);
                    $shopName = implode(', ', $arData);
                    $storeCities[$shopCity][] = $arStore['ID'];
                }
            }

            // удалим неактивные РМ (нет ни одного флага Omni)
            $arHiddenStores = Channel::getHiddenShops();
            if ($arHiddenStores) {
                foreach ($storeCities as $city => $arStores) {
                    foreach ($arStores as $key => $storeId) {
                        if (in_array($storeId, $arHiddenStores)) unset($arStores[$key]);
                    }
                    $storeCities[$city] = array_values($arStores);
                }
            }

            $cache->endDataCache($storeCities);
        }

        if ($addId) {
            // заменим названия городов на их ID
            $arStoreCities = [];
            foreach ($storeCities as $city => $arStores) {
                $locationId = false;
                if (class_exists('\Jamilco\Delivery\Location')) {
                    $locationId = \Jamilco\Delivery\Location::getLocationByName($city, true);
                } else {
                    $loc = LocationTable::getList(
                        [
                            'filter' => [
                                '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                                'NAME.NAME'         => $city,
                            ],
                            'select' => ['ID'],
                            'limit'  => 1,
                        ]
                    );
                    if ($arLoc = $loc->Fetch()) {
                        $locationId = $arLoc['ID'];
                    }
                }

                $storeCities[$city] = [
                    'CITY_ID' => $locationId,
                    'STORES'  => $arStores,
                ];
            }
        }

        return $storeCities;
    }
}