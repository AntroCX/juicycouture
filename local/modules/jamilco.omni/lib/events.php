<?php
namespace Jamilco\Omni;

use \Bitrix\Sale\Internals\BasketTable;
use \Bitrix\Sale\Internals\PaymentTable;
use \Bitrix\Sale\Internals\ShipmentTable;
use \Bitrix\Sale\Internals\ShipmentItemTable;
use \Bitrix\Sale\Internals\BasketPropertyTable;
use \Bitrix\Sale\Fuser;
use \Jamilco\Omni\Channel;

/**
 * Class Events
 * @package Jamilco\Omni
 */
class Events
{
    const MODULE_ID = 'jamilco.omni';

    const DELIVERY_OCS = 'courier:courier';
    const DELIVERY_OZON = 'new19:profile';
    const DELIVERY_KCE = 'new22:profile';
    const DELIVERY_PICKUP = 20;
    const DELIVERY_DAY = 21;

    public function addMenuItem(&$adminMenu, &$moduleMenu)
    {
        global $APPLICATION;
        $RIGHT = $APPLICATION->GetGroupRight(self::MODULE_ID);
        if ($RIGHT == 'D') return false;

        // пункт меню "Omni Channel"
        $arPage = array(
            "text"     => "Omni Channel",
            "url"      => "/bitrix/admin/jamilco_omni_channel.php",
            "more_url" => array(),
            "title"    => "Панель управления Omni Channel"
        );

        $arDir = array(
            "parent_menu" => "global_menu_store",       // поместим в раздел "Магазин"
            "section"     => "jamilco",
            "sort"        => 1,                         // сортировка пункта меню
            "url"         => "",                        // ссылка на пункте меню
            "text"        => 'Jamilco',                 // текст пункта меню
            "title"       => 'Настройка модулей Jamilco', // текст всплывающей подсказки
            "icon"        => "jamilco_menu_icon",       // малая иконка
            "page_icon"   => "jamilco_page_icon",       // большая иконка
            "items_id"    => "jamilco",                 // идентификатор ветви
            "items"       => array($arPage)
        );

        $found = false;
        foreach ($moduleMenu as $key => $arOne) {
            if ($arOne['section'] == $arDir['section']) {
                $found = true;
                $moduleMenu[$key]['items'][] = $arPage;
            }
        }

        if (!$found) $moduleMenu[] = $arDir;

        // пункт меню "Загрузка купонов из файла"
        // добавим его в блок Маркетинг - Товарный маркетинг
        foreach ($moduleMenu as $key => $arOne) {
            if ($arOne['parent_menu'] == 'global_menu_marketing' && $arOne['items_id'] == 'menu_sale_discounts') {
                $moduleMenu[$key]['items'][] = array(
                    'text'  => 'Загрузка купонов из файла',
                    'title' => 'Загрузка купонов из файла',
                    'url'   => 'jamilco_discount_coupons_load.php',
                );
            }
        }
    }

    /**
     * после создания заказа пропишем свойство OMNI_CHANNEL и изменим оплату, если не все товары доступны к доставке
     *
     * @param int   $orderId
     * @param array $arOrder
     * @param array $arParams
     */
    public function OnSaleComponentOrderOneStepCompleteHandler($orderId = 0, $orderFields = [], $arParams = [])
    {
        if (!$orderId) return false;
        $arOrder = \CSaleOrder::GetByID($orderId);

        $delayNoDelivery = \COption::GetOptionInt('jamilco.omni', 'delay.nodeliveried', 0);

        // свойства
        $pr = \CSaleOrderPropsValue::GetOrderProps($orderId);
        while ($arProp = $pr->Fetch()) {
            $arOrder['PROPS'][$arProp['CODE']] = $arProp;
        }

        // товары
        $ba = \CSaleBasket::GetList(
            array('PRODUCT_ID' => 'ASC'),
            array(
                'ORDER_ID' => $orderId,
            ),
            false,
            false,
            array('ID', 'PRODUCT_ID', 'PRICE', 'QUANTITY')
        );
        while ($arBasket = $ba->Fetch()) {
            // свойства
            $pr = BasketPropertyTable::getList(['filter' => ['BASKET_ID' => $arBasket['ID']]]);
            while ($arProp = $pr->Fetch()) {
                unset($arProp['BASKET_ID']);
                unset($arProp['ID']);
                $arBasket['PROPS'][$arProp['CODE']] = $arProp;
            }
            $arOrder['PRODUCTS_ID'][] = $arBasket['PRODUCT_ID'];
            $arOrder['BASKET_ITEMS'][] = $arBasket;
        }

        if ($delayNoDelivery) {
            $ba = BasketTable::getList(
                [
                    'filter' => [
                        'FUSER_ID' => Fuser::getId(),
                        'ORDER_ID' => null,
                        'LID'      => SITE_ID,
                        //'DELAY'    => 'Y', // товары из корзины, без условия отложенности
                    ],
                    'select' => ['ID', 'PRODUCT_ID']
                ]
            );
            while ($arBasket = $ba->Fetch()) {
                $arOrder['PRODUCTS_ID'][] = $arBasket['PRODUCT_ID']; // только для того, чтобы подцепился нужный массив OMNI-данных
            }
        }

        sort($arOrder['PRODUCTS_ID']);

        // проверим omni-запись в сессии
        $locationId = ($arOrder['PROPS']['LOCATION']['VALUE']) ?: $arOrder['PROPS']['TARIF_LOCATION']['VALUE'];
        $omniSessionKey = $locationId.'-'.implode('-', $arOrder['PRODUCTS_ID']);
        $arOmni = $_SESSION['OMNI'][$omniSessionKey];
        $deliveryType = self::getDeliveryType($arOrder['DELIVERY_ID']);

        if ($deliveryType && $arOmni) {
            $arMayItems = [];
            if ($deliveryType == 'curier') {
                $arMayItems = $arOmni['DELIVERY'];
                if (!$arOmni['DELIVERY'] && $arOmni['OMNI_DELIVERY']) $arMayItems = $arOmni['OMNI_DELIVERY'];
            }
            if ($deliveryType == 'day') $arMayItems = $arOmni['FAST_DELIVERY'];
            if ($deliveryType == 'ozon') $arMayItems = $arOmni['PICK_POINT'];
            if ($deliveryType == 'pickup') {
                $shopId = $arOrder['PROPS']['STORE_ID']['VALUE'];
                $arMayItems = array_keys($arOmni['SHOPS'][$shopId]);
            }

            // цену, которую можно оплатить = стоимость доступных к доставке товаров + стоимость доставки
            $payPrice = $arOrder['PRICE_DELIVERY'];
            foreach ($arOrder['BASKET_ITEMS'] as $arBasket) {
                if (in_array($arBasket['ID'], $arMayItems)) {
                    $payPrice += $arBasket['PRICE'] * $arBasket['QUANTITY'];

                    if (array_key_exists('NO_DELIVERY', $arBasket['PROPS'])) {
                        unset($arBasket['PROPS']['NO_DELIVERY']);

                        self::delBasketProps($arBasket['ID']);
                        \CSaleBasket::Update($arBasket['ID'], ['PROPS' => $arBasket['PROPS']]);
                    }

                } else {
                    if ($delayNoDelivery) {
                        // недоступные к доставке товары отложены, их надо вернуть, чтоб можно было дальше оформить на них заказ
                        BasketHandlers::unDelayAll();
                    } else {
                        // пометим товары, которые нельзя доставить выбранным способом
                        $arBasket['PROPS']['NO_DELIVERY'] = [
                            'NAME'  => 'Не доставляется выбранным способом',
                            'VALUE' => 'Y',
                            'CODE'  => 'NO_DELIVERY',
                            'SORT'  => '500',
                        ];
                        self::delBasketProps($arBasket['ID']);
                        \CSaleBasket::Update($arBasket['ID'], ['PROPS' => $arBasket['PROPS']]);
                    }
                }
            }

            // сохраним цену в единственную (уже созданную автоматически "оплату")
            // получим заказ и его коллекцию оплат
            $pay = PaymentTable::getList(
                array(
                    'filter' => array('ORDER_ID' => $orderId)
                )
            );
            if ($arPay = $pay->Fetch()) {
                if ($arPay['SUM'] != $payPrice) {
                    PaymentTable::Update($arPay['ID'], array('SUM' => $payPrice));
                }
            }

            // оставим в "отгрузке" только те товары, которые можно доставить
            $del = ShipmentTable::getList(['order' => ['ID' => 'DESC'], 'filter' => ['ORDER_ID' => $orderId]]);
            if ($arDelivery = $del->Fetch()) {
                $shIt = ShipmentItemTable::getList(['filter' => ['ORDER_DELIVERY_ID' => $arDelivery['ID']]]);
                while ($arOne = $shIt->Fetch()) {
                    if (!in_array($arOne['BASKET_ID'], $arMayItems)) ShipmentItemTable::Delete($arOne['ID']);
                }
            }

            // пометим заказ OMNI_CHANNEL меткой
            $omniChannel = [];
            if ($deliveryType == 'curier') {
                if ($arOmni['DELIVERY']) $omniChannel[] = 'Delivery';
                if (!$arOmni['DELIVERY'] && $arOmni['OMNI_DELIVERY']) $omniChannel[] = 'OMNI_Delivery';
            }
            if ($deliveryType == 'day') $omniChannel[] = 'DayDelivery';
            if ($deliveryType == 'ozon') $omniChannel[] = 'Pick_Point';
            if ($deliveryType == 'pickup') {
                $shopId = $arOrder['PROPS']['STORE_ID']['VALUE'];
                foreach ($arOmni['SHOPS'][$shopId] as $itemId => $typeOne) {
                    $omniChannel[] = Channel::getTypeByShopType($typeOne);
                }
            }
            $omniChannel = array_unique($omniChannel);
            $omniChannel = implode(', ', $omniChannel);

            // логирование признака Omni
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'].'/local/log/order_omni_.'.date('y.m.d').'.log',
                print_r($arOrder,1)."\r\n".print_r($_SESSION['OMNI'],1)."\r\n------------\r\n\r\n", FILE_APPEND
            );
            if(empty($omniChannel)) {
                $receivers = "galiev@jamilco.ru";
                $body = date("d.m.Y  H:i:s") . ".\r\n" . print_r($arOrder, 1) . "\r\n". print_r($_SESSION['OMNI'],1);
                $subject = $_SERVER['SERVER_NAME'] . ". empty omni order_id=".$arOrder['ID'];
                $headers = "From: " . COption::GetOptionString('main', 'email_from', '') . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=utf-8\r\n";
                mail($receivers, $subject, $body, $headers);
            }

            if ($arOrder['PROPS']['OMNI_CHANNEL']) {
                \CSaleOrderPropsValue::Update($arOrder['PROPS']['OMNI_CHANNEL']['ID'], array('VALUE' => $omniChannel));
            } else {
                $rsOrder = \CSaleOrderProps::GetList(array(), array('CODE' => 'OMNI_CHANNEL'));
                if ($arOrderProp = $rsOrder->Fetch()) {
                    \CSaleOrderPropsValue::Add(
                        array
                        (
                            "ORDER_ID"       => $orderId,
                            "ORDER_PROPS_ID" => $arOrderProp['ID'],
                            "NAME"           => $arOrderProp['NAME'],
                            "CODE"           => $arOrderProp['CODE'],
                            "VALUE"          => $omniChannel,
                        )
                    );
                }
            }

            if (substr_count($omniChannel, 'OMNI_Delivery')) {
                \CEvent::Send(
                    "SALE_OMNI_DELIVERY",
                    SITE_ID,
                    [
                        'SALE_EMAIL' => \COption::GetOptionString('sale', 'order_email', ''),
                        'ORDER_ID'   => $orderId,
                        'OMNI_TYPE'  => $omniChannel,
                    ]
                );

                \CEvent::CheckEvents();
            } elseif (substr_count($omniChannel, 'DayDelivery')) {
                \CEvent::Send(
                    "SALE_OMNI_DAY_DELIVERY",
                    SITE_ID,
                    [
                        'SALE_EMAIL' => \COption::GetOptionString('sale', 'order_email', ''),
                        'ORDER_ID'   => $orderId,
                        'OMNI_TYPE'  => $omniChannel,
                    ]
                );
                \CEvent::CheckEvents();
            }
        }
    }

    public static function getDeliveryType($deliveryId = 0)
    {
        if ($deliveryId == self::DELIVERY_OCS) return 'curier';
        if ($deliveryId == self::DELIVERY_KCE) return 'curier';
        if ($deliveryId == self::DELIVERY_OZON) return 'ozon';
        if ($deliveryId == self::DELIVERY_PICKUP) return 'pickup';
        if ($deliveryId == self::DELIVERY_DAY) return 'day';

        return false;
    }

    public static function delBasketProps($basketId = 0)
    {
        $pr = BasketPropertyTable::getList(['filter' => ['BASKET_ID' => $basketId]]);
        while ($arProp = $pr->Fetch()) {
            BasketPropertyTable::Delete($arProp['ID']);
        }
    }

    public static function OnOrderSaveHandler($ID, $arFields, $orderFields, $isNew)
    {
        self::checkUpsale($ID, $isNew);
    }

    /**
     * выстанавливает флаг заказа UpSale
     *
     * @param $orderId
     * @param $isNew
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function checkUpsale($orderId, $isNew)
    {
        $upsaleUserGroupId = \COption::GetOptionString("jamilco.omni", 'upsale.group', '');
        $upsaleItemSections = \COption::GetOptionString("jamilco.omni", 'upsale.section', '');
        $upsaleItemSections = explode(',', $upsaleItemSections);
        if (!count($upsaleItemSections) || !$upsaleUserGroupId) return true;

        $isUpSale = false;
        $arOrder = \CSaleOrder::GetByID($orderId);
        $arUserGroups = \CUser::GetUserGroup($arOrder['USER_ID']);

        if (in_array($upsaleUserGroupId, $arUserGroups)) {
            // заказ создан членом фокусной группы, проверяем все товары на принадлежность к фокусным разделам
            $ba = BasketTable::getList(
                [
                    'filter' => ['ORDER_ID' => $arOrder['ID']],
                    'select' => ['PRODUCT_ID']
                ]
            );
            while ($arBasket = $ba->Fetch()) {
                if (self::checkUpsaleItem($arBasket['PRODUCT_ID'], $upsaleItemSections)) {
                    $isUpSale = true;
                    break;
                }
            }
        } elseif (!$isNew) {
            // заказ создан обычным пользователем, проверим по истории заказов, добавлял ли член фокусной группы нужный товар
            $arItemID = [];
            $oh = \CSaleOrderChange::GetList([], ['ORDER_ID' => $orderId, 'TYPE' => ['BASKET_ADDED', 'BASKET_REMOVED']]);
            while ($arHistory = $oh->Fetch()) {
                $arHistoryData = unserialize($arHistory['DATA']);
                if ($arHistory['TYPE'] == 'BASKET_ADDED') $arItemID[$arHistoryData['PRODUCT_ID']] = $arHistoryData['PRODUCT_ID'];
                if ($arHistory['TYPE'] == 'BASKET_REMOVED') unset($arItemID[$arHistoryData['PRODUCT_ID']]);
            }

            foreach ($arItemID as $itemId) {
                if (self::checkUpsaleItem($itemId, $upsaleItemSections)) {
                    $isUpSale = true;
                    break;
                }
            }
        }

        if ($isNew && !$isUpSale) return true;

        $pr = \CSaleOrderPropsValue::GetOrderProps($orderId);
        while ($arProp = $pr->Fetch()) {
            $arOrder['PROPS'][$arProp['CODE']] = $arProp;
        }

        $upSale = ($isUpSale) ? 'Y' : 'N';
        if ($arOrder['PROPS']['UPSALE']) {
            \CSaleOrderPropsValue::Update($arOrder['PROPS']['UPSALE']['ID'], ['VALUE' => $upSale]);
        } else {
            $rsOrder = \CSaleOrderProps::GetList([], ['CODE' => 'UPSALE']);
            if ($arOrderProp = $rsOrder->Fetch()) {
                \CSaleOrderPropsValue::Add(
                    [
                        "ORDER_ID"       => $orderId,
                        "ORDER_PROPS_ID" => $arOrderProp['ID'],
                        "NAME"           => $arOrderProp['NAME'],
                        "CODE"           => $arOrderProp['CODE'],
                        "VALUE"          => $upSale,
                    ]
                );
            }
        }
    }

    /**
     * проверяет, входит ли товар в указанные группы
     *
     * @param int   $itemId
     * @param array $arSections
     *
     * @return bool
     */
    public static function checkUpsaleItem($itemId = 0, $arSections = [])
    {
        if (!$itemId || !$arSections) return false;

        $el = \CIBlockElement::GetList(
            [],
            ['ID' => $itemId],
            false,
            ['nTopCount' => 1],
            [
                'ID',
                'PROPERTY_CML2_LINK',
                'PROPERTY_CML2_LINK.IBLOCK_SECTION_ID',
            ]
        );
        if ($arItem = $el->Fetch()) {
            $se = \CIBlockSection::GetNavChain(0, $arItem['PROPERTY_CML2_LINK_IBLOCK_SECTION_ID'], ['ID']);
            while ($arSect = $se->Fetch()) {
                if (in_array($arSect['ID'], $arSections)) {
                    return true;
                    break;
                }
            }
        }

        return false;
    }

    /**
     * при обновлении продукта (остатки и резерв) происходит пересохранение данных по доступным способам доставки
     *
     * @param int   $ID
     * @param array $arFields
     */
    function OnProductUpdateHandler($ID = 0, $arFields = [])
    {
        global $skipProductUpdateHandler;
        if (!$skipProductUpdateHandler) {
            if (!$arFields['IBLOCK_ID']) {
                $arItem = \CIblockElement::GetList([], ['ID' => $ID], false, ['nTopCount' => 1], ['IBLOCK_ID', 'ID'])->Fetch();
                $arFields['IBLOCK_ID'] = $arItem['IBLOCK_ID'];
            }

            if ($arFields['IBLOCK_ID'] == IBLOCK_SKU_ID) {
                Channel::reSaveCityAvailables(true, $ID);
            }
        }
    }
}