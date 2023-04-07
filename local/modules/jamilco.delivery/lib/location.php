<?

namespace Jamilco\Delivery;

use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Cookie;
use \Bitrix\Sale\Location\LocationTable;

class Location
{
    const PLACE = 'CUSTOM_LOCATION';
    const DEFAULT_CITY_NAME = 'Москва';

    public static function getCurrentLocation($getRegionId = false)
    {
        Loader::includeModule('sale');

        $locationId = self::getStoredLocation();
        if (!$locationId) $locationId = self::getGeoLocation();
        if (!$locationId) $locationId = self::getDefaultLocation();
        if ($locationId) {
            $arLoc = self::getLocationData($locationId);
            self::saveLocation($arLoc);
            if ($getRegionId) {
                $arLoc['REGION_ID'] = self::getRegionByLocationID($locationId);
                $arLoc['REGION_NAME'] = $arLoc['PATH'][$arLoc['REGION_ID']];
            }

            return $arLoc;
        }

        return false;
    }

    public static function getLocationData($locationId = 0, $locationCode = '', $clearCache = false)
    {
        if (!$locationId && !$locationCode) return false;
        Loader::includeModule('sale');

        $arMainLoc = false;

        $cacheManager = \Bitrix\Main\Data\Cache::createInstance();
        $cacheTime = 86400 * 30; // 30 дней
        $cachePath = '/location/get/'.$locationId.$locationCode.'/';
        $cacheParams = ['id' => $locationId, 'code' => $locationCode];
        $cacheId = [SITE_ID, LANGUAGE_ID, 'location:get'.$locationId.$locationCode];
        $cacheId = implode('|', $cacheId)."|".serialize($cacheParams);
        if ($clearCache || $cacheManager->startDataCache($cacheTime, $cacheId, $cachePath)) {
            $arFilter = [
                '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
            ];
            if ($locationCode) {
                $arFilter['CODE'] = $locationCode;
            } else {
                $arFilter['ID'] = $locationId;
            }

            $loc = LocationTable::getList(
                [
                    'filter' => $arFilter,
                    'select' => [
                        '*',
                        'NAME_RU'   => 'NAME.NAME',
                        'TYPE_CODE' => 'TYPE.CODE',
                    ],
                    'limit'  => 1,
                ]
            );
            if ($arMainLoc = $loc->Fetch()) {
                $path = LocationTable::getPathToNodeByCode(
                    $arMainLoc['CODE'],
                    [
                        'filter' => [
                            '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                            '!TYPE.CODE'        => 'COUNTRY',
                        ],
                        'select' => [
                            '*',
                            'NAME_RU'   => 'NAME.NAME',
                            'TYPE_CODE' => 'TYPE.CODE',
                        ],
                    ]
                );
                while ($arLoc = $path->Fetch()) {
                    $arMainLoc['PATH'][$arLoc['ID']] = $arLoc['NAME_RU'];
                }
                $arMainLoc['PATH'] = array_reverse($arMainLoc['PATH'], true);

                $cacheManager->endDataCache($arMainLoc);
            } else {
                $cacheManager->abortDataCache();
            }
        } else {
            $arMainLoc = $cacheManager->GetVars();
        }

        return $arMainLoc;
    }

    public static function getLocationByName($locName = '', $getId = false)
    {
        $loc = LocationTable::getList(
            [
                'filter' => [
                    '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    'NAME.NAME'         => $locName,
                ],
                'select' => ['ID'],
                'limit'  => 1,
            ]
        );
        if ($arLoc = $loc->Fetch()) {
            if ($getId) return $arLoc['ID'];

            return self::getLocationData($arLoc['ID']);
        }
    }

    public static function saveLocation($arLoc = [])
    {
        $locationId = $arLoc['ID'];
        $locationName = $arLoc['NAME_RU'];

        //$_SESSION[self::PLACE] = $locationId;

        if (self::PLACE == 'CUSTOM_LOCATION') {
            setcookie('city_id', $locationId, time() + 86400 * 365, '/');
            setcookie('city_name', $locationName, time() + 86400 * 365, '/');
        } else {
            $cookie = new Cookie(self::PLACE, $locationId);
            Application::getInstance()->getContext()->getResponse()->addCookie($cookie);
        }
    }

    private static function getDefaultLocation()
    {
        $loc = LocationTable::getList(
            [
                'filter' => [
                    '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    'NAME.NAME'         => self::DEFAULT_CITY_NAME,
                ],
                'select' => ['ID'],
                'limit'  => 1,
            ]
        );
        if ($arLoc = $loc->Fetch()) {
            return $arLoc['ID'];
        }

        return false;
    }

    private static function getGeoLocation()
    {
        if (Loader::includeModule("statistic")) {
            // Узнаем местоположение пользователя по его IP
            $city = new \CCity;
            $city->GetCityID();
            $arCity = $city->GetFullInfo();
            $arDataIP = [
                'region' => $arCity['REGION_NAME']['VALUE'],
                'city'   => $arCity['CITY_NAME']['VALUE'],
            ];

            if ($arDataIP['city'] == 'Москва' || $arDataIP['city'] == 'Санкт-Петербург') $arDataIP['region'] = '';

            $arFilter = [
                '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                'NAME.NAME'         => $arDataIP['city'],
            ];

            if ($arDataIP['region']) {
                $arFilter['=PARENT.NAME.LANGUAGE_ID'] = LANGUAGE_ID;
                $arFilter['=PARENT.PARENT.NAME.LANGUAGE_ID'] = LANGUAGE_ID;
                $arFilter[] = [
                    'LOGIC'                   => 'OR',
                    'PARENT.NAME.NAME'        => $arDataIP['region'],
                    'PARENT.PARENT.NAME.NAME' => $arDataIP['region'],
                ];
            }

            $loc = LocationTable::getList(
                [
                    'filter' => $arFilter,
                    'select' => [
                        '*',
                        'NAME_RU'   => 'NAME.NAME',
                        'TYPE_CODE' => 'TYPE.CODE',
                        //'PARENT_NAME_RU'        => 'PARENT.NAME.NAME',
                        //'PARENT_TYPE'           => 'PARENT.TYPE.CODE',
                        //'PARENT_PARENT_NAME_RU' => 'PARENT.PARENT.NAME.NAME',
                        //'PARENT_PARENT_TYPE'    => 'PARENT.PARENT.TYPE.CODE',
                    ],
                    'limit'  => 1,
                ]
            );
            if ($arLoc = $loc->Fetch()) {
                return $arLoc['ID'];
            }
        }

        return false;
    }

    private static function getStoredLocation()
    {
        //$locationId = (int)$_SESSION[self::PLACE];
        if (!$locationId) {
            if (self::PLACE == 'CUSTOM_LOCATION') {
                $locationId = $_COOKIE['city_id'];
            } else {
                $locationId = Application::getInstance()->getContext()->getRequest()->getCookie(self::PLACE);
                $locationId = (int)$locationId;
            }
        }

        return $locationId;
    }

    public static function clearStoredLocation()
    {
        unset($_SESSION[self::PLACE]);

        $cookie = new Cookie(self::PLACE, '');
        Application::getInstance()->getContext()->getResponse()->addCookie($cookie);
    }

    public static function getRegionByLocationID($locationId = 0)
    {
        if (!$locationId) return false;
        Loader::includeModule('sale');

        $loc = LocationTable::getByID($locationId);
        $arMainLoc = $loc->Fetch();

        $res = \Bitrix\Sale\Location\TypeTable::getList(
            [
                'filter' => ['CODE' => 'REGION'],
                'select' => ['ID'],
            ]
        );
        $arRegionType = $res->fetch();

        $path = LocationTable::getPathToNodeByCode($arMainLoc['CODE'], []);
        while ($arLoc = $path->Fetch()) {
            if ($arLoc['TYPE_ID'] == $arRegionType['ID']) return $arLoc['ID'];
        }

        return false;
    }
}