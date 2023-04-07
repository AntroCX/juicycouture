<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

global $MESS;
include(GetLangFileName(dirname(__FILE__)."/", "/success.php"));

$APPLICATION->SetTitle(GetMessage("RSB_ACQUIRING_DEFAULT_PAGE_TITLE"));

CModule::IncludeModule("rsb.acquiring");
$success = new \Rsb\Acquiring\Success($_POST);
if($success->bInstallError()) {
    echo(GetMessage("RSB_ACQUIRING_OSIBKA_PERUSTANOVIT"));
    return;
}
$success->setDebug("11");
$arResult = $success->action();

if($arResult["RESULT"]["RESULT_CODE"] == "000") {
    $APPLICATION->SetTitle(GetMessage("RSB_ACQUIRING_SUCCESS_PAGE_TITLE"));
    echo "<br />".GetMessage("RSB_ACQUIRING_SUCCESS_PAGE_MESS");
} 
elseif($arResult["RESULT"]["RESULT_CODE"] == "100") {
    $APPLICATION->SetTitle(GetMessage("RSB_ACQUIRING_ERROR_PAGE_TITLE"));
    echo "<br />".GetMessage("RSB_ACQUIRING_SUCCESS_PAGE_100");
} 
elseif($arResult["RESULT"]["RESULT_CODE"] == "101") {
    $APPLICATION->SetTitle(GetMessage("RSB_ACQUIRING_ERROR_PAGE_TITLE"));
    echo "<br />".GetMessage("RSB_ACQUIRING_SUCCESS_PAGE_101");
} 
elseif($arResult["RESULT"]["RESULT_CODE"] == "116") {
    $APPLICATION->SetTitle(GetMessage("RSB_ACQUIRING_ERROR_PAGE_TITLE"));
    echo "<br />".GetMessage("RSB_ACQUIRING_SUCCESS_PAGE_116");
} 
else {
    $APPLICATION->SetTitle(GetMessage("RSB_ACQUIRING_ERROR_PAGE_TITLE"));
    echo "<br />".GetMessage("RSB_ACQUIRING_ERROR_PAGE_MESS");
}

echo "<br /><br />".$arResult["MESSAGE"];

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");