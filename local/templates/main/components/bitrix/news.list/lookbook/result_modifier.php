<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

foreach ($arResult['ITEMS'] as $id => $arItem) {
    $arResult['ITEMS'][$id]['RESIZE_PICTURE'] = \CFile::ResizeImageGet($arItem['PREVIEW_PICTURE'], array('width' => '380', 'height' => '495'));
}

