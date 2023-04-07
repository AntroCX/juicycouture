<?php

/**
 * Обработчик проверяет все заказы за дату которые оплачиваются через Сбербанк и находятся в статусах:
 * N - Новый
 * EP - Ожидает оплаты.
 * При подтверждении устанавливает признак оплаты у заказа.
 * Работает в паре с событием OnSalePayOrder (см. главный модуль)
 */

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

@set_time_limit(0);
@ignore_user_abort(true);

$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../..');
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
/* Подключение класса RBS */
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/rbs.payment/config.php");
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/rbs.payment/payment/rbs.php");

use Bitrix\Main\Loader;
use Bitrix\Sale;

Loader::includeModule('sale');

global $USER, $DB;

function logFile($data)
{
    file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/local/cron/result/'.date("Y.m.d").'_Sberbank_pay_order.txt',
        print_r($data, 1),
        FILE_APPEND
    );
}

$timeStart = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time());
logFile("Start - {$timeStart} \r\n--------------\r\n");

// Ночью с 3 до 4 - проверяем заказы за 3 дня, днем только за посление 24ч
$hour = date("H", time());
$countDay = ($hour >= 3 && $hour < 4) ? 72 : 24;

$timeFilter = time() - $countDay * 60 * 60; // выборка заказов за 3 дня

$parameters = [
    'filter' => [
        ">=DATE_INSERT" => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), $timeFilter),
        'STATUS_ID' => ['N', 'A', 'P', 'R', 'S' , 'F', 'EP'],
        'PAY_SYSTEM_ID' => ONLINE_PAY_SYSTEM,
        "PAYED"         => 'N',
    ],
    'select' => ['ID']
];

$arOrders = [];
$or = Sale\Internals\OrderTable::getList(
    $parameters
);
while ($arOrder = $or->Fetch()) {
    $arOrders[] = $arOrder['ID'];
}


foreach ($arOrders as $index => $orderID) {
    if (IntVal($orderID) > 0) {
        $cheque = '';
        $response = '';
        logFile("Проверка заказа: {$orderID} \r\n");

        $arOrder = CSaleOrder::GetByID(IntVal($orderID));

        CSalePaySystemAction::InitParamArrays($arOrder, $orderID);

        if (CSalePaySystemAction::GetParamValue("TEST_MODE") == 'Y') {
            $test_mode = true;
        } else {
            $test_mode = false;
        }
        if (CSalePaySystemAction::GetParamValue("TWO_STAGE") == 'Y') {
            $two_stage = true;
        } else {
            $two_stage = false;
        }
        if (CSalePaySystemAction::GetParamValue("LOGGING") == 'Y') {
            $logging = true;
        } else {
            $logging = false;
        }

        $rbs = new RBS(
            CSalePaySystemAction::GetParamValue("USER_NAME"),
            CSalePaySystemAction::GetParamValue("PASSWORD"),
            $two_stage,
            $test_mode,
            $logging
        );

        $order = Bitrix\Sale\Order::load($orderID);
        $orderSumPrice = $order->getPrice() * 100; // убираем копейки
        $paymentCollection = $order->getPaymentCollection();
        $onePayment = $paymentCollection[0];

        for ($prefix = 0; $prefix <= 10; $prefix++) {
            $order_prefix = $orderID.'_'.$prefix;
            $response = $rbs->get_order_status_by_orderNumber($order_prefix);

            if ((int)$response['errorCode'] === 0) {
                if (round($orderSumPrice) === round($response['paymentAmountInfo']['approvedAmount'])) {
                    logFile(json_encode($response, JSON_UNESCAPED_UNICODE) . "\r\n");

                    // оплата заказа
                    $onePayment->setPaid("Y");

                    // добавляем данные в историю заказа
                    $historyEntityType = 'ORDER';
                    $historyType = 'ORDER_COMMENTED';

                    Bitrix\Sale\OrderHistory::addAction(
                        $historyEntityType,
                        $order->getId(),
                        $historyType,
                        $order->getId(),
                        $order,
                        ['COMMENTS' => 'Заказ оплачен на основе запроса из Сбербанка (CRON)']
                    );

                    $order->save();

                    // Сохранение ифнормации о заказе
                    $arOrderFields = array(
                        "PS_SUM" => $response["amount"]/100,
                        "PS_CURRENCY" => $response["currency"],
                        "PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
                        "PS_STATUS" => "Y",
                        "PS_STATUS_DESCRIPTION" => $response["cardAuthInfo"]["pan"].";".$response['cardAuthInfo']["cardholderName"],
                        "PS_STATUS_MESSAGE" => $response["paymentAmountInfo"]["paymentState"],
                        "PS_STATUS_CODE" => "Y",
                    );
                    \CSaleOrder::Update($orderID, $arOrderFields);

                    logFile("Заказ успешно оплачен: {$orderID} \r\n");
                    break;
                } else {
                    logFile("Код ответа процессинга: {$response['actionCode']} \r\n");
                    logFile("Состояние платежа: {$response['paymentAmountInfo']['paymentState']} \r\n");
                    logFile(
                        "ОШИБКА Совпадения сумм: ".
                        round($orderSumPrice).'!='.round($response['paymentAmountInfo']['approvedAmount'])."\r\n"
                    );
                }
                //break;
            }
        }
    }
}

$timeEnd = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time());
logFile("END - {$timeEnd} \r\n\r\n");

die();
