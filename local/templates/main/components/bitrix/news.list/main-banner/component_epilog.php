<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Jamilco\Blocks\Block::load(array('b-mainpage-banner'));

/** DigitalDataLayer start */
$digitalData = \DigitalDataLayer\Manager::getInstance()->getData();
if (isset($arResult['DDL_CAMPAIGNS'])) {
    foreach ($arResult['DDL_CAMPAIGNS'] as $key => $campaign) {
        $digitalData->add('campaigns', [
            'id' => $campaign['ID'],
            'name' => $campaign['NAME'],
            'description' => $campaign['DESC'] ?: '',
            'category' => 'Juicy Couture',
            'subcategory' => 'homepageslider',
            'design' => '',
            'position' => 'Главная страница. Элемент ' . ($key + 1) . ' в карусели'
        ]);
    }
}
/** DigitalDataLayer end */
