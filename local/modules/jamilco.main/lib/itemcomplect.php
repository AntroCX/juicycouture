<?
namespace Jamilco\Main;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Iblock;

class ItemComplect
{

    const USER_TYPE = 'EItemCheckbox';

    public static function GetUserTypeDescription()
    {
        return [
            "PROPERTY_TYPE"             => Iblock\PropertyTable::TYPE_ELEMENT,
            "USER_TYPE"                 => self::USER_TYPE,
            'DESCRIPTION'               => "Привязка к элементам + чекбокс",
            'GetPropertyFieldHtml'      => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetPropertyFieldHtmlMulty' => [__CLASS__, 'GetPropertyFieldHtmlMulty'],
            "ConvertToDB"               => [__CLASS__, "ConvertToDB"],
            "ConvertFromDB"             => [__CLASS__, "ConvertFromDB"],
            'GetSettingsHTML'           => [__CLASS__, 'GetSettingsHTML'],
            'GetAdminListViewHTML'      => [__CLASS__, 'GetAdminListViewHTML'],
        ];
    }

    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        static $cache = [];
        if (strlen($value["VALUE"]) > 0) {
            if (!isset($cache[$value["VALUE"]])) {
                $db_res = \CIBlockElement::GetList(
                    [],
                    ["=ID" => $value["VALUE"], "SHOW_HISTORY" => "Y"],
                    false,
                    false,
                    ["ID", "IBLOCK_TYPE_ID", "IBLOCK_ID", "NAME", "DETAIL_PAGE_URL"]
                );
                $ar_res = $db_res->GetNext();
                if ($ar_res) {
                    $cache[$value["VALUE"]] = $ar_res;
                } else {
                    $cache[$value["VALUE"]] = $value["VALUE"];
                }
            }

            if (isset($strHTMLControlName['MODE']) && ($strHTMLControlName["MODE"] == "SIMPLE_TEXT" || $strHTMLControlName["MODE"] == 'ELEMENT_TEMPLATE')) {
                if (is_array($cache[$value["VALUE"]])) {
                    return $cache[$value["VALUE"]]["~NAME"];
                } else {
                    return $cache[$value["VALUE"]];
                }
            } else {
                if (is_array($cache[$value["VALUE"]])) {
                    return '<a href="'.$cache[$value["VALUE"]]["DETAIL_PAGE_URL"].'">'.$cache[$value["VALUE"]]["NAME"].'</a>';
                } else {
                    return htmlspecialcharsex($cache[$value["VALUE"]]);
                }
            }
        } else {
            return '';
        }
    }

    public static function GetPropertyFieldHtmlMulty($arProperty, $value, $strHTMLControlName)
    {
        $max_n = 0;
        $values = [];
        if (is_array($value)) {
            foreach ($value as $property_value_id => $arValue) {
                $values[$property_value_id] = $arValue;

                if (preg_match("/^n(\\d+)$/", $property_value_id, $match)) {
                    if ($match[1] > $max_n) {
                        $max_n = intval($match[1]);
                    }
                }
            }
        }

        if (end($values) != "" || substr(key($values), 0, 1) != "n") $values["n".($max_n + 1)] = ['VALUE' => '', 'DESCRIPTION' => ''];

        $name = $strHTMLControlName["VALUE"]."VALUE";

        $IBLOCK_ID = $arProperty['LINK_IBLOCK_ID'];
        $fixIBlock = true;
        $windowTableId = 'iblockprop-'.Iblock\PropertyTable::TYPE_ELEMENT.'-'.$arProperty['ID'].'-'.$IBLOCK_ID;

        $html = '<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tb'.md5($name).'">';
        foreach ($values as $property_value_id => $value) {
            $html .= '<tr><td>';

            if ($value['VALUE'] > 0) {
                $db_res = \CIBlockElement::GetList(
                    [],
                    ["=ID" => $value['VALUE'], "SHOW_HISTORY" => "Y"],
                    false,
                    false,
                    ["ID", "IBLOCK_ID", "NAME"]
                );
                $ar_res = $db_res->Fetch();
            } else {
                $ar_res = [];
            }

            $inputName = $strHTMLControlName["VALUE"].'['.$property_value_id.'][VALUE]';
            $inputDescription = $strHTMLControlName["VALUE"].'['.$property_value_id.'][DESCRIPTION]';

            $windowUrl = '/bitrix/admin/iblock_element_search.php?lang='.LANGUAGE_ID.
                '&n='.$inputName.'&a=b'.($fixIBlock ? '&iblockfix=y' : '').'&tableId='.$windowTableId;
            $windowUrl .= '&IBLOCK_ID='.$IBLOCK_ID;

            $html .= '<input size="10" type="text"
                    name="'.htmlspecialcharsbx($inputName).'"
                    id="'.htmlspecialcharsbx($inputName).'"
                    value="'.htmlspecialcharsEx($value["VALUE"]).'"
                >'.
                '<input type="button" value="..." onClick="jsUtils.OpenWindow(\''.\CUtil::JSEscape($windowUrl).'\', 900, 700);">'.
                '&nbsp;<span id="sp_'.htmlspecialcharsbx($inputName).'" >'.$ar_res['NAME'].'</span><br />'.
                '<input type="checkbox"
                id="check_'.htmlspecialcharsbx($inputDescription).'"
                name="'.htmlspecialcharsbx($inputDescription).'" value="Y"
                '.($value['DESCRIPTION'] == 'Y' ? 'checked="checked"' : '').'
            ><label for="check_'.htmlspecialcharsbx($inputDescription).'">Показать комплект в карточке товара</label><br /><br />';

            $html .= '</td></tr>';
        }
        $html .= '</table>';

        $nameMD5 = md5($name);
        $html .= '<input type="button" value="Добавить" onClick="if(window.addNewRow){addNewRow(\'tb'.$nameMD5.'\', -1)}else{addNewTableRow(\'tb'.$nameMD5.'\', 1, /\[(n)([0-9]*)\]/g, 2)}">';


        return $html;
    }

    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        static $cache = [];
        if (strlen($value["VALUE"]) > 0) {
            if (!array_key_exists($value["VALUE"], $cache)) {
                $db_res = \CIBlockElement::GetList(
                    [],
                    ["=ID" => $value["VALUE"], "SHOW_HISTORY" => "Y"],
                    false,
                    false,
                    ["ID", "IBLOCK_TYPE_ID", "IBLOCK_ID", "NAME"]
                );
                $ar_res = $db_res->GetNext();
                if ($ar_res) {
                    $cache[$value["VALUE"]] = htmlspecialcharsbx($ar_res['NAME']).
                        ' [<a href="'.
                        '/bitrix/admin/iblock_element_edit.php?'.
                        'type='.urlencode($ar_res['IBLOCK_TYPE_ID']).
                        '&amp;IBLOCK_ID='.$ar_res['IBLOCK_ID'].
                        '&amp;ID='.$ar_res['ID'].
                        '&amp;lang='.LANGUAGE_ID.
                        '" title="Изменить">'.$ar_res['ID'].'</a>]';
                } else {
                    $cache[$value["VALUE"]] = htmlspecialcharsbx($value["VALUE"]);
                }
            }

            return $cache[$value["VALUE"]];
        } else {
            return '&nbsp;';
        }
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $ar_res = false;
        if (strlen($value["VALUE"])) {
            $db_res = \CIBlockElement::GetList(
                [],
                ["=ID" => $value["VALUE"], "SHOW_HISTORY" => "Y"],
                false,
                false,
                ["ID", "IBLOCK_ID", "NAME"]
            );
            $ar_res = $db_res->GetNext();
        }

        if (!$ar_res) $ar_res = ["NAME" => ""];

        $IBLOCK_ID = $arProperty['LINK_IBLOCK_ID'];
        $fixIBlock = true;
        $windowTableId = 'iblockprop-'.Iblock\PropertyTable::TYPE_ELEMENT.'-'.$arProperty['ID'].'-'.$IBLOCK_ID;

        $windowUrl = '/bitrix/admin/iblock_element_search.php?lang='.LANGUAGE_ID.
            '&n='.$strHTMLControlName["VALUE"].'&a=b'.($fixIBlock ? '&iblockfix=y' : '').'&tableId='.$windowTableId;
        $windowUrl .= '&IBLOCK_ID='.$IBLOCK_ID;

        return '<input size="10" type="text"
                    name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'"
                    id="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'"
                    value="'.htmlspecialcharsEx($value["VALUE"]).'"
                >'.
        '<input type="button" value="..." onClick="jsUtils.OpenWindow(\''.\CUtil::JSEscape($windowUrl).'\', 900, 700);">'.
        '&nbsp;<span id="sp_'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'" >'.$ar_res['NAME'].'</span><br />'.
        '<input type="checkbox"
                id="check_'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'"
                name="'.htmlspecialcharsbx($strHTMLControlName["DESCRIPTION"]).'" value="Y"
                '.($value['DESCRIPTION'] == 'Y' ? 'checked="checked"' : '').'
            ><label for="check_'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'">Показать комплект в карточке товара</label>';
    }

    public static function ConvertToDB($arProperty, $value)
    {
        $result = [];
        $return = [];
        if (is_array($value["VALUE"])) {
            $result["VALUE"] = $value["VALUE"]["VALUE"];
            $result["DESCRIPTION"] = $value["DESCRIPTION"]["VALUE"];
        } else {
            $result["VALUE"] = $value["VALUE"];
            $result["DESCRIPTION"] = $value["DESCRIPTION"];
        }
        $return["VALUE"] = trim($result["VALUE"]);
        $return["DESCRIPTION"] = trim($result["DESCRIPTION"]);

        return $return;
    }

    public static function ConvertFromDB($arProperty, $value)
    {
        $return = [];
        if (strLen(trim($value["VALUE"])) > 0) {
            $return["VALUE"] = $value["VALUE"];
        }
        if (strLen(trim($value["DESCRIPTION"])) > 0) {
            $return["DESCRIPTION"] = $value["DESCRIPTION"];
        }

        return $return;
    }

    public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
    {
        $arPropertyFields = [
            "HIDE" => ["MULTIPLE_CNT"],
        ];

        return '';
    }
}