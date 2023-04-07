<?php
namespace Jamilco\Omni;

class ElementIblockProperty
{
    public static function GetUserTypeDescription()
    {
        return array(
            'PROPERTY_TYPE'        => 'E',
            'USER_TYPE'            => 'JmlShopElements',
            'DESCRIPTION'          => "Привяка к элементам (магазин)",
            'GetPropertyFieldHtml' => array(__CLASS__, 'GetPropertyFieldHtml'),
        );
    }

    /**
     * одиночное значение свойства
     *
     * @param $arProperty
     * @param $arValue
     * @param $strHTMLControlName
     *
     * @return string
     */
    function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $settings = \CIBlockPropertyElementList::PrepareSettings($arProperty);
        if ($settings["size"] > 1) {
            $size = ' size="'.$settings["size"].'"';
        } else {
            $size = '';
        }

        if ($settings["width"] > 0) {
            $width = ' style="width:'.$settings["width"].'px"';
        } else {
            $width = '';
        }

        $bWasSelect = false;
        $arElements = self::GetOptionsHtml($arProperty, array($value["VALUE"]));

        $group = '';
        $html = '<select name="'.$strHTMLControlName["VALUE"].'"'.$size.$width.'>';
        foreach ($arElements as $arElement) {
            $html .= '';
            if ($arElement['GROUP'] > '' && $arElement['GROUP'] != $group) {
                if ($group) $html .= '</optgroup>';
                $group = $arElement['GROUP'];
                $html .= '<optgroup label="'.$group.'">';
            }
            $bSelected = ($value["VALUE"] == $arElement["ID"]);
            $bWasSelect = $bWasSelect || $bSelected;
            $html .= '<option value="'.$arElement["ID"].'"'.($bSelected ? ' selected' : '').'>'.$arElement['ID'].'. '.$arElement["VALUE"].'</option>';
        }
        if ($group) $html .= '</optgroup>';
        $html .= '</select>';

        return $html;
    }

    function GetOptionsHtml($arProperty, $values)
    {
        $arElements = [];
        $arFilter = Array("IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"]);

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


        return $arElements;
    }
}

?>