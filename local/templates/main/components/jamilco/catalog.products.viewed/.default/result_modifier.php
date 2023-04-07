<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$storeCities = \Jamilco\Main\Retail::getCityStores();
$arStoresID = $storeCities[$arParams['CITY_STORES_NAME']];

foreach ($arResult['ITEMS'] as $index => $arItem) {
    if (!$arItem['OFFER_ID_SELECTED']) {
        foreach ($arItem['OFFERS'] as $keyOffer => $arOffer) {
            $check = false;
            if ($arOffer['CATALOG_QUANTITY'] <= 0) {
                if ($arStoresID) {
                    $rsStore = \CCatalogStoreProduct::GetList(
                        [],
                        [
                            'PRODUCT_ID' => $arOffer['ID'],
                            'STORE_ID'   => $arStoresID,
                            '>AMOUNT'    => 0,
                        ]
                    );
                    if ($arrStore = $rsStore->Fetch()) $check = true;
                }
            } else {
                $check = true;
            }
            if ($check) {
                $arItem['OFFERS'][$keyOffer] = $arOffer;
                $arItem['OFFER_ID_SELECTED'] = $arOffer['ID'];
                break;
            }
        }
    }
    $arItem['RESIZE_IMAGE'] = \CFile::ResizeImageGet(
        $arItem['PREVIEW_PICTURE']['ID'],
        [
            'width' => 320,
            'height' => 399
        ],
        BX_RESIZE_IMAGE_PROPORTIONAL,
        true,
        false,
        false,
        85
    );

    $arResult['ITEMS'][$index] = $arItem;
}
