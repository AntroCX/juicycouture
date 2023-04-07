<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * комплект для товара
 *
 * @param array  $arIDs
 * @param string $cityName
 * @param array  $arColorPics
 *
 * @return mixed
 */
function getComplects($arIDs = [], $cityName = '')
{
    $arOut = [
        'SIZES'   => [], // массив для замены размеров
        'ITEMS'   => [], // ID товаров. которые нужно отобразить в комплекте
        'PRODUCT' => [], // массив для хранения данных о товарах из комплекта
    ];

    $el = \CIblockElement::GetList(
        ['SORT' => 'ASC'],
        [
            'IBLOCK_ID'      => IBLOCK_COMPLECTS_ID,
            'ACTIVE'         => 'Y',
            'PROPERTY_ITEMS' => $arIDs,
        ],
        false,
        ['nTopCount' => 10],
        [
            'IBLOCK_ID',
            'ID',
            'NAME',
            'PROPERTY_ITEMS',
        ]
    );
    while ($arItem = $el->Fetch()) {
        if ($arOut['ITEMS']) break;

        // проверим, включен ли для текущего товара вывод этого комплекта
        foreach ($arItem['PROPERTY_ITEMS_VALUE'] as $key => $val) {
            if (in_array($val, $arIDs) && $arItem['PROPERTY_ITEMS_DESCRIPTION'][$key] == 'Y') {

                foreach ($arItem['PROPERTY_ITEMS_VALUE'] as $key2 => $val2) {
                    $arOut['ITEMS'][] = $val2;
                }
                break;
            }
        }
    }

    if (count($arOut['ITEMS']) > 1) {

        $arColorPics = [];
        $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList(["filter" => ["ID" => HIBLOCK_COLOR_ID]])->fetch();
        $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        foreach ($arOut['ITEMS'] as $id) {
            $el = \CIblockElement::GetList(
                [],
                [
                    'IBLOCK_ID'     => IBLOCK_CATALOG_ID,
                    '=ID'           => $id,
                    'ACTIVE'        => 'Y',
                    'GLOBAL_ACTIVE' => 'Y',
                    'PROPERTY_HIDE' => false,
                ],
                false,
                ['nTopCount' => 1],
                [
                    'IBLOCK_ID',
                    'IBLOCK_SECTION_ID',
                    'ID',
                    'NAME',
                    'DETAIL_PAGE_URL',
                    'PROPERTY_ARTNUMBER',
                ]
            );
            if ($arItem = $el->GetNext()) {
                $arProduct = [
                    'ID'              => $arItem['ID'],
                    'NAME'            => $arItem['NAME'],
                    'DETAIL_PAGE_URL' => $arItem['DETAIL_PAGE_URL'],
                    'ARTNUMBER'       => $arItem['PROPERTY_ARTNUMBER_VALUE'],
                    'OFFERS'          => [],
                ];

                // получим доступные ТП
                $arOfferAvailableFilter = [
                    'LOGIC'                  => 'OR',
                    '!PROPERTY_DELIVERY_CAN' => false,
                ];
                if ($cityName) $arOfferAvailableFilter['PROPERTY_RETAIL_CITIES'] = $cityName;

                $of = \CIblockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID'          => IBLOCK_SKU_ID,
                        'PROPERTY_CML2_LINK' => $arProduct['ID'],
                        'ACTIVE'             => 'Y',
                        '>CATALOG_PRICE_1'   => 0,
                        $arOfferAvailableFilter
                    ],
                    false,
                    ['nTopCount' => 20],
                    [
                        'ID',
                        'NAME',
                        'CATALOG_GROUP_1',
                        'CATALOG_GROUP_2',
                        'PREVIEW_PICTURE',
                        'PROPERTY_SIZES_SHOES',
                        'PROPERTY_SIZES_CLOTHES',
                        'PROPERTY_SIZES_RINGS',
                        'PROPERTY_COLOR',
                    ]
                );
                while ($arOffer = $of->Fetch()) {

                    $arOffer['CATALOG_PRICE_1'] = (int)$arOffer['CATALOG_PRICE_1'];
                    $arOffer['CATALOG_PRICE_2'] = (int)$arOffer['CATALOG_PRICE_2'];

                    $arOne = [
                        'ID'    => $arOffer['ID'],
                        'NAME'  => $arOffer['NAME'],
                        'PRICE' => [
                            'BASE' => $arOffer['CATALOG_PRICE_1'],
                            'SALE' => ($arOffer['CATALOG_PRICE_2']) ?: $arOffer['CATALOG_PRICE_1'],
                        ],
                        'SIZE'  => $arOffer['PROPERTY_SIZES_CLOTHES_VALUE'],
                    ];

                    $arOne['PRICE']['BASE_FORMAT'] = \CCurrencyLang::CurrencyFormat($arOne['PRICE']['BASE'], 'RUB', true);
                    $arOne['PRICE']['SALE_FORMAT'] = \CCurrencyLang::CurrencyFormat($arOne['PRICE']['SALE'], 'RUB', true);

                    if ($arOffer['PROPERTY_SIZES_SHOES_VALUE']) $arOne['SIZE'] = $arOffer['PROPERTY_SIZES_SHOES_VALUE'];
                    if ($arOffer['PROPERTY_SIZES_RINGS_VALUE']) $arOne['SIZE'] = $arOffer['PROPERTY_SIZES_RINGS_VALUE'];

                    if ($arOffer['PROPERTY_COLOR_VALUE'] && !$arColorPics[$arOffer['PROPERTY_COLOR_VALUE']]) {
                        $rsHlData = $entity_data_class::getList(
                            [
                                'filter' => ['UF_XML_ID' => $arOffer['PROPERTY_COLOR_VALUE']],
                                'select' => ['UF_NAME']
                            ]
                        );
                        if ($arHlData = $rsHlData->Fetch()) $arColorPics[$arHlData["UF_XML_ID"]] = $arHlData['UF_NAME'];
                    }

                    if (!$arProduct['COLOR']) $arProduct['COLOR'] = $arColorPics[$arOffer['PROPERTY_COLOR_VALUE']];
                    if (!$arProduct['PHOTO']) $arProduct['PHOTO'] = \CFile::GetPath($arOffer['PREVIEW_PICTURE']);

                    $arProduct['OFFERS'][$arOffer['ID']] = $arOne;
                }

                // максимум 4 товара в комплекте, включая текущий товар
                if ($arProduct['OFFERS'] && count($arOut['PRODUCT']) < 4) $arOut['PRODUCT'][] = $arProduct;
            }
        }
    }

    return $arOut['PRODUCT'];
}

if (!function_exists('getSliderForItem')) {
    function getSliderForItem(&$item, $propertyCode, $addDetailToSlider)
    {
        $result = array();

        if (!empty($item) && is_array($item)) {
            if (
                '' != $propertyCode &&
                isset($item['PROPERTIES'][$propertyCode]) &&
                'F' == $item['PROPERTIES'][$propertyCode]['PROPERTY_TYPE']
            ) {
                if ('MORE_PHOTO' == $propertyCode && isset($item['MORE_PHOTO']) && !empty($item['MORE_PHOTO'])) {
                    foreach ($item['MORE_PHOTO'] as &$onePhoto) {
                        $result[] = array(
                            'ID'     => intval($onePhoto['ID']),
                            'SRC'    => $onePhoto['SRC'],
                            'WIDTH'  => intval($onePhoto['WIDTH']),
                            'HEIGHT' => intval($onePhoto['HEIGHT'])
                        );
                    }
                    unset($onePhoto);
                } else {
                    if (
                        isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) &&
                        !empty($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE'])
                    ) {
                        $fileValues = (
                        isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']['ID']) ?
                            array(0 => $item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) :
                            $item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']
                        );
                        foreach ($fileValues as &$oneFileValue) {
                            $result[] = array(
                                'ID'     => intval($oneFileValue['ID']),
                                'SRC'    => $oneFileValue['SRC'],
                                'WIDTH'  => intval($oneFileValue['WIDTH']),
                                'HEIGHT' => intval($oneFileValue['HEIGHT'])
                            );
                        }
                        if (isset($oneFileValue)) {
                            unset($oneFileValue);
                        }
                    } else {
                        $propValues = $item['PROPERTIES'][$propertyCode]['VALUE'];
                        if (!is_array($propValues)) {
                            $propValues = array($propValues);
                        }

                        foreach ($propValues as &$oneValue) {
                            $oneFileValue = CFile::GetFileArray($oneValue);
                            if (isset($oneFileValue['ID'])) {
                                $result[] = array(
                                    'ID'     => intval($oneFileValue['ID']),
                                    'SRC'    => $oneFileValue['SRC'],
                                    'WIDTH'  => intval($oneFileValue['WIDTH']),
                                    'HEIGHT' => intval($oneFileValue['HEIGHT'])
                                );
                            }
                        }
                        if (isset($oneValue)) {
                            unset($oneValue);
                        }
                    }
                }
            }
            if ($addDetailToSlider || empty($result)) {
                if (!empty($item['DETAIL_PICTURE'])) {
                    if (!is_array($item['DETAIL_PICTURE'])) {
                        $item['DETAIL_PICTURE'] = CFile::GetFileArray($item['DETAIL_PICTURE']);
                    }
                    if (isset($item['DETAIL_PICTURE']['ID'])) {
                        array_unshift(
                            $result,
                            array(
                                'ID'     => intval($item['DETAIL_PICTURE']['ID']),
                                'SRC'    => $item['DETAIL_PICTURE']['SRC'],
                                'WIDTH'  => intval($item['DETAIL_PICTURE']['WIDTH']),
                                'HEIGHT' => intval($item['DETAIL_PICTURE']['HEIGHT'])
                            )
                        );
                    }
                }
            }
        }

        return $result;
    }
}