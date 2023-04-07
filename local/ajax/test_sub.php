<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;

Loader::includeModule("iblock");
Loader::includeModule("sale");
Loader::includeModule("catalog");


$allEmails = array();
$sql = "SELECT b_subscription.EMAIL as mail , b_subscription.DATE_INSERT as date_register   FROM b_subscription
UNION
SELECT b_catalog_subscribe.USER_CONTACT as mail, b_catalog_subscribe.DATE_FROM as date_register  FROM b_catalog_subscribe
UNION
SELECT b_user.EMAIL as mail, b_user.DATE_REGISTER as date_register FROM b_user";


$arEmails = [];
$arEmailsDate = [];
$dbRes = $DB->Query($sql);

while ($arRes = $dbRes->Fetch()) {
    //pr($arRes);
    //die;
    $arEmails[$arRes['mail']] = $arRes['mail'];
    $arEmailsDate[$arRes['mail']] = $arRes['date_register'];
}
//pr($arEmailsDate);
//pr(count($arEmails));
// Получить всех подписчиков из 
$el = new CIBlockElement;

$count = 0;

$arFilter = Array(
    "IBLOCK_ID"=>SUBSCRIBE_IBLOCK,
    "NAME" => $arEmails);
    $res = CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false,false,array('NAME','ID','DATE_CREATE'));
    while($ar_fields = $res->GetNext())
    {
        //pr($ar_fields);
        //pr($arEmailsDate[$ar_fields['NAME']]);
        //pr($DB->FormatDate($arEmailsDate[$ar_fields['NAME']], 'YYYY-DD-MM HH:MI:SS', 'DD.MM.YYYY HH:MI:SS' ));
        //pr($arEmailsDate[$ar_fields['NAME']]);

        $arLoadProductArray = Array(
            "DATE_CREATE"    => $DB->FormatDate($arEmailsDate[$ar_fields['NAME']], 'YYYY-MM-DD HH:MI:SS', 'DD.MM.YYYY HH:MI:SS' )
            );
          

        $ress = $el->Update($ar_fields['ID'], $arLoadProductArray);

        //die();

        $count++;

    }

    pr($count);