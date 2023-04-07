<?php
/**
 * Обработчик проверяет заказы с неотправленным в Ocs признаком он-лайн оплаты и осуществляет повторную попытку его отправки.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

@set_time_limit(0);
@ignore_user_abort(true);

$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../..');
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale;
use Jamilco\Main\Oracle;

Loader::includeModule('sale');

global $USER, $DB;

$start_time_str = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time());

$parameters = [
    'filter' => [
        ">=DATE_UPDATE" => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), strtotime("${$start_time_str} -1 day")), // заказы за сутки
        "<DATE_UPDATE" => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), strtotime("${start_time_str}")),
        '=PROPERTY_VAL.CODE' => 'OCS_ERROR',
        '=PROPERTY_VAL.VALUE' => 'Y',
        "PAYED" => 'Y', // "Оплачен"
    ],
    'select' => ['ID'],
    'runtime' => [
        new \Bitrix\Main\Entity\ReferenceField(
            'PROPERTY_VAL',
            '\Bitrix\sale\Internals\OrderPropsValueTable',
            ["=this.ID" => "ref.ORDER_ID"],
            ["join_type"=>"left"]
        )
    ]
];

$arOrders = [];
$or = Sale\Internals\OrderTable::getList(
    $parameters
);
while ($arOrder = $or->Fetch()) {
    $arOrders[$arOrder['ID']] = $arOrder['ID'];
}

if ($arOrders) {
    $ocs = Oracle::getInstance();
    foreach ($arOrders as $orderId) {
        $r = $ocs->setOrderPaid($orderId);
    }
}

die();
