<?php

use Bitrix\Sale\Order;
use Bitrix\Sale\Basket;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (count($arResult['BASKET_ITEMS'])) {
    Jamilco\Blocks\Block::load(array('b-order'));
    $APPLICATION->SetAdditionalCss("/local/templates/main/components/bitrix/sale.order.ajax/main/styles.css");
} else {
    $APPLICATION->SetDirProperty("minimalFooter", "N");
    $APPLICATION->SetAdditionalCss("/local/templates/main/components/bitrix/sale.order.ajax/main/empty.css");
}

/** DigitalDataLayer start */
$ddm = \DigitalDataLayer\Manager::getInstance();
$digitalData = $ddm->getData();

if ($arResult['USER_VALS']['CONFIRM_ORDER'] == 'Y') {

    $APPLICATION->setPageProperty('ddlPageType', 'confirmation');
    $APPLICATION->setPageProperty('ddlPageCategory', 'Confirmation');

    /** признак перезагрузки пользователем по refresh страницы confirmation */
    $sessionLabel = 'IS_CONFIRM_ORDER_' . $arResult['ORDER']['ID'];

    /** Параметры заказа */
    $rsOrderProps = \CSaleOrderPropsValue::GetOrderProps($arResult['ORDER']['ID']);

    $orderProps = [];
    while ($arProp = $rsOrderProps->Fetch()) {
        $orderProps[$arProp['ORDER_PROPS_ID']] = $arProp['VALUE'];
    }

    /** Параметры доставки */
    $deliveryList = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
    $deliveryId = $arResult['ORDER']['DELIVERY_ID'];
    $shippingMethod = $deliveryList[$deliveryId]['NAME'];

    /** Город */
    $cityName = '';
    if ($arCity = \Bitrix\Sale\Location\LocationTable::getList([
        'filter' => [
            'ID' => $orderProps[5],
            'NAME.LANGUAGE_ID' => 'ru'
        ],
        'select' => [
            'LNAME' => 'NAME.NAME'
        ],
        'limit' => 1
    ])->fetch()) {
        $cityName = $arCity['LNAME'];
    };

    /** Содержимое корзины */
    $ddlCartObject = $ddm->doProcessCartObject([], $arResult['ORDER']['ID']);

    $digitalData->transaction = [
        'orderId' => $arResult['ORDER']['ACCOUNT_NUMBER'],
        'checkoutType' => 'reservation',
        'isFirst' => false,
        'isReturning' => $_SESSION[$sessionLabel] ?: false,
        'currency' => $arResult['ORDER']['CURRENCY'],
        'subtotal' => $ddlCartObject['subtotal'],
        'total' => (float)$arResult['ORDER']['PRICE'],
        'lineItems' => $ddlCartObject['lineItems'],
        'shippingMethod' => $shippingMethod,
        'shippingCost' => (float)$arResult['ORDER']['PRICE_DELIVERY'],
        'paymentMethod' => $arResult['PAY_SYSTEM']['NAME'],
        'contactInfo' => [
            'firstName' => $orderProps[1],
            'lastName' => $orderProps[2],
            'phone' => $orderProps[4],
            'email' => $orderProps[3],
            'city' => $cityName,
            'address' => $orderProps[6]
        ],
        'vouchers' => []
    ];

    /**
     * Если пользователь не был залогинен, тогда нужно заполнить объект user данными из заказа
     * далее они могут быть перезаписаны актуальными данными, в случае если пользователь вошел
     */
    $digitalData->user = [
        'firstName' => $orderProps[1],
        'lastName' => $orderProps[2],
        'phone' => $orderProps[4],
        'email' => $orderProps[3]
    ];

    if (!isset($_SESSION[$sessionLabel])) {
        $_SESSION[$sessionLabel] = true;
    }
}
/** DigitalDataLayer end */


/** Adspire start */
if ($arResult['USER_VALS']['CONFIRM_ORDER'] == 'Y' && $arResult['ORDER']['ID']) {

    // признак перезагрузки пользователем по refresh страницы confirmation
    $sessionLabel = 'IS_SHOW_ADSPIRE_CONFIRM_' . $arResult['ORDER']['ID'];

    if (!isset($_SESSION[$sessionLabel])) {
        $_SESSION[$sessionLabel] = true;

        $adspire = \Adspire\Manager::getInstance();

        $order = Order::load($arResult['ORDER']['ID']);

        // тип заказа
        $orderType = 'default';
        if ($arResult['ORDER']['DELIVERY_ID'] == PICKUP_DELIVERY) {
            $orderType = 'pickup';
        }

        // тип email
        $rsOrders = Order::getList([
            'select' => [
                'ID'
            ],
            'filter' => [
                'USER_ID' => $order->getUserId(),
                'LID'     => SITE_ID
            ],
            'count_total' => true
        ]);
        if ($rsOrders->getCount() > 1) {
            $userMailType = 'old';
        } else {
            $userMailType = 'new';
        }

        // Купон
        $coupon = '';
        $discountData = $order->getDiscount()->getApplyResult();
        if (!empty($discountData['COUPON_LIST'])) {
            $coupon = reset($discountData['COUPON_LIST'])['COUPON'] ?: '';
        }

        // Содержимое корзины
        $basket = $order->getBasket()->getBasketItems();

        $productIds = [];
        $products = [];
        foreach ($basket as $basketItem) {
            $productId = $basketItem->getProductId();
            $productIds[] = $productId;

            // необходимо сохранить id, кол-во, стоимость
            $products[$productId]['variantId'] = $productId;
            $products[$productId]['price'] = $basketItem->getPrice();
            $products[$productId]['quantity'] = $basketItem->getQuantity();
        }

        $productsInfo = $adspire->fillProductObject($productIds);

        $orderItems = [];
        foreach ($productsInfo as $arItem) {
            $orderItems[] = [
                'cid'        => $arItem['cid'],
                'cname'      => array_pop($arItem['cname']),
                'pid'        => $arItem['pid'],
                'pname'      => $arItem['pname'],
                'quantity'   => $products[$arItem['variant_id']]['quantity'],
                'price'      => (float)$products[$arItem['variant_id']]['price'],
                'currency'   => $arItem['currency'],
                'variant_id' => $arItem['variant_id'],
            ];
        }

        $order = [
            'id'           => $arResult['ORDER']['ID'],
            'type'         => $orderType,
            'totalprice'   => (float)$arResult['ORDER']['PRICE'],
            'coupon'       => $coupon,
            //'loyalty_card' => '', // необязательно
            'usermail'     => $userMailType,
            'userphone'    => 'old',
            'name'         => $orderProps[1],
            'lastname'     => $orderProps[2],
            'email'        => $orderProps[3],
            'phone'        => $orderProps[4],
        ];

        $adspire->setContainerElement([
            'push' => [
                'TypeOfPage' => 'confirm',
                'Order'      => $order,
                'OrderItems' => $orderItems
            ]
        ]);
    }
}
/** Adspire end */
