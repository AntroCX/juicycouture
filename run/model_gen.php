<?php
//$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define('BX_NO_ACCELERATOR_RESET', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/prolog.php');

global $USER;
if($USER->IsAdmin()) {

    \Bitrix\Main\Loader::includeModule('iblock');

    $fn = "jc_models.csv";
    $iblock_id = 1;
	$art_code = "ARTNUMBER";
	$model_code = "MODEL";
	$cat_code = "CATEGORY";

    $csv = array_map('str_getcsv', file($fn));

    echo "<pre>";
    print_r($csv);
    echo "</pre>";

    if(!empty($csv)) {

        $arArts = array();
        foreach ($csv as $data) {
            $arArts[] = $data[0];
        }

        $arItemIds = array();
        $res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $iblock_id, "PROPERTY_".$art_code => $arArts), false, false, array("ID", "PROPERTY_".$art_code));
        while ($arItem = $res->Fetch()) {
            $arItemIds[$arItem["PROPERTY_".$art_code."_VALUE"]][] = $arItem["ID"];
        }
        
        echo "<pre>";
        print_r($arItemIds);
        echo "</pre>";

        // set model - category
        foreach ($csv as $data) {
            foreach($arItemIds[$data[0]] as $itemId)
                CIBlockElement::SetPropertyValuesEx($itemId, $iblock_id, array($model_code => $data[1]));
                CIBlockElement::SetPropertyValuesEx($itemId, $iblock_id, array($cat_code => $data[2]));
        }

        echo "all done.";
    }
    else
        echo "cannot parse csv file: ".$fn;

}
else{
    echo "admin rights are mandatory.";
}