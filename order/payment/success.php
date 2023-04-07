<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

global $MESS;
include(GetLangFileName(dirname(__FILE__)."/", "/success.php"));

$APPLICATION->SetTitle(GetMessage("RSB_ACQUIRING_DEFAULT_PAGE_TITLE"));

CModule::IncludeModule("rsb.acquiring");
$success = new \Rsb\Acquiring\Success($_POST);

if($success->bInstallError()) {
    echo "Ошибка. Перустановите модуль";
    return;
}


$success->setDebug("11");
$arResult = $success->action();
echo "<div style = 'text-align: center; font-size: 19px;'>";
if($arResult["RESULT"]["RESULT_CODE"] == "000") {
    $APPLICATION->SetTitle("Заказ успешно оплачен");
    echo "<br />"."Заказ успешно оплачен";
}
elseif($arResult["RESULT"]["RESULT_CODE"] == "100") {
    $APPLICATION->SetTitle("Ошибка оплаты");
    echo "<br />"."Пожалуйста, свяжитесь с Банком-Эмитентом Вашей карты";
}
elseif($arResult["RESULT"]["RESULT_CODE"] == "101") {
    $APPLICATION->SetTitle("Ошибка оплаты");
    echo "<br />"."Срок действия карты истек";
}
elseif($arResult["RESULT"]["RESULT_CODE"] == "116") {
    $APPLICATION->SetTitle("Ошибка оплаты");
    echo "<br />"."Недостаточно средств на карте";
}
else {
    $APPLICATION->SetTitle("Ошибка оплаты");
    echo "<br />"."При оплате заказа произошла ошибка, попробуйте еще раз<br>при повторе ошибки свяжитесь с Банком-Эмитентом Вашей карты";
}
echo "</div>";
//echo "<br /><br /> ->".$arResult["MESSAGE"];

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");