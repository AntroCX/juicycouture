<?php

namespace Jamilco\Loyalty;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Sale;
use \Bitrix\Sale\Order;
use \Jamilco\Loyalty\Bonus;
use \Jamilco\Main\Manzana;

/**
 * Class Events
 * @package Jamilco\Loyalty
 */
class Events
{
    const MODULE_ID = 'jamilco.loyalty';

    static $propertiesToLog = ['PROGRAMM_LOYALTY_CARD', 'PROGRAMM_LOYALTY_WRITEOFF', 'COUPONS']; // список свойств для лога

    const CASH_PAY_SYSTEM = 3; // оплата наличными
    const PICKUP_DELIVERY = 20; // доставка самовывозом из РМ

    /**
     * построение меню в админке
     *
     * @param $adminMenu
     * @param $moduleMenu
     */
    public static function addMenuItem(&$adminMenu, &$moduleMenu)
    {
        global $APPLICATION;
        $RIGHT = $APPLICATION->GetGroupRight(self::MODULE_ID);
        if ($RIGHT == 'D') return false;

        $arPage = [
            "text"     => "Программа лояльности",
            "url"      => "/bitrix/admin/jamilco_loyalty_settings.php",
            "more_url" => [],
            "title"    => "Программа лояльности",
        ];

        $arDir = [
            "parent_menu" => "global_menu_store",       // поместим в раздел "Магазин"
            "section"     => "jamilco",
            "sort"        => 1,                         // сортировка пункта меню
            "url"         => "",                        // ссылка на пункте меню
            "text"        => 'Jamilco',                 // текст пункта меню
            "title"       => 'Настройка модулей Jamilco', // текст всплывающей подсказки
            "icon"        => "jamilco_menu_icon",       // малая иконка
            "page_icon"   => "jamilco_page_icon",       // большая иконка
            "items_id"    => "jamilco",                 // идентификатор ветви
            "items"       => [$arPage],
        ];

        $found = false;
        foreach ($moduleMenu as $key => $arOne) {
            if ($arOne['section'] == $arDir['section']) {
                $found = true;
                $moduleMenu[$key]['items'][] = $arPage;
            }
        }

        if (!$found) $moduleMenu[] = $arDir;
    }

    /**
     * перед созданием заказа
     *
     * @param $arFields
     */
    public static function OnBeforeOrderAddHandler(&$arFields)
    {

    }

    /**
     * после созданием заказа добавим свойства, спишем бонусы из цены
     *
     * @param int   $ID       - ID заказа
     * @param array $arFields - ['CREATE' == 'Y'] создает заказ в Манзане в любом случае
     * @param array $arParams
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ObjectNotFoundException
     */
    public static function OnOrderSaveHandler($ID = 0, $arFields = [], $arParams = [])
    {
        if (!$ID) return false;

        global $skipReCreateManzanaOrder;
        $skipReCreateManzanaOrder = true; // блокирует каскадные пересоздания заказа в Манзане

        $order = Sale\Order::load($ID);

        $arPaySystemIDs = $order->getPaySystemIdList();
        $arDeliveryIDs = $order->getDeliveryIdList();

        $pr = \CSaleOrderPropsValue::GetOrderProps($ID);
        while ($arProp = $pr->Fetch()) {
            $arOrderProps[$arProp['CODE']] = $arProp;
        }

        $reducePrice = true; // снизить цену элементам корзины на величину списанных бонусов

        if (in_array(self::PICKUP_DELIVERY, $arDeliveryIDs)) $reducePrice = false;

        // если был применен купон, то не применяем бонусы

        if (\Jamilco\Loyalty\Common::discountsAreMoved()) {
            $arManzanaCoupons = [];
            if ($arOrderProps['COUPONS']['VALUE']) {
                $arManzanaCoupons = explode(',', $arOrderProps['COUPONS']['VALUE']);
                TrimArr($arManzanaCoupons, true);
            } elseif ($_SESSION['MANZANA_COUPONS']) {
                foreach ($_SESSION['MANZANA_COUPONS'] as $coupon => $arOne) {
                    if ($arOne['TYPE'] == 'OK') $arManzanaCoupons[] = $coupon; // добавляем только валидные купоны
                }
            }
            if ($arManzanaCoupons) $reducePrice = false;

        } else {
            $discountData = $order->getDiscount()->getApplyResult();
            foreach ($discountData['DISCOUNT_LIST'] as $arDiscount) {
                if ($arDiscount['USE_COUPONS'] == 'Y') $reducePrice = false;
            }
        }

        $arData = Bonus::getCardByOrder($ID, $reducePrice);

        // проставим количество списанных бонусов в свойства заказа
        $arOrderSetProps = [
            'PROGRAMM_LOYALTY_CARD'     => $arData['CARD'],
            'PROGRAMM_LOYALTY_WRITEOFF' => ($reducePrice) ? $arData['BONUSES'] : 0,
        ];
        if (!defined('ADMIN_SECTION') && !$arOrderSetProps['PROGRAMM_LOYALTY_CARD'] && $_SESSION['LOYALTY_CARD_NUMBER']) $arOrderSetProps['PROGRAMM_LOYALTY_CARD'] = $_SESSION['LOYALTY_CARD_NUMBER'];

        if (!$arOrderProps[$arProp['CODE']]['ITEM_LIST']) $arOrderSetProps['ITEM_LIST'] = self::getOrderItemList($ID);

        if (\Jamilco\Loyalty\Common::discountsAreMoved() && $arFields['SKIP_COUPONS'] != 'Y' && $arManzanaCoupons) {
            $arOrderSetProps['COUPONS'] = implode(',', $arManzanaCoupons);

        }

        $rsProps = \CSaleOrderProps::GetList([], ['CODE' => array_keys($arOrderSetProps)]);
        while ($arrProp = $rsProps->Fetch()) {
            if ($arOrderProps[$arrProp['CODE']]) {
                if ($arOrderProps[$arrProp['CODE']]['VALUE'] != $arOrderSetProps[$arrProp['CODE']]) {
                    \CSaleOrderPropsValue::Update($arOrderProps[$arrProp['CODE']]['ID'], ['VALUE' => $arOrderSetProps[$arrProp['CODE']]]);
                }
            } else {
                \CSaleOrderPropsValue::Add(
                    [
                        "ORDER_ID"       => $ID,
                        "ORDER_PROPS_ID" => $arrProp['ID'],
                        "NAME"           => $arrProp['NAME'],
                        "CODE"           => $arrProp['CODE'],
                        "VALUE"          => $arOrderSetProps[$arrProp['CODE']]
                    ]
                );
            }
        }

        // если в заказе была бонусная карта или купон
        // Отправляем любой заказ по запросу Покровской
        $arFields['CREATE'] = 'Y';
        if ($arOrderSetProps['PROGRAMM_LOYALTY_CARD'] || $arOrderSetProps['COUPONS'] || $arFields['CREATE'] == 'Y') {
            $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0); // отправим данные по заказу в Манзану
            $manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0); // отправляем заказы в Манзану
            if ($manzanaUse && $manzanaOrders) {
                Manzana::getInstance()->sendOrder($ID); // лишний раз передаем Calc-запрос, чтобы залогировать его в лог заказа
                Manzana::getInstance()->sendOrder($ID, 'Fiscal', 'Sale', 'Create');
            }
        }

        unset($_SESSION['MANZANA_COUPONS']);

        return true;
    }

    public static function OnOrderUpdateHandler($ID = 0, $arFields = [], $orderFields = [], $isNew = false)
    {
        if (!$ID) return false;

        // исключаем заказы Goods
        if($arFields['DELIVERY_ID'] && $arFields['DELIVERY_ID'] == GOODS_RU_DELIVERY) return;

        if ($isNew) \Jamilco\Main\BadOrder::checkOrder($ID);

        if (ADMIN_SECTION !== true) return true; // только для запросов из админки
        if ($isNew) return true; // только для изменений ранее созданных заказов

        $order = Order::load($ID);
        if ($order->isCanceled() || $order->isPaid()) return true;

        $pr = \CSaleOrderPropsValue::GetList([], ['ORDER_ID' => $ID, 'CODE' => 'ITEM_LIST']);
        $arProp = $pr->Fetch();

        $basketList = self::getOrderItemList($ID);
        if ($arProp['ID'] && $basketList == $arProp['VALUE_ORIG']) return true; // товарный состав не изменился

        // товарный состав изменился, сохраним его
        if ($arProp['ID']) {
            \CSaleOrderPropsValue::Update($arProp['ID'], ['VALUE' => $basketList]);
        } else {
            $pr = \CSaleOrderProps::GetList([], ['CODE' => 'ITEM_LIST']);
            $arItemListProp = $pr->Fetch();
            \CSaleOrderPropsValue::Add(
                [
                    "ORDER_ID"       => $ID,
                    "ORDER_PROPS_ID" => $arItemListProp['ID'],
                    "NAME"           => $arItemListProp['NAME'],
                    "CODE"           => $arItemListProp['CODE'],
                    "VALUE"          => $basketList
                ]
            );
        }

        \Jamilco\Main\Handlers::reCreateManzanaOrder($ID, '', true);

        return true;
    }

    public static function getOrderItemList($ID)
    {
        $arOut = [];
        $ba = Sale\Internals\BasketTable::getList(
            [
                'order'  => ['PRODUCT_ID' => 'ASC'],
                'filter' => ['ORDER_ID' => $ID],
                'select' => ['PRODUCT_ID', 'QUANTITY']
            ]
        );
        while ($arBasket = $ba->Fetch()) {
            $arOut[] = $arBasket['PRODUCT_ID'].':'.(int)$arBasket['QUANTITY'];
        }

        return implode(',', $arOut);
    }

    function OnSalePropertyValueSetFieldHandler(\Bitrix\Main\Event $event)
    {
        $entity = $event->getParameter('ENTITY');
        $property = $entity->getProperty();
        if (in_array($property['CODE'], self::$propertiesToLog)) {
            $collection = $entity->getCollection();
            $order = $collection->getOrder();
            $orderId = $order->getId();
            $propertyName = $entity->getName();
            $value = $event->getParameter('VALUE');
            $oldValue = $event->getParameter('OLD_VALUE');

            if ($value != $oldValue) {

                global $USER;
                $userId = $USER->GetID();
                if (!$userId) $userId = $order->getUserId();
                \Bitrix\Sale\Internals\OrderChangeTable::add(
                    [
                        'ORDER_ID'  => $orderId,
                        'TYPE'      => 'ORDER_COMMENTED', // отображается в списке "значимых изменений"
                        'DATA'      => serialize(['COMMENTS' => 'Изменилось свойство "'.$propertyName.'": '.$oldValue.' -> '.$value]),
                        'USER_ID'   => $userId,
                        'ENTITY'    => 'ORDER',
                        'ENTITY_ID' => $orderId,
                    ]
                );
            }
        }
    }

    function OrderPropsValueOnUpdateHandler(\Bitrix\Main\Event $event)
    {
        $arParams = $event->getParameters();

        $arProp = \CSaleOrderPropsValue::GetList([], ['ID' => $arParams['primary']['ID']])->Fetch();
        $propertyName = $arProp['NAME'];
        $orderId = $arProp['ORDER_ID'];
        $oldValue = $arProp['VALUE'];
        $value = $arParams['fields']['VALUE'];

        if (in_array($arProp['CODE'], self::$propertiesToLog)) {
            if ($value != $oldValue) {

                global $USER;
                $userId = $USER->GetID();
                if (!$userId) {
                    $order = Order::load($orderId);
                    $userId = $order->getUserId();
                }
                \Bitrix\Sale\Internals\OrderChangeTable::add(
                    [
                        'ORDER_ID'  => $orderId,
                        'TYPE'      => 'ORDER_COMMENTED', // отображается в списке "значимых изменений"
                        'DATA'      => serialize(['COMMENTS' => 'Изменилось свойство "'.$propertyName.'": '.$oldValue.' -> '.$value]),
                        'USER_ID'   => $userId,
                        'ENTITY'    => 'ORDER',
                        'ENTITY_ID' => $orderId,
                    ]
                );
            }
        }
    }
}