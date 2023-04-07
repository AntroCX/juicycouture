<?
namespace Jamilco\Main\Property;

use \Bitrix\Main\Loader;
use \Bitrix\Sale\Internals\BasketTable;

class Changebasketprice
{
    public static function GetUserTypeDescription()
    {
        return [
            'PROPERTY_TYPE'             => 'S',
            'USER_TYPE'                 => 'JamilcoChangeBasketPrice',
            'DESCRIPTION'               => "Изменение цены товара в заказе",
            'GetPropertyFieldHtmlMulty' => [__CLASS__, 'GetPropertyFieldHtmlMulty'],
        ];
    }

    /**
     * множественное значение свойства
     *
     * @param $arProperty
     * @param $arValue
     * @param $strHTMLControlName
     *
     * @return string
     */
    public static function GetPropertyFieldHtmlMulty($arProperty, $arValue, $strHTMLControlName)
    {
        Loader::includeModule('sale');

        $tableId = 'property_table_'.$arProperty['ID'];

        $strResult = '';

        $strResult .= '
            <table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="'.$tableId.'">
                <tbody>';

        // сохраненные значения
        foreach ($arValue as $key => $arOne) {

            $arBasket = BasketTable::GetByID($arOne['VALUE'])->Fetch();
            $arPrices = explode(':', $arOne['DESCRIPTION']);

            $strResult .= '
            <tr><td>
                <input name="'.$strHTMLControlName["VALUE"].'['.$key.'][VALUE]" value="'.$arOne['VALUE'].'" type="hidden">
                <input name="'.$strHTMLControlName["VALUE"].'['.$key.'][DESCRIPTION]" value="'.$arOne['DESCRIPTION'].'" type="hidden">

                <a href="bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.IBLOCK_SKU_ID.'&type=offers&ID='.$arBasket['PRODUCT_ID'].'&lang=ru&find_section_section=0&WF=Y" target="_blank">'.$arBasket['NAME'].'</a>,
                цена: '.(float)$arPrices[0].' -> '.(float)$arPrices[1].'
            </td></tr>';
        }

        $strResult .= '
                </tbody>
            </table>';

        return $strResult;
    }
}