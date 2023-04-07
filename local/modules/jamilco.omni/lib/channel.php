<?

namespace Jamilco\Omni;

use \Bitrix\Main\Loader;
use \Bitrix\Iblock\TypeTable;
use \Bitrix\Iblock\IblockTable;
use \Bitrix\Iblock\SectionTable;
use \Bitrix\Sale;
use \Bitrix\Highloadblock\HighloadBlockTable;
use \Jamilco\Main\Oracle;

class Channel
{

    const HlName = 'OmniChannel';
    const BASE_PRICE_ID = 1;
    const SALE_PRICE_ID = 2;

    const MINUTES_FOR_CACHE_RETAIL = 30; // количество минут для кеша наличия на розничных складах

    public static $itemFlags = ['Delivery', 'DayDelivery', 'PVZ', 'HideRetail', 'OMNI_Retail', 'OMNI_Pickup', 'OMNI_Delivery', 'Shop2Shop'];
    public static $shopFlags = ['OMNI_Retail', 'OMNI_Pickup', 'OMNI_Delivery', 'DayDelivery', 'Shop2Shop', 'SaleCanRetail'];
    public static $flagsTitle = [
        'Delivery'      => 'Курьерская доставка со склада',
        'DayDelivery'   => 'Экпресс-доставка из РМ',
        'PVZ'           => 'Доставка в ПВЗ',
        'Pick_Point'    => 'Доставка в ПВЗ', // дубль из-за несовпадения названия флага здесь и в свойстве заказа OmniChannel
        'HideRetail'    => 'Скрыть &quot;Бронирование&quot; в карточке товара',
        'OMNI_Retail'   => 'Самовывоз из РМ',
        'OMNI_Pickup'   => 'Доставка в РМ',
        'OMNI_Delivery' => 'Курьерская доставка из РМ',
        'Shop2Shop'     => 'Доставка из одного РМ в другой РМ',
        'SaleCanRetail' => 'Sale-товары можно бронировать',
    ];

    /**
     * возврат информации по разрешенной доставке
     *
     * @param array  $arOffer              [
     *                                     ID              -
     *                                     IBLOCK_ID       -
     *                                     ARTICLE         - артикул
     *                                     QUANTITY        - количество на складе
     *                                     DENIED_SHOPS    - если Y, то список магазинов не запрашивается
     *                                     ]
     * @param array  $arItem               [ID | IBLOCK_SECTION_ID | IBLOCK_ID]
     * @param bool   $getStores            получить списки РМ по типам доставки, связанным с РМ
     * @param bool   $getOnlyDeliveryFlags оставляем только флаги типов доставки
     * @param string $cityName             название города для фильтрации складов
     *
     * @return array|bool
     */
    public static function getDeliveryData($arOffer = [], $arItem = [], $getStores = false, $getOnlyDeliveryFlags = true, $cityName = '')
    {
        if (!$arOffer['ID']) return false;
        Loader::includeModule("catalog");

        if (!$arOffer['IBLOCK_ID']) {
            $arOff = \CIBlockElement::GetList([], ['ID' => $arOffer['ID']], false, ['nTopCount' => 1], ['IBLOCK_ID'])->Fetch();
            $arOffer['IBLOCK_ID'] = $arOff['IBLOCK_ID'];
        }
        if (!$arOffer['ARTICLE']) $arOffer = self::getOfferArticle($arOffer);

        if (!$arItem['ID'] || !$arItem['']) {
            $arOff = \CIBlockElement::GetList(
                [],
                ['ID' => $arOffer['ID'], 'IBLOCK_ID' => $arOffer['IBLOCK_ID']],
                false,
                ['nTopCount' => 1],
                [
                    'IBLOCK_ID',
                    'PROPERTY_CML2_LINK',
                    'PROPERTY_CML2_LINK.IBLOCK_ID',
                    'PROPERTY_CML2_LINK.IBLOCK_SECTION_ID',
                ]
            )->Fetch();
            $arItem = [
                'ID'                => $arOff['PROPERTY_CML2_LINK_VALUE'],
                'IBLOCK_ID'         => $arOff['PROPERTY_CML2_LINK_IBLOCK_ID'],
                'IBLOCK_SECTION_ID' => $arOff['PROPERTY_CML2_LINK_IBLOCK_SECTION_ID'],
            ];
        }

        // все флаги, участвующие в расчетах доставки
        $arAllFlags = [];

        $arItemSections = []; // массив разделов, в которые входит товар
        $arFlags = self::getFlagForOffer($arOffer, $arItem, $arItemSections);

        if ($arOffer['CHECK_LOCATION']) {
            $excludeSections = \COption::GetOptionString("jamilco.omni", "exclude.sections");
            $excludeLocations = \COption::GetOptionString("jamilco.omni", "exclude.locations");

            $excludeSections = explode(',', $excludeSections);
            $excludeLocations = explode(',', $excludeLocations);

            TrimArr($excludeSections);
            TrimArr($excludeLocations);

            if ($excludeSections && $excludeLocations) {

                // проверим локацию
                $locValid = false;
                foreach ($arOffer['CHECK_LOCATION'] as $locId) {
                    if (in_array($locId, $excludeLocations)) {
                        $locValid = true;
                        break;
                    }
                }

                if (!$locValid) {
                    // проверим раздел

                    $sectValid = false;
                    foreach ($arItemSections as $sectId) {
                        if (in_array($sectId, $excludeSections)) {
                            $sectValid = true;
                            break;
                        }
                    }

                    if ($sectValid) return $arAllFlags; // возвращаем пустой массив
                }
            }
        }

        foreach ($arFlags as $flag) {
            $arAllFlags[ToUpper($flag).'_T'] = 'Y';
        }

        if (!$arOffer['QUANTITY']) {
            $arProduct = \CCatalogProduct::GetByID($arOffer['ID']);
            $arOffer['QUANTITY'] = $arProduct['QUANTITY'];
        }

        global $skipCatalogQuantityCheck; // флаг, при котором пропускается проверка на CATALOG_QUANTITY

        // наличие товара на складе
        if ($arOffer['QUANTITY'] > 0 || $skipCatalogQuantityCheck) $arAllFlags['STORE_AV'] = 'Y';

        // ФЛАГ 1: доставка со склада
        if ($arAllFlags['STORE_AV'] == 'Y' && $arAllFlags['DELIVERY_T']) $arAllFlags['DELIVERY_T_DEV'] = 'Y';

        // ФЛАГ 5: самовывоз из ПВЗ
        if ($arAllFlags['STORE_AV'] == 'Y' && $arAllFlags['PVZ_T'] == 'Y') $arAllFlags['PICKUP_POINT_T_DEV'] = 'Y';

        if (0 && $arOffer['DENIED_SHOPS'] == 'Y') { // больше не используется
            // не запрашиваем список РМ, возвращает то, что есть - флаги самого товара
            // соединим в один флаг данные о "возможности бронирования товара"
            if ($arAllFlags['OMNI_RETAIL_T'] == 'Y' ||
                $arAllFlags['OMNI_PICKUP_T'] == 'Y' ||
                $arAllFlags['OMNI_DELIVERY_T'] == 'Y' ||
                $arAllFlags['SHOP2SHOP_T'] == 'Y'
            ) {
                $arAllFlags['PM_N_PICK'] = 'Y';
            }
        } else {
            // запрашиваем список РМ
            $arShops = self::getShops($arOffer, $cityName, false, $arOffer['DENIED_OCS']);
            foreach ($arShops as $key => $arOne) {
                $arShop = $arOne['INFO'];
                if (!$arShop['ID']) continue;
                $arShop['QUANTITY'] = $arOne['QUANTITY'];
                $arShop['FLAGS'] = self::getShopFlag($arShop, true);

                $hasSalePrice = in_array('has_sale_price', $arFlags);
                $canRezerv = (!$hasSalePrice || $arShop['FLAGS']['SALECANRETAIL'] == 'Y') ? true : false;

                // сохраним флаги
                $arShops[$key]['INFO'] = $arShop;

                // ФЛАГ 2: самовывоз в РМ
                if ($canRezerv && $arAllFlags['OMNI_RETAIL_T'] == 'Y' && $arShop['FLAGS']['OMNI_RETAIL'] == 'Y' && $arShop['QUANTITY'] > 0) $arAllFlags['PM_N_PICK'] = 'Y';

                // ФЛАГ 3: доставка в РМ (со склада)
                if (!$hasSalePrice && $arAllFlags['OMNI_PICKUP_T'] == 'Y' && $arShop['FLAGS']['OMNI_PICKUP'] == 'Y' && $arAllFlags['DELIVERY_T_DEV'] == 'Y') $arAllFlags['PM_N_DEV_TO'] = 'Y';

                // ФЛАГ 4: доставка из РМ
                if ($canRezerv && $arAllFlags['OMNI_DELIVERY_T'] == 'Y' && $arShop['FLAGS']['OMNI_DELIVERY'] == 'Y' && $arShop['QUANTITY'] > 0) $arAllFlags['PM_N_DEV_FROM'] = 'Y';

                // ФЛАГ 6: доставка из одного РМ в другой РМ
                // флаг рассчитывается после всего списка складов

                if (!$canRezerv) unset($arShops[$key]['INFO']['FLAGS']['OMNI_RETAIL']);
                if ($hasSalePrice) unset($arShops[$key]['INFO']['FLAGS']['OMNI_PICKUP']);

                // если товар сейловый, но в магазине не разрешена доставка для сейловых товаров
                if($hasSalePrice && $arShop['FLAGS']['SALECANRETAIL'] != 'Y') unset($arShops[$key]['INFO']['FLAGS']['OMNI_DELIVERY']);
            }

            // ФЛАГ 6: доставка из одного РМ в другой РМ
            // т.к. мы не проверили еще все склады (без наличия товара) на флаг Shop2Shop, то обойдемся без него
            if ($arAllFlags['SHOP2SHOP_T'] == 'Y' && $arAllFlags['PM_N_DEV_FROM'] == 'Y') $arAllFlags['SHOP2SHOP_T_DEV'] = 'Y';

            if ($getStores) {
                // получить списки РМ по типам доставки, связанным с РМ
                $arStoreIDs = [];
                foreach ($arShops as $arOne) {
                    $arShop = $arOne['INFO'];
                    $arStoreIDs[] = $arShop['ID'];

                    // в этом списке склады с >0 наличием товара

                    // самовывоз в РМ
                    if ($arAllFlags['OMNI_RETAIL_T'] == 'Y' && $arShop['FLAGS']['OMNI_RETAIL'] == 'Y' && $arShop['QUANTITY'] > 0) {
                        $arAllFlags['SHOP']['RETAIL'][] = $arShop;
                    } elseif ($arAllFlags['OMNI_PICKUP_T'] == 'Y' && $arShop['FLAGS']['OMNI_PICKUP'] == 'Y' && $arAllFlags['PM_N_DEV_TO'] == 'Y') {
                        // если товар в магазине есть, но его нельзя там забронировать, проверим - можно ли в этот магазин товар доставить
                        $arAllFlags['SHOP']['PICKUP'][] = $arShop;
                    }

                    // доставка из РМ
                    if ($arAllFlags['OMNI_DELIVERY_T'] == 'Y' && $arShop['FLAGS']['OMNI_DELIVERY'] == 'Y' && $arShop['QUANTITY'] > 0) {
                        $arAllFlags['SHOP']['DELIVERY'][] = $arShop;
                    }

                    // быстрая доставка из РМ
                    if ($arAllFlags['DAYDELIVERY_T'] == 'Y' && $arShop['FLAGS']['DAYDELIVERY'] == 'Y' && $arShop['QUANTITY'] > 0) {
                        $arAllFlags['SHOP']['DAY_DELIVERY'][] = $arShop;
                    }
                }

                if ($arAllFlags['OMNI_PICKUP_T'] == 'Y' || $arAllFlags['SHOP2SHOP_T'] == 'Y') {
                    // получим список складов, в которых товара нет, но в которые можно доставить
                    $st = \CCatalogStore::GetList([], ['!ID' => $arStoreIDs, 'ACTIVE' => 'Y']);
                    while ($arShop = $st->Fetch()) {
                        $arShop['FLAGS'] = self::getShopFlag($arShop, true);

                        // в этом списке склады с нулевым количеством товара, проверим возможность доставки

                        // доставка в РМ (со склада)
                        if ($arAllFlags['OMNI_PICKUP_T'] == 'Y' && $arShop['FLAGS']['OMNI_PICKUP'] == 'Y' && $arAllFlags['PM_N_DEV_TO'] == 'Y') {
                            $arAllFlags['SHOP']['PICKUP'][] = $arShop;
                        }

                        // доставка из одного РМ в другой РМ
                        if ($arAllFlags['SHOP2SHOP_T'] == 'Y' && $arAllFlags['PM_N_DEV_FROM'] == 'Y' && $arShop['FLAGS']['SHOP2SHOP'] == 'Y') {
                            $arAllFlags['SHOP']['2SHOP'][] = $arShop;
                        }
                    }
                }
            }
        }

        $arAllFlags['SHOP'] = self::clearShopsByCity($arAllFlags['SHOP'], $cityName);

        if ($getOnlyDeliveryFlags) {
            // оставляем только флаги типов доставки
            $arCanFlags = [
                'DELIVERY_T_DEV',       // доставка со склада
                'DAYDELIVERY_T',        // быстрая доставка
                'PM_N_PICK',            // самовывоз в РМ
                'PM_N_DEV_TO',          // доставка в РМ
                'PM_N_DEV_FROM',        // доставка из РМ
                'PICKUP_POINT_T_DEV',   // самовывоз из ПВЗ
                'SHOP2SHOP_T_DEV',      // доставка из РМ в РМ
                'SHOP',                 // список магазинов
            ];
            $arNewAllFlags = [];
            foreach ($arCanFlags as $flag) {
                if ($arAllFlags[$flag]) $arNewAllFlags[$flag] = $arAllFlags[$flag];
            }
            $arAllFlags = $arNewAllFlags;
        }

        return $arAllFlags;
    }

    public static function clearShopsByCity($arShopsBlock = [], $cityName = '')
    {
        if (!$cityName) return $arShopsBlock;
        $cityName = ToLower($cityName);

        foreach ($arShopsBlock as $type => $arShops) {
            if ($type == 'DELIVERY') continue; // OmniDelivery - по всей стране
            foreach ($arShops as $key => $arShop) {
                $shopTitle = ToLower($arShop['TITLE']);
                if (!substr_count($shopTitle, $cityName)) {
                    unset($arShops[$key]);
                }
            }
            if (count($arShops)) {
                $arShops = array_values($arShops);
                $arShopsBlock[$type] = $arShops;
            } else {
                unset($arShopsBlock[$type]);
            }
        }

        return $arShopsBlock;
    }

    /**
     * флаги для склада \ магазина
     *
     * @param array $arStore
     *
     * @return array
     */
    public static function getShopFlag($arStore = [], $reformatFlags = false)
    {
        if (!$arStore['ID']) return false;
        if (!$arStore['TITLE']) {
            $arStore = \CCatalogStore::GetList([], ['ID' => $arStore['ID']])->Fetch();
        }

        $hlDataClass = self::getHlClass();

        $arFlags = self::getFlags($hlDataClass, 'element', $arStore['ID']);
        if (!$arFlags) {
            $arData = explode(',', $arStore['TITLE']);
            TrimArr($arData, true);
            $arFlags = self::getFlags($hlDataClass, 'section', $arData[1]);
        }

        // реформат флагов
        if ($reformatFlags) {
            $arNewFlags = [];
            foreach ($arFlags as $flag) {
                $arNewFlags[ToUpper($flag)] = 'Y';
            }
            $arFlags = $arNewFlags;
        }

        return $arFlags;
    }

    /**
     * пересохраняет значения свойства "Розничное наличие по городам" для всех Товаров и ТП
     */
    public static function reSaveCityAvailables($checkDelivery = false, $checkOfferId = 0, $returnData = false)
    {
        $arLog = [];
        $arCatalog = self::getCatalogIblock();

        $storeCities = \Jamilco\Main\Retail::getCityStores(false, true); // список складов по городам, из списка уже убраны все неактивные РМ (без флагов Omni)

        $arData = [
            'STORES' => [],
            'SKU'    => [],
            'ITEMS'  => [],
        ];
        $arStoresToPickup = []; // массив ID РМ, куда можно доставлять со склада
        foreach ($storeCities as $city => $arCityStores) {
            foreach ($arCityStores as $storeId) {
                $arData['STORES'][$storeId] = [
                    'CITY'  => $city,
                    'FLAGS' => self::getShopFlag(['ID' => $storeId], true)
                ];
                if ($arData['STORES'][$storeId]['FLAGS']['OMNI_PICKUP'] == 'Y') {
                    $arStoresToPickup[] = $storeId;
                }
            }
        }
        $arLog['OUTLET'] = self::checkOutletOffers($arData['STORES']);

        $arStoreFilter = [
            'STORE_ID' => array_keys($arData['STORES']),
            '>AMOUNT'  => 0,
        ];
        if ($checkOfferId) $arStoreFilter['PRODUCT_ID'] = $checkOfferId;

        // получим остатки
        $st = \CCatalogStoreProduct::GetList(
            [],
            $arStoreFilter,
            false,
            false,
            ['ID', 'PRODUCT_ID', 'STORE_ID']
        );
        while ($arStoreProduct = $st->Fetch()) {
            $arData['SKU'][$arStoreProduct['PRODUCT_ID']]['STORE_ID'][] = $arStoreProduct['STORE_ID'];
        }

        // получим данные по ТП
        $arOfferFilter = [
            'IBLOCK_ID'           => $arCatalog['OFFERS_IBLOCK_ID'],
            '!PROPERTY_CML2_LINK' => false,
        ];

        if (!$checkDelivery) {
            // проверяем только возможность самовывоза
            $arOfferFilter['ID'] = ($arData['SKU']) ? array_keys($arData['SKU']) : [0];
        } else {
            $arOfferFilter[0] = [
                'LOGIC' => 'OR',
                'ID'    => ($arData['SKU']) ? array_keys($arData['SKU']) : [0],
                0       => [
                    '!ID'               => ($arData['SKU']) ? array_keys($arData['SKU']) : [0],
                    '>CATALOG_QUANTITY' => 0,
                ]
            ];

            if ($checkOfferId) $arOfferFilter[0][0]['ID'] = $checkOfferId;


            // получим вариант значения свойства "Доступность доставки во все города"
            $en = \CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $arCatalog['OFFERS_IBLOCK_ID'], 'CODE' => 'DELIVERY_CAN']);
            $arDeliveryCanEnum = $en->Fetch();
        }

        $of = \CIblockElement::GetList(
            [],
            $arOfferFilter,
            false,
            false,
            [
                'ID',
                'CATALOG_QUANTITY',
                'CATALOG_GROUP_'.self::BASE_PRICE_ID,
                'CATALOG_GROUP_'.self::SALE_PRICE_ID,
                'PROPERTY_PRICE_WITHOUT_DISCOUNT',
                'PROPERTY_CML2_LINK',
                'PROPERTY_CML2_LINK.IBLOCK_SECTION_ID',
            ]
        );
        while ($arOffer = $of->Fetch()) {
            $arData['SKU'][$arOffer['ID']]['ITEM'] = $arOffer['PROPERTY_CML2_LINK_VALUE'];
            $arData['SKU'][$arOffer['ID']]['ITEM_SECTION_ID'] = $arOffer['PROPERTY_CML2_LINK_IBLOCK_SECTION_ID'];
            $arData['SKU'][$arOffer['ID']]['QUANTITY'] = $arOffer['CATALOG_QUANTITY'];

            $price = $arOffer['CATALOG_PRICE_'.self::BASE_PRICE_ID];
            $oldPrice = 0;
            if ($arOffer['PROPERTY_PRICE_WITHOUT_DISCOUNT_VALUE'] > 0) {
                $oldPrice = $arOffer['PROPERTY_PRICE_WITHOUT_DISCOUNT_VALUE'];
            } elseif ($arOffer['CATALOG_PRICE_'.self::SALE_PRICE_ID] > 0) {
                $price = $arOffer['CATALOG_PRICE_'.self::SALE_PRICE_ID];
                $oldPrice = $arOffer['CATALOG_PRICE_'.self::BASE_PRICE_ID];
            }

            $arData['SKU'][$arOffer['ID']]['IS_SALE'] = ($oldPrice && $price && $oldPrice > $price) ? 'Y' : 'N';

            $arData['ITEMS'][$arOffer['PROPERTY_CML2_LINK_VALUE']] = [];
        }

        if (!$arData['ITEMS']) {
            if ($checkOfferId) {
                // ТП недоступно ко всем видам доставки
                \CIBlockElement::SetPropertyValuesEx($checkOfferId, $arCatalog['OFFERS_IBLOCK_ID'], ['RETAIL_CITIES' => false]);
                \CIBlockElement::SetPropertyValuesEx($checkOfferId, $arCatalog['OFFERS_IBLOCK_ID'], ['DELIVERY_CAN' => false]);
                \CIBlockElement::SetPropertyValuesEx($checkOfferId, $arCatalog['OFFERS_IBLOCK_ID'], ['DELIVERY_DAY' => false]);
            }

            return false;
        }

        foreach ($arData['SKU'] as $offerId => $arOfferData) {
            if (!$arOfferData['ITEM']) continue;

            if (!$arData['ITEMS'][$arOfferData['ITEM']]['FLAGS']) {
                $arItemSections = [];
                $arData['ITEMS'][$arOfferData['ITEM']]['FLAGS'] = self::getFlagForItem($arOfferData['ITEM'], $arItemSections);
            }

            $arOfferOmni = [];

            $canDeliveryToShop = ($arOfferData['QUANTITY'] > 0 && in_array('delivery', $arData['ITEMS'][$arOfferData['ITEM']]['FLAGS']));
            foreach ($arData['ITEMS'][$arOfferData['ITEM']]['FLAGS'] as $flag) {

                $flag = ToUpper($flag);

                switch ($flag) {
                    // флаги, влияющие на доставку
                    case 'DELIVERY':
                    case 'PVZ':
                    case 'OMNI_DELIVERY':

                        if (!$checkDelivery) break;

                        if ($flag == 'OMNI_DELIVERY') {
                            // Sale-товары можно доставлять по каналу OMNI_Delivery, если в РМ стоит флаг SaleCanRetail
                            $saleDelivery = \COption::GetOptionInt("jamilco.omni", 'sale.delivery');

                            if ($arOfferData['IS_SALE'] == 'Y' && $saleDelivery) {
                                // нужно проверить РМ-флаг SaleCanRetail
                            } else {
                                // при скидочной цене доставка из РМ не действует
                                break;
                            }

                            foreach ($arOfferData['STORE_ID'] as $storeId) {
                                $arStore = $arData['STORES'][$storeId];
                                if (
                                    $arStore['FLAGS'][$flag] == 'Y' &&                                              // стоит РМ-флаг OMNI_Delivery
                                    ($arOfferData['IS_SALE'] != 'Y' || $arStore['FLAGS']['SALECANRETAIL'] == 'Y')   // товар скидочный, но стоит РМ-флаг SaleCanRetail
                                ) {
                                    $arOfferOmni['DELIVERY'] = 'Y';
                                }
                            }
                        } else {
                            if ($arOfferData['QUANTITY'] <= 0) break;

                            $arOfferOmni['DELIVERY'] = 'Y';
                        }

                        break;

                    // флаги, влияющие на самовывоз
                    case 'OMNI_RETAIL':
                    case 'OMNI_PICKUP':
                    case 'SHOP2SHOP':
                    case 'DAYDELIVERY':

                        if ($arOfferData['IS_SALE'] == 'Y' && ($flag == 'DAYDELIVERY' || $flag == 'SHOP2SHOP')) break; // sale-товары нельзя доставлять из РМ
                        if ($flag == 'OMNI_PICKUP' && !$canDeliveryToShop) break; // флаг работает только при возможности доставки

                        $arStoresToCheck = [];
                        if ($flag == 'OMNI_PICKUP') {
                            foreach ($arStoresToPickup as $storeId) {
                                if (in_array($storeId, $arOfferData['STORE_ID'])) continue; // проверяем только те РМ, в которых нет этого товара в наличии

                                $arStoresToCheck[] = $storeId;
                            }
                        } else {
                            $arStoresToCheck = $arOfferData['STORE_ID'];
                        }

                        foreach ($arStoresToCheck as $storeId) {
                            $arStore = $arData['STORES'][$storeId];

                            if ($arOfferData['IS_SALE'] == 'Y') {
                                $skip = ($arStore['FLAGS']['SALECANRETAIL'] == 'Y') ? false : true;

                                if ($skip) continue; // sale-товары из этого РМ нельзя бронировать, в каждом РМ мб установлен разрешающий флаг
                            }

                            if ($arStore['FLAGS'][$flag] == 'Y') {
                                $arOfferOmni['ALL'][$arStore['CITY']] = '';
                                $arOfferOmni[$flag][$arStore['CITY']] = '';
                            }
                        }

                        break;
                }
            }

            $arData['SKU'][$offerId]['OMNI'] = $arOfferOmni;
        }

        // формат для сохранения
        foreach ($arData['SKU'] as $offerId => $arOfferData) {
            $saveCities = ($arOfferData['OMNI']['ALL']) ? array_keys($arOfferData['OMNI']['ALL']) : false;
            $arData['SKU'][$offerId]['SAVE'] = $saveCities;
            $arData['SKU'][$offerId]['DAY_DELIVERY'] = array_keys($arOfferData['OMNI']['DAYDELIVERY']);

            $arItemCities = $arData['ITEMS'][$arOfferData['ITEM']]['CITY'];
            if (!$arItemCities) $arItemCities = [];
            $arItemCities = array_merge($arItemCities, $saveCities);
            $arData['ITEMS'][$arOfferData['ITEM']]['SAVE'] = ($arItemCities) ?: false;
        }

        // сохраняем данные
        foreach ($arData['SKU'] as $offerId => $arOfferData) {
            // города с розничным наличием
            \CIBlockElement::SetPropertyValuesEx($offerId, $arCatalog['OFFERS_IBLOCK_ID'], ['RETAIL_CITIES' => $arOfferData['SAVE']]);
            \CIBlockElement::SetPropertyValuesEx($offerId, $arCatalog['OFFERS_IBLOCK_ID'], ['DELIVERY_DAY' => $arOfferData['DAY_DELIVERY']]);

            if ($checkDelivery) {
                // возможность доставки в города
                $deliveryCanSave = ($arOfferData['OMNI']['DELIVERY'] == 'Y') ? $arDeliveryCanEnum['ID'] : false;
                \CIBlockElement::SetPropertyValuesEx($offerId, $arCatalog['OFFERS_IBLOCK_ID'], ['DELIVERY_CAN' => $deliveryCanSave]);
            }

            $arLog['SKU']['SAVE']++;
        }

        foreach ($arData['ITEMS'] as $itemId => $arItemData) {
            \CIBlockElement::SetPropertyValuesEx($itemId, $arCatalog['IBLOCK_ID'], ['RETAIL_CITIES' => $arItemData['SAVE']]);
            $arLog['ITEM']['SAVE']++;
        }

        if ($returnData) return $arData;

        if (!$checkOfferId) {
            // удаляем данные у тех элементов, которые не были обновлены
            $of = \CIblockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $arCatalog['OFFERS_IBLOCK_ID'],
                    '!ID'       => array_keys($arData['SKU']),
                    [
                        'LOGIC'                   => 'OR',
                        '!PROPERTY_RETAIL_CITIES' => false,
                        '!PROPERTY_DELIVERY_DAY'  => false,
                    ]
                ],
                false,
                false,
                ['ID']
            );
            while ($arOffer = $of->Fetch()) {
                \CIBlockElement::SetPropertyValuesEx($arOffer['ID'], $arCatalog['OFFERS_IBLOCK_ID'], ['RETAIL_CITIES' => false]);
                \CIBlockElement::SetPropertyValuesEx($arOffer['ID'], $arCatalog['OFFERS_IBLOCK_ID'], ['DELIVERY_DAY' => false]);
                $arLog['SKU']['RETAIL_DELETE']++;
            }

            $el = \CIblockElement::GetList(
                [],
                [
                    'IBLOCK_ID'               => $arCatalog['IBLOCK_ID'],
                    '!ID'                     => array_keys($arData['ITEMS']),
                    '!PROPERTY_RETAIL_CITIES' => false,
                ],
                false,
                false,
                ['ID']
            );
            while ($arItem = $el->Fetch()) {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arCatalog['IBLOCK_ID'], ['RETAIL_CITIES' => false]);
                $arLog['ITEM']['RETAIL_DELETE']++;
            }

            if ($checkDelivery) {
                $of = \CIblockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID'              => $arCatalog['OFFERS_IBLOCK_ID'],
                        '!ID'                    => array_keys($arData['SKU']),
                        '!PROPERTY_DELIVERY_CAN' => false,
                    ],
                    false,
                    false,
                    ['ID']
                );
                while ($arOffer = $of->Fetch()) {
                    \CIBlockElement::SetPropertyValuesEx($arOffer['ID'], $arCatalog['OFFERS_IBLOCK_ID'], ['DELIVERY_CAN' => false]);
                    $arLog['SKU']['CAN_DELIVERY_DELETE']++;
                }
            }
        }

        return $arLog;
    }

    /**
     * помечает свойство OUTLET-товарам
     *
     * @param array $arStores
     *
     * @return array|bool
     */
    public static function checkOutletOffers($arStores = [])
    {
        $arLog = [];

        $en = \CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => IBLOCK_SKU_ID, 'CODE' => 'OUTLET']);
        if (!$arEnum = $en->Fetch()) return false;

        $arStoresID = [];
        foreach ($arStores as $storeId => $arStore) {
            if ($arStore['FLAGS']['SALECANRETAIL'] == 'Y') $arStoresID[] = $storeId;
        }

        $arIDs = [
            'PRODUCT' => [],
            'SALE'    => [],
        ];
        if ($arStoresID) {
            $st = \CCatalogStoreProduct::getList(
                [],
                [
                    'STORE_ID' => $arStoresID,
                    '>AMOUNT'  => 0
                ],
                false,
                false,
                ['PRODUCT_ID']
            );
            while ($arStoreAmount = $st->Fetch()) {
                $arIDs['PRODUCT'][] = $arStoreAmount['PRODUCT_ID'];
            }

            if ($arIDs['PRODUCT']) {
                $of = \CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID'        => IBLOCK_SKU_ID,
                        'ID'               => $arIDs['PRODUCT'],
                        '>CATALOG_PRICE_2' => 0,
                    ],
                    false,
                    false,
                    ['ID', 'PROPERTY_OUTLET']
                );
                while ($arOffer = $of->Fetch()) {
                    $arIDs['SALE'][] = $arOffer['ID'];
                    if (!$arOffer['PROPERTY_OUTLET_VALUE']) {
                        \CIBlockElement::SetPropertyValuesEx($arOffer['ID'], IBLOCK_SKU_ID, ['OUTLET' => $arEnum['ID']]);
                        $arLog['SET']++;
                    } else {
                        $arLog['EXIST']++;
                    }
                }
            }
        }

        $of = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID'        => IBLOCK_SKU_ID,
                '!PROPERTY_OUTLET' => false,
                '!ID'              => $arIDs['SALE'],
            ],
            false,
            false,
            ['ID']
        );
        while ($arOffer = $of->Fetch()) {
            \CIBlockElement::SetPropertyValuesEx($arOffer['ID'], IBLOCK_SKU_ID, ['OUTLET' => false]);
            $arLog['DELETE']++;
        }

        return $arLog;
    }

    /**
     * возвращает список неактивных РМ (нет ни одного флага Omni)
     *
     * @return array
     */
    public static function getHiddenShops()
    {
        $arResult = [];
        $active = 0;
        $st = \CCatalogStore::GetList([], ['ACTIVE' => 'Y']);
        while ($arShop = $st->Fetch()) {
            $arShop['FLAGS'] = self::getShopFlag($arShop, true);
            if (!count($arShop['FLAGS']) || array_key_exists('NO', $arShop['FLAGS'])) {
                $arResult[] = $arShop['ID'];
            } else {
                $active++;
            }
        }

        return $arResult;
    }

    /**
     * получаем артикул для ТП
     *
     * @param array $arOffer
     *
     * @return array
     */
    public static function getOfferArticle($arOffer = [])
    {
        $arOff = \CIBlockElement::GetList(
            [],
            ['ID' => $arOffer['ID'], 'IBLOCK_ID' => $arOffer['IBLOCK_ID']],
            false,
            ['nTopCount' => 1],
            ['IBLOCK_ID', 'NAME']
        )->Fetch();
        $arOffer['ARTICLE'] = $arOff['NAME'];

        return $arOffer;
    }

    /**
     * флаги для Торгового Предложения
     *
     * @param array $arOffer [ID | IBLOCK_ID]
     * @param array $arItem  [ID | IBLOCK_SECTION_ID | IBLOCK_ID]
     *
     * @return array|bool
     */
    public static function getFlagForOffer($arOffer = [], $arItem = [], &$arItemSections = [])
    {
        if (!$arOffer['ID']) return false;
        Loader::includeModule('iblock');

        if (!$arOffer['IBLOCK_ID']) {
            $arOff = \CIBlockElement::GetList([], ['ID' => $arOffer['ID']], false, ['nTopCount' => 1], ['IBLOCK_ID'])->Fetch();
            $arOffer['IBLOCK_ID'] = $arOff['IBLOCK_ID'];
        }

        $arFlags = self::getFlagForItem($arItem['ID'], $arItemSections);

        $hasSalePrice = self::hasSalePrice($arOffer['ID'], $arOffer['IBLOCK_ID']);

        $arClearFlags = []; // удалим эти флаги
        if ($hasSalePrice) {
            $arFlags[] = 'has_sale_price';
            if (0) { //!$canSaleItemsBeReserved - больше не используется, возможность бронирования проверяется для каждого РМ отдельно
                // ТП со скидочной ценой не могут быть забронированы

                // убираем все флаги, связанные с РМ
                $arClearFlags = [
                    'omni_retail',      // самовывоз из РМ
                    'omni_pickup',      // доставка в РМ
                    'omni_delivery',    // доставка из РМ
                    'daydelivery',      // экспресс-доставка из РМ
                    'shop2shop',        // доставка из одного РМ в другой
                ];

            } else {
                // ТП со скидочной ценой МОГУТ быть забронированы
                // но не могут быть доставлены из РМ

                $arClearFlags = [
                    'omni_delivery',    // доставка из РМ
                    'daydelivery',      // экспресс-доставка из РМ
                    'shop2shop',        // доставка из одного РМ в другой
                ];

                // Sale-товары можно доставлять по каналу OMNI_Delivery, если в РМ стоит флаг SaleCanRetail
                $saleDelivery = \COption::GetOptionInt("jamilco.omni", 'sale.delivery');
                if ($saleDelivery) {
                    foreach ($arClearFlags as $key => $val) {
                        if ($val == 'omni_delivery') unset($arClearFlags[$key]);
                    }
                }
            }
        }
        if ($arClearFlags) {
            foreach ($arFlags as $key => $flag) {
                if (in_array($flag, $arClearFlags)) unset($arFlags[$key]);
            }
            $arFlags = array_values($arFlags);
        }

        return $arFlags;
    }

    /**
     * задана ли скидочная цена на товар
     *
     * @param int $id
     * @param int $iblockId
     *
     * @return bool
     */
    public static function hasSalePrice($id = 0, $iblockId = 0)
    {
        $hasSalePrice = false;

        if ($arSalePrice = \CCatalogGroup::GetByID(self::SALE_PRICE_ID)) {
            $pr = \CPrice::GetList([], ['CATALOG_GROUP_ID' => self::SALE_PRICE_ID, 'PRODUCT_ID' => $id]);
            if ($arPrice = $pr->Fetch()) {
                if ($arPrice['PRICE'] > 0) {
                    $hasSalePrice = true;
                }
            }
        } else {
            // если нет скидочной цены, то проверим свойство PRICE_WITHOUT_DISCOUNT и его отличие от основной цены
            $prop = \CIBlockElement::GetProperty($iblockId, $id, [], ["CODE" => "PRICE_WITHOUT_DISCOUNT"]);
            if ($arProp = $prop->Fetch()) {
                $arProp['VALUE'] = (int)$arProp['VALUE'];
                if ($arProp['VALUE'] > 0) {
                    $pr = \CPrice::GetList([], ['CATALOG_GROUP_ID' => self::BASE_PRICE_ID, 'PRODUCT_ID' => $id]);
                    if ($arPrice = $pr->Fetch()) {
                        if ($arProp['VALUE'] > $arPrice['PRICE']) {
                            $hasSalePrice = true;
                        }
                    }
                }
            }
        }

        return $hasSalePrice;
    }

    /**
     * возвращает флаги для товара
     *
     * @param int $itemId - обязательно
     *
     * @return array
     */
    public static function getFlagForItem($itemId = 0, &$arItemSections = [])
    {
        if (!$itemId) return false;
        Loader::includeModule('iblock');

        if (!$arItemSections || !is_array($arItemSections)) $arItemSections = [];

        $hlDataClass = self::getHlClass();

        $arFlags = self::getFlags($hlDataClass, 'element', $itemId);
        if (!$arFlags) {

            // получим все разделы товара и проверим флаги по каждому, итоговый результат будет содержать все возможные "разрешающие" значения
            $se = \CIBlockElement::GetElementGroups($itemId, true, ['ID', 'ACTIVE', 'NAME', 'IBLOCK_ID']);
            while ($arSect = $se->Fetch()) {
                //if ($arSect['ACTIVE'] != 'Y') continue;

                $arSectFlags = [];
                $arSects = [];
                $nav = \CIBlockSection::GetNavChain($arSect['IBLOCK_ID'], $arSect['ID'], ['ID']);
                while ($arNav = $nav->Fetch()) {
                    $arSects[] = $arNav['ID'];
                    $arItemSections[] = $arNav['ID'];
                }
                $arSects = array_reverse($arSects);
                while (!$arSectFlags && count($arSects)) {
                    $sectId = array_shift($arSects);
                    $arSectFlags = self::getFlags($hlDataClass, 'section', $sectId);
                }
                $arFlags = array_merge($arFlags, $arSectFlags);
            }
        }

        $arFlags = array_unique($arFlags);
        $arFlags = array_values($arFlags);

        return $arFlags;
    }

    public static function getChannelName($flag = '')
    {
        return self::$flagsTitle[$flag];
    }

    /**
     * @param string $shopType
     *
     * @return mixed
     */
    public static function getTypeByShopType($shopType = '')
    {
        $type = str_replace(
            [
                'RETAIL',
                'DAY_DELIVERY', // фикс на всякий случай
                'PICKUP',
                '2SHOP',
            ],
            [
                'OMNI_Retail',
                'OMNI_Retail',
                'OMNI_Pickup',
                'Shop2Shop',
            ],
            $shopType
        );

        return $type;
    }

    /**
     * запрашивает данные о наличии товара на складах в OCS
     *
     * @param string $article  артикул ТП
     * @param string $cityName название выбранного города для фильтрации складов по нему
     *
     * @return array
     */
    public static function getShops($arOffer = [], $cityName = '', $skipCache = false, $deniedOcs = 'N')
    {
        $article = $arOffer['ARTICLE'];
        if (!$article) return false;

        Loader::IncludeModule('jamilco.main');

        /*
        // Это больше так не работает, т.к. OmniDelivery позволяет доставлять по всей стране
        // если в выбранном городе нет РМ, то и запрашивать не надо
        if ($cityName) {
            $st = \CCatalogStore::GetList([], ['~TITLE' => '%'.$cityName.'%', 'ACTIVE' => 'Y']);
            $storeCount = $st->SelectedRowsCount();
            if (!$storeCount) return [];
        }
        */

        /**
         * после каждого запроса наличия в РМ данные сохраняются:
         *  - наличие по складам
         *  - временная отметка получения данных
         *
         * при дальнейших запросах проверяется:
         *  - если временная отметка "моложе" self::MINUTES_FOR_CACHE_RETAIL минут, то берутся сохраненные данные
         *  - если временная отметка "старше" или не существует, то запрашиватся новые данные
         *  - - если при этом OCS недоступен, то берутся сохраненные данные
         */

        $hasStoreCache = false; // есть сохраненные данные
        $needNewRequest = true; // нужно запросить данные заново

        $pr = \CIBlockElement::GetProperty($arOffer['IBLOCK_ID'], $arOffer['ID'], [], ['CODE' => 'RETAIL_TIMESTAMP']);
        if ($arProp = $pr->Fetch()) {
            $timeStamp = $arProp['VALUE'];
            if ($timeStamp > 0) {
                $hasStoreCache = true;

                $lastTimeStamp = time() - self::MINUTES_FOR_CACHE_RETAIL * 60;
                if ($timeStamp > $lastTimeStamp) $needNewRequest = false;
            }
        }

        if ($skipCache) $needNewRequest = true;
        if ($deniedOcs == 'Y') $needNewRequest = false;

        if ($hasStoreCache && $needNewRequest) {
            // если удаленный сервер недоступен, то возвращаем сохраненные данные
            $serverDisable = Oracle::getInstance()->isLockFileExists();
            if ($serverDisable) $needNewRequest = false;
        }

        if ($hasStoreCache && !$needNewRequest) {
            // получим склады из сохраненных данных
            $arStoresQuantity = [];
            $st = \CCatalogStoreProduct::getList(
                [],
                [
                    'PRODUCT_ID' => $arOffer['ID'],
                    '>AMOUNT'    => 0
                ],
                false,
                false,
                ['STORE_ID', 'AMOUNT']
            );
            while ($arStoreAmount = $st->Fetch()) {
                $arStoresQuantity[$arStoreAmount['STORE_ID']] = $arStoreAmount['AMOUNT'];
            }

            // соберем результирующий массив
            $arShops = [];
            $st = \CCatalogStore::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['ID' => array_keys($arStoresQuantity), 'ACTIVE' => 'Y']);
            while ($arStore = $st->Fetch()) {
                $arShops[] = [
                    'QUANTITY' => $arStoresQuantity[$arStore['ID']],
                    'INFO'     => $arStore,
                ];
            }

        } else {
            // получим склады из удаленного сервера
            $arShops = Oracle::getInstance()->getShopsNew($article);

            // сохраним временную отметку и данные по складам
            \CIBlockElement::SetPropertyValuesEx($arOffer['ID'], $arOffer['IBLOCK_ID'], ['RETAIL_TIMESTAMP' => time()]);

            $arStoresQuantity = [];
            foreach ($arShops as $arStore) {
                if ($arStore['INFO']['ID'] == 0) {
                    // основной склад (ИМ)
                    \CCatalogProduct::Update($arOffer['ID'], ['QUANTITY' => $arStore['QUANTITY']]);
                } else {
                    $arStoresQuantity[$arStore['INFO']['ID']] = $arStore['QUANTITY'];
                }
            }
            if (!$arStoresQuantity) $arStoresQuantity[0] = 0;

            // сохраним полученные данные
            foreach ($arStoresQuantity as $storeId => $storeQuantity) {
                $st = \CCatalogStoreProduct::getList(
                    [],
                    [
                        'STORE_ID'   => $storeId,
                        'PRODUCT_ID' => $arOffer['ID'],
                    ],
                    false,
                    ['nTopCount' => 1],
                    ['ID', 'AMOUNT']
                );
                if ($arStoreAmount = $st->Fetch()) {
                    if ($arStoreAmount['AMOUNT'] != $storeQuantity) {
                        \CCatalogStoreProduct::Update($arStoreAmount['ID'], ['AMOUNT' => $storeQuantity]);
                    }
                } else {
                    $res = \CCatalogStoreProduct::Add(
                        [
                            'STORE_ID'   => $storeId,
                            'PRODUCT_ID' => $arOffer['ID'],
                            'AMOUNT'     => $storeQuantity,
                        ]
                    );
                }
            }

            // удалим количество по складам, которых не было в данных
            $st = \CCatalogStoreProduct::getList(
                [],
                [
                    '!STORE_ID'  => array_keys($arStoresQuantity),
                    'PRODUCT_ID' => $arOffer['ID'],
                    '>AMOUNT'    => 0
                ],
                false,
                false,
                ['ID', 'STORE_ID']
            );
            while ($arStoreAmount = $st->Fetch()) {
                if ($arStoreAmount['STORE_ID'] == RETAIL_STORE_ID) continue;
                \CCatalogStoreProduct::Update($arStoreAmount['ID'], ['AMOUNT' => 0]);
            }

            self::reSaveCityAvailables(true, $arOffer['ID']); // пересохранить флаги доставки для ТП
        }

        /*
        // склады не фильтруем, т.к. могут действовать способы пересылки товара между РМ
        if ($cityName) {
            // оставим только те склады, что относятся к переданному городу
            foreach ($arShops as $key => $arShop) {
                $storeTitle = ToLower($arShop['INFO']['TITLE']);
                $cityName = ToLower($cityName);
                if (!substr_count($storeTitle, $cityName)) {
                    unset($arShops[$key]);
                }
            }
            $arShops = array_values($arShops);
        }
        */

        return $arShops;
    }

    /**
     * обновляем осттатки (ИМ и РМ) для ТП
     *
     * @param int   $orderId - все ТП из заказа
     * @param array $arIDs   - все ТП из массива
     *
     * @return bool
     */
    public static function reloadQuantities($orderId = 0, $arIDs = [])
    {
        $arBaskets = [];
        if ($orderId > 0) {
            $ba = Sale\Internals\BasketTable::getList(
                [
                    'filter' => ['ORDER_ID' => $orderId],
                    'select' => ['ID', 'PRODUCT_ID']
                ]
            );
            while ($arBasket = $ba->Fetch()) {
                $arBaskets[$arBasket['ID']] = $arBasket['PRODUCT_ID'];
                $arIDs[] = $arBasket['PRODUCT_ID'];
            }
        }

        if (!$arIDs) return false;
        if (!is_array($arIDs)) $arIDs = [$arIDs];

        $arCatalog = self::getCatalogIblock();
        $arStore = [];

        $of = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arCatalog['OFFERS_IBLOCK_ID'],
                'ID'        => $arIDs,
            ],
            false,
            ['nTopCount' => count($arIDs)],
            ['ID', 'IBLOCK_ID', 'NAME']
        );
        while ($arOffer = $of->Fetch()) {
            $arItemShops = self::getShops(
                [
                    'IBLOCK_ID' => $arOffer['IBLOCK_ID'],
                    'ID'        => $arOffer['ID'],
                    'ARTICLE'   => $arOffer['NAME'],
                ],
                '',
                true // всегда запрашиваем OCS
            );
            foreach ($arItemShops as $arShop) {
                if ($arShop['INFO']['ID'] == 0) $arStore[$arOffer['ID']] = $arShop['QUANTITY'];
            }
        }

        if ($orderId) {
            $arOut = [];
            foreach ($arBaskets as $basketId => $offerId) {
                $arOut[$basketId] = $arStore[$offerId];
            }

            return $arOut;
        }

        return true;
    }

    /**
     * сохраняет полную информацию о флагах
     *
     * @param array $arData
     */
    public static function saveData($arData = [])
    {
        $hlDataClass = self::getHlClass();

        foreach ($arData as $type => $arOnes) {
            foreach ($arOnes as $id => $arFlags) {
                self::saveOne($hlDataClass, $type, $id, $arFlags);
            }
        }
    }

    public static function clearChildFlags($id = 0, $arData = [], $hlDataClass = '')
    {
        if (!$arData) $arData = Channel::getIblocksData();
        if (!$hlDataClass) $hlDataClass = self::getHlClass();

        $arBlock = (array_key_exists($id, $arData['CATALOG']['SECTIONS'])) ? $arData['CATALOG'] : $arData['SHOP'];

        // по всем подразделам
        foreach ($arBlock['CHILD'][$id] as $subSectId) {
            self::saveOne($hlDataClass, 'section', $subSectId, []);
            self::clearChildFlags($subSectId, $arData, $hlDataClass);
        }

        // по всем элементам
        foreach ($arBlock['ELEMENT'][$id] as $arItem) {
            self::saveOne($hlDataClass, 'element', $arItem['ID'], []);
        }
    }

    /**
     * сохраняет флаги одного элемента \ раздела
     *
     * @param string $hlDataClass
     * @param string $type    - тип
     * @param string $itemId  - ID элемента \ раздела \ название города
     * @param array  $arFlags - массив флагов
     */
    public static function saveOne($hlDataClass = '', $type = '', $id = '', $arFlags = [])
    {
        if (!$hlDataClass) $hlDataClass = self::getHlClass();

        if (!$id) return false;

        // маркер того, что все флаги надо удалить. при это не будут восстановлены значения раздела (а будут просто снаты все галочки)
        if (in_array('0', $arFlags)) $arFlags = ['no'];

        // маркер того, что все флаги надо удалить (они будут наследованы с верхних уровней)
        if (in_array('dubble', $arFlags)) $arFlags = [];

        foreach ($arFlags as $key => $flag) {
            $arFlags[$key] = ToLower($flag);
        }

        $arFlagExist = [];
        $arFilter = [
            'UF_TYPE' => $type,
            'UF_ID'   => $id,
        ];
        $res = $hlDataClass::getList(['filter' => $arFilter]);
        while ($arOne = $res->Fetch()) {
            $arFlagExist[$arOne['UF_FLAG']] = $arOne['ID'];
        }

        // добавим новые флаги
        foreach ($arFlags as $flag) {
            if (array_key_exists($flag, $arFlagExist)) continue;

            $arFilterOne = $arFilter;
            $arFilterOne['UF_FLAG'] = $flag;
            $hlId = $hlDataClass::add($arFilterOne);
            $arHlIDs[] = $hlId;
        }

        // удалим ненужные флаги
        foreach ($arFlagExist as $flag => $hlId) {
            if (in_array($flag, $arFlags)) {
                $arHlIDs[] = $hlId;
            } else {
                $hlDataClass::delete($hlId);
            }
        }
    }

    /**
     * возвращает все флаги
     *
     * @param string $hlDataClass
     *
     * @return array
     */
    public static function getAllFlags($hlDataClass = '')
    {
        if (!$hlDataClass) $hlDataClass = self::getHlClass();

        $arFlags = [
            'section' => [
                '-' => [],
            ],
            'element' => [
                '-' => [],
            ],
        ];
        $res = $hlDataClass::getList(['filter' => []]);
        while ($arOne = $res->Fetch()) {
            $arFlags[$arOne['UF_TYPE']][$arOne['UF_ID']][] = $arOne['UF_FLAG'];
        }

        return $arFlags;
    }

    /**
     * возвращает флаги конкретного элемента \ раздела
     *
     * @param string $hlDataClass
     * @param string $type
     * @param int    $itemId
     *
     * @return array
     */
    public static function getFlags($hlDataClass = '', $type = '', $itemId = 0)
    {
        if (!$hlDataClass) $hlDataClass = self::getHlClass();
        $arFlagExist = [];
        $res = $hlDataClass::getList(
            [
                'filter' => [
                    'UF_TYPE' => $type,
                    'UF_ID'   => $itemId,
                ],
                'select' => ['UF_FLAG']
            ]
        );
        while ($arOne = $res->Fetch()) {
            $arFlagExist[] = $arOne['UF_FLAG'];
        }

        return $arFlagExist;
    }

    public static function addFlags(&$arData = [], $arAddFlags = [], $inherit = true)
    {
        foreach ($arData['SECTIONS'] as $oneId => $arOne) {
            $arFlags = $arAddFlags['section'][$oneId];
            $parentId = $arOne['IBLOCK_SECTION_ID'];
            while ($inherit && !$arFlags && $parentId > 0) {
                $arFlags = $arAddFlags['section'][$parentId];
                $arFlags['INHERIT'] = 'Y';
                $parentId = $arData['SECTIONS'][$parentId]['IBLOCK_SECTION_ID'];
            }
            $arData['SECTIONS'][$oneId]['FLAGS'] = $arFlags;
        }

        foreach ($arData['ELEMENT'] as $sectionId => $arItems) {
            foreach ($arItems as $oneId => $arOne) {
                $arFlags = $arAddFlags['element'][$oneId];
                $parentId = $arOne['IBLOCK_SECTION_ID'];
                while ($inherit && !$arFlags && $parentId > 0) {
                    $arFlags = $arAddFlags['section'][$parentId];
                    $arFlags['INHERIT'] = 'Y';
                    $parentId = $arData['SECTIONS'][$parentId]['IBLOCK_SECTION_ID'];
                }
                $arData['ELEMENT'][$sectionId][$oneId]['FLAGS'] = $arFlags;
            }
        }
    }

    /**
     * выставляет всем корневым разделам и всем корневым элементам отмеченные флаги
     */
    public static function restoreDefaults()
    {
        $arIblocksData = self::getIblocksData();
        $arSaveData = [];
        foreach ($arIblocksData as $type => $arData) {
            if ($type == 'SHOP') $arData['FLAGS'] = ['OMNI_Retail']; // по умолчанию из РМ доступен только самовывоз (как и до разработки этого модуля)
            foreach ($arData['MAIN'] as $sectionId) {
                $arSaveData['section'][$sectionId] = $arData['FLAGS'];
            }

            foreach ($arData['ELEMENT'][0] as $itemId) {
                $arSaveData['element'][$itemId] = $arData['FLAGS'];
            }
        }

        self::deleteAllFlags();
        self::saveData($arSaveData);
    }

    /**
     * удаляет все флаги
     */
    public static function deleteAllFlags()
    {
        $hlDataClass = self::getHlClass();
        $res = $hlDataClass::getList(['filter' => []]);
        while ($arOne = $res->Fetch()) {
            $hlDataClass::delete($arOne['ID']);
        }
    }

    /**
     * возвращает класс HL-инфоблока
     * @return mixed
     */
    public static function getHlClass()
    {
        Loader::includeModule("highloadblock");

        $hldata = HighloadBlockTable::getList(['filter' => ['NAME' => self::HlName]]);
        $hlIblock = $hldata->Fetch();

        //затем инициализировать класс сущности
        $hlEntity = HighloadBlockTable::compileEntity($hlIblock);
        $hlDataClass = $hlEntity->getDataClass();

        return $hlDataClass;
    }

    /**
     * возвращает каталог товаров
     * @return array|bool
     */
    public static function getCatalogIblock()
    {
        Loader::includeModule("catalog");

        $cat = \CCatalog::GetList(['IBLOCK_ID' => 'ASC'], ['!OFFERS_IBLOCK_ID' => false], false, ['nTopCount' => 1]);
        if ($arCatalog = $cat->Fetch()) {
            return $arCatalog;
        }

        return false;
    }

    /**
     * возвращает ID инфоблока магазинов
     * @return int|bool
     */
    public static function getShopIblockID()
    {
        Loader::includeModule("iblock");

        $ib = TypeTable::getList(
            [
                'filter' => [
                    'ID' => ['shops', 'stores']
                ],
                'limit'  => 1
            ]
        );
        if ($arType = $ib->Fetch()) {
            $res = IblockTable::getList(
                [
                    'filter' => ['IBLOCK_TYPE_ID' => $arType['ID'], 'NAME' => 'Магазины'],
                    'limit'  => 1,
                    'select' => ['ID']
                ]
            );
            if ($arIblock = $res->Fetch()) {
                return $arIblock['ID'];
            }
        }

        return false;
    }

    /**
     * возвращает массив ID инфоблоков
     * @return array
     */
    public static function getAllIblocks()
    {
        $catalogIblockId = self::getCatalogIblock();
        $arIblocks = [
            'CATALOG' => $catalogIblockId['IBLOCK_ID'],
            'OFFERS'  => $catalogIblockId['OFFERS_IBLOCK_ID'],
            'SHOP'    => self::getShopIblockID(),
        ];

        return $arIblocks;
    }

    public static function getStores()
    {
        Loader::IncludeModule('catalog');
        $arOut = [
            'MAIN'     => [], // ID разделов первого уровня
            'CHILD'    => [], // ID подразделов всех уровней
            'SECTIONS' => [], // все разделы
            'ELEMENT'  => [], // товары в подмассивах с ключем раздела
            'FLAGS'    => self::$shopFlags,
        ];

        $hiddenFlags = \COption::GetOptionString("jamilco.omni", 'flags.hidden', '');
        $hiddenFlags = explode(',', $hiddenFlags);
        if (count($hiddenFlags)) {
            foreach ($arOut['FLAGS'] as $key => $flag) {
                if (in_array($flag, $hiddenFlags)) unset($arOut['FLAGS'][$key]);
            }
        }

        $st = \CCatalogStore::GetList([], ['ACTIVE' => 'Y']);
        while ($arStore = $st->Fetch()) {
            $arData = explode(',', $arStore['TITLE']);
            TrimArr($arData, true);
            if (count($arData) >= 3) {
                $shopName = array_shift($arData);
                $shopCity = array_shift($arData);
                $shopName = implode(', ', $arData);
                if (!in_array($shopCity, $arOut['MAIN'])) {
                    $arOut['MAIN'][] = $shopCity;
                    $arOut['SECTIONS'][$shopCity] = [
                        'NAME'        => $shopCity,
                        'ACTIVE'      => 'Y',
                        'DEPTH_LEVEL' => 1,
                    ];
                }

                $arStore['DEPTH_LEVEL'] = 2;
                $arStore['IBLOCK_SECTION_ID'] = $shopCity;
                $arStore['NAME'] = $shopName;
                $arStore['PROPERTY_ADDRESS_VALUE'] = $arStore['ADDRESS'];

                $arOut['ELEMENT'][$shopCity][$arStore['ID']] = $arStore;
            }
        }

        return $arOut;
    }

    /**
     * возвращает массив данных:
     *  - CATALOG - по каталогу
     *  - SHOP - по магазинам
     *
     * @return array
     */
    public static function getIblocksData()
    {
        $arIblocks = self::getAllIblocks();
        $hlDataClass = self::getHlClass();

        $arResult = [
            'CATALOG' => [],
            // 'SHOP'    => [], // магазины берем из складов, а не из инфоблока "Магазины"
        ];

        // удалим скрытые флаги
        $arItemFlags = self::$itemFlags;
        $hiddenFlags = \COption::GetOptionString("jamilco.omni", 'flags.hidden', '');
        $hiddenFlags = explode(',', $hiddenFlags);
        if (count($hiddenFlags)) {
            foreach ($arItemFlags as $key => $flag) {
                if (in_array($flag, $hiddenFlags)) unset($arItemFlags[$key]);
            }
        }

        foreach ($arResult as $type => &$arOut) {
            $arOut = [
                'MAIN'        => [], // ID разделов первого уровня
                'CHILD'       => [], // ID подразделов всех уровней
                'SECTIONS'    => [], // все разделы
                'ELEMENT'     => [], // товары в подмассивах с ключем раздела
                'FLAGS'       => $arItemFlags,
                'FLAGS_TITLE' => self::$flagsTitle,
            ];

            if ($type == 'SHOP') {
                $arOut['FLAGS'] = self::$shopFlags;
                if (count($hiddenFlags)) {
                    foreach ($arOut['FLAGS'] as $key => $flag) {
                        if (in_array($flag, $hiddenFlags)) unset($arOut['FLAGS'][$key]);
                    }
                }
            }

            $se = SectionTable::getList(
                [
                    'order'  => ['LEFT_MARGIN' => 'ASC'],
                    'filter' => ['IBLOCK_ID' => $arIblocks[$type]],
                    'select' => ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'DEPTH_LEVEL', 'ACTIVE']
                ]
            );
            while ($arSect = $se->Fetch()) {
                if ($arSect['IBLOCK_SECTION_ID']) {
                    $arOut['CHILD'][$arSect['IBLOCK_SECTION_ID']][] = $arSect['ID'];
                } else {
                    $arOut['MAIN'][] = $arSect['ID'];
                }
                //$arSect['FLAGS'] = self::getFlags($hlDataClass, 'section', $arSect['ID']); // запрашивает по одному
                $arOut['SECTIONS'][$arSect['ID']] = $arSect;
            }

            // получим товары
            $articleCode = \COption::GetOptionString('jamilco.omni', 'prop.article', 'ARTNUMBER');
            $el = \CIblockElement::getList(
                ['ID' => 'ASC'],
                ['IBLOCK_ID' => $arIblocks[$type]],
                false,
                false,
                [
                    'ID',
                    'IBLOCK_SECTION_ID',
                    'NAME',
                    'PROPERTY_'.$articleCode,
                    'PROPERTY_ADDRESS',
                ]
            );
            while ($arItem = $el->Fetch()) {
                unset($arItem['PROPERTY_'.$articleCode.'_VALUE_ID']);
                unset($arItem['PROPERTY_ADDRESS_VALUE_ID']);
                $arItem['PROPERTY_ARTNUMBER_VALUE'] = $arItem['PROPERTY_'.$articleCode.'_VALUE'];

                $arItem['IBLOCK_SECTION_ID'] = (int)$arItem['IBLOCK_SECTION_ID'];
                //$arItem['FLAGS'] = self::getFlags($hlDataClass, 'element', $arItem['ID']); // запрашивает по одному
                $arOut['ELEMENT'][$arItem['IBLOCK_SECTION_ID']][$arItem['ID']] = $arItem;
            }
        }
        unset($arOut);

        $arResult['SHOP'] = self::getStores();
        $arResult['FLAGS'] = self::getAllFlags($hlDataClass);

        return $arResult;
    }
}
