<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**@var array $arResult */

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Jamilco\Delivery\Ozon;
use Jamilco\Omni\Channel;
use Jamilco\Delivery;

require_once('functions.php');

if ($arResult['USER_VALS']['CONFIRM_ORDER'] === 'N') {
    //имя, id города, по умолчанию - Москва.
    $locationName = (string)$_COOKIE['city_name'] ?: 'Москва';
    $locationId = (int)$_COOKIE['city_id'] ?: DEFAULT_CITY_ID;

    $arResult['NEED_SUBMIT'] = false;

    if (!class_exists('\Sale\Handlers\Delivery\KceHandler')) {
        require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/sale_delivery/kce/handler.php';
    }
    $kce = \Sale\Handlers\Delivery\KceHandler::getElementByLocationID($locationId, ['PROPERTY_cash_payment']);
    $arResult['CASH_PAYMENT'] = true;
    if ($kce && strtolower($kce['PROPERTY_CASH_PAYMENT_VALUE']) !== 'да') {
        $arResult['CASH_PAYMENT'] = false;
        $arResult['ORDER_DATA']['PAY_SYSTEM_ID'] = ONLINE_PAYSYSTEM;
        $arResult['NEED_SUBMIT'] = true;
    }

    // определяем возможную недоступность оплаты наличными
    $CashPermittedRegions = [
        'москва',
        'московская область',
        'санкт-петербург',
        'ленинградская область'
    ];
    $enableCash = false;
    $arLoc = Jamilco\Delivery\Location::getLocationData($locationId);
    foreach ($arLoc['PATH'] as $loc) {
        $check = trim(ToLower($loc));
        if (in_array($check, $CashPermittedRegions)) {
            $enableCash = true;
            break;
        }
    }
    // кэш недоступен
    if (!$enableCash){
        $activePickUpDelivery = false;
        foreach ($arResult['DELIVERY'] as $delivery){
            if($delivery['ID'] == PICKUP_DELIVERY && $delivery['CHECKED'] == 'Y'){
                $activePickUpDelivery = true;
                break;
            }
        }
        // если не самовывоз, убираем оплату наличными
        if (!$activePickUpDelivery) {
            foreach ($arResult['PAY_SYSTEM'] as $id => $ps) {
                if ($ps['ID'] == CASH_PAYSYSTEM) {
                    unset($arResult['PAY_SYSTEM'][$id]);
                    break;
                }
            }
            // установим новую активную ПС
            foreach ($arResult["PAY_SYSTEM"] as $key => $arPaySystem) {
                $arResult["PAY_SYSTEM"][$key]['CHECKED'] = 'Y';
                break;
            }
        }
    }

    $arResult['OMNI'] = [
        'DELIVERY'   => [], // которые можно доставлять Курьером
        'PICK_POINT' => [], // которые можно доставлять в ПВЗ
        'PICKUP'     => [], // которые можно самовывезти из РМ
        'SHOPS'      => [], // массив магазинов и связей с товарами через флаги
    ];
    $arResult['SHOPS'] = [];
    $arResult['PRODUCTS_ID'] = [];

    $pvzFilter = [
        'PROPERTY_HALF_TAKE' => 'Y'
    ];

    foreach ($arResult['BASKET_ITEMS'] as $key => $arItem) {
        $pr = CPrice::GetList(
            [],
            [
                "CATALOG_GROUP_ID" => 1,
                "PRODUCT_ID"       => $arItem['PRODUCT_ID']
            ]
        );
        $arPrice = $pr->Fetch();
        if ($arItem['BASE_PRICE'] < $arPrice['PRICE']) $arItem['BASE_PRICE'] = $arPrice['PRICE'];
        $arItem['BASE_PRICE'] = \Bitrix\Catalog\Product\Price::roundPrice(1, $arItem['BASE_PRICE'], $arItem['CURRENCY']);

        $arItem['DISCOUNT_PRICE'] = $arItem['BASE_PRICE'] - $arItem['PRICE'];

        foreach ($arItem['PROPS'] as $prop) {
            if ($prop['CODE'] === 'SIZES_SHOES') {
                $pvzFilter['PROPERTY_FITTING_SHOES'] = 'Y';
            } elseif ($prop['CODE'] === 'SIZES_CLOTHES') {
                $pvzFilter['PROPERTY_FITTING_CLOTHES'] = 'Y';
            }
        }

        $arOfferID = explode('#', $arItem['PRODUCT_XML_ID']);
        $arOmni = Channel::getDeliveryData(
            [
                'ID'      => $arOfferID[1],
                'ARTICLE' => $arItem['NAME'],
            ],
            ['ID' => $arOfferID[0]],
            true,
            true,
            $locationName
        );
        pr($arOmni);

        // доставлять курьером можно либо со склада, либо из РМ, из которого разрешена доставка
        if ($arOmni['DELIVERY_T_DEV'] == 'Y') $arResult['OMNI']['DELIVERY'][] = (int)$arItem['ID'];
        if (count($arOmni['SHOP']['DELIVERY'])) $arResult['OMNI']['OMNI_DELIVERY'][] = (int)$arItem['ID'];
        if ($arOmni['PICKUP_POINT_T_DEV'] == 'Y') $arResult['OMNI']['PICK_POINT'][] = (int)$arItem['ID'];

        // если в текущем городе есть РМ с опцией "DayDelivery"
        $hasCityDayDelivery = false;
        $locationName = ToLower($locationName);
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

        $arrShipment = array(); // массив всех актуальных связанных графиков с ИБ-50

        foreach ($arOmni['SHOP'] as $type => $arShops) {
            if ($type == 'DELIVERY') continue; // доставка из РМ
            foreach ($arShops as $arShop) {
                $shopOne = true;

                // оставляем только те РМ, в названии которых есть выбранный город
                if (
                    substr_count(ToUpper($arShop['TITLE']), ToUpper($locationName)) ||
                    substr_count(ToUpper($arShop['ADDRESS']), ToUpper($locationName))
                ) {
                    if (!array_key_exists($arShop['ID'], $arResult['SHOPS'])) {
                        $arResult['SHOPS'][$arShop['ID']] = $arShop;
                    }

                    // поиск ID графика отгрузки
                    $storeRes = CCatalogStore::GetList(
                        array('ID' => 'ASC'),
                        array('ACTIVE' => 'Y', 'ID' => $arShop['ID']),
                        false,
                        false,
                        array('ID', 'UF_SHIPPING')
                    );
                    while ($ar_store = $storeRes->GetNext()) {
                        $arrShipment[] = $ar_store['UF_SHIPPING'];
                        $arResult['SHOP_SHIPPING_ID'][$arShop['ID']] = $ar_store['UF_SHIPPING'];
                        $arResult['SHOPS'][$arShop['ID']]['SHIPPING'] = $ar_store['UF_SHIPPING'];
                    }

                    $arResult['SHOPS'][$arShop['ID']]['ITEMS'][] = $arItem['ID'];

                    $arResult['OMNI']['SHOPS'][$arShop['ID']][$arItem['ID']] = $type;
                }
            }
        }

        // массив значений графика отгрузок по каждому магазину/складу
        $arrStoreShipment = array();
        /*
        $arFilterShipment = Array(
            "IBLOCK_ID" => 50,
            "ID"        => $arResult['SHOP_SHIPPING_ID']
        );
        $resShipment = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterShipment);
        while ($ob = $resShipment->GetNextElement()) {
            $arFields = $ob->GetFields();
            $arrStoreShipment[$arFields['ID']]['NAME'] = $arFields['NAME'];
            $arProps = $ob->GetProperties();
            foreach ($arProps as $code => $dayShip) {
                //if ($dayShip['VALUE'] > 0) {
                if (strpos($dayShip['CODE'], '_SHIP')) {
                    $arrStoreShipment[$arFields['ID']]['SHIPMENT'][] = $dayShip['VALUE'];
                } elseif (strpos($dayShip['CODE'], '_DELY')) {
                    $arrStoreShipment[$arFields['ID']]['DELIVERY'][] = $dayShip['VALUE'];
                }
                //}
            }
        }
        */
        $arResult['STOCK_SHIPMENT'] = $arrStoreShipment;

        if ($shopOne) $arResult['OMNI']['PICKUP'][] = (int)$arItem['ID'];

        $arItem['OMNI'] = $arOmni;
        $arResult['BASKET_ITEMS'][$key] = $arItem;
        $arResult['PRODUCTS_ID'][] = $arItem["PRODUCT_ID"];
    }

    //сортируем товары для соответствия ключа сессии в событии OMNI
    sort($arResult['PRODUCTS_ID']);

    //список ПВЗ для текущего location
    $arResult['PVZ'] = Ozon::getPvzList($locationId, 0, $pvzFilter);

    // удаление тех ПВЗ где максимальная сумма меньше чем сумма заказа
    $pvzList = $arResult['PVZ'];
    foreach ($pvzList as $id => $pvz) {
        if ($pvz['PROPERTIES']['MAX_PRICE'] > 0 && $pvz['PROPERTIES']['MAX_PRICE'] < $arResult['ORDER_PRICE']) {
            unset($arResult['PVZ'][$id]);
        }
    }
    unset($pvzList);

    $arResult['LOCATION'] = [];
    $rsLocation = \Bitrix\Sale\Location\Search\Finder::find(
        [
            'select' => [
                'ID',
                'NAME',
                'TYPE_CODE' => 'TYPE.CODE'
            ],
            'filter' => [
                'ID'                => $locationId,
                '=NAME.LANGUAGE_ID' => 'ru',
            ]
        ]
    );
    if ($arLocation = $rsLocation->Fetch()) {
        $arResult['LOCATION'] = $arLocation;
        $arResult['LOCATION']['STREETS'] = getStreets($locationId);
    }

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

    //ключа сессии для события OMNI
    $omniSessionKey = $locationId.'-'.implode('-', $arResult['PRODUCTS_ID']);
    $_SESSION['OMNI'][$omniSessionKey] = $arResult['OMNI'];
    //ppr($arResult['OMNI']);

    $arResult['PROPS_ID'] = [];
    $arResult['ORDER_PROP']['USER_PROPS_Y'] = $arResult['ORDER_PROP']['USER_PROPS_Y'] ?: [];
    $arResult['ORDER_PROP']['USER_PROPS_N'] = $arResult['ORDER_PROP']['USER_PROPS_N'] ?: [];
    $arCheckProps = array_merge($arResult['ORDER_PROP']['USER_PROPS_Y'], $arResult['ORDER_PROP']['USER_PROPS_N']);

    $arResult['ORDER_PROP']['ADDRESS_PROPS'] = [];
    $arResult['ORDER_PROP']['LOCATION'] = [];
    foreach ($arCheckProps as $arProp) {
        $arResult['PROPS_ID'][$arProp['CODE']] = $arProp['ID'];

        if ($arProp['CODE'] === 'F_ADDRESS') {
            $arResult['ORDER_PROP']['ADDRESS_PROPS'][] = $arProp;
            continue;
        }
        if ($arProp['TYPE'] === 'LOCATION') {
            $arResult['ORDER_PROP']['LOCATION'] = $arProp;
            continue;
        }

        $arResult['ORDER_PROP']['USER_PROPS'][] = $arProp;
    }

    // не выводим службы доставки если нет пунктов выдачи или товаров, которые можно ей доставить
    if (!$arResult['SHOPS'] || !$arResult['OMNI']['PICKUP']) unset($arResult['DELIVERY'][PICKUP_DELIVERY]);
    if (!$arResult['PVZ'] || !$arResult['OMNI']['PICK_POINT']) unset($arResult['DELIVERY'][OZON_DELIVERY]);
    if (!$arResult['OMNI']['DELIVERY'] && !$arResult['OMNI']['OMNI_DELIVERY']) unset($arResult['DELIVERY'][COURIER_DELIVERY]);
    if (!$arResult['OMNI']['DELIVERY'] && !$arResult['OMNI']['OMNI_DELIVERY']) unset($arResult['DELIVERY'][KCE_DELIVERY]);

    // экспресс-доставка может быть и без курьерской доставки
    if (!$arResult['OMNI']['FAST_DELIVERY']) unset($arResult['DELIVERY'][DAY_DELIVERY]);

    // доставка день-в-день работает с 8-30 до 17-00 по будням
    $arDate = [
        'h' => (int)date('G'),
        'm' => (int)date('i'),
        'd' => (int)date('w'),
    ];
    if ($arDate['d'] === 0 || $arDate['d'] == 6) unset($arResult['DELIVERY'][DAY_DELIVERY]); // не работает по выходным
    if ($arDate['h'] < 8 || $arDate['h'] > 16) unset($arResult['DELIVERY'][DAY_DELIVERY]); // до 8:00 и после 17:00
    if ($arDate['h'] == 8 && $arDate['m'] < 30) unset($arResult['DELIVERY'][DAY_DELIVERY]); // от 8:00 и до 8:30

    if (array_key_exists(KCE_DELIVERY, $arResult['DELIVERY'])) unset($arResult['DELIVERY'][COURIER_DELIVERY]);

    // проверим что есть активная служба доставки
    $hasDeliveryChecked = false;
    foreach ($arResult["DELIVERY"] as $delivery_id => $arDelivery) {
        if ($arDelivery['CHECKED'] === 'Y') {
            $hasDeliveryChecked = true;
            break;
        }
    }
    if (!$hasDeliveryChecked) {
        // установим активной первую службу доставки
        foreach ($arResult["DELIVERY"] as $delivery_id => $arDelivery) {
            $arResult["DELIVERY"][$delivery_id]['CHECKED'] = 'Y';
            break;
        }
    }

    $deliverySelectedId = false;
    $paySystemSelectedId = false;
    foreach ($arResult["DELIVERY"] as $delivery_id => $arDelivery) {
        if ($arDelivery['CHECKED'] == 'Y') {
            $deliverySelectedId = $arDelivery['ID'];
            break;
        }
    }

    foreach ($arResult["PAY_SYSTEM"] as $pay_id => &$arPaySystem) {
        if (!$arResult['CASH_PAYMENT']) {
            if (intval($arPaySystem['ID']) === CASH_PAYSYSTEM) {
                unset($arResult['PAY_SYSTEM'][$pay_id]);
                continue;
            } else if (intval($arPaySystem['ID']) === ONLINE_PAYSYSTEM) {
                $arPaySystem['CHECKED'] = 'Y';
            }
        }

        if ($arPaySystem['CHECKED'] == 'Y') {
            $paySystemSelectedId = $arPaySystem['ID'];
        }
    }
    unset($pay_id, $arPaySystem);

    if ($deliverySelectedId == PICKUP_DELIVERY) {
        foreach ($arResult["PAY_SYSTEM"] as $keyPay => $arPaySystem) {
            // блокируем оплату онлайн для самовывоза \ кроме админов
            if (!$USER->isAdmin() && $arPaySystem['ID'] == 7) unset($arResult["PAY_SYSTEM"][$keyPay]);
            // переименовываем название платежной системы для самовывоза
            if ($arPaySystem['ID'] == 3) $arResult["PAY_SYSTEM"][$keyPay]['PSA_NAME'] = 'Оплата в магазине';
        }
    }

    $arResult['GIFT_ADDED'] = \Jamilco\Loyalty\Gift::checkGifts($deliverySelectedId, $paySystemSelectedId);

    foreach ($arResult['DELIVERY'] as $deliveryId => $arDelivery) {

        $dayCount = (int)$arDelivery['PERIOD_TEXT'];
        if (substr_count($arDelivery['PERIOD_TEXT'], '-')) {
            $dayCount = explode('-', $arDelivery['PERIOD_TEXT']);
            if ((int)$dayCount[1] > 0) $dayCount = (int)$dayCount[1];
        }

        $serverDateTime = getdate();
        if ($deliveryId == COURIER_DELIVERY || $deliveryId == OZON_DELIVERY) {
            // Если время на сервере больше чем 16.00, то нужно прибавить 1 день к сроку доставки
            if ($serverDateTime['hours'] >= 16) $dayCount++;
        } elseif ($deliveryId == PICKUP_DELIVERY) {
            // Если время на сервере больше чем 20.00, то нужно прибавить 1 день к сроку доставки
            if ($serverDateTime['hours'] >= 20) $dayCount++;
        }

        if ($dayCount == 0) {
            $dayCountFormat = 'Сегодня';
        } elseif ($dayCount == 1) {
            $dayCountFormat = 'Завтра';
        } else {
            $dayCountFormat = date('d.m.y', time() + 86400 * $dayCount);
        }

        $arResult['DELIVERY'][$deliveryId]['DAY_FORMAT'] = $dayCountFormat;
    }

    /** Adspire start */
    $productsInfo = \Adspire\Manager::getInstance()->fillProductObject(array_column($arResult['BASKET_ITEMS'] ?: [], 'PRODUCT_ID'));

    $products = [];
    foreach ($arResult['BASKET_ITEMS'] as $arItem) {
        $products[] = [
            'cid'        => $productsInfo[$arItem['PRODUCT_ID']]['cid'],
            'cname'      => array_pop($productsInfo[$arItem['PRODUCT_ID']]['cname']),
            'pid'        => $productsInfo[$arItem['PRODUCT_ID']]['pid'],
            'pname'      => $productsInfo[$arItem['PRODUCT_ID']]['pname'],
            'quantity'   => $arItem['QUANTITY'],
            'price'      => (float)$arItem['PRICE'],
            'currency'   => $productsInfo[$arItem['PRODUCT_ID']]['currency'],
            'variant_id' => $productsInfo[$arItem['PRODUCT_ID']]['variant_id']
        ];
    }

    \Adspire\Manager::getInstance()->setContainerElement(['push' => ['TypeOfPage' => 'basket', 'Basket' => $products]]);
    /** Adspire end */
}

// свойство Omni Tablet ID не служебное, его нужно исключить из списка обычных свойств
$rsProp = \CSaleOrderProps::GetList(array(), array('CODE' => array('OMNI_TABLET_ID')));
if ($arProp = $rsProp->Fetch()) $arResult['TABLET']['PROP'] = $arProp;

$arCheckKeys = array('USER_PROPS_Y', 'USER_PROPS_N');
foreach ($arCheckKeys as $keyType) {
    foreach ($arResult['ORDER_PROP'][$keyType] as $key => $arProp) {
        if ($arProp['ID'] == $arResult['TABLET']['PROP']['ID']) unset($arResult['ORDER_PROP'][$keyType][$key]);
    }
}

$arResult['need_reload'] = ($arResult['NEED_SUBMIT']) ? 'Y' : 'N';

if (class_exists('\Jamilco\Loyalty\Bonus')) {
    global $skipCheckCardType, $manzanaDeliveryId, $manzanaPaymentId;
    $skipCheckCardType = true; // пропустить проверку на бренд карты
    $manzanaDeliveryId = $deliverySelectedId;
    $manzanaPaymentId = $paySystemSelectedId;

    $arCardData = \Jamilco\Loyalty\Bonus::getData();

    if ($arResult['GIFT_ADDED']) $arResult['need_reload'] = 'Y';
    if (method_exists('\Jamilco\Loyalty\Common', 'discountsAreMoved') && \Jamilco\Loyalty\Common::discountsAreMoved()) {
        global $priceChangedCustom;
        // никаких подарков на сайте
        if ($priceChangedCustom === 'N' || $priceChangedCustom === 'Y') $arResult['need_reload'] = 'Y';

        if ($_SESSION['MANZANA_GIFT']) {
            $arResult["GIFTS"] = getGiftData($_SESSION['MANZANA_GIFT']);
        }
    }
}
if (!$arResult["GIFTS"]) $arResult["GIFTS"] = [];

// список табельных номеров для ввода
if (Loader::includeModule("jamilco.omni")) {
    $arResult['TABLET']['LIST'] = Jamilco\Omni\Tablet::getCurrentShopList();
}

// таблица соответствия размеров US->RU
$arResult['SIZES_TABLE'] = [
    '5'    => 35,
    '5.5'  => 35.5,
    '6'    => 36,
    '6.5'  => 36.5,
    '7'    => 37,
    '7.5'  => 37.5,
    '8'    => 38,
    '8.5'  => 39,
    '9'    => 40,
    '9.5'  => 40.5,
    '10'   => 41,
    '11'   => 42,
    // на всякий случай для русских размеров
    '35'   => 35,
    '35.5' => 35.5,
    '36'   => 36,
    '36.5' => 36.5,
    '37'   => 37,
    '37.5' => 37.5,
    '38'   => 38,
    '39'   => 39,
    '40'   => 40,
    '40.5' => 40.5,
    '41'   => 41,
    '42'   => 42,
];

$productsSort = \Jamilco\Loyalty\Common::productsSort();
if ($productsSort > \Jamilco\Loyalty\Common::NO_SORT) {
    uasort(
        $arResult['BASKET_ITEMS'],
        $productsSort === \Jamilco\Loyalty\Common::SORT_PRICE_ASC ?
            "\Jamilco\Loyalty\Common::cmpPriceAsc" : "\Jamilco\Loyalty\Common::cmpPriceDesc"
    );
}

//Массив для формирования значений селекторов выбора даты-время
$arResult['JS_OBJ']['deliveryTime'] = Delivery\Helper::getDeliveryTimes($arResult['LOCATION']['ID']);


/** Подготавливаем массив для window.dataLayer */
foreach ($arResult['BASKET_ITEMS'] as $item) {
    $productId = (int)$item['PRODUCT_XML_ID'];

    //находим ID категории по товару
    $productRes = CIBlockElement::GetByID($productId);
    $product = $productRes->GetNext();
    // Массив разделов
    $arSectionsByCurrent = [];
    $res = CIBlockSection::GetNavChain(false, $product['IBLOCK_SECTION_ID']);
    while ($arSectionPath = $res->GetNext()) {
        $arSectionsByCurrent[] = $arSectionPath['NAME'];
    }
    $category = implode('/', $arSectionsByCurrent);

    // BRAND
    $brandProps = CIBlockElement::GetProperty(IBLOCK_CATALOG_ID, $productId, [], ['CODE' => 'BRAND']);
    $brand = $brandProps->Fetch()['VALUE_ENUM'];

    $arResult['JS_OBJ']['WDL_ORDER'][$productId] = [
        'currencyCode'  => $item['CURRENCY'],
        'name'          => $product['NAME'],
        'id'            => (string)$item['PRODUCT_ID'],
        'price'         => number_format(round($item['PRICE']), 2, '.', ''),
        'brand'         => $brand,
        'category'      => $category,
        'variant'       => $item['NAME'],
        'quantity'      => $item['QUANTITY'],
    ];
}