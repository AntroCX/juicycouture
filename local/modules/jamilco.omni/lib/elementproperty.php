<?php
namespace Jamilco\Omni;

use \Bitrix\Main\Loader;
use \Bitrix\Iblock\ElementTable;

class ElementProperty extends \CUserTypeIBlockElement
{
    function GetUserTypeDescription()
    {
        return array(
            "USER_TYPE_ID" => "iblock_element_group",
            "CLASS_NAME"   => "Jamilco\\Omni\\ElementProperty",
            "DESCRIPTION"  => "Привязка к элементам инф. блоков (группы)",
            "BASE_TYPE"    => "int",
        );
    }

    function getElementList($arUserField)
    {
        $arElements = array();
        if (Loader::includeModule('iblock')) {
            $arFilter = Array("IBLOCK_ID" => $arUserField["SETTINGS"]["IBLOCK_ID"]);
            if ($arUserField["SETTINGS"]["ACTIVE_FILTER"] === "Y") {
                $arFilter["ACTIVE"] = "Y";
            }

            $arSections = array();
            $rsSection = \CIblockSection::GetList(
                array('SORT' => 'ASC'),
                $arFilter
            );
            while ($arSection = $rsSection->Fetch()) {
                $arSections[$arSection['ID']] = $arSection['NAME'];
            }

            $rsElement = \CIblockElement::GetList(
                array('IBLOCK_SECTION_ID' => 'ASC'),
                $arFilter,
                false,
                false,
                array('ID', 'IBLOCK_SECTION_ID', 'NAME', 'PROPERTY_ADDRESS')
            );
            while ($arElement = $rsElement->Fetch()) {
                $arElement['GROUP'] = $arSections[$arElement['IBLOCK_SECTION_ID']];
                if ($arElement['PROPERTY_ADDRESS_VALUE'] > '') $arElement['NAME'] .= ' ('.$arElement['PROPERTY_ADDRESS_VALUE'].')';
                $arElement['VALUE'] = $arElement['NAME'];
                $arElements[] = $arElement;
            }
        }

        return $arElements;
    }

    function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        if (($arUserField["ENTITY_VALUE_ID"] < 1) && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"]) > 0) {
            $arHtmlControl["VALUE"] = intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
        }

        $result = '';
        $arEnums = call_user_func_array(
            array($arUserField["USER_TYPE"]["CLASS_NAME"], "getElementList"),
            array(
                $arUserField,
            )
        );
        if (!$arEnums) {
            return '';
        }

        if ($arUserField["SETTINGS"]["DISPLAY"] == "CHECKBOX") {
            $bWasSelect = false;
            $result2 = '';
            foreach ($arEnums as $arEnum) {
                $bSelected = (
                    ($arHtmlControl["VALUE"] == $arEnum["ID"]) ||
                    ($arUserField["ENTITY_VALUE_ID"] <= 0 && $arEnum["DEF"] == "Y")
                );
                $bWasSelect = $bWasSelect || $bSelected;
                $result2 .= '<label><input type="radio" value="'.$arEnum["ID"].'" name="'.$arHtmlControl["NAME"].'"'.($bSelected ? ' checked' : '').($arUserField["EDIT_IN_LIST"] != "Y" ? ' disabled="disabled" ' : '').'>'.$arEnum["VALUE"].'</label><br>';
            }
            if ($arUserField["MANDATORY"] != "Y") {
                $result .= '<label><input type="radio" value="" name="'.$arHtmlControl["NAME"].'"'.(!$bWasSelect ? ' checked' : '').($arUserField["EDIT_IN_LIST"] != "Y" ? ' disabled="disabled" ' : '').'>'.htmlspecialcharsbx(
                        strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')
                    ).'</label><br>';
            }
            $result .= $result2;
        } else {
            $bWasSelect = false;
            $result2 = '';
            $group = '';
            foreach ($arEnums as $arEnum) {
                if ($arEnum['GROUP'] > '' && $arEnum['GROUP'] != $group) {
                    if ($group) $result2 .= '</optgroup>';
                    $group = $arEnum['GROUP'];
                    $result2 .= '<optgroup label="'.$group.'">';
                }
                $bSelected = (
                    ($arHtmlControl["VALUE"] == $arEnum["ID"]) ||
                    ($arUserField["ENTITY_VALUE_ID"] <= 0 && $arEnum["DEF"] == "Y")
                );
                $bWasSelect = $bWasSelect || $bSelected;
                $result2 .= '<option value="'.$arEnum["ID"].'"'.($bSelected ? ' selected' : '').'>'.$arEnum['ID'].'. '.$arEnum["VALUE"].'</option>';
            }
            if ($group) $result2 .= '</optgroup>';

            if ($arUserField["SETTINGS"]["LIST_HEIGHT"] > 1) {
                $size = ' size="'.$arUserField["SETTINGS"]["LIST_HEIGHT"].'"';
            } else {
                $arHtmlControl["VALIGN"] = "middle";
                $size = '';
            }

            $result = '<select name="'.$arHtmlControl["NAME"].'"'.$size.($arUserField["EDIT_IN_LIST"] != "Y" ? ' disabled="disabled" ' : '').'>';
            if ($arUserField["MANDATORY"] != "Y") {
                $result .= '<option value=""'.(!$bWasSelect ? ' selected' : '').'>'.htmlspecialcharsbx(
                        strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')
                    ).'</option>';
            }
            $result .= $result2;
            $result .= '</select>';
        }

        return $result;
    }

    function GetAdminListViewHTML($arUserField, $arHtmlControl)
    {
        static $cache = array();
        $empty_caption = '&nbsp;';

        if (!array_key_exists($arHtmlControl["VALUE"], $cache)) {
            $arEnums = call_user_func_array(
                array($arUserField["USER_TYPE"]["CLASS_NAME"], "getElementList"),
                array(
                    $arUserField,
                )
            );
            if (!$arEnums) return $empty_caption;
            foreach ($arEnums as $arEnum) {
                $cache[$arEnum["ID"]] = $arEnum["VALUE"];
            }
        }
        if (!array_key_exists($arHtmlControl["VALUE"], $cache)) {
            $cache[$arHtmlControl["VALUE"]] = $empty_caption;
        }

        return $cache[$arHtmlControl["VALUE"]];
    }

    function GetAdminListEditHTML($arUserField, $arHtmlControl)
    {
        $arEnums = call_user_func_array(
            array($arUserField["USER_TYPE"]["CLASS_NAME"], "getElementList"),
            array(
                $arUserField,
            )
        );
        if (!$arEnums) {
            return '';
        }

        if ($arUserField["SETTINGS"]["LIST_HEIGHT"] > 1) {
            $size = ' size="'.$arUserField["SETTINGS"]["LIST_HEIGHT"].'"';
        } else {
            $size = '';
        }

        $result = '<select name="'.$arHtmlControl["NAME"].'"'.$size.($arUserField["EDIT_IN_LIST"] != "Y" ? ' disabled="disabled" ' : '').'>';
        if ($arUserField["MANDATORY"] != "Y") {
            $result .= '<option value=""'.(!$arHtmlControl["VALUE"] ? ' selected' : '').'>'.htmlspecialcharsbx(
                    strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')
                ).'</option>';
        }
        $group = '';
        foreach ($arEnums as $arEnum) {
            if ($arEnum['GROUP'] > '' && $arEnum['GROUP'] != $group) {
                if ($group) $result .= '</optgroup>';
                $group = $arEnum['GROUP'];
                $result .= '<optgroup label="'.$group.'">';
            }
            $result .= '<option value="'.$arEnum["ID"].'"'.($arHtmlControl["VALUE"] == $arEnum["ID"] ? ' selected' : '').'>'.$arEnum["VALUE"].'</option>';
        }
        if ($group) $result .= '</optgroup>';
        $result .= '</select>';

        return $result;
    }
}

?>