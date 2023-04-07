<?php

namespace Jamilco\Main\Property;

class Location
{
    public static function GetUserTypeDescription()
    {
        return array(
            'PROPERTY_TYPE'             => 'N',
            'USER_TYPE'                 => 'PropLocation',
            'DESCRIPTION'               => "Привязка к местоположению",
            'GetPropertyFieldHtml'      => array(
                __CLASS__,
                'GetPropertyFieldHtml'
            ),
            'GetPropertyFieldHtmlMulty' => array(
                __CLASS__,
                'GetPropertyFieldHtmlMulty'
            ),
            'GetAdminListViewHTML'      => array(
                __CLASS__,
                'GetAdminListViewHTML'
            ),
        );
    }

    public static function GetAdminListViewHTML($arProperty, $arValue, $strHTMLControlName)
    {
        $location = \CSaleLocation::GetByID($arValue["VALUE"]);

        $value = array_filter(array($location["COUNTRY_NAME"], $location["CITY_NAME"]));

        return implode(",", $value);
    }

    public static function GetPropertyFieldHtml($arProperty, $arValue, $strHTMLControlName)
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent(
            "bitrix:sale.location.selector.search",
            "",
            Array(
                "COMPONENT_TEMPLATE"     => ".default",
                "ID"                     => ($arValue['VALUE']) ? $arValue['VALUE'] : "",
                "CODE"                   => "",
                "INPUT_NAME"             => $strHTMLControlName['VALUE'],
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
        $strResult = ob_get_contents();
        ob_end_clean();

        return $strResult;

    }

    public static function GetPropertyFieldHtmlMulty($arProperty, $arValue, $strHTMLControlName)
    {
        global $APPLICATION;
        \CJSCore::Init(array("jquery"));
        ob_start();
        $i = 0;
        $strResult = '<style>.admin-location-property-container .bx-sls{margin-bottom: 10px;}</style>';
        $strResult .= '<div class="admin-location-property-container">';
        do {
            $current = array_slice($arValue, $i, 1);
            $controlName = $strHTMLControlName['VALUE'];
            $APPLICATION->IncludeComponent(
                "bitrix:sale.location.selector.search",
                "",
                Array(
                    "COMPONENT_TEMPLATE"     => ".default",
                    "ID"                     => ($current[0]['VALUE']) ? $current[0]['VALUE'] : "",
                    "CODE"                   => "",
                    "INPUT_NAME"             => $controlName.'[n'.$i.']',
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
            $i++;
        } while (count($arValue) > $i);
        $strResult .= ob_get_contents();
        $strResult .= '</div>';
        ob_end_clean();

        $dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__);

        \Bitrix\Main\Page\Asset::getInstance()->addJs($dir.'/location.js');

        $nextIndex = (count($arValue) < 1) ? 1 : count($arValue);
        $strResult .= ' <input type="button" value="Добавить" onclick="addLocationProperty(this)" data-control-name="'.$controlName.'" data-next-prop-index="'.$nextIndex.'">';

        return $strResult;
    }
}