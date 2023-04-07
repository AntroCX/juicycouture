<?php require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$input_name = $request->getPost('input_name');

if (!empty($input_name)) {
    \Bitrix\Main\Loader::includeModule('search');

    $APPLICATION->IncludeComponent(
        "bitrix:sale.location.selector.search",
        "",
        Array(
            "COMPONENT_TEMPLATE"     => ".default",
            "ID"                     => "",
            "CODE"                   => "",
            "INPUT_NAME"             => $input_name,
            "PROVIDE_LINK_BY"        => "id",
            "JSCONTROL_GLOBAL_ID"    => "",
            "JS_CALLBACK"            => "",
            "SEARCH_BY_PRIMARY"      => "N",
            "EXCLUDE_SUBTREE"        => "",
            "FILTER_BY_SITE"         => "N",
            "SHOW_DEFAULT_LOCATIONS" => "Y",
            "CACHE_TYPE"             => "Y",
            "CACHE_TIME"             => "36000000",
            "FILTER_SITE_ID"         => "s1"
        )
    );
}


