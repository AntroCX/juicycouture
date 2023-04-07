<?
namespace Jamilco\Main\Property;

use Bitrix\Main\Loader;

class Order
{
    public static function GetUserTypeDescription()
    {
        return array(
            'PROPERTY_TYPE'        => 'N',
            'USER_TYPE'            => 'JamilcoOrder',
            'DESCRIPTION'          => "Привязка к Заказу",
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetAdminListViewHTML' => [__CLASS__, 'GetAdminListViewHTML'],
        );
    }

    /**
     * значение свойства в списке товаров
     *
     * @param $arProperty
     * @param $arValue
     * @param $strHTMLControlName
     *
     * @return string
     */
    function GetAdminListViewHTML($arProperty, $arValue, $strHTMLControlName)
    {
        if (!$arValue['VALUE']) return '';

        $strReturn = '<a href="/bitrix/admin/sale_order_view.php?ID='.$arValue['VALUE'].'&lang=ru" target="_blank">'.$arValue['VALUE'].'</a>';

        return $strReturn;
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
    public static function GetPropertyFieldHtml($arProperty, $arValue, $strHTMLControlName)
    {

        $arAddValue = explode('+++', $arValue["DESCRIPTION"]);

        $strResult = "<input
            name='".htmlspecialcharsbx($strHTMLControlName["VALUE"])."'
            id='".htmlspecialcharsbx($strHTMLControlName["VALUE"])."'
            value='".htmlspecialcharsex($arValue["VALUE"])."'
            type='hidden'>";

        $strResult .= '<a href="/bitrix/admin/sale_order_view.php?ID='.$arValue['VALUE'].'&lang=ru" target="_blank">'.$arValue['VALUE'].'</a>';

        return $strResult;
    }
}