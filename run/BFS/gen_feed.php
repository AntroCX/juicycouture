<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

use Bitrix\Main\Loader;

Loader::IncludeModule("iblock");
Loader::IncludeModule("catalog");

///////////////////////////
$outputFileName = "feed.csv";

/** Артикул */
define("ARTICUL_CODE", "ARTNUMBER");

/** Картинки */
define("PICTURE_PROP_CODE", "MORE_PHOTO");

/** Скидка */
define("BASE_PRICE_TYPE", 1);
define("SALE_PRICE_TYPE", 2);

/** список Артикулов */
$uploadfile = 'articuls.csv';

try {
    /** название сайта = бренд */
    $brand = "";
    $b = "sort";
    $o = "asc";
    $rsSite = CSite::GetList($b, $o, array("LID" => 's1'));
    if ($arSite = $rsSite->Fetch())
        $brand = $arSite["NAME"];
    if (strlen($brand) <= 0)
        $brand = COption::GetOptionString("main", "site_name", "");
    if (strlen($brand) <= 0)
        throw new Exception("Невозможно определить бренд. Заполните поле \"Название\" в параметрах сайта");

    /** IBLOCK_ID каталога и торг. предложений */
    $catalogIblockId = 0;
    $skuIblockId = 0;

    /** пробуем найти */
    if(!$catalogIblockId || !$skuIblockId) {

        $arIBlockIDs = [];
        $rsCatalogs = CCatalog::GetList(
            array(),
            array('PRODUCT_IBLOCK_ID' => 0),
            false,
            false,
            array('IBLOCK_ID')
        );
        while ($arCatalog = $rsCatalogs->Fetch()) {
            $arCatalog['IBLOCK_ID'] = (int)$arCatalog['IBLOCK_ID'];
            if ($arCatalog['IBLOCK_ID'] > 0)
                $arIBlockIDs[] = $arCatalog['IBLOCK_ID'];
        }
        /** предполагаем один каталог на сайте */
        if (count($arIBlockIDs) <> 1) {
            throw new Exception("Невозможно определить IBLOCK_ID каталога. Задайте параметр вручную.");
        } else {
            $catalogIblockId = $arIBlockIDs[0];
        }

        $arIBlockIDs = [];
        $rsCatalogs = CCatalog::GetList(
            array(),
            array('PRODUCT_IBLOCK_ID' => $catalogIblockId),
            false,
            false,
            array('IBLOCK_ID')
        );
        while ($arCatalog = $rsCatalogs->Fetch()) {
            $arCatalog['IBLOCK_ID'] = (int)$arCatalog['IBLOCK_ID'];
            if ($arCatalog['IBLOCK_ID'] > 0)
                $arIBlockIDs[] = $arCatalog['IBLOCK_ID'];
        }
        /** предполагаем один каталог на сайте */
        if (count($arIBlockIDs) <> 1) {
            throw new Exception("Невозможно определить IBLOCK_ID торг. предложений. Задайте параметр вручную.");
        } else {
            $skuIblockId = $arIBlockIDs[0];
        }

    }
}
catch (Exception $e){
    echo "<br>".$e->getMessage()."<br>";
    die();
}

///////////////////////////

try {
    $db_iblock = CIBlock::GetByID($catalogIblockId);
    if (!($ar_iblock = $db_iblock->Fetch())){
        throw new Exception("iblock не найден");
    }

    // get root sections
    $arSect = array();
    $res = CIBlockSection::GetList(
        array("id" => "asc"),
        array("IBLOCK_ID" => $catalogIblockId, "ACTIVE" => "Y", "SECTION_ID" => 0),
        false,
        array("ID")
    );
    while ($arSect_ = $res->GetNext()){
        $arSect[] = $arSect_["ID"];
    }
    if(empty($arSect))
        throw new Exception("разделы не найдены");


    /** Читаем артикулы из файла */
    $file = dirname(__FILE__).'/'.$uploadfile;
    $fp = fopen($file, 'r');
    $articuls = [];
    $articuls2Price = [];
    while (($row = fgetcsv($fp, 0)) !== false) {
        $articuls[] = $row[0];
        $articuls2Price[$row[0]] = [
            "PRICE" => $row[1],
            "SALE_PRICE" => $row[2],
        ];
    }

    $articuls = array_unique($articuls);
    if(empty($articuls))
        throw new Exception("артикулы в файле не найдены");

    $arItemsList = [];
    $rs = \CIBlockElement::GetList([], ['IBLOCK_ID' => $catalogIblockId, 'PROPERTY_'.ARTICUL_CODE => $articuls], false, false, ['ID', 'PROPERTY_'.ARTICUL_CODE]);
    while($arItem = $rs->Fetch()){
        $arItemsList[$arItem['ID']] = $arItem['PROPERTY_'.ARTICUL_CODE.'_VALUE'];
    }

    // нет в ИБ
    if($diff = array_diff($articuls, $arItemsList)){
        echo "<br>Не найдены артикулы:<br>".implode('<br>', $diff)."<br>";
    }

    $arItemIds = array_keys($arItemsList);
    $arProducts = array();

    $arItemFilter = array(
        "ID" => $arItemIds,
        "IBLOCK_ID" => $catalogIblockId,
        "SECTION_ID" => $arSect,
        "INCLUDE_SUBSECTIONS" => "Y",
    );
    $arItemSelect = array(
        "ID",
        "IBLOCK_ID",
        "NAME",
        "PREVIEW_TEXT",
        "DETAIL_PAGE_URL",
        "IBLOCK_SECTION_ID",
        'PROPERTY_'.ARTICUL_CODE
    );

    $rsItems = CIBlockElement::GetList(
        array("id" => "asc"),
        $arItemFilter,
        false,
        false,
        $arItemSelect
    );
    while ($arItem = $rsItems->GetNext()) {
        $arProducts[$arItem['ID']] = $arItem;
    }

    if(empty($arProducts))
        throw new Exception("товары не найдены");

    // get sections paths
    foreach ($arProducts as &$arProduct) {
        $nav = CIBlockSection::GetNavChain(false, $arProduct["IBLOCK_SECTION_ID"], array("NAME"));
        $path = "";
        while($nav->ExtractFields("nav_")) {
            if($path)
                $path .= " > ";
            $path .= $nav_NAME;
        }
        if($path)
            $arProduct["PATH"] = $path;
    }
    unset($arProduct);

    // SKU
    $arOffers = CCatalogSKU::GetInfoByProductIBlock($catalogIblockId);
    $arBasePrice = CCatalogGroup::GetBaseGroup();

    if (!empty($arOffers)) {
        foreach ($arProducts as $itemId => $arItem) {
            $arOfferSelect = array(
                "ID",
                'NAME',
                "DETAIL_PAGE_URL",
                "PREVIEW_PICTURE",
                "DETAIL_PICTURE",
                // "PROPERTY_CML2_LINK.NAME",
                "PROPERTY_".PICTURE_PROP_CODE,
                "PROPERTY_".ARTICUL_CODE,
                "CATALOG_GROUP_".BASE_PRICE_TYPE,
                "CATALOG_GROUP_".SALE_PRICE_TYPE,
            );
            $arOfferFilter = array(
                'IBLOCK_ID' => $skuIblockId,
                'PROPERTY_' . $arOffers['SKU_PROPERTY_ID'] => $arItem['ID'],
                //"ACTIVE" => "Y",
                //"ACTIVE_DATE" => "Y",
                //">CATALOG_QUANTITY" => 0
            );
            $rsOfferItems = CIBlockElement::GetList(
                array("id" => "asc"),
                $arOfferFilter,
                false,
                false,
                $arOfferSelect
            );

            // выбираем первый оффер
            if ($offerItem = $rsOfferItems->GetNext()) {
                $arProducts[$itemId]["OFFERS"][$offerItem["ID"]] = $offerItem;
            }
        }

        // currencies
        $BASE_CURRENCY = \Bitrix\Currency\CurrencyManager::getBaseCurrency();
        if ($arCurrency = CCurrency::GetByID('RUR'))
            $RUR = 'RUR';
        else
            $RUR = 'RUB';

    }
} catch (Exception $e) {
    $errMsg = $e->getMessage();
}

if(!empty($errMsg)){
    echo "<br>".$errMsg."<br>";
}
else{

    // заголовки
    $arFieldsName = [
        "name",
        "description",
        "oldprice",
        "price",
        "discount",
        "category",
        "category", // не заполняем
        "promo", // не заполняем
        "url",
        "picture",
        "picture",
        "picture",
        "picture",
        "keywords" // не заполняем
    ];

    $fp = @fopen(dirname(__FILE__)."/".$outputFileName, "wb");
    $res = fputcsv($fp, $arFieldsName, ',');

    foreach ($arProducts as $arProduct) {

        $articul = $arProduct['PROPERTY_'.ARTICUL_CODE.'_VALUE'];
        $name = iconv("utf-8", "windows-1251", $arProduct["NAME"]);
        $previewText = preg_replace("/\r|\n|\&nbsp|\&/", ' ', strip_tags($arProduct['~PREVIEW_TEXT']));
        $previewText = iconv("utf-8", "windows-1251", $previewText);
        $path = iconv("utf-8", "windows-1251", $arProduct["PATH"]);
        $url = "https://".htmlspecialcharsbx($ar_iblock['SERVER_NAME']).$arProduct["DETAIL_PAGE_URL"];

        foreach ($arProduct["OFFERS"] as $arOffer) {
            $arValues = [];

            /** price */
            /** скидка из типа цены */
            $basePrice =  intval($arOffer["CATALOG_PRICE_".BASE_PRICE_TYPE]);
            $salePrice = intval($arOffer["CATALOG_PRICE_".SALE_PRICE_TYPE]);

            $minPrice = $fullPrice = 0;
            if ($arPrice = CCatalogProduct::GetOptimalPrice(
                $arOffer['ID'],
                1,
                array(2), // anonymous
                'N',
                array(),
                's1',
                array()
            )
            )
            {
                if($salePrice)
                    $minPrice = $salePrice;
                else
                    $minPrice = $arPrice['RESULT_PRICE']['DISCOUNT_PRICE'];
                if($basePrice)
                    $fullPrice = $basePrice;
                else
                    $fullPrice = $arPrice['RESULT_PRICE']['BASE_PRICE'];
            }

            if ($minPrice <= 0)
                continue;

            // картинки
            $image = $fid = "";
            $imgPath = [];
            if($arOffer['PREVIEW_PICTURE']){
                $fid = $arOffer['PREVIEW_PICTURE'];
                $image = CFile::GetFileArray($fid);
                if($image["SRC"])
                    $imgPath[] = "https://".htmlspecialcharsbx($ar_iblock['SERVER_NAME']).$image["SRC"];
            }
            if($arOffer['DETAIL_PICTURE']){
                $fid = $arOffer['DETAIL_PICTURE'];
                $image = CFile::GetFileArray($fid);
                if($image["SRC"])
                    $imgPath[] = "https://".htmlspecialcharsbx($ar_iblock['SERVER_NAME']).$image["SRC"];
            }
            if(!empty($arOffer["PROPERTY_".PICTURE_PROP_CODE."_VALUE"])){
                if(is_array($arOffer["PROPERTY_".PICTURE_PROP_CODE."_VALUE"])){
                    foreach($arOffer["PROPERTY_".PICTURE_PROP_CODE."_VALUE"] as $fid) {
                        $image = CFile::GetFileArray($fid);
                        if($image["SRC"])
                            $imgPath[] = "https://".htmlspecialcharsbx($ar_iblock['SERVER_NAME']).$image["SRC"];
                    }
                }
                else{
                    $fid = $arOffer["PROPERTY_".PICTURE_PROP_CODE."_VALUE"];
                    $image = CFile::GetFileArray($fid);
                    if($image["SRC"])
                        $imgPath[] = "https://".htmlspecialcharsbx($ar_iblock['SERVER_NAME']).$image["SRC"];
                }
            }

            $price = $sale_price = 0;

            /** ПРАЙС ИЗ ФАЙЛА! */

            $price = $articuls2Price[$arProduct["PROPERTY_".ARTICUL_CODE."_VALUE"]]["PRICE"];
            $sale_price = $articuls2Price[$arProduct["PROPERTY_".ARTICUL_CODE."_VALUE"]]["SALE_PRICE"];

            $discount = number_format(round(($price-$sale_price)/$price*100, 0), 0, '.', '').'%';

            /*
            if($minPrice < $fullPrice) {
                $price = $fullPrice;
                $sale_price = $minPrice;
            }
            else
                $price = $fullPrice;
            */

            $arValues = [
                $name,
                $previewText? $previewText : '',
                $price,
                $sale_price? $sale_price: $price,
                $discount? $discount: '',
                $path,
                '',
                '',
                $url,
                $imgPath[0]? $imgPath[0]: '',
                $imgPath[1]? $imgPath[1]: '',
                $imgPath[2]? $imgPath[2]: '',
                $imgPath[3]? $imgPath[3]: '',
                ''
            ];

            $res = fputcsv($fp, $arValues, ',');
        }
    }
    echo "<br>All done.<br>";
}