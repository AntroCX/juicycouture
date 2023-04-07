<?
namespace Jamilco\Main;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Sale;

class CancelOrder
{
    const PROP_CODE_REASON = 'CANCEL_REASON';

    public static function onInit()
    {
        return [
            "BLOCKSET"        => "Jamilco\Main\CancelOrder",
            "check"           => ["Jamilco\Main\CancelOrder", "check"],
            "action"          => ["Jamilco\Main\CancelOrder", "action"],
            "getScripts"      => ["Jamilco\Main\CancelOrder", "getScripts"],
            "getBlocksBrief"  => ["Jamilco\Main\CancelOrder", "getBlocksBrief"],
            "getBlockContent" => ["Jamilco\Main\CancelOrder", "getBlockContent"],
        ];
    }

    public static function check($args) { }

    public static function action($args) { }

    // собственных блоков нет
    public static function getBlocksBrief($args) { }

    // собственных блоков нет
    public static function getBlockContent($blockCode, $selectedTab, $args) { }

    public static function getScripts($args)
    {
        global $APPLICATION;
        $APPLICATION->addHeadScript('/local/modules/jamilco.main/admin/cancel-order/script.js');
        $APPLICATION->SetAdditionalCSS('/local/modules/jamilco.main/admin/cancel-order/style.css');

        $arEnums = self::getReasonList();

        $order = $args['ORDER'];
        $orderId = !empty($order) ? $order->getId() : 0;
        if (!$orderId) return '';

        $order = Sale\Order::load($orderId);
        $arOrderData = $order->getFields()->getValues();
        if ($arOrderData['CANCELED'] == 'Y' && $arOrderData['REASON_CANCELED'] > '') {
            $arEnums[0] = [
                'NAME'  => $arOrderData['REASON_CANCELED'],
                'VALUE' => 0,
            ];
        }

        return '
        <script type="text/javascript">
            window.orderId = \''.$orderId.'\';
            window.isCanceled = \''.$arOrderData['CANCELED'].'\';
            window.cancelReasonIs = \''.$arOrderData['REASON_CANCELED'].'\';
            window.cancelReasons = '.Json::encode($arEnums).';
        </script>
        ';

    }

    /**
     * список причин для отмены заказа
     *
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    static public function getReasonList(&$propID = 0)
    {
        Loader::includeModule('sale');

        $arEnums = [0 => ['NAME' => '']]; // варианты отмены заказа
        $pr = \CSaleOrderProps::GetList([], ['CODE' => self::PROP_CODE_REASON]);
        if ($arProp = $pr->Fetch()) {
            $propID = $arProp['ID'];
            $en = \CSaleOrderPropsVariant::GetList(['ID' => 'ASC'], ['ORDER_PROPS_ID' => $arProp['ID']]);
            while ($arEnum = $en->Fetch()) {
                $arEnums[] = $arEnum;
            }
        }

        return $arEnums;
    }

}