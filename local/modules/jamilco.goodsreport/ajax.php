<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

global $DB;
use \Bitrix\Main,
    \Bitrix\Highloadblock\HighloadBlockTable as HLBT;

Main\Loader::IncludeModule('sale');
Main\Loader::IncludeModule('iblock');
Main\Loader::IncludeModule('catalog');
Main\Loader::IncludeModule('highloadblock');

if(!check_bitrix_sessid())
    die();

if(!$_REQUEST['path'] || !$_REQUEST['titles'] || !$_REQUEST['max']){
    echo json_encode(array("error" => 'не заданы необходимые параметры'), JSON_UNESCAPED_UNICODE);
    die();
}
/************************/
// путь к файлу выгрузки
$upload_file_path = $_REQUEST['path'];
/************************/
// заголовки таблицы
$arFieldTitles = $_REQUEST['titles'];
/************************/
// размер выборки для пошагового исполнения
$max = $_REQUEST['max'];
/************************/

if(intval($_REQUEST['step'])>0 )
    $step = intval($_REQUEST['step']);
else
    $step = 1;

try {
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
    $msg = $e->getMessage();
    echo json_encode(array("error" =>$msg), JSON_UNESCAPED_UNICODE);
    die();
}
/************************/
// Цвета
$colorsIblockId = 1;
$colorNames = [];
if ($arData = HLBT::getById($colorsIblockId)->fetch()) {
    $colorEntity = HLBT::compileEntity($arData);
    $colors = $colorEntity->getDataClass();

    $params = [
        'select' => [
            'ID',
            'UF_NAME',
            'UF_XML_ID'
        ]
    ];
    $rsColors = $colors::GetList($params);
    while ($arColor = $rsColors->Fetch()) {
        $colorNames[$arColor['UF_XML_ID']] = $arColor['UF_NAME'];
    }
}
/************************/
// товары
$arItemFilter = [
    'IBLOCK_ID'             => $catalogIblockId,
    'INCLUDE_SUBSECTIONS'   => 'Y',
    //'ACTIVE'                => 'Y',
    //'SECTION_ACTIVE'        => 'Y',
    //'SECTION_GLOBAL_ACTIVE' => 'Y',
    //'SECTION_SCOPE'         => 'IBLOCK',
    //'>CATALOG_QUANTITY'     => 0
];
$arItemNavigation = [
    'nTopCount' => false,
    'iNumPage' => $step,
    'nPageSize' => $max,
    'checkOutOfRange' => true
];
$arItemSelect = [
    'ID',
    'IBLOCK_ID',
    'NAME',
    'ACTIVE',
    'DETAIL_TEXT',
    'PREVIEW_TEXT',
    'DETAIL_PAGE_URL',
    'IBLOCK_SECTION_ID',
    'PROPERTY_ARTNUMBER',
    'PROPERTY_SEASON',
    'PROPERTY_COLLECTIONS',
    'PROPERTY_TECHNOLOGY',
    'PROPERTY_MATERIAL',
    'PROPERTY_MATERIAL_STR',
    'PROPERTY_COLLECTION_YEAR',
    'PROPERTY_MODEL',
];

$arProductsById = [];
$arProductIds = [];
$rsItems = \CIBlockElement::GetList(
    [],
    $arItemFilter,
    false,
    $arItemNavigation,
    $arItemSelect
);
while ($arItem = $rsItems->GetNext()) {
    $arProductsById[$arItem['ID']] = $arItem;
    $arProductIds[] = $arItem["ID"];
}

// общ. число
$cnt = CIBlockElement::GetList(array(), $arItemFilter, array());

// торг. предл.
if(!empty($arProductIds)) {
    $arOfferFilter = [
        'IBLOCK_ID' => $skuIblockId,
        //'ACTIVE'    => 'Y',
        'PROPERTY_CML2_LINK'        => $arProductIds
    ];
    $arOfferSelect = [
        'ID',
        'NAME',
        'DETAIL_PAGE_URL',
        'PROPERTY_CML2_LINK',
        'PROPERTY_COLOR',
        'PROPERTY_SIZES_SHOES',
        'PROPERTY_SIZES_CLOTHES',
        'PROPERTY_SIZES_RINGS',
        'PROPERTY_ARTNUMBER',
        'PROPERTY_RUS_SIZE',
        'PROPERTY_CATEGORY',
        'PROPERTY_RETAIL_SKU_QUANTITY',
        'CATALOG_QUANTITY',
        'CATALOG_GROUP_1',
        'CATALOG_GROUP_2'
    ];

    $rsOffers = \CIBlockElement::GetList(
        [],
        $arOfferFilter,
        false,
        [],
        $arOfferSelect
    );
    while ($arOffer = $rsOffers->GetNext()) {
        $arProductsById[$arOffer['PROPERTY_CML2_LINK_VALUE']]["OFFERS"][] = $arOffer;
    }
}

if(empty($arProductIds)){
ob_start();
?>
    <tr><td>Готово!</td></tr>
    <tr><td><a href="<?=$upload_file_path?>"><?=$upload_file_path?></a></td></tr>
    <?/*?>
    <tr><td>&nbsp;</td></tr>
        <?
        $fp = fopen($_SERVER['DOCUMENT_ROOT'].$upload_file_path, 'r');
        $cnt = 0;
        while($arRow = fgetcsv($fp, 1000000, chr(59))){
            foreach ($arRow as &$item){
                $item = convWin2Utf($item);
            }
            unset($item);
            if($cnt == 0){?>
        <tr style="font-weight: bold;">
            <?foreach($arRow as $title):?>
            <td><?=$title?></td>
            <?endforeach;?>
        </tr>
        <?}
        else {
            echo "<tr>";
            foreach ($arRow as $item) {
                echo "<td>".$item."</td>";
            }
            echo "</tr>";
        }
        $cnt++;
        }?>
    <?*/?>
<?
    $result = ob_get_contents();
    ob_get_clean();
    echo json_encode(array("result" => $result), JSON_UNESCAPED_UNICODE);
    die();
}

// output file
if($step == 1) {
    $fp = fopen($_SERVER['DOCUMENT_ROOT'].$upload_file_path, 'w');
    foreach ($arFieldTitles as &$title) {
        $title = convUtf2Win($title);
    }
    unset($title);
    fputcsv($fp, $arFieldTitles, chr(59));
}
else
    $fp = fopen($_SERVER['DOCUMENT_ROOT'].$upload_file_path, 'a');

foreach ($arProductsById as $arProduct){
    $tech = '';
    $collection = '';
    $name = $arProduct["NAME"];
    $active = $arProduct["ACTIVE"];
    $detail_text = preg_replace("/[\r|\n|\&nbsp|\&|;]/", ' ', strip_tags($arProduct['DETAIL_TEXT']));
    if(!$detail_text)
        $detail_text = preg_replace("/[\r|\n|\&nbsp|\&|;]/", ' ', strip_tags($arProduct['PREVIEW_TEXT']));
    $material = "";
    if(is_array($arProduct["PROPERTY_MATERIAL_VALUE"])){
        foreach($arProduct["PROPERTY_MATERIAL_VALUE"] as $val){
            $material .= ($material? ', ':'').$val;
        }
    }
    else{
        $material = $arProduct["PROPERTY_MATERIAL_VALUE"];
    }
    if(!$material){
        if(is_array($arProduct["PROPERTY_MATERIAL_STR_VALUE"])){
            foreach($arProduct["PROPERTY_MATERIAL_STR_VALUE"] as $val){
                $material .= ($material? ', ':'').$val;
            }
        }
        else{
            $material = $arProduct["PROPERTY_MATERIAL_STR_VALUE"];
        }
    }
    $section = "";
    $dbres = CIBlockSection::GetList(array(),array("IBLOCK_ID"=>$catalogIblockId, "ID"=>$arProduct["IBLOCK_SECTION_ID"]), array("ID", "NAME"));
    if($arSection=$dbres->Fetch()){
        $section = $arSection["NAME"];
    }
    $season = "";
    foreach($arProduct["PROPERTY_SEASON_VALUE"] as $val){
        $season .=  ($season? ', ' :'').$val;
    }
    $articul = $arProduct["PROPERTY_ARTNUMBER_VALUE"];
    foreach ($arProduct["OFFERS"] as $arOffer){
        if ($arOffer['CATALOG_PRICE_2'] > 0) {
            $oldprice = (float)$arOffer['CATALOG_PRICE_1'];
            $price = (float)$arOffer['CATALOG_PRICE_2'] ?: (float)$arOffer['CATALOG_PRICE_1'];
        } else {
            $oldprice = $price = (float)$arOffer['CATALOG_PRICE_1'];
        }
        $size = ($arOffer['PROPERTY_SIZES_SHOES_VALUE']? $arOffer['PROPERTY_SIZES_SHOES_VALUE']: ($arOffer['PROPERTY_SIZES_CLOTHES_VALUE']? $arOffer['PROPERTY_SIZES_CLOTHES_VALUE'] : ($arOffer['PROPERTY_SIZES_RINGS_VALUE']? $arOffer['PROPERTY_SIZES_RINGS_VALUE'] : '')));
        $color = $colorNames[$arOffer['PROPERTY_COLOR_VALUE']];
        $arFieldsVal = [
            $articul,
            $arProduct['PROPERTY_MODEL_VALUE'],
            $size,
            $active,
            $arOffer["CATALOG_QUANTITY"],
            $price,
            $oldprice,
            $name,
            $detail_text,
            $material,
            $tech,
            $section,
            $collection,
            $color,
            $season,
            $arProduct["PROPERTY_COLLECTION_YEAR_VALUE"]
        ];
        foreach ($arFieldsVal as &$val){
            $val = convUtf2Win($val);
        }
        unset($val);
        fputcsv($fp, $arFieldsVal, chr(59));
    }
}
fclose($fp);

echo json_encode(array('step' =>($step+1), 'total' => ceil($cnt/$max)+1) );
die();

/************************/
function convUtf2Win($str){
    return iconv("UTF-8", "windows-1251", $str);
}
function convWin2Utf($str){
    return iconv("windows-1251", "UTF-8", $str);
}
?>