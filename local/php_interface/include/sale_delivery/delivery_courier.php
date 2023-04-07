<?php

use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Sale\Location\LocationTable;

Loader::includeModule('sale');

class CDeliveryCourier
{
    const IBLOCK_TARIF_CODE = 'delivery_tarifs';
    const DEFAULT_DELIVERY_PRICE = 500; // дефолтная цена доставки
    const DEFAULT_DELIVERY_DAYS = 5; // дефолтный срок доставки
    const FREE_DELIVERY_PRICE = 10000; // порог бесплатной доставки

    public function Init()
    {
        return [
            'SID' => 'courier',
            'NAME' => 'Доставка курьером (по тарифам)',
            'DESCRIPTION' => '',
            'DESCRIPTION_INNER' => 'Обработчик, получает стоимость доставки из инфоблока Тарифы доставки в зависимости от указанного города',
            'BASE_CURRENCY' => \COption::GetOptionString('sale', 'default_currency', 'RUB'),
            'HANDLER' => __FILE__,
            'DBGETSETTINGS' => ['CDeliveryCourier', 'GetSettings'],
            'DBSETSETTINGS' => ['CDeliveryCourier', 'SetSettings'],
            'GETCONFIG' => ['CDeliveryCourier', 'GetConfig'],
            'COMPABILITY' => ['CDeliveryCourier', 'Compability'],
            'CALCULATOR' => ['CDeliveryCourier', 'Calculate'],
            'PROFILES' => [
                'courier' => [
                    'TITLE' => 'доставка',
                    'DESCRIPTION' => 'Срок доставки до 3 дней',
                    'RESTRICTIONS_WEIGHT' => [0], // без ограничений
                    'RESTRICTIONS_SUM' => [0], // без ограничений
                ],
            ]
        ];
    }

    // настройки обработчика
    public function GetConfig()
    {
        $arConfig = [
            'CONFIG_GROUPS' => ['all' => 'Стоимость доставки'],
            'CONFIG' => [],
        ];

        // настройками обработчика в данном случае являются значения стоимости доставки в различные группы местоположений.
        // для этого сформируем список настроек на основе списка групп

        $dbLocationGroups = \CSaleLocationGroup::GetList();
        while ($arLocationGroup = $dbLocationGroups->Fetch()) {
            $arConfig['CONFIG']['price_' . $arLocationGroup['ID']] = [
                'TYPE' => 'STRING',
                'DEFAULT' => '',
                'TITLE' =>
                    'Стоимость доставки в группу "'
                    . $arLocationGroup['NAME'] . '" '
                    . '(' . \COption::GetOptionString('sale', 'default_currency', 'RUB') . ')',
                'GROUP' => 'all',
            ];
        }

        return $arConfig;
    }

    // подготовка настроек для занесения в базу данных
    public function SetSettings($arSettings)
    {
        // Проверим список значений стоимости. Пустые значения удалим из списка.
        foreach ($arSettings as $key => $value) {
            if (strlen($value) > 0) {
                $arSettings[$key] = (float)$value;
            } else {
                unset($arSettings[$key]);
            }
        }

        // вернем значения в виде сериализованного массива.
        // в случае более простого списка настроек можно применить более простые методы сериализации.
        return serialize($arSettings);
    }

    // подготовка настроек, полученных из базы данных
    public function GetSettings($strSettings)
    {
        // вернем десериализованный массив настроек
        return unserialize($strSettings);
    }

    // введем служебный метод, определяющий группу местоположения и возвращающий стоимость для этой группы.
    public function __GetLocationPrice($LOCATION_ID, $arConfig)
    {
        // получим список групп для переданного местоположения
        $dbLocationGroups = \CSaleLocationGroup::GetLocationList(['LOCATION_ID' => $LOCATION_ID]);

        while ($arLocationGroup = $dbLocationGroups->Fetch()) {
            if (
                array_key_exists('price_' . $arLocationGroup['LOCATION_GROUP_ID'], $arConfig)
                &&
                strlen($arConfig['price_' . $arLocationGroup['LOCATION_GROUP_ID']]['VALUE']) > 0
            ) {
                // если есть непустая запись в массиве настроек для данной группы, вернем ее значение
                return $arConfig['price_' . $arLocationGroup['LOCATION_GROUP_ID']]['VALUE'];
            }
        }

        // если не найдено подходящих записей, вернем false
        return false;
    }

    // метод проверки совместимости в данном случае практически аналогичен рассчету стоимости
    public function Compability($arOrder, $arConfig)
    {
        // проверим наличие стоимости доставки
        $price = self::DEFAULT_DELIVERY_PRICE;

        // если стоимость не найдено, вернем пустой массив - не подходит ни один профиль
        if ($price === false) {
            return [];
        }

        // в противном случае вернем массив, содержащий идентфиикатор единственного профиля доставки
        return ['courier'];
    }

    // собственно, рассчет стоимости
    public function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
    {
        $arResult = [
            'RESULT' => 'OK',
            'VALUE' => self::DEFAULT_DELIVERY_PRICE,
            'TRANSIT' => self::DEFAULT_DELIVERY_DAYS,
        ];

        if (!$arOrder['LOCATION_TO']) {
            $arOrder['LOCATION_TO'] = $_COOKIE['city_id'];
        }
        if (!$arOrder['LOCATION_TO']) {
            $arOrder['LOCATION_TO'] = DEFAULT_CITY_ID; // Moscow is default city
        }

        Loader::includeModule('iblock');
        $context = Context::getCurrent();

        $loc = LocationTable::getList([
            'filter' => [
                '=NAME.LANGUAGE_ID' => $context->getLanguage(),
                [
                    'LOGIC' => 'OR',
                    'ID' => $arOrder['LOCATION_TO'],
                    'CODE' => $arOrder['LOCATION_TO'],
                ]
            ],
            'select' => ['ID', 'CODE', 'CITY_NAME' => 'NAME.NAME']
        ]);
        $arLocation = $loc->Fetch();

        $path = LocationTable::getPathToNodeByCode(
            $arLocation['CODE'],
            [
                'filter' => [
                    '=NAME.LANGUAGE_ID' => $context->getLanguage(),
                    '!TYPE.CODE' => 'COUNTRY',
                ],
                'select' => ['NAME_RU' => 'NAME.NAME'],
            ]
        );
        if ($arLoc = $path->Fetch()) {
            $arLocation['REGION_NAME'] = $arLoc['NAME_RU'];
        }

        // определим сначала срок и стоимость доставки по региону, потом - попробуем определить конкретный город в этом регионе
        $arFilter = [
            'IBLOCK_CODE' => self::IBLOCK_TARIF_CODE,
            'NAME' => '%' . $arLocation['CITY_NAME'],
        ];

        $arRegion = self::getRegionData($arLocation['REGION_NAME']);
        if ($arRegion && $arLocation['REGION_NAME']) {
            $arResult['VALUE'] = $arRegion['PROPERTY_VALUE_VALUE'];
            $arResult['TRANSIT'] = $arRegion['PROPERTY_TERMS_VALUE'];

            $arFilter['PROPERTY_address_region'] = $arRegion['PROPERTY_ADDRESS_REGION_VALUE'];
        }

        $rsCity = \CIBlockElement::GetList(
            [],
            $arFilter,
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME', 'PROPERTY_value', 'PROPERTY_address_region', 'PROPERTY_terms']
        );
        if ($arCity = $rsCity->Fetch()) {
            $arResult['VALUE'] = $arCity['PROPERTY_VALUE_VALUE'];
            $arResult['TRANSIT'] = $arCity['PROPERTY_TERMS_VALUE'];
        }

        if ($arRegion || $arCity) {
            // особые сроки для крупных городов
            if ($arLocation['CITY_NAME'] === 'Москва') {
                $arResult['TRANSIT'] = 1;
            }
            if ($arLocation['CITY_NAME'] === 'Санкт-Петербург') {
                $arResult['TRANSIT'] = 2;
            }
        }

        // бесплатная доставка
        if (self::FREE_DELIVERY_PRICE > 0 && $arOrder['PRICE'] >= self::FREE_DELIVERY_PRICE) {
            $arResult['VALUE'] = 0;
        }

        return $arResult;
    }

    public static function getRegionData($regionName = '')
    {
        if (!$regionName || !Loader::includeModule('iblock')) {
            return false;
        }

        // общие замены
        if (substr_count($regionName, 'Республика')) {
            $regionName = trim(str_replace('Республика', '', $regionName));
            $regionName .= ' респ.';
        }
        $regionName = str_replace(
            [
                'автономная область',
                'автономный округ',
                'область',
                '-Алания',
                'Ингушетия',
                'Марий Эл',
                'Чувашская',
                'Саха (Якутия)',
            ],
            [
                'авт. обл.',
                'авт. округ',
                'обл.',
                '',
                'Ингушская',
                'Марий-Эл',
                'Чувашия',
                'Саха',
            ],
            $regionName
        );

        $regionName = trim($regionName);

        $el = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_CODE' => self::IBLOCK_TARIF_CODE,
                'NAME' => $regionName,
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME', 'PROPERTY_value', 'PROPERTY_address_region', 'PROPERTY_terms']
        );

        if ($arItem = $el->Fetch()) {
            return $arItem;
        }

        return false;
    }
}

AddEventHandler('sale', 'onSaleDeliveryHandlersBuildList', ['CDeliveryCourier', 'Init']);