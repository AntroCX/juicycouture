<?php

use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Sale\DiscountCouponsManager;

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

$request = Context::getCurrent()->getRequest();
$coupon = trim((string)$request->get('coupon'));

if ($coupon !== '' && check_bitrix_sessid()) {
    $applyBonusCard = '';
    $bonusClassExists = class_exists('\Jamilco\Loyalty\Bonus');
    if ($bonusClassExists && $applyBonusCard = \Jamilco\Loyalty\Bonus::checkBasketForBonusCard()) {
        // если бонусы были применены, то на время апдейта корзины, выключим их
        \Jamilco\Loyalty\Bonus::getData($applyBonusCard, 'N');
    }

    Loader::includeModule('catalog');
    Loader::includeModule('sale');

    if (method_exists('\Jamilco\Loyalty\Common', 'discountsAreMoved') && \Jamilco\Loyalty\Common::discountsAreMoved()) {
        unset($_SESSION['MANZANA_COUPONS'][$coupon]);
    } else {
        DiscountCouponsManager::delete($coupon);
    }

    if ($bonusClassExists && $applyBonusCard) {
        // если бонусы были выключены, их надо вернуть
        \Jamilco\Loyalty\Bonus::getData($applyBonusCard, 'Y');
    }
}