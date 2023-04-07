<?php

namespace Jamilco\Merch;

use \Bitrix\Main\Loader;

/**
 * Class Common
 * @package Jamilco\Merch
 */
class Common
{
    const MODULE_ID = 'jamilco.merch';
    const NEW_TIME_DAYS_DEFAULT = 21; // дней до сброса флага "Новинка"
    const CAPSULE_IBLOCK_ID = 20; // ID инфоблока "Капсулы"

    static $arModuleProperties = [
        'prop.default.sort' => [
            'TITLE'   => 'Дефолтная сортировка по индексу сортировки <br>(если установлена, другие типы сортировки не используются)',
            'INPUT'   => 'select',
            'TYPE'    => 'string',
            'DEFAULT' => 'NO',
            'GROUP'   => 'MAIN',
            'LIST'    => [
                'NO'   => 'Нет',
                'ASC'  => 'По возрастанию',
                'DESC' => 'По убыванию',
            ],
        ],
        'prop.season'          => [
            'TITLE'     => 'Свойство "Сезон"',
            'INPUT'     => 'select',
            'TYPE'      => 'string',
            'LIST_TYPE' => 'ITEMS_PROPS',
            'DEFAULT'   => 'SEASON',
            'GROUP'     => 'SETTINGS',
        ],
        'prop.season_group'    => [
            'TITLE'   => 'Группировать сезоны',
            'INPUT'   => 'select',
            'TYPE'    => 'string',
            'DEFAULT' => 'NO',
            'GROUP'   => 'SETTINGS',
            'LIST'    => [
                'NO'  => 'Нет',
                'TWO' => 'По два',
            ],
        ],
        'prop.season_tech'     => [
            'TITLE'     => 'Свойство "Сезон" (сортировка)',
            'INPUT'     => 'select',
            'TYPE'      => 'string',
            'LIST_TYPE' => 'ITEMS_PROPS',
            'DEFAULT'   => 'SEASON_TECH',
            'GROUP'     => 'SETTINGS',
        ],
        'prop.new'             => [
            'TITLE'     => 'Свойство "Новинка"',
            'INPUT'     => 'select',
            'TYPE'      => 'string',
            'LIST_TYPE' => 'ITEMS_PROPS',
            'DEFAULT'   => 'NEW',
            'GROUP'     => 'SETTINGS',
        ],
        'prop.soon'             => [
            'TITLE'     => 'Свойство "Скоро в продаже"',
            'INPUT'     => 'select',
            'TYPE'      => 'string',
            'LIST_TYPE' => 'ITEMS_PROPS',
            'DEFAULT'   => '',
            'GROUP'     => 'SETTINGS',
        ],
        'prop.new_clear'       => [
            'TITLE'     => 'Свойство "Окончание Новинки"',
            'INPUT'     => 'select',
            'TYPE'      => 'string',
            'LIST_TYPE' => 'ITEMS_PROPS',
            'DEFAULT'   => 'NEW_CLEAR',
            'GROUP'     => 'SETTINGS',
        ],
        'prop.collection_year' => [
            'TITLE'     => 'Свойство "Год коллекции"',
            'INPUT'     => 'select',
            'TYPE'      => 'string',
            'LIST_TYPE' => 'ITEMS_PROPS',
            'DEFAULT'   => 'COLLECTION_YEAR',
            'GROUP'     => 'SETTINGS',
        ],
        'prop.capsule'         => [
            'TITLE'     => 'Свойство "Капсула"',
            'INPUT'     => 'select',
            'TYPE'      => 'string',
            'LIST_TYPE' => 'ITEMS_PROPS',
            'DEFAULT'   => 'CAPSULE',
            'GROUP'     => 'SETTINGS',
        ],
        'prop.capsule_sort'    => [
            'TITLE'     => 'Свойство "Капсула" (сортировка)',
            'INPUT'     => 'select',
            'TYPE'      => 'string',
            'LIST_TYPE' => 'ITEMS_PROPS',
            'DEFAULT'   => 'CAPSULE_SORT',
            'GROUP'     => 'SETTINGS',
        ],
        'prop.sale_sort'       => [
            'TITLE'   => 'Товары sale внизу(сортировка)',
            'INPUT'   => 'checkbox',
            'TYPE'    => 'int',
            'DEFAULT' => 0,
            'GROUP'   => 'SETTINGS',
        ],
        'sale.all'             => [
            'TITLE'   => 'Sale-товары в общем каталоге',
            'INPUT'   => 'checkbox',
            'TYPE'    => 'int',
            'DEFAULT' => 0,
            'GROUP'   => 'MAIN',
        ],
        'outlet.all'           => [
            'TITLE'   => 'Outlet-товары в общем каталоге',
            'INPUT'   => 'checkbox',
            'TYPE'    => 'int',
            'DEFAULT' => 0,
            'GROUP'   => 'MAIN',
        ],
        'new.up'               => [
            'TITLE'   => '"Новинки" наверх',
            'INPUT'   => 'checkbox',
            'TYPE'    => 'int',
            'DEFAULT' => 0,
            'GROUP'   => 'MAIN',
        ],
        'new.time'             => [
            'TITLE'   => 'Дата сброса флага',
            'INPUT'   => 'date',
            'TYPE'    => 'string',
            'DEFAULT' => '',
            'GROUP'   => 'MAIN',
        ],
    ];

    /**
     * возвращает все свойства модуля
     *
     * @return array
     */
    public function getProps()
    {
        Loader::includeModule('iblock');

        $arCatalogIblock = self::getCatalogIblock();
        $arIblockProperties = [];
        $arIblockPropertiesListIds = [];
        $pr = \CIBlockProperty::GetList(["SORT" => "ASC"], ["IBLOCK_ID" => $arCatalogIblock['IBLOCK_ID']]);
        while ($arProp = $pr->Fetch()) {
            $arIblockProperties[$arProp['CODE']] = $arProp['NAME'];
            $arIblockPropertiesListIds[$arProp['CODE']] = $arProp['ID'];
        }

        $arProps = self::$arModuleProperties;
        foreach ($arProps as $propertyCode => $arProp) {
            if ($arProp['INPUT'] == 'select') {
                if ($arProp['LIST_TYPE'] == 'ITEMS_PROPS') $arProps[$propertyCode]['LIST'] = $arIblockProperties;
            }

            if ($arProp['TYPE'] == 'int') {
                $arProps[$propertyCode]['VALUE'] = \COption::GetOptionInt(self::MODULE_ID, $propertyCode, $arProp['DEFAULT']);
            } else {
                $arProps[$propertyCode]['VALUE'] = \COption::GetOptionString(self::MODULE_ID, $propertyCode, $arProp['DEFAULT']);
                if (\COption::GetOptionString(self::MODULE_ID, $propertyCode, $arProp['DEFAULT'])) {
                    $arProps[$propertyCode]['ID'] = $arIblockPropertiesListIds[\COption::GetOptionString(self::MODULE_ID, $propertyCode, $arProp['DEFAULT'])];
                }
            }
        }

        return $arProps;
    }

    /**
     * получаем список сезонов в сохраненном порядке
     *
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public function getSeasons()
    {
        Loader::includeModule('iblock');

        $arCatalogIblock = self::getCatalogIblock();
        $arProps = self::getProps();

        $arSeasons = [];
        $se = \CIBlockPropertyEnum::GetList(['sort' => 'asc'], ['IBLOCK_ID' => $arCatalogIblock['IBLOCK_ID'], 'CODE' => $arProps['prop.season']['VALUE']]);
        while ($arSeason = $se->Fetch()) {
            $arSeasons[$arSeason['XML_ID']] = $arSeason;
        }

        return $arSeasons;
    }

    /**
     * сохраняем пересортировку сезонов
     *
     * @param array $arSaveSeasons
     */
    public function saveSeasons($arSaveSeasons = [])
    {
        Loader::includeModule('iblock');

        $arSeasons = self::getSeasons();

        $sort = 0;
        foreach ($arSaveSeasons as $seasonCode) {
            if (!array_key_exists($seasonCode, $arSeasons)) continue;

            $sort += 10;
            \CIBlockPropertyEnum::Update($arSeasons[$seasonCode]['ID'], ['SORT' => $sort]);
        }

        self::reCheckAllSeasonInItems();
    }

    /**
     * Получаем дефолтный массив сортировки
     * по указанным параметрам в модуле jamilco.merch
     * @return array
     */
    public function getSortCustom()
    {
        $sortFields = array();
        $seasonSortCode = \COption::GetOptionString('jamilco.merch', 'prop.season_tech', 'SEASON_TECH');
        $collectionYearCode = \COption::GetOptionString('jamilco.merch', 'prop.collection_year', 'COLLECTION_YEAR');

        $sortFields['SORT'] = 'ASC';

        if ($seasonSortCode) {
            $sortFields['PROPERTY_'.$seasonSortCode] = 'DESC';
        }
        if ($collectionYearCode) {
            $sortFields['PROPERTY_'.$collectionYearCode] = 'DESC';
        }
        $newSort = \COption::GetOptionInt('jamilco.merch', 'new.up', 0);
        if ($newSort) {
            $sortFields['PROPERTY_'.\COption::GetOptionString('jamilco.merch', 'prop.new', 'NEW')] = 'DESC';
        }

        return $sortFields;
    }

    /**
     * пересохраняет во всех товарах сортировку по сезонам
     */
    public function reCheckAllSeasonInItems()
    {
        Loader::includeModule('iblock');

        $arProps = self::getProps();
        $arSeasons = self::getSeasons();
        $arCatalog = self::getCatalogIblock();

        $seasonCode = $arProps['prop.season']['VALUE'];
        $seasonSortCode = $arProps['prop.season_tech']['VALUE'];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID'              => $arCatalog['IBLOCK_ID'],
                '!PROPERTY_'.$seasonCode => false,
            ],
            false,
            false,
            ['IBLOCK_ID', 'ID', 'PROPERTY_'.$seasonCode, 'PROPERTY_'.$seasonSortCode]
        );
        while ($arItem = $el->Fetch()) {
            self::checkSeasonInItem($arItem['ID'], $arCatalog['IBLOCK_ID'], $arProps, $arSeasons);
        }
    }

    /**
     * пересохраняет сортировку по сезону в одном товаре
     *
     * @param int   $itemId
     * @param int   $iblockId
     * @param array $arProps
     * @param array $arSeasons
     */
    public function checkSeasonInItem($itemId = 0, $iblockId = 0, $arProps = [], $arSeasons = [])
    {
        if (!$itemId) return false;
        if (!$iblockId) {
            $arCatalog = self::getCatalogIblock();
            $iblockId = $arCatalog['IBLOCK_ID'];
        }
        if (!$arProps) $arProps = self::getProps();
        if (!$arSeasons) $arSeasons = self::getSeasons();

        $seasonCode = $arProps['prop.season']['VALUE'];
        $seasonSortCode = $arProps['prop.season_tech']['VALUE'];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID'              => $iblockId,
                '!PROPERTY_'.$seasonCode => false,
                'ID'                     => $itemId,
            ],
            false,
            ['nTopCount' => 1],
            ['IBLOCK_ID', 'ID', 'PROPERTY_'.$seasonSortCode]
        );
        if ($arItem = $el->Fetch()) {
            // получим сезоны
            $arItemSeasons = [];
            $pr = \CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], [], ['CODE' => $seasonCode]);
            while ($arProp = $pr->Fetch()) {
                $arItemSeasons[$arProp['VALUE']] = $arProp;
            }

            $seasonSort = self::getSeasonSort($arItemSeasons, $arSeasons);

            if ($arItem['PROPERTY_'.$seasonSortCode.'_VALUE'] != $seasonSort) {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$seasonSortCode => $seasonSort]);
            }
        }
    }

    /**
     * считает сортировку согласно порядку сезонов
     *
     * @param array $itemSeasons
     * @param array $arSeasons
     *
     * @return int
     */
    public function getSeasonSort($itemSeasons = [], $arSeasons = [])
    {
        if (!$arSeasons) $arSeasons = self::getSeasons();

        $resultSort = 0;
        $sortStep = 1000;
        foreach ($arSeasons as $arSeason) {
            if (array_key_exists($arSeason['ID'], $itemSeasons)) {
                $resultSort = $sortStep;
                break; // используем индекс первого из встреченных сезонов
            }

            $sortStep = $sortStep / 10;
        }

        return $resultSort;
    }

    /**
     * сохраняет срок "Новинки"
     *
     * @param int   $itemId
     * @param int   $iblockId
     * @param array $arProps
     */
    public function checkNewTimeInItem($itemId = 0, $iblockId = 0, $arProps = [])
    {
        if (!$itemId) return false;
        if (!$iblockId) {
            $arCatalog = self::getCatalogIblock();
            $iblockId = $arCatalog['IBLOCK_ID'];
        }
        if (!$arProps) $arProps = self::getProps();
        $newCode = $arProps['prop.new']['VALUE'];
        $newTimeCode = $arProps['prop.new_clear']['VALUE'];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'ID'        => $itemId,
                [
                    'LOGIC'                   => 'OR',
                    '!PROPERTY_'.$newCode     => false,
                    '!PROPERTY_'.$newTimeCode => false,
                ]
            ],
            false,
            ['nTopCount' => 1],
            ['IBLOCK_ID', 'ID', 'PROPERTY_'.$newCode, 'PROPERTY_'.$newTimeCode]
        );
        if ($arItem = $el->Fetch()) {
            if ($arItem['PROPERTY_'.$newCode.'_ENUM_ID'] > 0 && $arItem['PROPERTY_'.$newTimeCode.'_VALUE'] == '') {
                // "Новинка" - есть, даты - нет
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$newTimeCode => self::getNextDate()]);
            }
            if (!$arItem['PROPERTY_'.$newCode.'_ENUM_ID'] && $arItem['PROPERTY_'.$newTimeCode.'_VALUE'] != '') {
                // "Новинка" - нет, дата - есть
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$newTimeCode => '']);
            }
        }
    }

    /**
     * пересохраняет срок "Новинки" для всех товаров
     */
    public function checkAllNewTimeInItems()
    {
        $arCatalog = self::getCatalogIblock();
        $arProps = self::getProps();
        $newCode = $arProps['prop.new']['VALUE'];
        $newTimeCode = $arProps['prop.new_clear']['VALUE'];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arCatalog['IBLOCK_ID'],
                [
                    'LOGIC'                   => 'OR',
                    '!PROPERTY_'.$newCode     => false,
                    '!PROPERTY_'.$newTimeCode => false,
                ]
            ],
            false,
            false,
            ['IBLOCK_ID', 'ID', 'PROPERTY_'.$newCode, 'PROPERTY_'.$newTimeCode]
        );
        while ($arItem = $el->Fetch()) {
            if ($arItem['PROPERTY_'.$newCode.'_ENUM_ID'] > 0 && $arItem['PROPERTY_'.$newTimeCode.'_VALUE'] == '') {
                // "Новинка" - есть, даты - нет
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$newTimeCode => self::getNextDate()]);
            }
            if (!$arItem['PROPERTY_'.$newCode.'_ENUM_ID'] && $arItem['PROPERTY_'.$newTimeCode.'_VALUE'] != '') {
                // "Новинка" - нет, дата - есть
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$newTimeCode => '']);
            }
        }
    }

    /**
     * обновление капсульной сортировки для всех товаров
     */
    public function checkCapsulesInItems()
    {
        $arCatalog = self::getCatalogIblock();
        $arProps = self::getProps();
        $arCaps = self::getCapsules();

        $capsuleCode = $arProps['prop.capsule']['VALUE'];
        $capsuleSortCode = $arProps['prop.capsule_sort']['VALUE'];

        $arID = [];
        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID'               => $arCatalog['IBLOCK_ID'],
                '!PROPERTY_'.$capsuleCode => false,
            ],
            false,
            false,
            ['IBLOCK_ID', 'ID', 'PROPERTY_'.$capsuleCode, 'PROPERTY_'.$capsuleSortCode]
        );
        while ($arItem = $el->Fetch()) {
            $arID[] = $arItem['ID'];
            $sort = ($arItem['PROPERTY_'.$capsuleCode.'_VALUE'] > 0) ? $arCaps[$arItem['PROPERTY_'.$capsuleCode.'_VALUE']] : 0;
            if ($sort != $arItem['PROPERTY_'.$capsuleSortCode.'_VALUE']) {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$capsuleSortCode => $sort]);
            }
        }

        // сбросим сортировку в необработанных
        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arCatalog['IBLOCK_ID'],
                '!ID'       => $arID,
            ],
            false,
            false,
            ['IBLOCK_ID', 'ID', 'PROPERTY_'.$capsuleCode, 'PROPERTY_'.$capsuleSortCode]
        );
        while ($arItem = $el->Fetch()) {
            $sort = 0;
            if ($sort != $arItem['PROPERTY_'.$capsuleSortCode.'_VALUE']) {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$capsuleSortCode => $sort]);
            }
        }
    }

    /**
     * обновить сортировку по капсуле для одного элемента
     *
     * @param int   $itemId
     * @param int   $iblockId
     * @param array $arProps - массив свойств модуля
     * @param array $arCaps  - массив капсул
     */
    public function checkCapsuleInItem($itemId = 0, $iblockId = 0, $arProps = [], $arCaps = [])
    {
        if (!$itemId) return false;
        if (!$iblockId) {
            $arCatalog = self::getCatalogIblock();
            $iblockId = $arCatalog['IBLOCK_ID'];
        }
        if (!$arProps) $arProps = self::getProps();
        if (!$arCaps) $arCaps = self::getCapsules();

        $capsuleCode = $arProps['prop.capsule']['VALUE'];
        $capsuleSortCode = $arProps['prop.capsule_sort']['VALUE'];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'ID'        => $itemId,
            ],
            false,
            ['nTopCount' => 1],
            ['IBLOCK_ID', 'ID', 'PROPERTY_'.$capsuleCode, 'PROPERTY_'.$capsuleSortCode]
        );
        if ($arItem = $el->Fetch()) {
            $sort = ($arItem['PROPERTY_'.$capsuleCode.'_VALUE'] > 0) ? $arCaps[$arItem['PROPERTY_'.$capsuleCode.'_VALUE']] : 0;
            if ($sort != $arItem['PROPERTY_'.$capsuleSortCode.'_VALUE']) {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$capsuleSortCode => $sort]);
            }
        }
    }

    public function getCapsules()
    {
        $arOut = [];
        $el = \CIblockElement::GetList(
            [
                'SORT' => 'ASC',
                'NAME' => 'ASC',
                'ID'   => 'ASC',
            ],
            [
                'IBLOCK_ID' => self::CAPSULE_IBLOCK_ID,
                'ACTIVE'    => 'Y',
            ],
            false,
            false,
            ['IBLOCK_ID', 'ID', 'NAME', 'SORT']
        );
        $sort = 10000; // сортировка по убыванию
        while ($arItem = $el->Fetch()) {
            $arOut[$arItem['ID']] = $sort;
            $sort -= 10;
        }

        return $arOut;
    }

    public static function getCapsuleIblockId()
    {
        return self::CAPSULE_IBLOCK_ID;
    }

    public function getNextDate()
    {
        $date = ConvertTimeStamp(time() + 86400 * self::NEW_TIME_DAYS_DEFAULT, 'SHORT');

        return $date;
    }

    /**
     * возвращает каталог товаров
     *
     * @return array|bool
     */
    public function getCatalogIblock()
    {
        Loader::includeModule("catalog");

        $cat = \CCatalog::GetList(['IBLOCK_ID' => 'ASC'], ['!OFFERS_IBLOCK_ID' => false], false, ['nTopCount' => 1]);
        if ($arCatalog = $cat->Fetch()) {
            return $arCatalog;
        }

        return false;
    }

    /**
     * пересохраняет индекс сортировки товарам по доступности их размеров
     */
    public function resortItemsByAvailability()
    {
        $start = microtime(true);
        $arLog = [];
        $arCatalog = self::getCatalogIblock();

        $arProducts = [];
        // получим Товары
        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arCatalog['IBLOCK_ID'],
                [
                    'LOGIC'                     => 'OR',
                    '>PROPERTY_RETAIL_QUANTITY' => 0,
                    '>CATALOG_QUANTITY'         => 0,
                ]
            ],
            false,
            false,
            ['ID', 'PROPERTY_AVAILABLE_SORT']
        );
        while ($arItem = $el->Fetch()) {
            $arProducts[$arItem['ID']] = [
                'AVAILABLE' => 0,
                'RETAIL'    => 0,
                'PRE_SORT'  => $arItem['PROPERTY_AVAILABLE_SORT_VALUE'],
            ];
        }

        // получим ТП
        $of = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID'          => $arCatalog['OFFERS_IBLOCK_ID'],
                'PROPERTY_CML2_LINK' => array_keys($arProducts)
            ],
            false,
            false,
            [
                'ID',
                'PROPERTY_CML2_LINK',
                'CATALOG_QUANTITY',
                'CATALOG_STORE_AMOUNT_'.RETAIL_STORE_ID
            ]
        );
        while ($arOffer = $of->Fetch()) {
            $productId = $arOffer['PROPERTY_CML2_LINK_VALUE'];
            if ($arOffer['CATALOG_QUANTITY'] > 0) {
                $arProducts[$productId]['AVAILABLE']++;
            } elseif ($arOffer['CATALOG_STORE_AMOUNT_'.RETAIL_STORE_ID] > 0) {
                $arProducts[$productId]['RETAIL']++;
            }
        }

        // сохраним
        foreach ($arProducts as $productId => $arData) {
            $arProducts['NEW_SORT'] = $arData['AVAILABLE'] * 100 + $arData['RETAIL'] * 10;

            if ($arData['PRE_SORT'] != $arProducts['NEW_SORT']) {
                \CIBlockElement::SetPropertyValuesEx($productId, $arCatalog['IBLOCK_ID'], ['AVAILABLE_SORT' => $arProducts['NEW_SORT']]);
                $arLog['SET']++;
            } else {
                $arLog['EXIST']++;
            }
        }

        // удалим значение сортировки тем Товарам, которые не попали в выборку
        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID'                => $arCatalog['IBLOCK_ID'],
                '!ID'                      => array_keys($arProducts),
                '>PROPERTY_AVAILABLE_SORT' => 0,
            ],
            false,
            false,
            ['ID']
        );
        while ($arItem = $el->Fetch()) {
            \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arCatalog['IBLOCK_ID'], ['AVAILABLE_SORT' => 0]);
            $arLog['CLEAR']++;
        }
        $arLog['TIME'] = microtime(true) - $start;
        //pr($arLog);
    }

    /**
     * Заменяем флаги "Скоро в Продаже" на "Новинки"
     *
     * @param $productIds
     *
     * @return void
     */
    public static function checkSoonFlag($productIds)
    {
        if (!$productIds) {
            return;
        }

        $arCatalog = self::getCatalogIblock();
        $arProps = self::getProps();

        if (!$arProps['prop.soon']['VALUE']) {
            return;
        }
        if (!$arCatalog['IBLOCK_ID']) {
            return;
        }

        $r = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arCatalog['IBLOCK_ID'],
                'ID' => $productIds,
                '!PROPERTY_'.$arProps['prop.soon']['VALUE'] => false
            ],
            false,
            false,
            ['ID', 'PROPERTY_'.$arProps['prop.soon']['VALUE']]
        );
        while ($arItem = $r->fetch()) {
            if ($arItem['PROPERTY_'.$arProps['prop.soon']['VALUE'].'_VALUE']) {
                \CIBlockElement::SetPropertyValuesEx(
                    $arItem['ID'],
                    $arCatalog['IBLOCK_ID'],
                    [$arProps['prop.soon']['VALUE'] => false]
                );
                if ($arProps['prop.new']['VALUE']) {
                    $property_enums = \CIBlockPropertyEnum::GetList(
                        ["SORT" => "ASC"],
                        [
                            "IBLOCK_ID" => $arCatalog['IBLOCK_ID'],
                            "CODE"      => $arProps['prop.new']['VALUE']
                        ]
                    );
                    if($enum_fields = $property_enums->fetch()) {
                        \CIBlockElement::SetPropertyValuesEx(
                            $arItem['ID'],
                            $arCatalog['IBLOCK_ID'],
                            [$arProps['prop.new']['VALUE'] => $enum_fields['ID']]
                        );
                    }
                }
            }
        }
    }

    /**
     * событие при сохранении параметров
     *
     * @return bool
     */
    public function EventSaveOptions()
    {
        self::reCheckAllSaleInItems();

        return false;
    }

    /**
     * пересохраняет во всех товарах сортировку по sale
     */
    public function reCheckAllSaleInItems()
    {
        Loader::includeModule('iblock');

        $arCatalog = self::getCatalogIblock();

        // Получить все товары
        $arGoods = [];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arCatalog['IBLOCK_ID']
            ],
            false,
            false,
            ['ID', 'PROPERTY_GOODS_SALE']
        );
        while ($arItem = $el->Fetch()) {
            $arGoods[] = ['ID' => $arItem['ID'], 'GOODS_SALE' => $arItem['PROPERTY_GOODS_SALE_VALUE']];
        }

        // Получить все предложения, со скидочной ценой
        $arOffers = [];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arCatalog['OFFERS_IBLOCK_ID']
            ],
            false,
            false,
            ['CATALOG_GROUP_2', 'PROPERTY_CML2_LINK', 'ID']
        );
        while ($arItem = $el->Fetch()) {
            $arOffers[$arItem['PROPERTY_CML2_LINK_VALUE']][] = $arItem['CATALOG_PRICE_2'];
        }
        // Получаем код свойства

        $property_enums = \CIBlockPropertyEnum::GetList(Array(), Array("IBLOCK_ID" => $arCatalog['IBLOCK_ID'], "CODE" => "GOODS_SALE"));
        $enum_fields = $property_enums->GetNext();

        // Перебераем товары
        foreach ($arGoods as $goods) {
            // Перебераем торговые предложения
            foreach ($arOffers[$goods['ID']] as $offer) {
                // Устонавливаем галку сортировки в зависимости от sale цены
                if ($offer) { //Ставим галку если не стоит
                    if (!$goods['GOODS_SALE']) {
                        $arProperty = array(
                            'GOODS_SALE' => $enum_fields['ID'],
                        );
                        \CIBlockElement::SetPropertyValuesEx($goods['ID'], $arCatalog['IBLOCK_ID'], $arProperty);
                    }
                } else { //Убираем галку если стояла
                    if ($goods['GOODS_SALE']) {
                        $arProperty = array(
                            'GOODS_SALE' => false,
                        );
                        \CIBlockElement::SetPropertyValuesEx($goods['ID'], $arCatalog['IBLOCK_ID'], $arProperty);
                    }
                }
            }
        }
    }

}