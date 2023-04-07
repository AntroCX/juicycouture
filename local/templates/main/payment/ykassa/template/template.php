<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . '/payment/ykassa/template/style.css');
Loc::loadMessages(__FILE__);
Loader::IncludeModule('sale');

define('STORE_DELIVERY_ID', 20); // служба доставки "Получить в магазине"

/**
 * Способы для тестовой оплаты
 * https://tech.yandex.ru/money/doc/payment-solution/examples/examples-test-data-docpage/
 *
 */

/** Формирование параметров для чека
 *
 * нужно сформировать параметр ym_merchant_receipt как объект json
 * https://tech.yandex.ru/money/doc/payment-solution/payment-form/payment-form-receipt-docpage/
 *
 * $tax
 * 1 — без НДС;
 * 2 — НДС по ставке 0%;
 * 3 — НДС чека по ставке 10%;
 * 4 — НДС чека по ставке 18%;
 * 5 — НДС чека по расчетной ставке 10/110;
 * 6 — НДС чека по расчетной ставке 18/118.
*/
$tax = 6;

// ID's свойств заказа
define('EMAIL_PROPERTY_ID', 3);
define('PAYER_NAME_PROPERTY_ID', 1);

$arErrors = [];
$arYmMerchant = [];

list($orderId, $paymentId) = \Bitrix\Sale\PaySystem\Manager::getIdsByPayment($params['PAYMENT_ID']);

if (intval($orderId) > 0) {

    $order = \Bitrix\Sale\Order::load($orderId);

    if (!empty($order)) {

        // номер заказа
        $accountNumber = $order->getField('ACCOUNT_NUMBER');

        // данные клиента
        $propertyCollection = $order->getPropertyCollection();

        /**
         * UserEmail будет получено только в том случае, если у какого-либо свойства заказа будет выставлен флажок "Использовать как E-mail"
         * в настройках этого свойства
         *
         * в ином случае будет null и тогда можно получить само свойство зная его ID в списке свойств заказа
         */

        if ($emailProperty = $propertyCollection->getUserEmail() ?: $propertyCollection->getItemByOrderPropertyId(EMAIL_PROPERTY_ID)) {
            $buyerEmail = $emailProperty->getValue();
        } else {
            $arErrors[] = Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_ERROR_NOT_CUSTOMER_DATA');
        }

        /**
         * PayerName будет получено только в том случае, если у какого-либо свойства заказа будет выставлен флажок "Использовать как имя плательщика"
         * в настройках этого свойства
         *
         * в ином случае будет null и тогда можно получить само свойство зная его ID в списке свойств заказа
         */
        if ($payerProperty = $propertyCollection->getPayerName() ?: $propertyCollection->getItemByOrderPropertyId(PAYER_NAME_PROPERTY_ID)) {
            $buyerName = $payerProperty->getValue();
        } else {
            $arErrors[] = Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_ERROR_NOT_CUSTOMER_DATA');
        }

        $arYmMerchant['customerContact'] = $buyerEmail;

        // данные о доставке
        $shipmentCollection = $order->getShipmentCollection();

        $isAdvancePayment = false;
        foreach ($shipmentCollection as $shipment) {
            if ($shipment->isSystem()) {
                continue;
            }
            if ($shipment->getField('DELIVERY_ID') == STORE_DELIVERY_ID) {
                // признак авансового платежа для каждого товара в чеке
                $isAdvancePayment = true;
            }
        }

        $isAdvancePayment = false; // пока не работает

        // корзина заказа
        $arYmMerchant['items'] = [];

        $basket = $order->getBasket();

        foreach ($basket as $basketItem) {
            $arYmMerchant['items'][] = [
                'quantity' => (int)$basketItem->getQuantity(),
                'price'    => [
                    'amount' => number_format(roundEx($basketItem->getPrice(), 2), 2, '.', '')
                ],
                'tax'      => $tax,
                'text'     => $isAdvancePayment ? 'Авансовый платеж' : $basketItem->getField("NAME"),
                "paymentMethodType" => "full_prepayment",
                "paymentSubjectType" => "commodity"
            ];
        }

        if (empty($arYmMerchant['items'])) {
            $arErrors[] = Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_ERROR_ITEMS_NOT_FOUND');
        }

        if (empty($arErrors)) {

            // доставка
            $deliveryPrice = $order->getDeliveryPrice();

            if (intval($deliveryPrice) > 0) {
                $arYmMerchant['items'][] = array(
                    'quantity' => 1,
                    'price' => [
                        'amount' => number_format(round($deliveryPrice), 2, '.', '')
                    ],
                    'tax' => $tax,
                    'text' => Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_DELIVERY_ITEM'),
                    "paymentMethodType" => "full_prepayment",
                    "paymentSubjectType" => "service"
                );
            }
        }
    } else {
        $arErrors[] = Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_ERROR_ORDER_NOT_FOUND');
    }
} else {
    $arErrors[] = Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_ERROR_ORDER_ID_NOT_FOUND');
}


// проверим сумму товаров по чеку в сравнении в итоговой суммой по заказу
$params['PAYMENT_SHOULD_PAY'] = roundEx($params['PAYMENT_SHOULD_PAY'], 2);

$checkSumm = 0;
foreach ($arYmMerchant['items'] as $arOne) {
    $checkSumm += $arOne['quantity'] * $arOne['price']['amount'];
}
if ($checkSumm != $params['PAYMENT_SHOULD_PAY']) {

    \CEvent::Send(
        "PAY_FORM_ERROR",
        SITE_ID,
        [
            'SALE_EMAIL'    => \COption::GetOptionString('sale', 'order_email', ''),
            'ORDER_ID'      => $accountNumber,
            'ORDER_PAYMENT' => $params['PAYMENT_SHOULD_PAY'],
            'ITEMS_PAYMENT' => $checkSumm,
        ]
    );

    \CEvent::CheckEvents();
}

$needAutoSubmit = ($APPLICATION->GetCurDir() == '/order/') ? true : false;
?>

<div class="sale-paysystem-wrapper">
    <?if (empty($arErrors)):?>
        <span>
            <?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_DESCRIPTION2') ?> <b><?= SaleFormatCurrency($params['PAYMENT_SHOULD_PAY'], $payment->getField('CURRENCY')) ?></b>
        </span>
        <form name="ShopForm" action="<?= $params['URL'] ?>" method="post">
            <input name="ShopID" value="<?= $params['YANDEX_SHOP_ID'] ?>" type="hidden">
            <input name="scid" value="<?= $params['YANDEX_SCID'] ?>" type="hidden">

            <?php /** внимание: customerNumber участвует в расчете MD5, должен равняться тому что в настройках платежной системы в поле "Код покупателя" */ ?>
            <input name="customerNumber" value="<?= $params['PAYMENT_BUYER_ID'] ?>" type="hidden">

            <?php
            /** orderNumber - это значение попадает на экран при оплате в системе яндекс.деньги
             * а также в чек покупателю после успешной оплаты
             * Является ID по которому в дальнейшем обработчик ищет заказ, по умолчанию равен $params['PAYMENT_ID']
             * Здесь этот параметр изменен. И дополнительно изменения внесены в обработчик.
             */
            ?>
            <input name="orderNumber" value="<?= $accountNumber ?>" type="hidden">
            <input name="Sum" value="<?= number_format(roundEx($params['PAYMENT_SHOULD_PAY'], 2), 2, '.', '') ?>" type="hidden">
            <input name="paymentType" value="<?= $params['PS_MODE'] ?>" type="hidden">
            <input name="cms_name" value="1C-Bitrix" type="hidden">
            <input name="BX_HANDLER" value="YANDEX" type="hidden">
            <input name="BX_PAYSYSTEM_CODE" value="<?= $params['BX_PAYSYSTEM_CODE'] ?>" type="hidden">
            <input name="cps_email" value="<?= $buyerEmail ?>" type="hidden">
                
            <?php /** параметры для чека, заполняются выше по коду */ ?>
            <input name="ym_merchant_receipt" value='<?= json_encode($arYmMerchant, JSON_UNESCAPED_UNICODE) ?>' type="hidden">

            <?php /** доп. параметры */ ?>
            <input name="site_name" value="<?= SITE_SERVER_NAME ?>" type="hidden">
            <input name="account_number" value="<?= $accountNumber ?>" type="hidden">
            <input name="buyer_name" value="<?= $buyerName ?>" type="hidden">
            <input name="payment_id" value="<?= $params['PAYMENT_ID'] ?>" type="hidden">
                
            <div class="sale-paysystem-yandex-button-container">
                <span class="sale-paysystem-yandex-button">
                    <input class="sale-paysystem-yandex-button-item" name="BuyButton" value="<?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_BUTTON_PAID') ?>" type="submit">
                </span>
                <span class="sale-paysystem-yandex-button-description">
                    <?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_REDIRECT_MESS') ?>
                </span>
            </div>
            <p>
                <span class="sale-paysystem-description"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_WARNING_RETURN') ?></span>
            </p>
        </form>

    <? if ($needAutoSubmit) { ?>
        <script type="text/javascript">
          $(function() {
            setTimeout(function() {
              $('form[name="ShopForm"]').submit();
            }, 10000);
          });
        </script>
    <? } ?>
    <?else:?>
        <p class="sale-paysystem-error-title"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_YANDEX_ERROR_TITLE') ?></p>
        <p class="sale-paysystem-error">
          <?foreach ($arErrors as $error):?>
              <?= $error ?><br>
          <?endforeach;?>
        </p>
    <?endif?>
</div>
