<?php

namespace Jamilco\Order;

use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Order;
use Jamilco\Gtm\Catalog;
use Jamilco\Gtm\GtmRequest\Exceptions\GtmRequestException;
use Jamilco\Gtm\GtmRequest\GtmRequest;
use Jamilco\Main\Helper as MainHelper;
use Oneway\Logger;

class Helper
{
    public static function sendDLMeasureProtocolDelete(int $basketRecordId)
    {
        $logger = new Logger('../../logs/cart/' . date('Y-m-d') . '_measureProtocol.log');
        $logger->info($basketRecordId);

        global $USER;
        $userId = $USER->GetID();

        $basketRecord = BasketTable::getRowById($basketRecordId);

        $productId = (int)$basketRecord['PRODUCT_XML_ID'];

        $content = [
            'v' => 1,
            'tid' => 'UA-85115488-2',
            'uid' => $userId,
            'cid' => MainHelper::getGaClientId(),
            't' => 'event',
            'ta' => 'Juicy Couture',
            'ea' => 'Removing a Product from a Shopping Cart',
            'ec' => 'Enhanced Ecommerce',
            'pa' => 'remove',
            'ni' => 0,
            'in' => MainHelper::getProductName($productId),
            'ip' => number_format(round($basketRecord['BASE_PRICE']), 2, '.', ''),
            'iq' => $basketRecord['QUANTITY'],
            'ic' => $basketRecord['PRODUCT_ID'],
            'iv' => $basketRecord['NAME'],
        ];

        self::gtmRequest($content, $logger);
    }

    public static function sendDLMeasureProtocolAdd(int $basketRecordId, array $arFields)
    {
        $logger = new Logger('../../logs/cart/' . date('Y-m-d') . '_measureProtocol.log');
        $logger->info($basketRecordId);

        global $USER;
        $userId = $USER->GetID();

        $productId = (int)$arFields['PRODUCT_XML_ID'];

        $content = [
            'v' => 1,
            'tid' => 'UA-85115488-2',
            'uid' => $userId,
            'cid' => MainHelper::getGaClientId(),
            't' => 'event',
            'ta' => 'Juicy Couture',
            'ea' => 'Adding a Product to a Shopping Cart',
            'ec' => 'Enhanced Ecommerce',
            'pa' => 'add',
            'ni' => 0,
            'in' => MainHelper::getProductName($productId),
            'ip' => number_format(round($arFields['BASE_PRICE']), 2, '.', ''),
            'iq' => $arFields['QUANTITY'],
            'ic' => $arFields['PRODUCT_ID'],
            'iv' => $arFields['NAME'],
        ];

        self::gtmRequest($content, $logger);
    }

    public static function sendDLMeasureProtocolPurchase(Order $order)
    {
        $logger = new Logger('../../logs/orders/' . date('Y-m-d') . '_measureProtocol.log');
        $logger->info($order->getId());

        $content = [
            'v' => 1,
            'tid' => 'UA-85115488-2',
            'uid' => $order->getUserId(),
            'cid' => MainHelper::getGaClientId(),
            't' => 'event',
            'ti' => $order->getId(),
            'ta' => 'Juicy Couture',
            'ea' => 'Purchase',
            'ec' => 'Enhanced Ecommerce',
            'pa' => 'purchase',
            'ni' => 0,
            'tr' => number_format(round($order->getPrice()), 2, '.', ''),
            'tt' => number_format($order->getTaxPrice(), 2, '.', ''),
            'ts' => number_format($order->getDeliveryPrice(), 2, '.', ''),
        ];

        $gtmProducts = Catalog::getProducts($order->getBasket());
        $i = 1;
        foreach ($gtmProducts as $gtmProduct) {
            $content['pr' . $i . 'id'] = $gtmProduct['id'];
            $content['pr' . $i . 'nm'] = $gtmProduct['name'];
            $content['pr' . $i . 'pr'] = $gtmProduct['price'];
            $content['pr' . $i . 'qt'] = $gtmProduct['quantity'];
            $content['pr' . $i . 'br'] = $gtmProduct['brand'];
            $content['pr' . $i . 'ca'] = $gtmProduct['category'];
            $content['pr' . $i . 'cc'] = $gtmProduct['coupon'];
            $content['pr' . $i . 'va'] = $gtmProduct['variant'];
            $i++;
        }
        self::gtmRequest($content, $logger);
    }

    public static function sendDLMeasureProtocolFullRefund(Order $order)
    {
        $logger = new Logger('../../logs/orders/' . date('Y-m-d') . '_measureProtocol.log');
        $logger->info($order->getId());

        $content = [
            'v' => 1,
            'tid' => 'UA-85115488-2',
            'uid' => $order->getUserId(),
            'cid' => MainHelper::getGaClientId(),
            't' => 'event',
            'ti' => $order->getId(),
            'ta' => 'Juicy Couture',
            'ea' => 'Full Refund',
            'ec' => 'Enhanced Ecommerce',
            'pa' => 'refund',
            'ni' => 0,
        ];
        self::gtmRequest($content, $logger);
    }

    /**
     * @param array $content
     * @param Logger $logger
     * @return void
     */
    private static function gtmRequest(array $content, Logger $logger)
    {
        try {
            $res = GtmRequest::request($content);
        } catch (GtmRequestException $e) {
            $logger->error((string)$e->getCurlHandle(), [$e->getCurlHandle()]);
            $res = $e->getResult();
        }
        $logger->info($res);
    }
}
