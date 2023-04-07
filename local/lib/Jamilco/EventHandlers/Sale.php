<?php

namespace Jamilco\EventHandlers;

use Bitrix\Main\Web\Json;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Order;
use Jamilco\Gtm\Catalog;
use Jamilco\Main\Helper as MainHelper;
use Jamilco\Order\Helper as OrderHelper;
use Bitrix\Main\Event;
use Oneway\Logger;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie;


class Sale
{
    public static function OnSaleOrderSavedHandler(Event $event)
    {
        /** @var Order */
        $order = $event->getParameter('ENTITY');
        $orderId = $order->getId();
        $isNew = $event->getParameter('IS_NEW');

        // RROCKET
        if (!$_SESSION['SEND_RROCKET_ORDERS']) {
            $_SESSION['SEND_RROCKET_ORDERS'] = [];
        }
        if (
            $isNew
            && !array_key_exists($orderId, $_SESSION['SEND_RROCKET_ORDERS'])
        ) {
            $orderId = $order->getId();
            $stockId = \Jamilco\Main\Retail::getStoreName(true);
            $email = getOwnerEmail($orderId);

            $products = Catalog::getProducts($order->getBasket());
            $productIds =[];
            foreach ($products as $product) {
                $content[$product['id']] = [
                    'id'    => $product['id'],
                    'qnt'   => $product['quantity'],
                    'price' => $product['price'],
                ];
                $productIds[] = $product['id'];
            }

            $_SESSION['SEND_RROCKET_ORDERS'][$orderId] = [
                'transactionId' => (string)$orderId,
                'stockId' => $stockId,
                'userEmail' => $email,
                'productIds' => $productIds,
                'content' => $content,
            ];
        }

        // WINDOW DATA LAYER
        if (!$_SESSION['SEND_DATA_LAYER_ORDERS']) {
            $_SESSION['SEND_DATA_LAYER_ORDERS'] = [];
        }
        if (
            $isNew
            && !array_key_exists($orderId, $_SESSION['SEND_DATA_LAYER_ORDERS'])
        ) {
//            OrderHelper::sendDLMeasureProtocolPurchase($order);
            $currency = $order->getCurrency();
            $orderId = $order->getId();
            $revenue = number_format(round($order->getPrice()), 2, '.', '');
            $tax = number_format($order->getTaxPrice(), 2, '.', '');
            $shipping = number_format($order->getDeliveryPrice(), 2, '.', '');

            $products = Catalog::getProducts($order->getBasket());
            $productIds =[];
            foreach ($products as $product) {
                $content[$product['id']] = [
                    'id' =>         $product['id'],
                    'name' =>       $product['name'],
                    'price' =>      $product['price'],
                    'quantity' =>   $product['quantity'],
                    'brand' =>      $product['brand'] ?: '',
                    'category' =>   $product['category'],
                    'coupon' =>     $product['coupon'] ?: '',
                    'variant' =>    $product['variant'],
                ];
                $productIds[] = $product['id'];
            }

            $_SESSION['SEND_DATA_LAYER_ORDERS'][$orderId] = [
                'orderId' => (string)$orderId,
                'currency' => $currency,
                'revenue' => $revenue,
                'tax' => $tax,
                'shipping' => $shipping,
                'productIds' => $productIds,
                'content' => $content,
            ];
        }
    }

    public static function OnSaleOrderCanceledHandler(Event $event)
    {
        $order = $event->getParameter('ENTITY');
        $orderId = $order->getId();

        if (!$_SESSION['SEND_DATA_LAYER_ORDERS']) $_SESSION['SEND_DATA_LAYER_ORDERS'] = [];
//            OrderHelper::sendDLMeasureProtocolFullRefund($order);
        $_SESSION['SEND_DATA_LAYER_ORDERS']['ORDER_CANCEL'] = $orderId;
    }

    public static function OnBasketAddHandler($Id, $arFields)
    {
        if (!$_SESSION['SEND_DATA_LAYER_BASKET']) $_SESSION['SEND_DATA_LAYER_BASKET'] = [];
        if (!array_key_exists($Id, $_SESSION['SEND_DATA_LAYER_BASKET'])) {
            $basketRecordId = (int)$Id;
//        OrderHelper::sendDLMeasureProtocolAdd($basketRecordId, $arFields);

            $productCurrency = $arFields['CURRENCY'];
            $productId = (int)$arFields['PRODUCT_XML_ID'];
            $productName = MainHelper::getProductName($productId);
            $offerId = $arFields['PRODUCT_ID'];
            $offerPrice = number_format(round($arFields['BASE_PRICE']), 2, '.', '');
            $productBrand = MainHelper::getProductBrand($productId);
            $productCategory = MainHelper::getProductCategory($productId);
            $offerArtnumber = $arFields['NAME'];
            $offerQuantity = (int)$arFields['QUANTITY'];

            $_SESSION['SEND_DATA_LAYER_BASKET'][$Id] = [
                'ecommerce' => [
                    'currencyCode' => $productCurrency,
                    'add' => [
                        'products' => [
                            [
                                'name'      => $productName,
                                'id'        => (string)$offerId,
                                'price'     => $offerPrice,
                                'brand'     => $productBrand,
                                'category'  => $productCategory,
                                'variant'   => $offerArtnumber,
                                'quantity'  => $offerQuantity
                            ]
                        ]
                    ]
                ],
                'event' => 'gtm-ee-event',
                'gtm-ee-event-category' => 'Enhanced Ecommerce',
                'gtm-ee-event-action' => 'Adding a Product to a Shopping Cart',
                'gtm-ee-event-non-interaction' => 'False',
            ];
        }
        unset($_SESSION['SEND_DATA_LAYER_BASKET_REMOVE']);
    }

    public static function OnBeforeBasketDeleteHandler($Id)
    {
        if (!$_SESSION['SEND_DATA_LAYER_BASKET_REMOVE']) $_SESSION['SEND_DATA_LAYER_BASKET_REMOVE'] = [];
        if (!array_key_exists($Id, $_SESSION['SEND_DATA_LAYER_BASKET_REMOVE'])) {
            $basketRecordId = (int)$Id;
//        OrderHelper::sendDLMeasureProtocolDelete($basketRecordId);

            $basketRecord = BasketTable::getRowById($basketRecordId);
            $productId = (int)$basketRecord['PRODUCT_XML_ID'];
            $productCurrency = $basketRecord['CURRENCY'];
            $productName = MainHelper::getProductName($productId);
            $offerPrice = number_format(round($basketRecord['BASE_PRICE']), 2, '.', '');
            $productBrand = MainHelper::getProductBrand($productId);
            $productCategory = MainHelper::getProductCategory($productId);
            $offerId = $basketRecord['PRODUCT_ID'];
            $offerArtnumber = $basketRecord['NAME'];
            $offerQuantity = (int)$basketRecord['QUANTITY'];

            $_SESSION['SEND_DATA_LAYER_BASKET_REMOVE'][$Id] = [
                'ecommerce' => [
                    'currencyCode' => $productCurrency,
                    'remove' => [
                        'products' => [
                            [
                                'name'      => $productName,
                                'id'        => (string)$offerId,
                                'price'     => $offerPrice,
                                'brand'     => $productBrand,
                                'category'  => $productCategory,
                                'variant'   => $offerArtnumber,
                                'quantity'  => $offerQuantity
                            ]
                        ]
                    ]
                ],
                'event' => 'gtm-ee-event',
                'gtm-ee-event-category' => 'Enhanced Ecommerce',
                'gtm-ee-event-action' => 'Removing a Product from a Shopping Cart',
                'gtm-ee-event-non-interaction' => 'False',
            ];
        }
    }
}
