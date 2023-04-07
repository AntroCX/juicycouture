<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arExt = [];
if ($arParams['POPUP']) {
    $arExt[] = 'window';
}
CUtil::InitJSCore($arExt);
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/socialservices/ss.js');
