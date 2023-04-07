<?php

defined('NO_AGENT_CHECK') || define('NO_AGENT_CHECK', true);
defined('NO_KEEP_STATISTIC') || define('NO_KEEP_STATISTIC', 'Y');
defined('NO_AGENT_STATISTIC') || define('NO_AGENT_STATISTIC', 'Y');
defined('NOT_CHECK_PERMISSIONS') || define('NOT_CHECK_PERMISSIONS', true);

$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__) . '/../../';

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Sale;

\Bitrix\Main\Loader::includeModule('sale');

$dbRes = \Bitrix\Sale\Order::getList([
    'select' => ['ID'],
    'filter' => [
        'PROPERTY_NAME.VALUE' => 'autotest',
        'PROPERTY_NAME.CODE' => 'NAME',
        '!STATUS_ID' => 'J'
    ],
    'runtime' => [
        new \Bitrix\Main\Entity\ReferenceField(
            'PROPERTY_NAME',
            '\Bitrix\sale\Internals\OrderPropsValueTable',
            ["=this.ID" => "ref.ORDER_ID"]
        ),
    ],
]);

$orders = [];

while ($orderDB = $dbRes->fetch()) {
    $orders[] = $orderDB['ID'];
}


foreach ($orders as $orderId) {
    $order = Sale\Order::load($orderId);
    if ($order) {
        $order->setField('STATUS_ID', 'J');
        $order->save();
    }
}