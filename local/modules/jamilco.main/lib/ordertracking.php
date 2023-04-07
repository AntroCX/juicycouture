<?

namespace Jamilco\Main;

use \Bitrix\Main\Loader;
use \Bitrix\Sale;

class OrderTracking
{
    public static $tkData = [
        'SDEK'      => [
            'NAME'     => 'СДЭК',
            'URL'      => 'https://www.cdek.ru/tracking?orderNumber=',
            'LOGO_IMG' => '/upload/tk/sdek/sdek.png'
        ],
        'CSE'       => [
            'NAME'     => 'КСЕ',
            'URL'      => 'https://www.cse.ru/track/?numbers=',
            'LOGO_IMG' => '/upload/tk/cse/cse.png'
        ],
        'DPD'       => [
            'NAME'     => 'DPD',
            'URL'      => 'https://www.dpd.ru/ols/trace2/standard.do2?orderNum=',
            'LOGO_IMG' => '/upload/tk/dpd/dpd.png'
        ],
        'PICKPOINT' => [
            'NAME'     => 'PickPoint',
            'URL'      => 'https://pickpoint.ru/monitoring/?shop=2074378382_923&order_number=',
            'LOGO_IMG' => '/upload/tk/pickpoint/pickpoint.png'
        ],
        'REDEXP'    => [
            'NAME'     => 'REDEXPRESS',
            'URL'      => 'http://redexpress.ru/ru/bonus/CargoInfo/?invoiceNumbers=',
            'LOGO_IMG' => '/upload/tk/redexp/redexp.png'
        ]
    ];

    public static function sendTrackNumber($orderId = 0)
    {
        if (!$orderId) return;
        Loader::includeModule('sale');

        $order = Sale\Order::load($orderId);

        $propertyCollection = $order->getPropertyCollection();
        $orderUser = $propertyCollection->getProfileName()->getValue();
        $userEmail = $propertyCollection->getUserEmail()->getValue();
        $tkName = $propertyCollection->getItemByOrderPropertyId(ORDER_PROP_TK_NAME)->getValue();
        $tkNum = $propertyCollection->getItemByOrderPropertyId(ORDER_PROP_TK_NUM)->getValue();

        if (!$tkName || !$tkNum) return;

        if (!self::$tkData[$tkName]) return;

        $arFields = [
            'SALE_EMAIL'     => \COption::GetOptionString('sale', 'order_email', ''),
            'ORDER_ID'       => $orderId,
            'ORDER_USER'     => $orderUser,
            'EMAIL'          => $userEmail,
            'TK_NAME'        => self::$tkData[$tkName]['NAME'],
            'TK_NUM'         => $tkNum,
            'TK_URL'         => self::$tkData[$tkName]['URL'].$tkNum,
            'TK_LOGO_IMG'    => 'https://' . $_SERVER['SERVER_NAME'] . self::$tkData[$tkName]['LOGO_IMG'],
            'PAYMENT_STATUS' => $order->isPaid() ? 'заказ оплачен' : 'оплата курьеру'
        ];

        \CEvent::Send('OCS_AT_DELIVERY_SERVICE', 's1', $arFields);
        \CEvent::ExecuteEvents();
    }
}