<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Sale;
use \Jamilco\Main\Utils;
use \DigitalDataLayer\Manager as DDM;

$cache = Cache::createInstance();

$arSku = []; // массив с ску
$arSkuIDs = []; // массив с id ску

$arProducts = [];
$productsID = 0;

// удалить подарки из списка товаров
foreach ($arResult["CATEGORIES"]["READY"] as $key => $arItem) {
    $pr = Sale\Internals\BasketPropertyTable::getList(['filter' => ["BASKET_ID" => $arItem['ID']]]);
    while ($arProp = $pr->fetch()) {
        $arItem['PROPS'][$arProp['CODE']] = $arProp['VALUE'];
    }

    $arResult["CATEGORIES"]["READY"][$key] = $arItem;
    if ($arItem['PROPS']['GIFT'] > 0) unset($arResult["CATEGORIES"]["READY"][$key]);
}
$arResult["CATEGORIES"]["READY"] = array_values($arResult["CATEGORIES"]["READY"]);
$arResult['NUM_PRODUCTS'] = count($arResult["CATEGORIES"]["READY"]);

foreach ($arResult["CATEGORIES"] as $category => $items) {
    foreach ($items as $v) {
        $arSkuIDs[] = $v['PRODUCT_ID'];
    }
}
if (count($arSkuIDs) > 0) {

    /** пересортировка по id по убыванию */
    usort(
        $arResult["CATEGORIES"]["READY"],
        function ($a, $b) {
            return ($a['ID'] > $b['ID']) ? -1 : 1;
        }
    );

    $ddlCartObject = DDM::getInstance()->doProcessCartObject($arResult['CATEGORIES']['READY']);
    if ($ddlCartObject['total'] < 10000) {
        $arResult['ORDER_INFO'] = Utils::getPreOrderData(DELIVERY_COURIER_ID);
    }

    $arColorsNames = []; // название цветов
    if ($cache->initCache(43200, $sku_id, '/small_basket_basket_line/color/')) {
        $arColorsNames = $cache->getVars();
    } else {
        $cache->startDataCache();
        if (CModule::IncludeModule("highloadblock")) {
            $hlblock_id = 1; // id справочника с цветами
            $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getById($hlblock_id)->fetch();
            $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();
            $rsColors = $entity_data_class::getList(
                array(
                    'select' => array('*')
                )
            );
            while ($arrColors = $rsColors->Fetch()) {
                $arColorsNames[$arrColors['UF_XML_ID']] = $arrColors['UF_NAME'];
            }
            $cache->endDataCache($arColorsNames);
        }
    }

    foreach ($arSkuIDs as $sku_id) {

        if ($cache->initCache(43200, $sku_id, '/small_basket_basket_line/sku/')) {
            $arSku[$sku_id] = $cache->getVars();
        } else {
            $cache->startDataCache();

            $arPreviewPictureIDs = []; // собираем id превью изображений, так как компонент отдает слишком сжатые изображения
            $rsSKU = \CIBlockElement::GetList(
                [],
                array('IBLOCK_ID' => 2, 'ID' => $sku_id),
                false,
                false,
                array(
                    'PROPERTY_CML2_LINK',
                    'ID',
                    'PROPERTY_SIZES_SHOES',
                    'PROPERTY_SIZES_CLOTHES',
                    'PROPERTY_SIZES_RINGS',
                    'PROPERTY_COLOR',
                    'PREVIEW_PICTURE',
                    'DETAIL_PAGE_URL'
                )
            );
            if ($arrSKU = $rsSKU->Fetch()) {
                $productsID = $arrSKU['PROPERTY_CML2_LINK_VALUE'];
                $previewPictureID = $arrSKU['PREVIEW_PICTURE'];
                $arSku[$sku_id] = array(
                    'PREVIEW_PICTURE' => $arrSKU['PREVIEW_PICTURE'],
                    'PRODUCT_ID'      => $arrSKU['PROPERTY_CML2_LINK_VALUE'],
                    'SIZE'            => $arrSKU['PROPERTY_SIZES_SHOES_VALUE'] ?: $arrSKU['PROPERTY_SIZES_CLOTHES_VALUE'] ?: $arrSKU['PROPERTY_SIZES_RINGS_VALUE'],
                    'COLOR'           => $arColorsNames[$arrSKU['PROPERTY_COLOR_VALUE']]
                );
            }

            $arPreviewPictures = []; // собираем ссылки на изображения
            if ($previewPictureID) {
                $rsPictures = \CFile::GetList([], array('@ID' => $previewPictureID));
                while ($arrPictures = $rsPictures->Fetch()) {
                    $arPreviewPictures[$arrPictures['ID']] = $arrPictures;
                }
            }

            $rsProducts = \CIBlockElement::GetList(
                [],
                array('IBLOCK_ID' => 1, 'ID' => $productsID),
                false,
                false,
                array('NAME', 'ID', 'DETAIL_PAGE_URL')
            );

            if ($arrProducts = $rsProducts->GetNext()) {
                $arProducts = array(
                    'NAME'            => $arrProducts['NAME'],
                    'DETAIL_PAGE_URL' => $arrProducts['DETAIL_PAGE_URL']
                );
            }

            $arSku[$sku_id]['PREVIEW_PICTURE'] = CFile::ResizeImageGet(
                $arPreviewPictures[$arrSKU['PREVIEW_PICTURE']],
                array(
                    'width'  => 248,
                    'height' => 310
                )
            );

            $arSku[$sku_id]['NAME'] = $arProducts['NAME'];
            $arSku[$sku_id]['DETAIL_PAGE_URL'] = $arProducts['DETAIL_PAGE_URL'];

            $cache->endDataCache($arSku[$sku_id]);
        }
    }
}

$arResult['SKU_PROPS'] = $arSku;

// DigitalDataLayer

$cp = $this->__component;
if (is_object($cp)) {

    /** DigitalDataLayer start */
    $cp->arResult['DDL_CART_PROPERTIES'] = [
        // суммарная стоимость товаров в корзине без доставки и использованных бонусных баллов
        'subtotal'  => $ddlCartObject['subtotal'] ?: 0,
        // цена, которую заплатит пользователь, включая все скидки, использованные бонусные баллы и стоимость доставки
        'total'     => $ddlCartObject['total'] ?: 0,
        'lineItems' => $ddlCartObject['lineItems'] ?: []
    ];
    /** DigitalDataLayer end */

    $cp->SetResultCacheKeys(['DDL_CART_PROPERTIES']);
}

/** Подготавливаем массив для window.dataLayer */
$baseCurrency = Bitrix\Currency\CurrencyManager::getBaseCurrency();
foreach ($arResult["CATEGORIES"] as $category => $items) {
    foreach ($items as $itemId => $v) {
        //находим ID категории по товару
        $productRes = CIBlockElement::GetByID((int)$v['PRODUCT_XML_ID']);
        $product = $productRes->GetNext();
        // Массив разделов
        $arSectionsByCurrent = [];
        $res = CIBlockSection::GetNavChain(false, $product['IBLOCK_SECTION_ID']);
        while ($arSectionPath = $res->GetNext()) {
            $arSectionsByCurrent[] = $arSectionPath["NAME"];
        }
        $category = implode('/', $arSectionsByCurrent);

        // BRAND
        $brandProps = \CIBlockElement::GetProperty(IBLOCK_CATALOG_ID, [$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['PRODUCT_ID']], [], ['CODE' => 'BRAND']);
        $brand = $brandProps->Fetch()['VALUE_ENUM'];

        $arResult['JS_OBJ']['WDL_BASKET_LINE'][$v['PRODUCT_ID']] = [
            'currencyCode'  => $baseCurrency,
            'list'          => $arSectionsByCurrent[count($arSectionsByCurrent) - 1],
            'step'          => 1,
            'name'          => $arResult['SKU_PROPS'][$v['PRODUCT_ID']]['NAME'],
            'id'            => (string)$v['PRODUCT_ID'],
            'price'         => number_format(round($v['PRICE']), 2, '.', ''),
            'brand'         => $brand,
            'category'      => $category,
            'variant'       => $v['NAME'],
            'position'      => $itemId + 1,
            'quantity'      => $v['QUANTITY'],
        ];
    }
}