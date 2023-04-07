<?php

namespace Jamilco\Main;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Sale;
use \Bitrix\Sale\Basket;
use \Bitrix\Sale\Fuser;
use \Bitrix\Sale\Order;
use \Bitrix\Sale\Delivery;
use \Bitrix\Sale\DiscountCouponsManager;
use \Bitrix\Sale\Helpers\Admin\OrderEdit;
use \Bitrix\Highloadblock\HighloadBlockTable as HL;

class Utils
{
    private static $blockDir = '/local/blocks/';

    /**
     * проверка доступности экспресс-доставки
     *
     * @return bool
     */
    public static function checkTimeForExpressDelivery()
    {
        $canDelivery = true;

        // доставка день-в-день работает с 8-30 до 17-00 по будням
        $arDate = [
            'h' => (int)date('G'),
            'm' => (int)date('i'),
            'd' => (int)date('w'),
        ];
        if ($arDate['d'] === 0 || $arDate['d'] == 6) $canDelivery = false; // не работает по выходным
        if ($arDate['h'] < 8 || $arDate['h'] > 16) $canDelivery = false; // до 8:00 и после 17:00
        if ($arDate['h'] == 8 && $arDate['m'] < 30) $canDelivery = false; // от 8:00 и до 8:30

        return $canDelivery;
    }


    /**
     * добавляяем подарки из Манзаны
     *
     * @param array $arGifts
     *
     * @return bool - true только в том случае, что только что был добавлен товар-подарок в корзину
     */
    public static function addGift($arGifts = [], $ruleId = '')
    {
        $_SESSION['MANZANA_GIFT'] = false;

        // проверим, добавлен ли уже подарок из манзаны в корзину
        $basketGiftId = false;
        $basketGiftProductId = false;
        $ba = Sale\Internals\BasketTable::getList(
            [
                'filter' => ['FUSER_ID' => Sale\Fuser::getId(), 'ORDER_ID' => false, 'LID' => SITE_ID],
                'select' => ['ID', 'PRODUCT_ID']
            ]
        );
        while ($arBasket = $ba->Fetch()) {
            $pr = Sale\Internals\BasketPropertyTable::getList(['filter' => ["BASKET_ID" => $arBasket['ID']]]);
            while ($arProp = $pr->Fetch()) {
                $arBasket['PROPS'][$arProp['CODE']] = $arProp['VALUE'];
            }

            if ($arBasket['PROPS']['MANZANA_GIFT'] == $ruleId) {
                if ($basketGiftId) {
                    // подарок уже определён, второго подарка не может быть
                    Sale\Internals\BasketTable::Delete($arBasket['ID']);
                } else {
                    $basketGiftId = $arBasket['ID'];
                    $basketGiftProductId = $arBasket['PRODUCT_ID'];
                }
            }
        }

        if ($basketGiftProductId) {
            if (array_key_exists($basketGiftProductId, $arGifts)) {
                // подарок является одним из доступных подарков
            } else {
                // подарок не подходит к подаркам из манзаны, удаляем
                Sale\Internals\BasketTable::Delete($basketGiftId);
                $basketGiftId = $basketGiftProductId = false;
            }
        }

        if ($basketGiftId) return false;

        if ($arGifts && !$basketGiftId) {
            /**
             * 0 - атоматически добавлять подарок
             * 1 - дать выбор покупателю
             */
            $manzanaGift = \COption::GetOptionInt("jamilco.loyalty", "manzanagift", 0);
            if (count($arGifts) == 1) $manzanaGift = 0; // если подарок один, то сразу его добавляем
            if ($manzanaGift) {

                $_SESSION['MANZANA_GIFT'][$ruleId] = $arGifts;

                return false;
            } else {
                // выберем первый из возможных подарков (можно переделать и дать выбор пользователю)
                foreach ($arGifts as $offerId => $quantity) {
                    global $needReload;
                    $needReload = true;

                    self::addToBasket(
                        $offerId,
                        [
                            [
                                'NAME'  => 'Подарок из Манзаны',
                                'CODE'  => 'MANZANA_GIFT',
                                'VALUE' => $ruleId,
                                'SORT'  => 200,
                            ]
                        ],
                        $quantity,
                        0,
                        true
                    );
                    break;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * удаляет все подарки, которые больше не могут быть
     *
     * @param array $arGiftRules
     *
     * @return bool
     */
    public static function deleteGifts($arGiftRules = [])
    {
        $ba = Sale\Internals\BasketTable::getList(
            [
                'filter' => ['FUSER_ID' => Sale\Fuser::getId(), 'ORDER_ID' => false, 'LID' => SITE_ID],
                'select' => ['ID']
            ]
        );
        while ($arBasket = $ba->Fetch()) {
            $pr = Sale\Internals\BasketPropertyTable::getList(['filter' => ["BASKET_ID" => $arBasket['ID']]]);
            while ($arProp = $pr->Fetch()) {
                $arBasket['PROPS'][$arProp['CODE']] = $arProp['VALUE'];
            }

            if ($arBasket['PROPS']['MANZANA_GIFT'] > '' && !in_array($arBasket['PROPS']['MANZANA_GIFT'], $arGiftRules)) {
                global $needReload;
                $needReload = true;

                Sale\Internals\BasketTable::Delete($arBasket['ID']);
            }
        }

        return true;
    }

    /**
     * добавляет товар в корзину
     *
     * @param int   $offerId
     * @param array $arProps
     */
    public static function addToBasket($offerId = 0, $arProps = [], $quantity = 1, $orderId = 0, $skipGiftDeleting = false)
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        Loader::includeModule('sale');
        Loader::includeModule('highloadblock');

        $sku_arr = [];
        $mod_arr = [];
        $sku = \CIblockElement::GetList(
            [],
            array("IBLOCK_ID" => IBLOCK_SKU_ID, "=ID" => $offerId),
            false,
            ['nTopCount' => 1],
            array(
                "ID",
                "IBLOCK_ID",
                "NAME",
                "PROPERTY_CML2_LINK",
                "PROPERTY_SIZES_SHOES",
                "PROPERTY_SIZES_CLOTHES",
                "PROPERTY_SIZES_RINGS",
                "CATALOG_GROUP_1",
                "CATALOG_GROUP_2",
            )
        );
        if ($ob = $sku->Fetch()) {
            $pr = \CIBlockElement::GetProperty($ob['IBLOCK_ID'], $ob['ID'], [], ['CODE' => 'COLOR']);
            $arColor = $pr->Fetch();
            $hlblock = HL::getList(array('filter' => array('TABLE_NAME' => $arColor['USER_TYPE_SETTINGS']['TABLE_NAME'])))->Fetch();
            $entity = HL::compileEntity($hlblock);
            $dataClass = $entity->getDataClass();
            $res = $dataClass::getList(['filter' => ['UF_XML_ID' => $arColor['VALUE']],]);
            $arProp = $res->Fetch();
            $ob['PROPERTY_COLOR_VALUE'] = $arProp['UF_NAME'];

            $sku_arr = $ob;
        }

        $model = \CIblockElement::GetList(
            [],
            array("IBLOCK_ID" => IBLOCK_CATALOG_ID, "ID" => $sku_arr['PROPERTY_CML2_LINK_VALUE']),
            false,
            ['nTopCount' => 1],
            array("ID", "NAME", "DETAIL_PAGE_URL")
        );
        if ($ob = $model->GetNext()) $mod_arr = $ob;

        $arProps[] = array(
            "NAME"  => "Цвет",
            "CODE"  => "COLOR",
            "VALUE" => $sku_arr['PROPERTY_COLOR_VALUE'],
            "SORT"  => 1,
        );

        if ($sku_arr['PROPERTY_SIZES_CLOTHES_VALUE']) {
            $arProps[] = array(
                "NAME"  => "Размер одежды",
                "CODE"  => "SIZES_CLOTHES",
                "VALUE" => $sku_arr['PROPERTY_SIZES_CLOTHES_VALUE'],
                "SORT"  => 2,
            );
        } elseif ($sku_arr['PROPERTY_SIZES_SHOES_VALUE']) {
            $arProps[] = array(
                "NAME"  => "Размер обуви",
                "CODE"  => "SIZES_SHOES",
                "VALUE" => $sku_arr['PROPERTY_SIZES_SHOES_VALUE'],
                "SORT"  => 2,
            );
        } elseif ($sku_arr['PROPERTY_SIZES_RINGS_VALUE']) {
            $arProps[] = array(
                "NAME"  => "Размер колец",
                "CODE"  => "SIZES_RINGS",
                "VALUE" => $sku_arr['PROPERTY_SIZES_RINGS_VALUE'],
                "SORT"  => 2,
            );
        }

        /*
        $hasGiftProp = false;
        foreach ($arProps as $arProp) {
            if ($arProp['CODE'] == 'GIFT') $hasGiftProp = true;
        }
        if (!$hasGiftProp) {
            $arProps[] = [
                "NAME"  => "Подарок",
                "CODE"  => "GIFT",
                "VALUE" => 0,
                "SORT"  => 500,
            ];
        }
        */

        $arFields = [];

        $res = \Add2BasketByProductID($sku_arr['ID'], $quantity, $arFields, $arProps);

        if (!$orderId && !$skipGiftDeleting) {
            self::deleteGifts(); // удаляем все подарки
        }

        $_SESSION['MANZANA_BASKET'] = false;

        return $res;
    }

    private function load_from_array($ar_name = [])
    {
        foreach ($ar_name as $name) {
            self::load_css($name);
            self::load_js($name);
        }
    }

    private function load_from_name($name = '')
    {
        self::load_css($name);
        self::load_js($name);
    }


    private function load_css($name = '')
    {
        $path = $_SERVER['DOCUMENT_ROOT'].self::$blockDir.$name;
        foreach (glob($path.'/*.css', GLOB_BRACE) as $key => $file) {
            $GLOBALS['APPLICATION']->SetAdditionalCSS(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file));
        }
    }

    private function load_js($name = '')
    {
        $path = $_SERVER['DOCUMENT_ROOT'].self::$blockDir.$name;
        foreach (glob($path.'/*.min.js', GLOB_BRACE) as $key => $file) {
            $GLOBALS['APPLICATION']->AddHeadScript(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file));
        }
    }

    public static function load($name = '')
    {
        if (is_array($name)) {
            self::load_from_array($name);
        } else {
            self::load_from_name($name);
        }
    }

    /**
     * Возвращает информацию о стоимости товаров в корзине с учетом скидки по купону
     * Если указан ID товара, тогда информация только по одному товару, а не всей корзине
     *
     * @param int    $deliveryId - ID службы доставки
     * @param string $coupon     - купон из правил работы с корзиной
     * @param int    $productId  - ID товара для которого нужно узнать скидку
     *
     * @return array
     */
    public static function getPreOrderData($deliveryId = 0, $coupon = '', $productId = 0)
    {
        if (!$deliveryId) $deliveryId = Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
        $coupon = trim($coupon);

        $result = [
            'data'   => [],
            'errors' => []
        ];

        global $USER;

        $isOneProductMode = (int)$productId ? true : false;

        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        Loader::includeModule('sale');

        if ($coupon > '') {

            if (method_exists('\Jamilco\Loyalty\Common', 'discountsAreMoved') && \Jamilco\Loyalty\Common::discountsAreMoved()) {
                if (!array_key_exists($coupon, $_SESSION['MANZANA_COUPONS'])) {
                    $_SESSION['MANZANA_COUPONS'] = [$coupon => ['TEXT' => 'Купон не обработан', 'TYPE' => 'ERROR']];
                }

                global $manzanaDeliveryId;
                $manzanaDeliveryId = $deliveryId;

                \Jamilco\Loyalty\Bonus::getData();

                if ($_SESSION['MANZANA_COUPONS'][$coupon]['TYPE'] == 'ERROR') {
                    $result['errors'] = ['Купон не применим'];

                    return $result;
                }

            } else {
                if (DiscountCouponsManager::isExist($coupon)) {
                    DiscountCouponsManager::add($coupon);
                } else {
                    $result['errors'] = ['Купон не применим'];

                    return $result;
                }
            }
        }

        $siteId = $siteId = Context::getCurrent()->getSite();
        $fUserId = Fuser::getId();
        $userId = $USER->GetID() ? $USER->GetID() : \CSaleUser::GetAnonymousUserID();

        $checkBasketItemId = 0;

        if ($isOneProductMode) {
            // отложим все товары
            if (!self::delayAllBasket($productId)) {
                // добавляем в корзину товар
                $checkBasketItemId = self::addToBasket($productId);
            }
        }

        $order = Order::create($siteId, $userId);
        $order->isStartField();

        $basket = Basket::loadItemsForFUser($fUserId, $siteId)->getOrderableItems();
        $order->setBasket($basket);

        $shipmentCollection = $order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();

        $service = Delivery\Services\Manager::getById($deliveryId);
        $shipment->setFields(
            [
                'DELIVERY_ID'   => $service['ID'],
                'DELIVERY_NAME' => $service['NAME'],
                'CURRENCY'      => $order->getCurrency(),
            ]
        );

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        foreach ($order->getBasket() as $item) {
            $shipmentItem = $shipmentItemCollection->createItem($item);
            $shipmentItem->setQuantity($item->getQuantity());
        }

        $order->doFinalAction(true);

        foreach ($order->getBasket() as $basketItem) {

            $pr = \CPrice::GetList(
                [],
                [
                    "CATALOG_GROUP_ID" => 1,
                    "PRODUCT_ID"       => $basketItem->getProductId()
                ]
            );
            $arPrice = $pr->Fetch();
            $basePrice = $basketItem->getBasePrice();
            if ($basePrice < $arPrice['PRICE']) $basePrice = $arPrice['PRICE'];
            $basePrice = \Bitrix\Catalog\Product\Price::roundPrice(1, $basePrice, 'RUB');

            $result['data']['items'][] = [
                'id'            => $basketItem->getId(),
                'price'         => $basePrice,
                'priceDiscount' => $basketItem->getPrice(),
            ];
            $result['data']['discountSum'] += ($basePrice - $basketItem->getPrice()) * $basketItem->getQuantity();
            $result['data']['totalDiscountSum'] += $basketItem->getPrice() * $basketItem->getQuantity();
            $result['data']['totalSum'] += $basePrice * $basketItem->getQuantity();
        }

        $result['data']['deliveryPrice'] = $order->getDeliveryPrice();
        $result['data']['deliveryPeriod'] = $shipment->calculateDelivery()->getPeriodDescription();

        if ($isOneProductMode) {
            // удалить добавленный товар и вернуть обратно отложенные товары
            \CSaleBasket::Delete($checkBasketItemId);
            self::unDelayBasket();
        }

        return $result;
    }

    public static function delayAllBasket($productId = 0)
    {
        $res = false;

        $siteId = $siteId = Context::getCurrent()->getSite();
        $fUserId = Fuser::getId();

        $rsBasket = \CSaleBasket::GetList(
            [],
            [
                'FUSER_ID' => $fUserId,
                'LID'      => $siteId,
                'DELAY'    => 'N',
                'ORDER_ID' => false,
                'CAN_BUY'  => 'Y'
            ]
        );
        while ($arBasket = $rsBasket->Fetch()) {
            if ($productId && $arBasket['PRODUCT_ID'] == $productId) {
                $res = true;
            } else {
                \CSaleBasket::Update($arBasket['ID'], ['DELAY' => 'Y']);
            }
        }

        return $res;
    }

    public static function unDelayBasket($productId = 0)
    {
        $siteId = $siteId = Context::getCurrent()->getSite();
        $fUserId = Fuser::getId();
        $rsBasket = \CSaleBasket::GetList(
            [],
            [
                'FUSER_ID' => $fUserId,
                'LID'      => $siteId,
                'ORDER_ID' => false,
            ]
        );
        while ($arBasket = $rsBasket->Fetch()) {
            if ($productId && $arBasket['PRODUCT_ID'] == $productId) {
                \CSaleBasket::Delete($arBasket['ID']);
            } elseif ($arBasket['DELAY'] == 'Y') {
                \CSaleBasket::Update($arBasket['ID'], ['DELAY' => 'N']);
            }
        }
    }

    /**
     * обновляет цены в существующем заказе
     *
     * @param int $orderId
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public static function refreshOrder($orderId = 0)
    {
        if (!$orderId) return false;

        Loader::includeModule('sale');

        global $skipReCreateManzanaOrder;
        $skipReCreateManzanaOrder = true;

        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/lib/helpers/admin/orderedit.php");

        $order = Order::load($orderId);

        $userId = $order->getUserId();
        OrderEdit::initCouponsData($userId, $orderId, null);
        OrderEdit::saveCoupons($userId, []);

        $discount = $order->getDiscount();

        \Bitrix\Sale\DiscountCouponsManager::clearApply(true);
        \Bitrix\Sale\DiscountCouponsManager::useSavedCouponsForApply(true);
        $discount->setOrderRefresh(true);
        $discount->setApplyResult([]);

        if (\Jamilco\Loyalty\Common::discountsAreMoved()) {
            // скидки перенесены в манзану, цены по товарам не обновляем
        } else {
            $basket = $order->getBasket();
            $basket->refreshData(['PRICE', 'COUPONS']);
        }

        $res = $discount->calculate();

        $discountData = $res->getData();
        if (!empty($discountData) && is_array($discountData)) {
            $order->applyDiscount($discountData);
        }

        if (!$order->isCanceled() && !$order->isPaid()) {
            if (($paymentCollection = $order->getPaymentCollection()) && count($paymentCollection) == 1) {
                if (($payment = $paymentCollection->rewind()) && !$payment->isPaid()) {
                    $payment->setFieldNoDemand('SUM', $order->getPrice());
                }
            }
        }

        $order->save();

        return true;
    }

    /**
     * обновляет свойство "Минимальная цена" в товарах (для сортировки)
     * @return array
     */
    public static function checkItemPrices()
    {
        $start = \microtime(true);
        $arLog = [];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => IBLOCK_CATALOG_ID,
                'ACTIVE'    => 'Y',
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'PROPERTY_MINIMUM_PRICE']
        );
        while ($arItem = $el->Fetch()) {
            $of = \CIblockElement::GetList(
                [
                    'CATALOG_PRICE_2' => 'DESC', // если скидочная цена есть, то она и есть маленькая
                    'CATALOG_PRICE_1' => 'ASC',
                ],
                [
                    'IBLOCK_ID'          => IBLOCK_SKU_ID,
                    'ACTIVE'             => 'Y',
                    'PROPERTY_CML2_LINK' => $arItem['ID'],
                    [
                        'LOGIC'                   => 'OR',
                        '>CATALOG_QUANTITY'       => 0,
                        '!PROPERTY_RETAIL_CITIES' => false,
                    ]
                ],
                false,
                ['nTopCount' => 1],
                ['ID', 'CATALOG_GROUP_2', 'CATALOG_PRICE_1']
            );
            if ($arOffer = $of->Fetch()) {
                $price = ($arOffer['CATALOG_PRICE_2']) ?: $arOffer['CATALOG_PRICE_1'];
                if ($arItem['PROPERTY_MINIMUM_PRICE_VALUE'] != $price) {
                    \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], ['MINIMUM_PRICE' => $price]);
                    $arLog['UPDATE']++;
                }
            }
        }

        $arLog['TIME'] = \microtime(true) - $start;

        return $arLog;
    }

    /**
     * устанавливает свойство "тип товара" в товарах
     *
     * @param int $id - передать ID, если нужно установить значение в конкретном товаре
     */
    public static function checkItemType($id = 0)
    {
        $arItemFilter = [
            'IBLOCK_ID'     => IBLOCK_CATALOG_ID,
            'PROPERTY_TYPE' => false,
        ];
        if ($id) {
            $arItemFilter['ID'] = $id;
            //unset($arItemFilter['PROPERTY_TYPE']);
        }
        $el = \CIblockElement::GetList(
            [],
            $arItemFilter,
            false,
            false,
            ['ID', 'IBLOCK_SECTION_ID']
        );
        while ($arItem = $el->Fetch()) {
            $arData['ITEMS'][$arItem['ID']] = $arItem['IBLOCK_SECTION_ID'];
        }

        if (!$arData['ITEMS']) return false;

        $se = \CIblockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            [
                'IBLOCK_ID' => IBLOCK_CATALOG_ID,
                'ACTIVE'    => 'Y',
            ],
            false,
            ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'UF_ADDITIONAL']
        );
        while ($arSect = $se->Fetch()) {
            $add = ($arSect['UF_ADDITIONAL']) ? true : false;
            if (!$add && $arSect['IBLOCK_SECTION_ID'] && $arData['SECTS'][$arSect['IBLOCK_SECTION_ID']]['ADDITIONAL']) $add = true;
            $arData['SECTS'][$arSect['ID']] = [
                'ID'         => $arSect['ID'],
                'NAME'       => $arSect['NAME'],
                'ADDITIONAL' => $add,
            ];
        }

        $en = \CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => IBLOCK_CATALOG_ID, 'CODE' => 'TYPE']);
        while ($arEnum = $en->Fetch()) {
            $arData['ITEM_TYPE'][$arEnum['XML_ID']] = $arEnum['ID'];
        }
        $arProp = \CIBlockProperty::GetList([], ["IBLOCK_ID" => IBLOCK_CATALOG_ID, "CODE" => 'TYPE'])->Fetch();

        $ibpenum = new \CIBlockPropertyEnum();
        foreach ($arData['SECTS'] as $sectID => $arSect) {
            if ($arSect['ADDITIONAL']) continue;

            $xmlId = 'sect'.$sectID;
            if (!array_key_exists($xmlId, $arData['ITEM_TYPE'])) {
                $newId = $ibpenum->Add(
                    [
                        'PROPERTY_ID' => $arProp['ID'],
                        'VALUE'       => $arSect['NAME'],
                        'XML_ID'      => $xmlId,
                    ]
                );
                $arData['ITEM_TYPE'][$xmlId] = $newId;
            }
        }

        foreach ($arData['ITEMS'] as $itemID => $sectID) {
            $arSect = $arData['SECTS'][$sectID];
            if ($arSect['ADDITIONAL']) {
                $se = \CIBlockElement::GetElementGroups($itemID, true, ['ID']);
                while ($arAddSect = $se->Fetch()) {
                    if (!$arData['SECTS'][$arAddSect['ID']]['ADDITIONAL']) {
                        $arSect = $arData['SECTS'][$arAddSect['ID']];
                        break;
                    }
                }
            }
            if (!$arSect['ADDITIONAL']) {
                $itemTypeID = $arData['ITEM_TYPE']['sect'.$arSect['ID']];
                \CIBlockElement::SetPropertyValuesEx($itemID, IBLOCK_CATALOG_ID, ['TYPE' => $itemTypeID]);
            }
        }
    }

    /**
     * обновляет OCS_ID
     *
     * @param int    $offerId
     * @param string $article
     *
     * @return bool
     */
    public static function updateOcsId($offerId = 0, $article = '')
    {
        if (!$offerId || !$article) return false;
        $ocsId = Oracle::getInstance()->getOcsId($article);
        if ($ocsId) \CIBlockElement::SetPropertyValuesEx($offerId, IBLOCK_SKU_ID, ['OCS_ID' => $ocsId]);

        return $ocsId;
    }

    /**
     * сбросить кеш всем компонентам каталога
     */
    public static function clearCatalogCache()
    {
        global $CACHE_MANAGER, $stackCacheManager;

        // сбросить кеш компонентов
        \CBitrixComponent::clearComponentCache("bitrix:catalog");
        \CBitrixComponent::clearComponentCache("bitrix:catalog.section");
        \CBitrixComponent::clearComponentCache("jamilco:catalog.section");
        \CBitrixComponent::clearComponentCache("bitrix:catalog.smart.filter");
        \CBitrixComponent::clearComponentCache("jamilco:catalog.smart.filter");
        \CBitrixComponent::clearComponentCache("bitrix:catalog.element");
        \CBitrixComponent::clearComponentCache("bitrix:catalog.products.viewed");
        \CBitrixComponent::clearComponentCache("jamilco:catalog.products.viewed");

        // сбросить весь кеш
        //$CACHE_MANAGER->CleanAll();
        //$stackCacheManager->CleanAll();
    }

    /**
     * проверяет, чтобы сумма в оплате совпадала с полной суммой заказа
     *
     * @param int $orderId
     *
     * @return bool
     */
    public static function checkOrderPaymentSum($orderId = 0)
    {
        if (!$orderId) return false;

        $order = Sale\Order::load($orderId);
        if (!$order) return false;

        $paymentCollection = $order->getPaymentCollection();
        if (!$paymentCollection->hasPaidPayment()) {
            $onePayment = $paymentCollection[0];
            $priceToPaidOrder = $order->getPrice();      // сумма заказа
            $priceToPaid = $order->getDeliveryPrice();
            $basket = $order->getBasket();
            $priceToPaid += $basket->getPrice();

            $paymentSum = $onePayment->getSum();    // сумма в оплате
            if ($paymentSum != $priceToPaid || $priceToPaidOrder != $priceToPaid) {
                $onePayment->setFieldNoDemand('SUM', $priceToPaid);
                $order->setField('PRICE', $priceToPaid);
                $order->save();

                return true;
            }
        }

        return false;
    }

    /**
     * проверяет, была ли в заказе отмененная оплата
     *
     * @param int $orderId
     *
     * @return bool
     */
    public static function hasOrderCanceledPayment($orderId = 0)
    {
        if (!$orderId) return false;

        $paidFlags = [
            'PAID'     => 'N',
            'CANCELED' => 'N',
        ];
        $hi = \Bitrix\Sale\Internals\OrderChangeTable::getList(['filter' => ['ORDER_ID' => $orderId, 'TYPE' => 'PAYMENT_PAID'], 'limit' => 10]);
        while ($arHistory = $hi->Fetch()) {
            $arData = unserialize($arHistory['DATA']);
            if ($arData['PAID'] == 'Y') $paidFlags['PAID'] = 'Y';
            if ($arData['PAID'] == 'N') $paidFlags['CANCELED'] = 'Y';
        }
        if ($paidFlags['PAID'] == 'Y' && $paidFlags['CANCELED'] == 'Y') return true; // только если была и оплата и отмена оплаты

        return false;
    }

    /**
     * Отправляет клиенту купон из правила корзины
     *
     * @param $orderId - номер заказа
     * @param $discId - ид правила корзины
     * @param $event - шаблон для отправки письма
     * @param array $untilDate - [день, месяц, год]
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     */
    public static function sendCoupon($orderId, $discId, $event, $untilDate = [])
    {
        Loader::includeModule('sale');

        if (!$orderId || !$discId || !$event) return;

        if (!empty($untilDate) && is_array($untilDate)) {
            if (time() > mktime(23, 59, 59, $untilDate[1], $untilDate[0], $untilDate[2]))
                return;
        }

        global $USER;
        $userEmail = '';
        $parameters = [
            'filter' => [
                "ID" => $orderId,
            ],
            'select' => ['ID', 'USER_ID']
        ];
        $or = \Bitrix\Sale\Internals\OrderTable::getList(
            $parameters
        );
        if ($arOrder = $or->Fetch()) {
            $arUser = $USER->GetByID($arOrder['USER_ID'])->Fetch();
            $userEmail = $arUser['EMAIL'];
        }

        $coupon = '';
        $disc = \Bitrix\Sale\Internals\DiscountTable::getList(
            [
                'filter' => [
                    'ACTIVE' => 'Y',
                    'ID'     => $discId,
                ],
                'select' => ['ID'],
            ]
        );
        while ($arDiscount = $disc->Fetch()) {
            $co = \Bitrix\Sale\Internals\DiscountCouponTable::getList(
                [
                    'filter' => [
                        'ACTIVE'      => 'Y',
                        'DATE_APPLY'  => false,
                        'DISCOUNT_ID' => $arDiscount['ID'],
                    ],
                    'limit'  => 1,
                    'select' => ['COUPON'],
                ]
            );
            if ($arCoupon = $co->Fetch()) {
                $coupon = $arCoupon['COUPON'];
            }
        }

        if ($userEmail && $coupon) {
            // деактивируем купон
            $couponTable = new \Bitrix\Sale\Internals\DiscountCouponTable();
            $rsCoupons = $couponTable->getList(
                array(
                    'filter' => array('COUPON' => $coupon, "DISCOUNT_ID" => $discId)
                )
            );
            $arCoupons = $rsCoupons->Fetch();
            if ($arCoupons['ID']) {
                $couponTable->update(
                    $arCoupons['ID'],
                    array(
                        'ACTIVE' => 'N'
                    )
                );
            }
            // отправляем email
            $arFields = array(
                "EMAIL"  => $userEmail,
                "COUPON" => $coupon,
            );
            \CEvent::Send($event, 's1', $arFields);
            \CEvent::ExecuteEvents();
            // лог
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/../coupons.log', $discId.", ".$coupon.", ".$orderId.", ".$userEmail."\r\n", FILE_APPEND);
        }
    }

    /**
     * Отправляет клиенту купон из ИБ
     *
     * @param $orderId
     * @param $event
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     */
    public static function sendCouponFromIb($orderId, $event)
    {
        if (!$orderId) {
            return;
        }
        if (!$event) {
            return;
        }

        Loader::includeModule('iblock');
        Loader::includeModule('sale');

        global $USER;
        $userEmail = '';
        $parameters = [
            'filter' => [
                "ID" => $orderId,
            ],
            'select' => ['ID', 'USER_ID']
        ];
        $or = \Bitrix\Sale\Internals\OrderTable::getList(
            $parameters
        );
        if ($arOrder = $or->Fetch()) {
            $arUser = $USER->GetByID($arOrder['USER_ID'])->Fetch();
            $userEmail = $arUser['EMAIL'];
        }

        if (!$userEmail) {
            return;
        }

        $item = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => IBLOCK_MANZANA_COUPONS, 'ACTIVE' => 'Y'],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME']
        )->fetch();
        if ($item['NAME']) {
            // деактивируем купон
            $el = new \CIBlockElement();
            $el->Update($item['ID'], [
                'ACTIVE' => 'N'
            ]);
            // данные клиента
            \CIBlockElement::SetPropertyValuesEx($item['ID'], IBLOCK_MANZANA_COUPONS, [
                'EMAIL' => $userEmail,
                'ORDER_ID' => $orderId
            ]);
            // отправляем email
            $arFields = array(
                "EMAIL" => $userEmail,
                "COUPON_CODE" => $item['NAME'],
                "SERVER_NAME" => $_SERVER['SERVER_NAME']
            );
            \CEvent::Send($event, 's1', $arFields);
            \CEvent::ExecuteEvents();
        }
    }

}
