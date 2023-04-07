<?php

use Bitrix\Main\Web\Json;
use Bitrix\Sale\DiscountCouponsManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$ddm = \DigitalDataLayer\Manager::getInstance();
$digitalData = $ddm->getData();

$ddlCartObject = $ddm->doProcessCartObject($arResult['BASKET_ITEMS']);
$digitalData->cart = $ddlCartObject;

if (!empty($digitalData->cart['lineItems'])) {

    /** Оплата */
    $payMethod = '';
    foreach ($arResult['PAY_SYSTEM'] as $pay) {
        if ($pay['CHECKED'] == 'Y') {
            $payMethod = $pay['NAME'];
        }
    }

    /** Доставка */
    $deliveryCost = 0;
    $deliveryMethod = '';
    foreach ($arResult['DELIVERY'] as $delivery) {
        if ($delivery['CHECKED'] == 'Y') {
            $deliveryMethod = $delivery['NAME'];
            $deliveryCost = $delivery['PRICE'];
        }
    }

    /** Купоны */
    $vouchers = [];
    $arCoupons = DiscountCouponsManager::get(true, array(), true, true);
    if (!empty($arCoupons)) {
        foreach ($arCoupons as $coupon) {
            if ($coupon['ACTIVE'] == 'Y' && $coupon['STATUS'] == DiscountCouponsManager::STATUS_APPLYED) {
                $vouchers[] = $coupon['COUPON'];
            }
        }
    }

    /** Город */
    $cityId = (int)$_COOKIE['city_id'] ?: DEFAULT_CITY_ID;
    $cityName = (string)$_COOKIE['city_name'] ?: 'Москва';

    if (is_array($locationProp['VARIANTS']) && count($locationProp['VARIANTS']) > 0) {
        foreach ($locationProp['VARIANTS'] as $arVariant) {
            if ((int)$arVariant['ID'] === $cityId) {
                $cityId = $arVariant['ID'];
                $cityName = $arVariant['CITY_NAME'];
                break;
            }
        }
    }

    $digitalData->cart = [
        'shippingMethod' => $deliveryMethod,
        'shippingCost' => $deliveryCost,
        'paymentMethod' => $payMethod,
        'vouchers' => $vouchers,
        'contactInfo' => [
            'firstName' => $arResult['ORDER_PROP']['PRINT'][1]['VALUE'],
            'lastName' => $arResult['ORDER_PROP']['PRINT'][2]['VALUE'],
            'phone' => $arResult['ORDER_PROP']['PRINT'][4]['VALUE'],
            'email' => $arResult['ORDER_PROP']['PRINT'][3]['VALUE'],
            'city' => $cityName ?: $arResult['ORDER_PROP']['PRINT'][5]['VALUE'] ?: '',
            'address' => $arResult['ORDER_PROP']['PRINT'][6]['VALUE']
        ]
    ];

    /** поправка общей суммы корзины на сумму из заказа */
    $digitalData->cart = ['total' => (float)$arResult['ORDER_DATA']['ORDER_PRICE'] + $deliveryCost];
}


echo '<script>if (typeof window.top.digitalData.changes !== "undefined") { window.digitalData.changes.push(["cart", ' .
    Json::encode(
        $digitalData->cart,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) .
    ']);
        setTimeout(function() {
           window.digitalData.events.push({
                \'category\': \'Ecommerce\',
                \'name\'    : \'Updated Cart\',
                \'cart\'    : window.digitalData.cart
              });
        }, 500);
}</script>';
