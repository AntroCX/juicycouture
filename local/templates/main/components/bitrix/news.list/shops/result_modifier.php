<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arCities = array();

foreach ($arResult['ITEMS'] as $arItem) {
	$arCities[] = $arItem['PROPERTIES']['CITY']['VALUE'];
}

$arResult['CITIES'] = array_unique($arCities);
