<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

define('ADMIN_SECTION', true);

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Sale;
use \Bitrix\Sale\Order;
use \Bitrix\Sale\Internals;
use \Bitrix\Sale\Delivery;
use \Bitrix\Sale\Helpers\Admin\OrderEdit;
use \Jamilco\Omni;
use \Jamilco\Delivery\Ozon;

global $APPLICATION, $USER;

Loader::IncludeModule('iblock');
Loader::IncludeModule('sale');
Loader::IncludeModule('catalog');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if ($request->isPost() || 1) {
    $action = $request->get('action');
    $orderId = $request->get('order');
    if ($orderId > 0) $saleOrder = Sale\Order::load($orderId);
    if ($orderId > 0 && $saleOrder) {
        if ($action == 'reloadQuantities') {
            // обновление остатков всем ТП из заказа
            $arRes = Omni\Channel::reloadQuantities($orderId);
            echo Json::encode($arRes);
            die();
        } elseif ($action == 'reloadOrder') {
            // обновление заказа

            \Jamilco\Main\Utils::refreshOrder($orderId);

            die();
        } elseif ($action == 'changeLocation') {

            // свойства заказа
            $arOrderProps = Omni\ChangeDelivery::getProps($saleOrder, true);

            $locationId = $request->get('id');
            $arLocation = \Jamilco\Delivery\Location::getLocationData($locationId);

            \CSaleOrderPropsValue::Update($arOrderProps[Omni\ChangeDelivery::PROP_CODE_CITY]['ID'], ['VALUE' => $arLocation['CODE']]);

        } elseif ($action == 'getDelivery') {

            // свойства заказа
            $arOrderProps = Omni\ChangeDelivery::getProps($saleOrder);

            $arLocation = \Jamilco\Delivery\Location::getLocationData(false, $arOrderProps[Omni\ChangeDelivery::PROP_CODE_CITY]);

            global $skipCatalogQuantityCheck; // флаг для пропуска проверки на (CATALOG_QUANTITY > 0) в omni-модуле

            // массив Omni-флагов, при которых нужно пропустить проверку на наличие на складе ИМа (CATALOG_QUANTITY)
            $arOmniFlagsToSkipCatalogQuantityCheck = [
                'Delivery',
                'Pick_Point',
            ];
            $skipCatalogQuantityCheck = in_array($arOrderProps['OMNI_CHANNEL'], $arOmniFlagsToSkipCatalogQuantityCheck);

            $arOmni = getOmniFlags($saleOrder, $arLocation);
            $omniSessionKey = $arLocation['ID'].'-'.implode('-', $arOmni['PRODUCTS_ID']);
            $_SESSION['OMNI'][$omniSessionKey] = $arOmni['OMNI'];
            //ppr($arOmni['OMNI']);

            if (defined('OZON_DELIVERY') && OZON_DELIVERY > 0) {
                $arOmni['PVZ'] = getPvzList($saleOrder, $arLocation);
            }

            // возможные способы доставки
            $arDeliveries = getDeliveriesList($saleOrder);

            // удалим Omni-невозможные способы доставки

            // не выводим службы доставки если нет пунктов выдачи
            if (!$arOmni['SHOPS']) unset($arDeliveries[PICKUP_DELIVERY]);
            if (!$arOmni['PVZ']) unset($arDeliveries[OZON_DELIVERY]);

            // не выводим службы доставки, если нет ни одного товара, который можно ей доставить
            if (!$arOmni['OMNI']['DELIVERY'] && !$arOmni['OMNI']['OMNI_DELIVERY']) unset($arDeliveries[CURIER_DELIVERY]);
            if (!$arOmni['OMNI']['DELIVERY'] && !$arOmni['OMNI']['OMNI_DELIVERY']) unset($arDeliveries[KCE_DELIVERY]);
            if (!$arOmni['OMNI']['PICK_POINT']) unset($arDeliveries[OZON_DELIVERY]);
            if (!$arOmni['OMNI']['PICKUP']) unset($arDeliveries[PICKUP_DELIVERY]);

            // экспресс-доставка может быть и без курьерской доставки
            if (!$arOmni['OMNI']['FAST_DELIVERY']) unset($arDeliveries[DAY_DELIVERY]);

            // если есть КСЕ, то старой курьеской доставки не должно быть
            if (array_key_exists(KCE_DELIVERY, $arDeliveries)) unset($arDeliveries[CURIER_DELIVERY]);

            // соберем корзину
            $arBasketItems = [];
            $basket = $saleOrder->getBasket();
            foreach ($basket as $basketItem) {
                $arItem = $basketItem->getFields()->getValues();
                $arBasketItems[$arItem['ID']] = $arItem;
            }

            // проставим недоступные к доставке товары
            foreach ($arDeliveries as $key => $arOne) {
                $arDeliveries[$key]['ITEMS_NO_DELIVERY'] = '';

                if ($key == CURIER_DELIVERY || $key == KCE_DELIVERY) {
                    $arDeliveries[$key]['TYPE'] = 'CURIER';
                    $arDeliveries[$key]['ITEMS'] = ($arOmni['OMNI']['DELIVERY']) ?: $arOmni['OMNI']['OMNI_DELIVERY'];

                    foreach ($arBasketItems as $arBasket) {
                        if (!in_array($arBasket['ID'], $arDeliveries[$key]['ITEMS'])) {
                            $arDeliveries[$key]['ITEMS_NO_DELIVERY'][] = $arBasket['NAME'].' ('.$arBasket['QUANTITY'].' шт.)';
                        }
                    }

                    $arDeliveries[$key]['ITEMS_NO_DELIVERY'] = implode(', ', $arDeliveries[$key]['ITEMS_NO_DELIVERY']);

                } elseif ($key == DAY_DELIVERY) {
                    $arDeliveries[$key]['TYPE'] = 'CURIER';
                    $arDeliveries[$key]['ITEMS'] = $arOmni['OMNI']['FAST_DELIVERY'];

                    foreach ($arBasketItems as $arBasket) {
                        if (!in_array($arBasket['ID'], $arDeliveries[$key]['ITEMS'])) {
                            $arDeliveries[$key]['ITEMS_NO_DELIVERY'][] = $arBasket['NAME'].' ('.$arBasket['QUANTITY'].' шт.)';
                        }
                    }

                    $arDeliveries[$key]['ITEMS_NO_DELIVERY'] = implode(', ', $arDeliveries[$key]['ITEMS_NO_DELIVERY']);

                } elseif ($key == OZON_DELIVERY) {
                    $arDeliveries[$key]['TYPE'] = 'PVZ';
                    $arDeliveries[$key]['ITEMS'] = $arOmni['OMNI']['PICK_POINT'];
                    $arDeliveries[$key]['LIST'] = $arOmni['PVZ'];

                    foreach ($arBasketItems as $arBasket) {
                        if (!in_array($arBasket['ID'], $arDeliveries[$key]['ITEMS'])) {
                            $arDeliveries[$key]['ITEMS_NO_DELIVERY'][] = $arBasket['NAME'].' ('.$arBasket['QUANTITY'].' шт.)';
                        }
                    }

                    $arDeliveries[$key]['ITEMS_NO_DELIVERY'] = implode(', ', $arDeliveries[$key]['ITEMS_NO_DELIVERY']);

                } elseif ($key == PICKUP_DELIVERY) {
                    $arDeliveries[$key]['TYPE'] = 'SHOP';
                    foreach ($arOmni['SHOPS'] as $sh => $arShop) {
                        $arOmni['SHOPS'][$sh]['ITEMS'] = $arShop['ITEMS'] = array_unique($arShop['ITEMS']);
                        $arOmni['SHOPS'][$sh]['ITEMS_NO_DELIVERY'] = [];
                        foreach ($arBasketItems as $arBasket) {
                            if (!in_array($arBasket['ID'], $arShop['ITEMS'])) {
                                $arOmni['SHOPS'][$sh]['ITEMS_NO_DELIVERY'][] = $arBasket['NAME'].' ('.$arBasket['QUANTITY'].' шт.)';
                            }
                        }

                        $arOmni['SHOPS'][$sh]['ITEMS_NO_DELIVERY'] = implode(', ', $arOmni['SHOPS'][$sh]['ITEMS_NO_DELIVERY']);
                    }

                    $arDeliveries[$key]['LIST'] = $arOmni['SHOPS'];
                }
            }

            $arOut = [
                'BASKET'   => $arBasketItems,
                'DELIVERY' => $arDeliveries,
            ];

            if ($_GET['debug'] == 'Y') ppr($arOut, 1);

            echo Json::encode($arOut);
        } elseif ($action == 'changeDelivery') {
            $type = $request->get('type');
            $deliveryId = $request->get('id');
            $arParams = $request->get('params');

            // возможные способы доставки
            $arDeliveries = getDeliveriesList($saleOrder);
            $service = $arDeliveries[$deliveryId];

            $shipment = getCurrentShipment($saleOrder);
            $shipment->setFields(
                [
                    'DELIVERY_ID'    => $service['ID'],
                    'DELIVERY_NAME'  => $service['OWN_NAME'],
                    'PRICE_DELIVERY' => $service['PRICE'], // устанавливается автоматически
                ]
            );

            $saleOrder->doFinalAction(true);
            $saleOrder->refreshData(['PRICE', 'PRICE_DELIVERY']);
            $result = $saleOrder->save();

            // свойства заказа
            $arOrderProps = Omni\ChangeDelivery::getProps($saleOrder, true);
            $arLocation = \Jamilco\Delivery\Location::getLocationData($arOrderProps[Omni\ChangeDelivery::PROP_CODE_CITY]['VALUE']);

            $address = '';
            if ($type == 'CURIER') {
                $address = $arParams['address'];

                saveOrderProp($saleOrder->getId(), $arOrderProps, Omni\ChangeDelivery::PROP_CODE_STORE_ID, '');
            }
            if ($type == 'SHOP') {
                $arStore = \CCatalogStore::GetList([], ['ID' => $arParams['list']])->Fetch();
                $address = $arStore['ADDRESS'];

                saveOrderProp($saleOrder->getId(), $arOrderProps, Omni\ChangeDelivery::PROP_CODE_STORE_ID, $arParams['list']);
            }
            if ($type == 'PVZ') {

                $arPVZ = Ozon::getPvzList($arLocation['ID'], 0, ['CODE' => $arParams['list']]);
                $arPVZ = array_shift($arPVZ);
                $address = $arPVZ['PROPERTIES']['ADDRESS'];

                saveOrderProp($saleOrder->getId(), $arOrderProps, Omni\ChangeDelivery::PROP_CODE_STORE_ID, $arPVZ['CODE']);
            }

            saveOrderProp($saleOrder->getId(), $arOrderProps, Omni\ChangeDelivery::PROP_CODE_ADDRESS, $address);

            Omni\Events::OnSaleComponentOrderOneStepCompleteHandler($saleOrder->getId());
        }
    }
}

function saveOrderProp($orderId = 0, $arOrderProps = [], $propCode = '', $propValue = '')
{
    if ($arOrderProps[$propCode]) {
        \CSaleOrderPropsValue::Update($arOrderProps[$propCode]['ID'], ['VALUE' => $propValue]);
    } else {
        $rsOrder = \CSaleOrderProps::GetList([], ['CODE' => $propCode]);
        if ($arOrderProp = $rsOrder->Fetch()) {
            \CSaleOrderPropsValue::Add(
                [
                    "ORDER_ID"       => $orderId,
                    "ORDER_PROPS_ID" => $arOrderProp['ID'],
                    "NAME"           => $arOrderProp['NAME'],
                    "CODE"           => $arOrderProp['CODE'],
                    "VALUE"          => $propValue,
                ]
            );
        }
    }
}

function getPvzList(Order $saleOrder, $arLocation = [])
{
    $pvzFilter = [
        'PROPERTY_HALF_TAKE' => 'Y'
    ];
    $basket = $saleOrder->getBasket();
    foreach ($basket as $basketItem) {
        $arItem = $basketItem->getFields()->getValues();

        $pr = \CSaleBasket::GetPropsList([], ["BASKET_ID" => $arItem['ID']]);
        while ($arProp = $pr->Fetch()) {
            if ($arProp['CODE'] === 'SIZES_SHOES') {
                $pvzFilter['PROPERTY_FITTING_SHOES'] = 'Y';
            } elseif ($arProp['CODE'] === 'SIZES_CLOTHES') {
                $pvzFilter['PROPERTY_FITTING_CLOTHES'] = 'Y';
            }
        }
    }

    return Ozon::getPvzList($arLocation['ID'], 0, $pvzFilter);
}

function getOmniFlags(Order $saleOrder, $arLocation = [])
{
    $arResult['PRODUCTS_ID'] = [];

    // получим Омни флаги для всех товаров
    $basket = $saleOrder->getBasket();
    foreach ($basket as $basketItem) {
        $arItem = $basketItem->getFields()->getValues();

        $arResult['PRODUCTS_ID'][] = $arItem['PRODUCT_ID'];

        $arOfferID = explode('#', $arItem['PRODUCT_XML_ID']);

        $arOmni = Omni\Channel::getDeliveryData(
            [
                'ID'      => $arOfferID[1],
                'ARTICLE' => $arItem['NAME'],
            ],
            ['ID' => $arOfferID[0]],
            true
        );
        //pr($arOmni);

        // доставлять курьером можно либо со склада, либо из РМ, из которого разрешена доставка
        if ($arOmni['DELIVERY_T_DEV'] == 'Y') $arResult['OMNI']['DELIVERY'][] = (int)$arItem['ID'];
        if (count($arOmni['SHOP']['DELIVERY'])) $arResult['OMNI']['OMNI_DELIVERY'][] = (int)$arItem['ID'];
        if ($arOmni['PICKUP_POINT_T_DEV'] == 'Y') $arResult['OMNI']['PICK_POINT'][] = (int)$arItem['ID'];

        // если в текущем городе есть РМ с опцией "DayDelivery"
        $hasCityDayDelivery = false;
        $locationName = ToLower($arLocation['NAME_RU']);
        foreach ($arOmni['SHOP']['DAY_DELIVERY'] as $arShop) {
            $arShop['ADDRESS'] = ToLower($arShop['ADDRESS']);
            $arShop['TITLE'] = ToLower($arShop['TITLE']);
            if (substr_count($arShop['ADDRESS'], $locationName) || substr_count($arShop['TITLE'], $locationName)) {
                $hasCityDayDelivery = true;
                break;
            }
        }
        if ($arOmni['DAYDELIVERY_T'] == 'Y' && $hasCityDayDelivery) $arResult['OMNI']['FAST_DELIVERY'][] = (int)$arItem['ID'];

        $shopOne = false; // возможность самовывоза товара опредеяется многими флагами, если есть хоть один массив магазинов в SHOP, кроме DELIVERY, значит можно

        foreach ($arOmni['SHOP'] as $type => $arShops) {
            if ($type == 'DELIVERY' || $type == 'DAY_DELIVERY') continue; // доставка из РМ
            foreach ($arShops as $arShop) {
                // оставляем только те РМ, в названии которых есть выбранный город
                if (
                    substr_count(ToLower($arShop['TITLE']), $locationName) ||
                    substr_count(ToLower($arShop['ADDRESS']), $locationName)
                ) {
                    $shopOne = true;

                    if (!array_key_exists($arShop['ID'], $arResult['SHOPS'])) {
                        $arResult['SHOPS'][$arShop['ID']] = $arShop;
                    }

                    $arResult['SHOPS'][$arShop['ID']]['ITEMS'][] = $arItem['ID'];
                    $arResult['OMNI']['SHOPS'][$arShop['ID']][$arItem['ID']] = $type;
                }
            }
        }

        if ($shopOne) $arResult['OMNI']['PICKUP'][] = (int)$arItem['ID'];
    }

    sort($arResult['PRODUCTS_ID']);

    // пробежим по РМ, если в нём есть Omni_Pikcup (самовывоз), то убираем флаги Omni_Retail (доставка в РМ), чтоб флаги не смешивались
    foreach ($arResult['OMNI']['SHOPS'] as $shopId => $arShopData) {
        $hasFlags = [];
        foreach ($arShopData as $basketId => $shopType) {
            $hasFlags[$shopType]++;
        }
        if ($hasFlags['PICKUP'] > 0 && $hasFlags['RETAIL'] > 0) {
            foreach ($arShopData as $basketId => $shopType) {
                if ($shopType == 'RETAIL') unset($arResult['OMNI']['SHOPS'][$shopId][$basketId]);
            }
        }
    }

    if ($arResult['OMNI']['DELIVERY']) unset($arResult['OMNI']['OMNI_DELIVERY']);

    /*
     * сортируем магазины:
     *  - по количеству товаров, которые в них есть (убывание)
     *  - по индексу сортировки (возрастание)
     *  - по ID (возрастание)
     */
    uasort($arResult['SHOPS'], 'sortByCountItemsAndSort');

    return $arResult;
}

function sortByCountItemsAndSort($a, $b)
{
    $aCount = count($a['ITEMS']);
    $bCount = count($b['ITEMS']);
    if ($aCount == $bCount) {
        if ($a['SORT'] == $b['SORT']) {
            return ($a['ID'] >= $b['ID']) ? 1 : -1;
        }

        return ($a['SORT'] > $b['SORT']) ? 1 : -1;
    }

    return ($aCount > $bCount) ? -1 : 1;
}

function getCurrentShipment(Order $order)
{
    /** @var Shipment $shipment */
    foreach ($order->getShipmentCollection() as $shipment) {
        if (!$shipment->isSystem()) {
            return $shipment;
        }
    }

    return null;
}

function getOrderClone(Order $order)
{
    /** @var Order $orderClone */
    $orderClone = $order->createClone();

    $clonedShipment = getCurrentShipment($orderClone);
    if (!empty($clonedShipment)) {
        $clonedShipment->setField('CUSTOM_PRICE_DELIVERY', 'N');
    }

    return $orderClone;
}

function getDeliveriesList(Order $saleOrder)
{
    $arDeliveries = [];

    // проверим, есть ли скидка на 100% бесплатную доставку
    $freeDelivery = false; // беслатная доставка
    $notFreeDeliveriesID = []; // массив ID доставок, на которые не распространяется стоимость
    $disc = Internals\DiscountTable::getList(
        [
            'filter' => ['ACTIVE' => 'Y', 'XML_ID' => 'free_delivery']
        ]
    );
    if ($arFreeDiscount = $disc->Fetch()) {
        $basketPrice = $saleOrder->getBasket()->getPrice();
        foreach ($arFreeDiscount['CONDITIONS_LIST']['CHILDREN'] as $arOne) {
            if ($arOne['CLASS_ID'] == 'CondBsktAmtGroup') {
                if ($basketPrice > $arOne['DATA']['Value']) $freeDelivery = true;
            } elseif ($arOne['CLASS_ID'] == 'CondSaleDelivery') {
                $notFreeDeliveriesID = $arOne['DATA']['value'];
            }
        }
    }

    $shipment = getCurrentShipment($saleOrder);

    $arDeliveryServiceAll = Delivery\Services\Manager::getRestrictedObjectsList($shipment);

    foreach ($arDeliveryServiceAll as $deliveryId => $deliveryObj) {
        $arDelivery = array();

        $arDelivery['ID'] = $deliveryObj->getId();
        $arDelivery['NAME'] = $deliveryObj->isProfile() ? $deliveryObj->getNameWithParent() : $deliveryObj->getName();
        $arDelivery['OWN_NAME'] = $deliveryObj->getName();
        $arDelivery['DESCRIPTION'] = $deliveryObj->getDescription();
        $arDelivery["CURRENCY"] = $saleOrder->getCurrency();
        $arDelivery['SORT'] = $deliveryObj->getSort();

        if ((int)$shipment->getDeliveryId() === $deliveryId) {
            $arDelivery['CHECKED'] = 'Y';

            $mustBeCalculated = true;

            $calculationResult = Delivery\Services\Manager::calculateDeliveryPrice($shipment);
        } else {
            $mustBeCalculated = true;

            if (empty($orderClone)) $orderClone = getOrderClone($saleOrder);

            $orderClone->isStartField();

            $clonedShipment = getCurrentShipment($orderClone);
            $clonedShipment->setField('DELIVERY_ID', $deliveryId);

            $calculationResult = Delivery\Services\Manager::calculateDeliveryPrice($clonedShipment);
        }

        if ($mustBeCalculated) {

            $arDelivery['PRICE'] = Sale\PriceMaths::roundPrecision($calculationResult->getDeliveryPrice());
            $arDelivery['PERIOD_TEXT'] = $calculationResult->getPeriodDescription();

            // проверка на наличие скидки на 100% доставку
            if ($freeDelivery && !in_array($arDelivery['ID'], $notFreeDeliveriesID)) $arDelivery['PRICE'] = 0;

            $arDelivery['PRICE_FORMATED'] = SaleFormatCurrency($arDelivery['PRICE'], 'RUB');
        }

        $arDeliveries[$deliveryId] = $arDelivery;
    }

    // удалим "Без доставки"
    foreach ($arDeliveries as $key => $arOne) {
        if ($arOne['NAME'] == 'Без доставки') {
            unset($arDeliveries[$key]);
            break;
        }
    }

    return $arDeliveries;
}