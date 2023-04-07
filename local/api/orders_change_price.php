<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Sale;

CModule::IncludeModule('catalog');
CModule::IncludeModule('iblock');
CModule::IncludeModule("sale");

echo '<p></p><p><b>Выводятся заказы с изменной общей стоимостью и со статусом "Выполнен". Дополнительно проверяется изменение цены товара внутри заказа.</b></p>';
echo '<table border="1" cellpadding="5">
<tr><th>Номер заказа</th><th>Дата изменения</th><th>Кем изменено</th><th>Старая сумма заказа</th><th>Новая сумма заказа</th><th>Основание для скидки</th><th>Комментарий</th></tr>';

$history = CSaleOrderChange::GetList(array("ID" => "DESC"), array("TYPE" => "ORDER_PRICE_CHANGED"), false, false, array("ID", "ORDER_ID", "USER_ID", "DATE_MODIFY", "DATA"));

while ($ob = $history->GetNext()) {
    $data = unserialize(htmlspecialchars_decode($ob['DATA']));
    
    if ($data['OLD_PRICE'] <= $data['PRICE']) continue;
    
    $item = CSaleOrderChange::GetList(array(), array("TYPE" => "BASKET_PRICE_CHANGED", "ORDER_ID" => $ob["ORDER_ID"]), false, false, array("DATA"));
    if ($item->SelectedRowsCount() != 0) {
        $user = CUser::GetByID($ob["USER_ID"])->Fetch();
        $groups = CUser::GetUserGroup($ob["USER_ID"]);
        $name = $user['NAME'] . ' ' . $user['LAST_NAME'] . ' (' . $user['EMAIL'] . ')';
        $order = CSaleOrder::GetByID($ob["ORDER_ID"]);
        
        if ($order['STATUS_ID'] == 'F' && (in_array(1, $groups) || in_array(6, $groups) || in_array(14, $groups))) {
            
            $arrCoupon = [];
            $couponIterator = \Bitrix\Sale\Internals\OrderCouponsTable::getList(array('select' => array('*'), 'filter' => array('=ORDER_ID' => $ob["ORDER_ID"]), 'order' => array('ID' => 'ASC')));
            while ($coupon = $couponIterator->fetch()) {
                $arrCoupon = \Bitrix\Sale\DiscountCouponsManager::getData($coupon['COUPON']);
            }
            unset($coupon, $couponIterator);
            
            
            $discountText = '';
            echo '<tr><td><a href="/bitrix/admin/sale_order_view.php?ID=' . $ob["ORDER_ID"] . '" target="_blank">' . $order['ACCOUNT_NUMBER'] . '</td><td>' . $ob['DATE_MODIFY'] . '</td><td>' . $name . '</td><td>' . $data['OLD_PRICE'] . '</td><td>' . $data['PRICE'] . '</td>';
            if (!empty($arrCoupon["COUPON"])) {
                $discountText = $arrCoupon["DISCOUNT_NAME"] . '<br>( ' . $arrCoupon["COUPON"] . ' )';
            } else {
                $arPropCollection = Sale\Order::loadByAccountNumber($ob["ORDER_ID"])->getPropertyCollection();
                $arPropsData = $arPropCollection->getArray();
                foreach ($arPropsData['properties'] as $arProp) {
                    if (!array_key_exists($arProp['CODE'], $arProps)) continue;
                    $arProps[$arProp['CODE']] = $arProp['VALUE'][0];
                }
                
                $discountText = (!empty($arProps['PROGRAMM_LOYALTY_CARD'])) ? $arProps['PROGRAMM_LOYALTY_CARD'] . ': ' . $arProps['PROGRAMM_LOYALTY_WRITEOFF'] : '';
                
            }
            if (empty($discountText))
                $discountText = 'вручную оператором';
            
            echo '<td>' . $discountText . '</td>';
            echo '<td>' . $order["COMMENTS"] . '</td>';
            echo '</tr>';
        }
    }
}
echo '</table>';