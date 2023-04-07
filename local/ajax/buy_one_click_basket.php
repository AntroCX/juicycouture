<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Location\LocationTable;
use Bitrix\Sale\DiscountCouponsManager;

use Jamilco\Main\Utils;

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

Loader::IncludeModule('iblock');
Loader::includeModule('sale');
Loader::includeModule('catalog');

define('PERSON_TYPE_ID', 1); // физическое лицо
define('PAYSYSTEM_ID', 3);

$request = Context::getCurrent()->getRequest();

if ($request->isAjaxRequest() && $request->isPost() && check_bitrix_sessid()) {

    $responseResult = [
        'success' => false,
        'errors'  => 'Произошла ошибка. Попробуйте еще раз.'
    ];

    $type = filter_var($request->getPost('type'), FILTER_SANITIZE_STRING);
    $coupon = $request->getPost('coupon');

    /** Запрос на применение купона */
    if ($type == 'applyCoupon') {
        $orderInfo = Utils::getPreOrderData(DELIVERY_COURIER_ID, $coupon);

        if (empty($orderInfo['errors'])) {
            $responseResult['success'] = true;
            $responseResult['data'] = $orderInfo['data'];
            $responseResult['errors'] = '';
        } else {
            $responseResult['errors'] = implode('<br>', $orderInfo['errors']);
        }

        echo Json::encode($responseResult);
        die();
    }

    /** Запрос на стоимость доставки */
    if ($type == 'deliveryPrice') {
        $orderInfo = Utils::getPreOrderData(DELIVERY_COURIER_ID);

        echo Json::encode(
            [
                'success' => true,
                'errors'  => '',
                'data'    => $orderInfo['data']['deliveryPrice'],
            ]
        );
        die();
    }

    /** Оформление заказа */
    $phone = filter_var($request->getPost('phone'), FILTER_SANITIZE_NUMBER_INT);

    // нужный формат "+7 (999) 999-99-99"
    $phone = str_replace(['+', '(', ')', '-', ' '], '', $phone);
    $phone = '+'.substr($phone, 0, 1).' ('.substr($phone, 1, 3).') '.substr($phone, 4, 3).'-'.substr($phone, 7, 2).'-'.substr($phone, 9, 2);

    $name = filter_var($request->getPost('fio'), FILTER_SANITIZE_STRING);
    $email = filter_var($request->getPost('email'), FILTER_SANITIZE_EMAIL);

    if ($phone && $email) {

        \Jamilco\Loyalty\Gift::checkGifts(0, 0); // удаляем из корзины подарки

        $userId = (int)$USER->GetID();

        /** adspire признак */
        $usermail = 'old';

        if ($userId <= 0) {
            // проверка есть ли уже пользователь с аналогичным e-mail
            $rsUser = \CUser::GetList(($by = 'id'), ($order = 'desc'), ['EMAIL' => $email]);

            if ($arUser = $rsUser->Fetch()) {
                $userId = $arUser['ID'];
            }

            // пользователя нет, регистрируется новый
            if ($userId <= 0) {
                $password = randString(10);
                $arResult = $USER->Register($email, $name, '', $password, $password, $email);
                $userId = $USER->GetID();
                $usermail = 'new';
            }
        }

        // добавление купона на скидку
        if (DiscountCouponsManager::isExist($coupon)) DiscountCouponsManager::add($coupon);

        $siteId = Context::getCurrent()->getSite();

        $basket = Basket::loadItemsForFUser(Fuser::getId(), $siteId);

        if (count($basket)) {

            $order = Order::create($siteId, $userId);

            $order->setPersonTypeId(PERSON_TYPE_ID);

            $order->setField('CURRENCY', CurrencyManager::getBaseCurrency());

            $result = $order->setBasket($basket);

            if ($result->isSuccess()) {

                // Местоположение и тариф
                $arLocation = \Jamilco\Delivery\Location::getCurrentLocation();
                $locationId = $arLocation['ID'];
                $loc = LocationTable::getList(
                    [
                        'filter' => [
                            '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                            'ID'                => $locationId,
                        ],
                        'select' => [
                            '*',
                            'NAME_RU'   => 'NAME.NAME',
                            'TYPE_CODE' => 'TYPE.CODE',
                        ],
                        'limit'  => 1,
                    ]
                );
                $arCity = $loc->Fetch();

                // доставка
                $shipmentCollection = $order->getShipmentCollection();
                $shipment = $shipmentCollection->createItem();
                $service = Delivery\Services\Manager::getById(DELIVERY_COURIER_ID);
                $shipment->setFields(
                    [
                        'DELIVERY_ID'   => $service['ID'],
                        'DELIVERY_NAME' => $service['NAME'],
                    ]
                );

                // Товары из корзины
                $shipmentItemCollection = $shipment->getShipmentItemCollection();
                foreach ($order->getBasket() as $item) {
                    $shipmentItem = $shipmentItemCollection->createItem($item);
                    $shipmentItem->setQuantity($item->getQuantity());
                }

                // Оплата
                $paymentCollection = $order->getPaymentCollection();
                $payment = $paymentCollection->createItem();
                $paySystemService = PaySystem\Manager::getObjectById(PAYSYSTEM_ID);
                $payment->setFields(
                    [
                        'PAY_SYSTEM_ID'   => $paySystemService->getField('PAY_SYSTEM_ID'),
                        'PAY_SYSTEM_NAME' => $paySystemService->getField('NAME'),
                    ]
                );

                // свойства заказа
                $propertyCollection = $order->getPropertyCollection();

                $emailProp = $propertyCollection->getUserEmail();
                if ($emailProp) {
                    $emailProp->setValue($email);
                }
                $phoneProp = $propertyCollection->getPhone();
                if ($phoneProp) {
                    $phoneProp->setValue($phone);
                }
                $nameProp = $propertyCollection->getPayerName();
                $nameProp->setValue($name);

                // город
                $cityNameProp = getPropertyByCode($propertyCollection, 'TARIF_LOCATION_CITY');
                if ($cityNameProp) {
                    $cityNameProp->SetValue($arCity['NAME_RU']);
                }

                $cityLocation = getPropertyByCode($propertyCollection, 'TARIF_LOCATION');
                $cityLocation->SetValue($arCity['CODE']);

                // OMNI_CHANNEL
                $omniProp = getPropertyByCode($propertyCollection, 'OMNI_CHANNEL');
                $omniProp->SetValue('Delivery');

                // флаг "Виджет"
                $widgetProp = getPropertyByCode($propertyCollection, 'WIDGET');
                $widgetProp->SetValue('Y');

                $order->doFinalAction(true);

                $result = $order->save();

                if(!$result->isSuccess()){
                    $responseResult['success'] = false;
                } else {
                    $responseResult['success'] = true;
                    $responseResult['orderId'] = $order->getField('ACCOUNT_NUMBER');
                    $shippingCost = $order->getDeliveryPrice();
                    /** adspire */
                    $responseResult['adspire'] = array(
                        'orderId'    => $order->getId(),
                        'totalprice' => $order->getField('PRICE'),
                        'usermail'   => $usermail,
                        'name'       => $nameProp,
                        'lastname'   => '',
                        'email'      => $emailProp,
                        'coupon'     => $coupon ? $coupon : ''
                    );
                    /** DigitalDataLayer start */
                    $responseResult['DDL'] = [
                        'paymentMethod'  => "Наличными",
                        'shippingCost'   => (int)($shippingCost) ?: 0,
                        'shippingMethod' => "Доставка курьером",
                        // суммарная стоимость товаров в корзине без доставки и использованных бонусных баллов
                        'subtotal'       => $order->getField('PRICE') - $shippingCost,
                        // цена, которую заплатит пользователь, включая все скидки, использованные бонусные баллы и стоимость доставки
                        'total'          => $order->getField('PRICE')
                    ];

                    foreach ($order->getBasket() as $item) {

                        $scuRes = CCatalogProduct::GetByID($item->getProductId());

                        $arFilterScu = Array(
                            "IBLOCK_ID" => IBLOCK_SKU_ID,
                            "ID"        => $item->getProductId()
                        );
                        $res = CIBlockElement::GetList([], $arFilterScu, Array("ID", "NAME", "IBLOCK_SECTION_ID", "PREVIEW_PICTURE", "DETAIL_PICTURE"));
                        $resScu = $res->GetNext();

                        // находим товар по торговому предложению
                        $mxResult = CCatalogSku::GetProductInfo($item->getProductId());
                        //находим ID родительской категории
                        $res = CIBlockElement::GetByID($mxResult['ID']);
                        $resID = $res->GetNext();
                        // находим имя категории
                        $res = CIBlockSection::GetByID($resID['IBLOCK_SECTION_ID']);
                        $resCat = $res->GetNext();

                        // Массив разделов
                        $arSectionsByCurrent = [];
                        $res = CIBlockSection::GetNavChain(false, $resID['IBLOCK_SECTION_ID']);
                        while ($arSectionPath = $res->GetNext()) {
                            $arSectionsByCurrent[] = $arSectionPath["NAME"];
                        }

                        $responseResult['adspire']['OrderItems'][] = array(
                            "pid"      => $resID['ID'],
                            "pname"    => $item->getField('NAME'),
                            "cid"      => $resID['IBLOCK_SECTION_ID'],
                            "cname"    => $resCat['NAME'],
                            "price"    => $item->getPrice(),
                            "quantity" => intval($item->getQuantity()),
                            "currency" => 'RUB'
                        );

                        $responseResult['DDL']['lineItems'][] = [
                            "product"  => array(
                                'id'                   => (string)$resID['ID'],
                                'name'                 => $resID['NAME'],
                                'article'              => $item->getField('NAME'),
                                'currency'             => 'RUB',
                                'skuCode'              => (string)$item->getProductId(),
                                'unitPrice'            => (float)$item->getPrice(),
                                'unitSalePrice'        => (float)$item->getPrice(),
                                'category'             => $arSectionsByCurrent,
                                'availableForPickup'   => false, // пока false
                                'availableForDelivery' => false, // пока false
                                'categoryId'           => (string)$resID['IBLOCK_SECTION_ID'],
                                'url'                  => $resID['DETAIL_PAGE_URL'], // URL страницы с описанием товара
                                'imageUrl'             => CFile::GetPath($resScu["DETAIL_PICTURE"]), //URL большой картинки товара
                                'thumbnailUrl'         => CFile::GetPath($resScu["PREVIEW_PICTURE"]),
                                'stock'                => (int)$scuRes['QUANTITY'] // доступное количество
                            ),
                            "quantity" => intval($item->getQuantity()),
                            "subtotal" => $item->getPrice() * $item->getQuantity(),
                        ];

                    }
                    /** DigitalDataLayer end */
                    /** adspire */

                    // удаление купона
                    DiscountCouponsManager::delete($coupon);

                    // Send analytics
                    $orderId = $order->getId();
                    $sendFields = ['SEND_DATA_LAYER_ORDERS', 'SEND_RROCKET_ORDERS'];
                    foreach ($sendFields as $sendField) {
                        if ($_SESSION[$sendField][$orderId]) {
                            $responseResult[$sendField][$orderId] = $_SESSION[$sendField][$orderId];
                            unset($_SESSION[$sendField][$orderId]);
                        }
                    }
                }
            }
        }

        echo json_encode($responseResult);
    }
}

function getPropertyByCode($propertyCollection, $code)
{
    foreach ($propertyCollection as $property) {
        if ($property->getField('CODE') == $code) {
            return $property;
        }
    }
}