<?
use \Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;

Loader::includeModule('iblock');
Loader::includeModule('jamilco.main');

$of = \CIblockElement::GetList(
    ['ID' => 'ASC'],
    [
        'IBLOCK_ID'       => IBLOCK_SKU_ID,
        'PROPERTY_OCS_ID' => false,
    ],
    false,
    false,
    ['ID', 'NAME', 'ACTIVE']
);
while ($arOffer = $of->Fetch()) {
    $ocsId = \Jamilco\Main\Oracle::getInstance()->getOcsId($arOffer['NAME']);
    if ($ocsId) {
        \CIBlockElement::SetPropertyValuesEx($arOffer['ID'], IBLOCK_SKU_ID, ['OCS_ID' => $ocsId]);
        $arLog['SET']++;
    } else {
        $arLog['NOT_FOUND'][$arOffer['ACTIVE']][] = $arOffer['NAME'];
    }
}

ppr($arLog, 1, 1);