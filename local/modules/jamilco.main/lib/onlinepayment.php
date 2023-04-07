<?php

namespace Jamilco\Main;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Order;

class OnlinePayment
{
    private static $canSetPaymentUsers = [ // могут ставить Оплату
        'dzhulfalakyan@jamilco.ru'
    ];

    /**
     * Проверим, что изменение оплаты соотв. критериям.
     *
     * @param $orderId
     *
     * @return bool
     */
    public static function checkOnlinePayment($orderId)
    {
        global $USER;
        $currentUserLogin = $USER->GetLogin();

        if(in_array($currentUserLogin, self::$canSetPaymentUsers)){
            return true;
        }

        if($currentUserLogin == 'api') return true; // скрипт ocs
        if(empty($currentUserLogin)) return true; // крон-скрипт

        Main\Loader::IncludeModule('sale');

        $order = Order::load($orderId);
        $userId = $order->getField('USER_ID');

        if($userId) {
            $arUser = \CUser::GetList(($by = 'id'), ($order = 'desc'), ['ID' => $userId])->fetch();
            if($arUser['LOGIN'] == $currentUserLogin){ // оплата клиентом
                return  true;
            }
        }

        return  false;
    }

    /**
     * Отмена оплаты
     *
     * @param $order
     */
    public static function cancelPayment($order){
        global $checkOnlinePayment;
        $checkOnlinePayment = true;

        Main\Loader::IncludeModule('sale');
        $collection = $order->getPaymentCollection();

        foreach ($collection as $payment)
        {
            $payment->setField('PAID', 'N');
            $order->save();
        }
        // добавляем данные в историю заказа
        $msg = 'Онлайн-оплата не может быть проставлена вручную и была отменена.';
        \Bitrix\Sale\OrderHistory::addAction(
            'ORDER',
            $order->getId(),
            'ORDER_COMMENTED',
            $order->getId(),
            $order,
            ['COMMENTS' => $msg]
        );
        $order->save();
        \CAdminMessage::ShowMessage($msg);

    }
}
