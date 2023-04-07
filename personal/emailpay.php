<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Оплата");
?><?$APPLICATION->IncludeComponent("jamilco:emailpay.order.payment", "", array(
    "AUTH_PAGE" => "/personal/",
    "SITE_NAME" => "Juicycouture.ru"
),
    false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>