<?php

namespace Jamilco\Main;

use Bitrix\Main\Loader;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Sale\Order;
use Bitrix\Sale\ResultError;
use Bitrix\Sale\Internals\BasketTable;
use Jamilco\Main\Utils;

class Handlers
{
    const GROUP_ID_CHANGE_PRICES_IN_ORDER = 0; // ID группы пользователей "редактировать цены в заказах"
    const GROUP_ID_TO_ACCEPT_CHANGE_PRICES = 11; // ID группы пользователей "Подтерждать изменение цены в заказах"

    /**
     * @param $arFields
     *
     * @return array
     * формирование шаблона номеров заказа
     */
    function OnBuildAccountNumberTemplateList()
    {
        return array(
            'CODE' => 'JUICY_ORDER_ACCOUNTNUMBER',
            'NAME' => 'Шаблон номеров juicy (16000000)'
        );
    }

    /**
     * @param $ID
     * @param $template
     *
     * @return int
     *
     * генерация номера заказа
     *
     */
    function OnBeforeOrderAccountNumberSet($ID, $template)
    {
        if ($template == 'JUICY_ORDER_ACCOUNTNUMBER') {
            if (\CModule::IncludeModule('sale') && $ID > 0) {
                global $DB;
                $startNumber = 16000000;
                $maxLastID = 0;

                $rsOrder = \CSaleOrder::Getlist(array('ACCOUNT_NUMBER' => 'DESC'), array(), false, array('nTopCount' => 1));

                if ($arOrder = $rsOrder->Fetch()) {
                    $maxLastID = $arOrder['ACCOUNT_NUMBER'];
                }

                return ($maxLastID >= $startNumber) ? $maxLastID + 1 : $startNumber;
            }
        }
    }

    function OnAfterIBlockElementAddHandler($arFields = [])
    {
        if ($arFields['IBLOCK_ID'] == IBLOCK_SKU_ID && $arFields['NAME']) {
            \Jamilco\Main\Utils::updateOcsId($arFields['ID'], $arFields['NAME']);
        }

        if ($arFields['IBLOCK_ID'] == IBLOCK_CATALOG_ID) Utils::checkItemType($arFields['ID']);

        if ($arFields['IBLOCK_ID'] == IBLOCK_CHANGE_ORDER_ID) {
            $arEmails = [];
            $us = \Bitrix\Main\UserTable::getList(
                [
                    'filter' => [
                        "\Bitrix\Main\UserGroupTable:USER.GROUP_ID" => self::GROUP_ID_TO_ACCEPT_CHANGE_PRICES
                    ],
                    'select' => ['EMAIL']
                ]
            );
            while ($arUser = $us->Fetch()) {
                $arEmails[] = $arUser['EMAIL'];
            }

            \CEvent::Send(
                'CHANGE_ORDER_PRICE',
                's1',
                [
                    'NAME'      => $arFields['NAME'],
                    'ID'        => $arFields['ID'],
                    'IBLOCK_ID' => $arFields['IBLOCK_ID'],
                    'EMAILS'    => implode(', ', $arEmails),
                ],
                'N'
            );

            \CEvent::ExecuteEvents();
        }
    }

    function OnAfterIBlockElementUpdateHandler($arFields = [])
    {
        if ($arFields['IBLOCK_ID'] == IBLOCK_SKU_ID && $arFields['NAME']) {
            \Jamilco\Main\Utils::updateOcsId($arFields['ID'], $arFields['NAME']);
        }

        if ($arFields['IBLOCK_ID'] == IBLOCK_CATALOG_ID) Utils::checkItemType($arFields['ID']);

        if ($arFields['IBLOCK_ID'] == IBLOCK_CHANGE_ORDER_ID) {
            $arItem = \CIblockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $arFields['IBLOCK_ID'],
                    'ID'        => $arFields['ID'],
                ],
                false,
                ['nTopCount' => 1],
                [
                    'IBLOCK_ID',
                    'ID',
                    'DATE_ACTIVE_TO',
                    'PROPERTY_ORDER',
                    'PROPERTY_ITEMS',
                    'PROPERTY_ACCEPT',
                    'PROPERTY_USER',
                ]
            )->Fetch();

            if (!$arItem['PROPERTY_ACCEPT_VALUE']) {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], ['USER' => false]);
            }
            if ($arItem['PROPERTY_ACCEPT_VALUE'] && !$arItem['PROPERTY_USER_VALUE']) {
                // изменения подтвердили

                global $USER;

                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], ['USER' => $USER->GetID()]);

                $element = new \CIBlockElement();
                $element->Update($arItem['ID'], ['DATE_ACTIVE_TO' => \ConvertTimeStamp(false, 'FULL')]);

                Loader::includeModule('sale');

                $orderId = $arItem['PROPERTY_ORDER_VALUE'];

                foreach ($arItem['PROPERTY_ITEMS_VALUE'] as $key => $basketId) {
                    /**
                     * $arPrices[0] - старая цена
                     * $arPrices[1] - новая цена, которую хотели установить
                     */
                    $arPrices = explode(':', $arItem['PROPERTY_ITEMS_DESCRIPTION'][$key]);
                    $ba = BasketTable::getList(
                        [
                            'filter' => [
                                'ID'       => $basketId,
                                'ORDER_ID' => $orderId,
                            ],
                            'limit'  => 1,
                            'select' => ['ID', 'PRICE']
                        ]
                    );
                    if ($arBasket = $ba->Fetch()) {
                        if ($arBasket['PRICE'] == $arPrices[1]) continue;

                        BasketTable::Update($arBasket['ID'], ['PRICE' => $arPrices[1], 'CUSTOM_PRICE' => 'Y']);
                    }
                }

                \Jamilco\Main\Utils::refreshOrder($orderId);
            }
        }
    }

    function OnBeforeIBlockElementUpdateHandler(&$arFields = [])
    {
        if ($arFields['IBLOCK_ID'] == IBLOCK_CHANGE_ORDER_ID) {
            $pr = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $arFields['IBLOCK_ID'], 'CODE' => 'ACCEPT']);
            $arAcceptProp = $pr->Fetch();

            global $USER, $APPLICATION;

            $arUserGroups = $USER->GetUserGroupArray();

            $setConfirm = false;
            // детальная форма
            if (
                is_array($arFields['PROPERTY_VALUES'][$arAcceptProp['ID']]) &&
                $arFields['PROPERTY_VALUES'][$arAcceptProp['ID']][0]['VALUE']
            ) {
                $setConfirm = true;
            }
            // список элементов
            if (!is_array($arFields['PROPERTY_VALUES'][$arAcceptProp['ID']]) && $arFields['PROPERTY_VALUES'][$arAcceptProp['ID']]) $setConfirm = true;

            if ($setConfirm) {

                $arProp = \CIblockElement::GetProperty($arFields['IBLOCK_ID'], $arFields['ID'], [], ['CODE' => 'ACCEPT'])->Fetch();
                if ($arProp['VALUE']) {
                    $APPLICATION->throwException('Изменение цены уже было подтверждено ранее.');

                    return false;
                } else {
                    if (!in_array(self::GROUP_ID_TO_ACCEPT_CHANGE_PRICES, $arUserGroups)) {
                        $APPLICATION->throwException('Недостаточно прав для подтверждения изменения цены товаров.');

                        return false;
                    }
                }
            }
        }
    }

    /**
     * @param $arFields
     * email = логин
     */
    function OnBeforeUserRegister(&$arFields)
    {
        if ($arFields['LOGIN']) {
            $arFields['EMAIL'] = $arFields['LOGIN'];
        }
    }

    /**
     * @param $arFields
     * логин = email
     */
    function OnBeforeUserAdd(&$arFields)
    {
        if ($arFields['EMAIL']) {
            $arFields['LOGIN'] = $arFields['EMAIL'];
        }
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    function OnSalePropertyValueEntitySavedHandler(\Bitrix\Main\Event $event)
    {
        $arParams = $event->getParameters();
        $propertyValue = $arParams['ENTITY'];
        $arFields = $propertyValue->getPropertyObject()->getFields();
        if ($arFields['CODE'] == 'COUPONS') {
            global $needToReCreateManzanaOrder;
            if ($needToReCreateManzanaOrder == true) {
                $needToReCreateManzanaOrder = false;
                $orderId = $propertyValue->getCollection()->getOrder()->getId();

                self::reCreateManzanaOrder($orderId, '', true);
            }
        }
    }

    function OnSalePaymentSetFieldHandler(\Bitrix\Main\Event $event)
    {
        $arParams = $event->getParameters();
        if ($arParams['NAME'] == 'PAY_SYSTEM_ID' &&         // изменилась платежная система
            $arParams['OLD_VALUE'] &&                       // старое значение вообще было
            $arParams['VALUE'] != $arParams['OLD_VALUE']    // новое значение иное
        ) {
            global $needToReCreateManzanaOrder;
            $needToReCreateManzanaOrder = true;
        }
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    function OnSalePaymentEntitySavedHandler(\Bitrix\Main\Event $event)
    {
        $arParams = $event->getParameters();
        $payment = $arParams['ENTITY'];

        global $needToReCreateManzanaOrder;
        if ($needToReCreateManzanaOrder == true) {
            $needToReCreateManzanaOrder = false;
            $orderId = $payment->getOrderId();

            self::reCreateManzanaOrder($orderId, '', true);
        }
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    function OnSalePropertyValueSetFieldHandler(\Bitrix\Main\Event $event)
    {
        $propertiesToLog = ['F_ADDRESS', 'TARIF_LOCATION'];
        $entity = $event->getParameter('ENTITY');
        $property = $entity->getProperty();
        $value = $event->getParameter('VALUE');
        $oldValue = $event->getParameter('OLD_VALUE');

        if (in_array($property['CODE'], $propertiesToLog)) {
            $collection = $entity->getCollection();
            $order = $collection->getOrder();
            $orderId = $order->getId();
            $propertyId = $entity->getPropertyId();
            $propertyName = $entity->getName();

            if ($property['TYPE'] == 'LOCATION') {
                $value = \CSaleLocation::GetByID($value)['CITY_NAME'];
                $oldValue = \CSaleLocation::GetByID($oldValue)['CITY_NAME'];
            }

            \Bitrix\Sale\OrderHistory::addAction(
                'ORDER',
                $orderId,
                'PROPERTY_UPDATE',
                $orderId,
                $order,
                array(
                    'Изменилось свойство "'.$propertyName.'" на: '.$value.' ',
                    'NAME'      => $propertyName,
                    'VALUE'     => ''.$value.'',
                    'OLD_VALUE' => ''.$oldValue.''
                )
            );
        }

        if ($property['CODE'] == 'COUPONS' && $value != $oldValue) {
            global $needToReCreateManzanaOrder;
            $needToReCreateManzanaOrder = true;
        }
    }

    /**
     * перерассчитывает и пересоздает заказ в манзане
     *
     * @param int    $orderId
     * @param string $oldCard
     * @param bool   $allwaysSend
     *
     * @return bool
     */
    static public function reCreateManzanaOrder($orderId = 0, $oldCard = '', $allwaysSend = false, $step = false)
    {
        if (!$orderId) return false;

        global $skipReCreateManzanaOrder;
        if ($skipReCreateManzanaOrder == true) return false;

        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0); // отправим данные по заказу в Манзану
        $manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0); // отправляем заказы в Манзану
        if (!$manzanaUse || !$manzanaOrders) return false;

        Loader::includeModule('jamilco.loyalty');

        global $acceptOrderPriceChanging;
        $acceptOrderPriceChanging = true;
        if (!\Jamilco\Loyalty\Bonus::canChangeOrder($orderId)) return false;

        $cardNumber = \Jamilco\Loyalty\Bonus::checkCardInOrder($orderId);
        if (!$cardNumber && $step == 1) $step = false;
        if (!$cardNumber && $step == 2) return false;
        if (\Jamilco\Loyalty\Common::discountsAreMoved()) $step = false;

        if (!$step || $step == 1) {
            $res = Manzana::getInstance()->sendOrder($orderId, 'Fiscal', 'Rollback', 'RollbackCreate', $oldCard); // отмена заказа
        }

        // пересоздаем заказ только если он ранее был создан (и только что отменен)
        if ($res['RESULT'] == 'OK' || $allwaysSend || $step == 2) {
            // если карта задана и бонусы с нее были списан
            if ($cardNumber) {
                if (!$step || $step == 1) {
                    // 1. вернем в стоимость товаров списанные бонусы
                    \Jamilco\Loyalty\Bonus::getCardByOrder($orderId, false, true);
                }

                if ($step == 1) return false; // пересчет заказа не действует на этом же хите

                if (!\Jamilco\Loyalty\Common::discountsAreMoved()) \Jamilco\Main\Utils::refreshOrder($orderId);

                // 2. заново применим карту (автоматически спишется максимум доступных бонусов)
                \Jamilco\Loyalty\Bonus::getData($cardNumber, 'Y', $orderId, true); // добавим данные по бонусной карты в элементы корзины
            } else {
                \Jamilco\Loyalty\Bonus::getData(false, '', $orderId);
            }

            if ($step == 1) return false; // пересчет заказа не действует на этом же хите

            // заказ будет снова создан в Манзане (при необходимости будут списаны бонусы из цены)
            \Jamilco\Loyalty\Events::OnOrderSaveHandler($orderId, ['CREATE' => 'Y', 'SKIP_COUPONS' => 'Y']);
        }

        \Jamilco\Main\Utils::checkOrderPaymentSum($orderId); // проверка, чтобы цена в оплате совпадала с ценой заказа

        return true;
    }


    /**
    * @param \Bitrix\Main\Event $event
    * Если заказ новый, то ....
     *
     *  * Переводим Доставку на GoodsRU, если установлено св-во
    */
    function OnOrderSave(\Bitrix\Main\Event $event)
    {

        $isNew = $event->getParameter("IS_NEW");

        if ($isNew) {
            /** @var $order  Order */
            $order = $event->getParameter("ENTITY");

            // добвление в подписчики - без купона
            $propertyCollection = $order->getPropertyCollection();
            $emailPropValue = $propertyCollection->getUserEmail();
            if($emailPropValue)
                \Jamilco\Main\Subscribers::setSubcscribers($emailPropValue->getValue(), "N");

            // Переводим Доставку на GoodsRU, если установлено св-во
            $props = $order->getPropertyCollection()->GetArray();
            foreach($props["properties"] as $arProp){
                if($arProp['CODE'] == 'DELIVERY_GOODSRU' && $arProp['VALUE'][0] == 'Y'){
                    $shipmentCollection = $order->getShipmentCollection();
                    foreach ($shipmentCollection as $shipment){
                        if(!$shipment->isSystem()){
                            $result = $shipment->setFields(
                                [
                                    'DELIVERY_ID'   => GOODS_RU_DELIVERY,
                                    'DELIVERY_NAME' => 'Служба доставки GOODS'
                                ]
                            );
                            if ($result->isSuccess()) {
                                $order->save();
                            }
                            break;
                        }
                    }
                    break;
                }
            }
        }
    }

    /**
     * Событие после регистрации пользователя
     *
     * @param array $arFields
     */
    function onAfterUserRegister(&$arFields)
    {
        if ($arFields['USER_ID'] > 0) {
            /** Параметр проверяется в DigitalDataLayer\Manager */
            $GLOBALS['USER']->setParam('IS_REGISTER_EVENT', true);
        }

        // для нового пользователя с картой - подписываем с купоном
        $userSubscriber = \Bitrix\Main\UserTable::getList(array(
            'filter' => array(
                '=ID' => $arFields['USER_ID'],
            ),
            'limit' => 1,
            'select' => array('*', 'UF_BONUS_CARD_NUMBER'),
        ))->fetch();
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        $setActiveSubscribe = ($request->get('register_subscribe') == 'Y') ? 'Y' : 'N';
        if (!empty($userSubscriber["UF_BONUS_CARD_NUMBER"])) {
            \Jamilco\Main\Subscribers::setSubcscribers($arFields['LOGIN'], "Y",$setActiveSubscribe);
        } else {
            \Jamilco\Main\Subscribers::setSubcscribers($arFields['LOGIN'], "N",$setActiveSubscribe);
        }
    }

    /**
     * Событие после изменения статуса заказа
     *
     * @param \Bitrix\Main\Event $event
     */
    function onSaleStatusOrderChange(\Bitrix\Main\Event $event)
    {
        $order = $event->getParameter('ENTITY');
        $statusId = $event->getParameter('VALUE');
        $oldStatusId = $event->getParameter('OLD_VALUE');

        // статусы по которым нужно отменять заказ
        $statusFilter = [
            'J' => 'Cтатус заказа изменен на "#STATUS_NAME#"',
            'D' => 'Cтатус заказа изменен на "#STATUS_NAME#"',
        ];

        global $ocsIs;
        if ($ocsIs) $statusFilter['C'] = 'Отмена api OSC';

        if ($statusId != $oldStatusId && array_key_exists($statusId, $statusFilter)) {
            Loader::includeModule('sale');

            $rsStatus = \CSaleStatus::GetList(
                [],
                [
                    'LID' => LANGUAGE_ID,
                    'ID'  => $statusId
                ],
                false,
                ['nTopCount' => 1],
                ['ID', 'NAME']
            );
            $arStatus = $rsStatus->Fetch();
            $statusName = $arStatus['NAME'];

            $orderId = $order->getId();
            if (!$order->isCanceled()) {
                // ALL-559. При частичном возврате из Сollect передается статус Отмена.
                // вызов \CSaleOrder::CancelOrder снимет оплату, что неверно.
                // из документации: \CSaleOrder::CancelOrder - Если заказ был оплачен, то при отмене снимается флаг оплаты с возвращением денег на счет покупателя.)
                // поэтому отменяем заказ только, если он не оплачен
                $isPaid = false;
                $paymentCollection = $order->getPaymentCollection();
                foreach ($paymentCollection as $payment){
                    if($payment->isPaid()){
                        $isPaid = true;
                        break;
                    }
                }
                if(!$isPaid) {
                    global $skipCancelOrderComment;
                    $skipCancelOrderComment = true;
                    $cancelReason = str_replace(['#STATUS_NAME#'], [$statusName], $statusFilter[$statusId]);
                    \CSaleOrder::CancelOrder($orderId, 'Y', $cancelReason);
                }
            }
        }

        // массив статусов, которые означают "заказ завершен"
        $statusComplete = [
            'F', // Выполнен
            'I', // Выдан в РМ
            'PF', // Предзаказ. Выполнен
        ];
        if (in_array($statusId, $statusComplete) && !in_array($oldStatusId, $statusComplete)) {
            $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0); // отправим данные по заказу в Манзану
            $manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0); // отправляем заказы в Манзану
            if ($manzanaUse && $manzanaOrders) {
                $orderId = $order->getId();
                Manzana::getInstance()->sendOrder($orderId, 'Fiscal', 'Sale', 'Close'); // отправит информацию о том, что заказ оплачен
            }
        }

        // Доставка трансп. компанией
        $statusAtDelivery = ['S'];
        if (in_array($statusId, $statusAtDelivery) && !in_array($oldStatusId, $statusAtDelivery)
        ) {
            \Jamilco\Main\OrderTracking::sendTrackNumber($order->getId());
        }

        // ALL-478. Заказ выполнен - отправка купонов 8.12.2020 - 31.12.2020
        $statusFinish = ['F', 'I'];
        if (in_array($statusId, $statusFinish) && !in_array($oldStatusId, $statusFinish)) {
            $orderId = $order->getId();
            $untilDate = \COption::GetOptionString('jamilco.main', 'coupon_send_until_date', '');
            if ($untilDate && time() <= MakeTimeStamp($untilDate, 'DD.MM.YYYY')) {
                \Jamilco\Main\Utils::sendCouponFromIb($orderId, 'SALE_ORDER_COMPLETE');
            }
        }

    }

    /**
     * Событие после оплаты заказа
     *
     * Изменение статуса оплаченного заказа на A - 'Принят',
     * если статус заказа был EP - 'Онлайн оплата по уведомлению'
     *
     * @param int    $orderId id заказа
     * @param string $value   статус оплаты Y | N
     */
    function onSalePayOrder($orderId, $value)
    {
        global $checkOnlinePayment;
        if($checkOnlinePayment) return;
        Loader::includeModule('sale');

        $order = \Bitrix\Sale\Order::load($orderId);

        // проверим валидность изменения оплаты
        // (разрешена только скриптам и выделенным пользователям)
        $checkPayment = \Jamilco\Main\OnlinePayment::checkOnlinePayment($orderId);

        if (!$checkPayment && $order->isPaid()) { // отменяем оплату
            \Jamilco\Main\OnlinePayment::cancelPayment($order);
            return;
        }

        if ($order && $order->isPaid() && $order->getField('STATUS_ID') == 'EP') {
            $order->setField('STATUS_ID', 'A');
            $order->save();
        }

        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0); // отправим данные по заказу в Манзану
        $manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0); // отправляем заказы в Манзану
        if ($manzanaUse && $manzanaOrders) {
            if ($order->isPaid()) {
                Manzana::getInstance()->sendOrder($orderId, 'Fiscal', 'Sale', 'Payment'); // отправит информацию о том, что заказ оплачен
            } else {
                Manzana::getInstance()->sendOrder($orderId, 'Fiscal', 'Return', 'Return'); // отправит информацию о том, что отменена оплата
            }
        }

        /**
         * Отправляем номер оплаченного заказа в OCS
         */
        if ($value == 'Y') {
            $ocs = Oracle::getInstance();
            $ocs->setOrderPaid($orderId);
        }
    }

    function OnOrderCancel(\Bitrix\Main\Event $event)
    {
        $order = $event->getParameter('ENTITY');
        if ($order->isCanceled()) {
            $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0); // отправим данные по заказу в Манзану
            $manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0); // отправляем заказы в Манзану
            if ($manzanaUse && $manzanaOrders) {
                if (\Jamilco\Main\Utils::hasOrderCanceledPayment($order->getId())) {
                    // в заказе есть отмененная оплата, отменить заказ нельзя, можно только закрыть
                    Manzana::getInstance()->sendOrder($order->getId(), 'Fiscal', 'Sale', 'Close');
                } else {
                    Manzana::getInstance()->sendOrder($order->getId(), 'Fiscal', 'Rollback', 'RollbackCreate'); // отправит информацию о том, что заказ отменен
                }
            }
        }
    }

    /**
     * запрещает изменение товарных цен в заказах для пользователей, не входящих в группу "Редактирование цен в заказах"
     *
     * @param $event
     *
     * @return EventResult
     */
    function onBeforeBasketUpdate($event)
    {
        $result = new EventResult;

        global $USER, $APPLICATION, $mayToChangePrice;
        if ($mayToChangePrice === true) return $result;

        $ID = $event->getParameter("id");
        $ID = $ID['ID'];

        $arFields = $event->getParameter("fields");

        $ba = BasketTable::getList(
            [
                'filter' => ['ID' => $ID, '!ORDER_ID' => false],
                'select' => ['ID', 'ORDER_ID', 'CUSTOM_PRICE', 'PRICE'],
                'limit'  => 1,
            ]
        );
        if ($arBasket = $ba->Fetch()) {

            // если не изменялась цена, то пропускаем
            if (array_key_exists('PRICE', $arFields) && ($arBasket['CUSTOM_PRICE'] == 'Y' || $arFields['CUSTOM_PRICE'] == 'Y')) {
                //$order = Order::load($arBasket['ORDER_ID']);
                //$statusId = $order->getField('STATUS_ID');

                $arUserGroups = $USER->GetUserGroupArray();
                /*
                // если пользователь НЕ в группе "Администраторы" и НЕ в группе "Редактирование цен в заказах"
                if (!in_array(1, $arUserGroups) &&
                    !in_array(self::GROUP_ID_CHANGE_PRICES_IN_ORDER, $arUserGroups) &&
                    !in_array(self::GROUP_ID_TO_ACCEPT_CHANGE_PRICES, $arUserGroups)
                ) {
                    // запрещаем редактирование цен
                    $result->addError(
                        new ResultError('Access denied for changing items prices in orders', 'SALE_BASKET_ITEM_PRICE_CHANGE')
                    );
                    $APPLICATION->ThrowException('Недостаточно прав для редактирования цен товаров в заказе');
                } else {
                */
                $arBasket['PRICE'] = (float)$arBasket['PRICE'];
                $diff = abs($arFields['PRICE'] - $arBasket['PRICE']);
                if ($diff > 0 && $diff / $arBasket['PRICE'] >= 0.31 && !in_array(self::GROUP_ID_TO_ACCEPT_CHANGE_PRICES, $arUserGroups)) {
                    $itemId = false;
                    $el = \CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID'       => IBLOCK_CHANGE_ORDER_ID,
                            'PROPERTY_ORDER'  => $arBasket['ORDER_ID'],
                            'PROPERTY_ACCEPT' => false,
                        ],
                        false,
                        ['nTopCount' => 1],
                        ['ID', 'PROPERTY_ITEMS']
                    );
                    if ($arItem = $el->Fetch()) {
                        $itemId = $arItem['ID'];

                        $arPropsItem = [];
                        foreach ($arItem['PROPERTY_ITEMS_VALUE'] as $key => $val) {
                            if ($arBasket['ID'] == $val) continue;
                            $arPropsItem[] = [
                                'VALUE'       => $val,
                                'DESCRIPTION' => $arItem['PROPERTY_ITEMS_DESCRIPTION'][$key],
                            ];
                        }

                        $arPropsItem[] = [
                            'VALUE'       => $arBasket['ID'],
                            'DESCRIPTION' => $arBasket['PRICE'].':'.$arFields['PRICE'],
                        ];

                        \CIBlockElement::SetPropertyValuesEx($arItem['ID'], IBLOCK_CHANGE_ORDER_ID, ['ITEMS' => $arPropsItem]);
                    } else {
                        $el = new \CIblockElement();
                        $itemId = $el->Add(
                            [
                                'IBLOCK_ID'        => IBLOCK_CHANGE_ORDER_ID,
                                'NAME'             => 'Заказ '.$arBasket['ORDER_ID'],
                                'ACTIVE'           => 'Y',
                                'DATE_ACTIVE_FROM' => \ConvertTimeStamp(false, 'FULL'),
                                'PROPERTY_VALUES'  => [
                                    'ORDER' => $arBasket['ORDER_ID'],
                                    'ITEMS' => [
                                        [
                                            'VALUE'       => $arBasket['ID'],
                                            'DESCRIPTION' => $arBasket['PRICE'].':'.$arFields['PRICE']
                                        ]
                                    ]
                                ]
                            ]
                        );
                    }

                    if ($itemId && !in_array($itemId, $_SESSION['CHANGE_PRICE'])) $_SESSION['CHANGE_PRICE'][] = $itemId;

                    $result->addError(
                        new ResultError('Need the accepting for the price changing', 'SALE_BASKET_ITEM_PRICE_CHANGE')
                    );
                    $APPLICATION->ThrowException('Редактирование цен возможно только через подтверждение');
                }
                //}
            }
        }

        return $result;
    }

    /**
     * запрещает отмену заказа без указания причины
     *
     * @param $event
     *
     * @return \Bitrix\Main\EventResult
     */
    function OnSaleBeforeOrderCanceledHandler($event)
    {
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        $comment = $request->get('comment') ?: $request->get('REASON_CANCELED');

        global $skipCancelOrderComment;

        $order = $event->getParameter('ENTITY');
        $arOrder = $order->getFields()->getValues();
        if ($arOrder['CANCELED'] == 'N') $skipCancelOrderComment = true;

        if (!$comment && !$skipCancelOrderComment && ADMIN_SECTION) {
            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                new \Bitrix\Sale\ResultError('Не указана причина отмены заказа', 'SALE_ORDER_DENIED_CANCEL'),
                'sale'
            );
        } else {
            if ($comment) {
                $orderId = $request->get('orderId');
                $pr = \CSaleOrderPropsValue::GetOrderProps($orderId);
                while ($arProp = $pr->Fetch()) {
                    $arOrder['PROPS'][$arProp['ID']] = $arProp;
                }

                $arProps = [];
                $pr = \CSaleOrderPropsValue::GetOrderProps($orderId);
                while ($arProp = $pr->Fetch()) {
                    $arProps[$arProp['ORDER_PROPS_ID']] = $arProp;
                }

                $propID = 0; // ID свойства для причины отмены заказа
                $arReasonList = \Jamilco\Main\CancelOrder::getReasonList($propID);

                $value = false;
                // для записи значения свойства типа "список" нужно указать порядковый номер значения по сортировке по ID
                foreach ($arReasonList as $key => $arVal) {
                    if ($comment == $arVal['NAME']) {
                        $value = $arVal['VALUE'];
                        break;
                    }
                }

                if ($value) {
                    if ($arProps[$propID]) {
                        \CSaleOrderPropsValue::Update($arProps[$propID]['ID'], ['VALUE' => $value]);
                    } else {
                        $rsOrder = \CSaleOrderProps::GetList([], ['ID' => $propID]);
                        if ($arOrderProp = $rsOrder->Fetch()) {
                            \CSaleOrderPropsValue::Add(
                                array
                                (
                                    "ORDER_ID"       => $orderId,
                                    "ORDER_PROPS_ID" => $arOrderProp['ID'],
                                    "NAME"           => $arOrderProp['NAME'],
                                    "CODE"           => $arOrderProp['CODE'],
                                    "VALUE"          => $value,
                                )
                            );
                        }
                    }
                }
            }
        }

        return true;
    }

    function OnBeforeLocalRedirectHandler(&$url, $skip_security_check, $bExternal)
    {
        if (ADMIN_SECTION) {
            if ($_SESSION['CHANGE_PRICE']) {
                $_SESSION['CHANGE_PRICE'] = array_unique($_SESSION['CHANGE_PRICE']);
                $id = array_shift($_SESSION['CHANGE_PRICE']);
                $url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.IBLOCK_CHANGE_ORDER_ID.'&type=technical&ID='.$id.'&lang=ru&find_section_section=-1&WF=Y';
            } else {
                if (substr_count($url, '/bitrix/admin/sale_order.php') && substr_count($_SERVER['HTTP_REFERER'], '/bitrix/admin/sale_order_edit.php')) {
                    if ($_REQUEST['target'] == 'list' && $_REQUEST['ID'] > 0) {
                        $url = str_replace('sale_order.php', 'sale_order_view.php', $url);
                        $url .= '&ID='.$_REQUEST['ID'];
                    }
                }
            }
        }
    }

    function OnBeforeUserAddHandler(&$arFields = [])
    {
        $spamError = false;
        if ($arFields['NAME'] && substr_count($arFields['NAME'], 'http')) $spamError = true;
        if ($arFields['LAST_NAME'] && substr_count($arFields['LAST_NAME'], 'http')) $spamError = true;

        if ($spamError) {
            global $APPLICATION;
            $APPLICATION->throwException("Зарегистрирована попытка нечестной регистрации");

            return false;
        }
    }

    function OnAdminListDisplayHandler(&$list)
    {
        if ($list->table_id == 'tbl_sale_order') {
            $orderIDs = [];
            foreach ($list->aRows as $arRow) {
                if ($arRow->aFields['PROP_BAD']['view']['value'] != 'Нет') {
                    $orderId = str_replace('№', '', strip_tags($arRow->aFields['ID']['view']['value']));
                    $orderIDs[$orderId] = $arRow->aFields['PROP_BAD']['view']['value'];
                }
            }

            echo '<script>window.markOrders = '.\Bitrix\Main\Web\Json::encode($orderIDs).';</script>';
            \CJSCore::Init(['jquery']);
            \Bitrix\Main\Page\Asset::getInstance()->addJs('/local/modules/jamilco.main/admin/bad-orders.js');
        }
    }

    /**
     * Перед созданием заказа
     *
     * @param $event
     *
     * @return \Bitrix\Main\EventResult|void
     */
    function OnSaleOrderBeforeSavedHandler($event){
        $order = $event->getParameter("ENTITY");
        $orderId = $order->getId();

        if (!$orderId) // новый заказ
        {
            // проверка пользоват. данных на наличие в ЧС
            $blackListOrder = new \Jamilco\Main\BlackListOrder($order);
            if(!$blackListOrder->checkOrder()) {
                // очищаем корзину пользователя
                \CSaleBasket::DeleteAll(\CSaleBasket::GetBasketUserID());
                return new \Bitrix\Main\EventResult(
                    \Bitrix\Main\EventResult::ERROR,
                    new \Bitrix\Sale\ResultError('BLACK_LIST_USER', 'SALE_EVENT_WRONG_ORDER'),
                    'sale'
                );
            }
        }
    }
}
