<?php
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Page\Asset;

EventManager::getInstance()->addEventHandler('iblock', 'OnIBlockPropertyBuildList', ['UfColorPicker', 'GetUserTypeDescription']);

class UfColorPicker
{
    public static function GetUserTypeDescription()
    {
        return array(
            'PROPERTY_TYPE'        => 'S',
            'USER_TYPE'            => 'HTML',
            'DESCRIPTION'          => "Цвет",
            'GetPropertyFieldHtml' => array(__CLASS__, 'GetPropertyFieldHtml'),
        );
    }

    function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        Asset::getInstance()->addJs("/local/blocks/i-jquery/jquery-2.2.4.min.js");
        Asset::getInstance()->addJs("/local/properties/ufColorPicker/js/colorpicker.js");
        Asset::getInstance()->addJs("/local/properties/ufColorPicker/js/colorpicker_init.js");

        $strResult = '# <input type="text" class="colorpickerField" name="'.$strHTMLControlName["VALUE"].'" value="'.$value["VALUE"].'" maxlength="6" >';
        $strResult .= '<link href="/local/properties/ufColorPicker/css/colorpicker.css" type="text/css" rel="stylesheet" />';

        return $strResult;
    }
}