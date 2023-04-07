<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("CHK_EVENT", false);
define("BX_CRONTAB", true);

use \Bitrix\Main\Loader;
use \Jamilco\Main\Oracle;

if (!$_SERVER['DOCUMENT_ROOT']) $_SERVER['DOCUMENT_ROOT'] = str_replace('/local/ajax', '', __DIR__);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

Loader::includeModule('iblock');

if (!defined('IBLOCK_SKU_ID')) die('not defined IBLOCK_SKU_ID');

$file = __DIR__.'/ocs.csv';

if (($handle = fopen($file, "r")) !== false) {
    while (($data = fgetcsv($handle, 1000, ";")) !== false) {
        $data[0] = trim(str_replace(' ', '', $data[0]));
        $arData[$data[0]] = $data[1];
    }
    fclose($handle);
}


$el = \CIblockElement::GetList(
    [],
    [
        'IBLOCK_ID' => IBLOCK_SKU_ID,
        //'PROPERTY_OCS_ID' => false, //
    ],
    false,
    false,
    [
        'ID',
        'IBLOCK_ID',
        'ACTIVE',
        'NAME',
        'PROPERTY_OCS_ID',
        'CATALOG_QUANTITY',
        'PROPERTY_RETAIL_CITIES'
    ]
);
while ($arItem = $el->Fetch()) {
    $arItem['NAME'] = trim(str_replace(' ', '', $arItem['NAME']));
    $ocsId = $arItem['PROPERTY_OCS_ID_VALUE'];


    if (!$arData[$arItem['NAME']]) {
        $newName = str_replace(['OS', 'б/р'], '', $arItem['NAME']);
        if ($arData[$newName]) $arItem['NAME'] = $newName;
    }

    if ($arData[$arItem['NAME']]) {
        if ($ocsId) {
            if ($ocsId == $arData[$arItem['NAME']]) {
                $arLog['EXIST']++;
            } else {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], ['OCS_ID' => $arData[$arItem['NAME']]]);
                $arLog['UPDATE']++;
            }
        } else {
            \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], ['OCS_ID' => $arData[$arItem['NAME']]]);
            $arLog['ADD']++;
        }
    } else {
        ///*
        if ($arItem['CATALOG_QUANTITY'] || $arItem['PROPERTY_RETAIL_CITIES_VALUE']) {
            pr($arItem);
        }
        //*/
        $arLog['NOT_FOUND']++;
        $arLog['NOT_FOUNDS']['ACTIVE'][$arItem['ACTIVE']]++;

        //$arLog['NOT_FOUND'][] = $arItem['NAME'];
    }
}

pr($arLog, 1, 1);