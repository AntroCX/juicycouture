<?
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

use \Bitrix\Main\Web\Json;

global $APPLICATION;

$arElements = $APPLICATION->IncludeComponent(
    "bitrix:search.page",
    "main",
    Array(
        "RESTART"                                    => 'Y',
        "NO_WORD_LOGIC"                              => 'Y',
        "USE_LANGUAGE_GUESS"                         => 'N',
        "CHECK_DATES"                                => 'N',
        "arrFILTER"                                  => array("iblock_catalog"),
        "arrFILTER_iblock_".$arParams["IBLOCK_TYPE"] => array(IBLOCK_CATALOG_ID),
        "USE_TITLE_RANK"                             => "N",
        "DEFAULT_SORT"                               => "rank",
        "FILTER_NAME"                                => "",
        "SHOW_WHERE"                                 => "N",
        "arrWHERE"                                   => array(),
        "SHOW_WHEN"                                  => "N",
        "PAGE_RESULT_COUNT"                          => 20,
        "DISPLAY_TOP_PAGER"                          => "N",
        "DISPLAY_BOTTOM_PAGER"                       => "N",
        "PAGER_TITLE"                                => "",
        "PAGER_SHOW_ALWAYS"                          => "N",
        "PAGER_TEMPLATE"                             => "N",
    ),
    '',
    array('HIDE_ICONS' => 'Y')
);

$arData = [];
if (!empty($arElements) && is_array($arElements)) {
    global $searchFilter;

    \CModule::IncludeModule('iblock');
    $rsElements = \CIBlockElement::GetList(
        array(),
        array(
            'IBLOCK_ID' => IBLOCK_CATALOG_ID,
            'ID'        => array_values($arElements),
            'ACTIVE'    => 'Y'
        ),
        false,
        ['nTopCount' => count($arElements)],
        array(
            'ID',
            'IBLOCK_ID',
        )
    );
    $arIDs = array();
    while ($arrElements = $rsElements->Fetch()) {
        $arIDs[] = $arrElements['ID'];
    }

    $searchFilter = array(
        'IBLOCK_ID'     => IBLOCK_CATALOG_ID,
        'ACTIVE'        => 'Y',
        'GLOBAL_ACTIVE' => 'Y',
        'ID'            => $arIDs,
    );

    \Jamilco\Main\Retail::getItemFilter($searchFilter, false);
    $searchFilter['PROPERTY_HIDE'] = false;
    //ppr($searchFilter);

    $el = \CIBlockElement::GetList(
        [],
        $searchFilter,
        false,
        ['nTopCount' => count($searchFilter['ID'])],
        ['ID', 'NAME', 'PROPERTY_ARTNUMBER', 'DETAIL_PAGE_URL']
    );
    while ($arItem = $el->GetNext()) {
        $arData[] = [
            'ID'      => $arItem['ID'],
            'NAME'    => $arItem['NAME'],
            'ARTICLE' => $arItem['PROPERTY_ARTNUMBER_VALUE'],
            'LINK'    => $arItem['DETAIL_PAGE_URL'],
            'PRICES'  => [
                'BASE' => '',
                'SALE' => '',
            ]
        ];
    }
}

echo Json::encode($arData);
