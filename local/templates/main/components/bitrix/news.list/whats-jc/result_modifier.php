<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** DigitalDataLayer start */
foreach ($arResult['ITEMS'] as &$arItem) {
    $campaignId = (!empty(trim($arItem['NAME'])) ? \Cutil::translit($arItem['NAME'], 'ru', [
        'replace_space' => '-',
        'replace_other' => '-'
    ]) : 'main-slider-' . $arItem['ID']);
    $arItem['DDL_CAMPAIGN_ID'] = $campaignId;
    $arResult['DDL_CAMPAIGNS'][] = [
        'ID'   => $campaignId,
        'NAME' => $arItem['NAME'],
        'DESC' => $arItem['PREVIEW_TEXT']
    ];
}

$cp = $this->__component;
if (is_object($cp)) {
    $cp->SetResultCacheKeys(['DDL_CAMPAIGNS']);
}
/** DigitalDataLayer end */
