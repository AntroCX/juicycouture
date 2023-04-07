<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

if (Bitrix\Main\Loader::includeModule("jamilco.omni")) {
    $arResult['TABLET'] = Jamilco\Omni\Tablet::getCurrentShopList();
}
?>