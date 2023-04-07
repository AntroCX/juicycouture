<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;

/** @var array $templateData */
/** @var @global CMain $APPLICATION */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
  die();
}

global $APPLICATION;

if (isset($templateData['TEMPLATE_LIBRARY']) && !empty($templateData['TEMPLATE_LIBRARY']))
{
	$loadCurrency = false;
	if (!empty($templateData['CURRENCIES']))
		$loadCurrency = Loader::includeModule('currency');
	CJSCore::Init($templateData['TEMPLATE_LIBRARY']);
	if ($loadCurrency)
	{
	?>
	<script type="text/javascript">
		BX.Currency.setCurrencies(<? echo $templateData['CURRENCIES']; ?>);
	</script>
<?
	}
}

Jamilco\Blocks\Block::load(array('b-catalog', 'b-filter', 'b-pagination'));

/** DigitalDataLayer start */
$ddm = \DigitalDataLayer\Manager::getInstance();

$request = Context::getCurrent()->getRequest();
$isAjax = $request->getQuery('ajax');
$sort = $request->getQuery('sort');
$queryString = explode('?', $request->getRequestUri())[1];

$arSortNames = [
    'new'        => 'новые поступления',
    'price-desc' => 'по уменьшению цены',
    'price-asc'  => 'по возрастанию цены',
    'name-asc'   => 'по названию (а-я)',
    'name-desc'  => 'по названию (я-а)',
];

if ($sort && array_key_exists($sort, $arSortNames)) {
    $arResult['DDL_SECTION_PROPERTIES']['sortBy'] = $arSortNames[$sort];
}

/** На странице с поисковыми результатами и спец разделами типа 'Sale'
 * нет параметров category и categoryId в объекте listing
 */
if ($APPLICATION->getProperty('ddlPageType') == 'search' || $APPLICATION->getProperty('ddlPageSubType') == 'special') {
    $arResult['DDL_SECTION_PROPERTIES']['category'] = [$APPLICATION->getProperty('title')];
    unset($arResult['DDL_SECTION_PROPERTIES']['categoryId']);
}

/** Изменение параметров, если компонент был включен в страницу поиска */
if ($APPLICATION->getProperty('ddlPageType') == 'search') {
    /** listName должен быть search */
    $arResult['DDL_SECTION_PROPERTIES']['listName'] = 'search';
}

if ($isAjax == 'Y') {
    // По переходу постранично и по фильтрации изменяется информация о продуктах в списке, нужно добавлять событие
    $ddm->addContent(
        'viewedListPageEvent',
        '<script>if (typeof window.digitalData.events !== \'undefined\') {digitalData.events.push({"name":"Viewed Page","source":"code"})}</script>'
    );

    $ddm->addContent(
        'viewedListPageChanges',
        "<script>if (typeof window.digitalData.changes !== 'undefined') {
            window.digitalData.changes.push(['listing', " . Json::encode($arResult['DDL_SECTION_PROPERTIES'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "]);
        }</script>"
    );

    // Нужно изменять также объект page
    if ($queryString) {
        $ddm->addContent(
            'viewedPageChanges',
            "<script>if (typeof window.digitalData.changes !== 'undefined') {
                window.digitalData.changes.push(['page.queryString', '?" . $queryString . "']);
            }</script>"
        );
    }

    echo $ddm->getContent(['viewedListPageChanges', 'viewedPageChanges']);
    echo $ddm->getContent(['viewedListPageEvent']); // нужно чтобы событие было последним
}

$digitalData = $ddm->getData();

$digitalData->listing = $arResult['DDL_SECTION_PROPERTIES'] ?: [];
/** DigitalDataLayer end */
