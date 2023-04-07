<?php

namespace DigitalDataLayer;

use DigitalDataLayer\Data;
use Bitrix\Main\IO\File;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UserTable;
use Bitrix\Sale;
use Bitrix\Iblock\SectionTable;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Data\Cache;

/**
 * Class Manager
 */
class Manager
{
    /**
     * Путь файла с кодом снипета
     */
    const DDL_SNIPPET_PATH = '/local/includes/digital_data_layer/snippet.txt';
    const DEFAULT_CITY_ID = 19;
    const DEFAULT_CITY_NAME = 'Москва';
    const DEFAULT_CURRENCY = 'RUB';

    const CATALOG_IBLOCK_ID = 1;
    const SKU_IBLOCK_ID = 2;
    const COLOR_HLBLOCK_ID = 1;
    const RETAIL_STORE_ID = 10;

    const PREVIEW_PICTURE_WIDTH = 320;
    const PREVIEW_PICTURE_HEIGHT = 399;

    /** @var Manager $instance */
    protected static $instance = null;

    /** @var Data $digitalData */
    protected $digitalData = null;

    /** @var array $content */
    protected $content = [];

    /**
     *  Возвращает экземпляр класса (singleton pattern)
     *
     * @return Manager
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Конструктор
     */
    private function __construct() {

        /** Получение экземпляра класса Data */
        $this->digitalData = Data::getInstance();

        //$this->addContentFromFile('snippet', static::DDL_SNIPPET_PATH);

        $this->fillDataCartObject();
    }

    private function __clone() {}

    /**
     *  Возвращает объект Data
     *
     * @return Data
     */
    public function getData() {
        return $this->digitalData;
    }

    /**
     *  Выводит объект Data на страницу через отложенную функцию
     */
    public function showData()
    {
        $GLOBALS['APPLICATION']->AddBufferContent(['\DigitalDataLayer\Manager', 'onShowData']);
    }

    /**
     *  Возвращает объект Data в виде обернутого JS объекта для отложенной функции
     *
     * @return string
     */
    public function onShowData()
    {
        return static::$instance->digitalData->asJsObject(true);
    }

    /**
     *  Возвращает текст снипета
     *
     * @param bool $isNoPrint флаг напечатать или вернуть
     *
     * @return string
     */
    public function showSnippet($isNoPrint = false)
    {
        if ($isNoPrint) {
            return $this->getContent(['snippet']);
        } else {
            echo $this->getContent(['snippet']);
        }
    }

    /**
     *  Добавляет контент во временный буфер
     *
     * @param string $key для возможности обратится по ключу в будущем
     * @param string $content
     */
    public function addContent($key, $content)
    {
        $this->content[$key] = $content;
    }

    /**
     *  Добавляет контент во временный буфер
     *
     * @param string $key для возможности обратится по ключу в будущем
     * @param string $path путь до файла относительно корня сайта
     *
     * @return bool
     */
    public function addContentFromFile($key, $path)
    {
        $this->content[$key] = File::getFileContents($_SERVER['DOCUMENT_ROOT'] . $path);
    }

    /**
     *  Возвращает контент по ключам
     *
     * @param array|string $keys массив ключей или ключ
     *
     * @return string $content
     */
    public function getContent($keys)
    {
        if (is_array($keys)) {
            return implode(array_intersect_key($this->content, array_flip($keys)));
        } else {
            return $this->content[$keys];
        }
    }

    /**
     *  Заполняет Data начальными данными
     */
    public function fillData()
    {
        $this->fillDataWebsiteObject();
        $this->fillDataPageObject();
        $this->fillDataUserObject();
    }

    /**
     * Заполнение объекта website начальными данными
     * (region) название города
     * (regionId) id города
     */
    protected function fillDataWebsiteObject()
    {
        $arLocation = \Jamilco\Delivery\Location::getCurrentLocation();
        $this->digitalData->website = [
            'region'   => $arLocation['NAME_RU'],
            'regionId' => (string)$arLocation['ID'],
        ];
    }

    /**
     * Заполнение объекта page начальными данными
     * (type) тип страницы
     * (breadcrumb) навигационная цепочка
     */
    protected function fillDataPageObject()
    {
        /**
         * Тип страницы (type)
         * - возможные значения: home, listing, search, product, cart, confirmation, content
         */
        $pageType = $GLOBALS['APPLICATION']->getProperty('ddlPageType');
        if ($pageType) {
            $this->digitalData->page = [
                'type' => $pageType
            ];
        }
        /**
         * Тип страницы (category)
         * - возможные значения: home, listing, search, product, cart, confirmation, content
         */
        $pageCategory = $GLOBALS['APPLICATION']->getProperty('ddlPageCategory');
        if ($pageCategory) {
            $this->digitalData->page = [
                'category' => $pageCategory
            ];
        }
        /** Навигационная цепочка (breadcrumb)
         *  - игнорирование страниц: home
         */
        $navChain = $GLOBALS['APPLICATION']->arAdditionalChain;
        if (!empty($navChain) && !in_array($pageType, ['home'])) {
            $this->digitalData->page = [
                'breadcrumb' => array_column($navChain, 'TITLE')
            ];
        }
    }

    /**
     * Заполнение объекта user начальными данными
     *  (userId)
     *  (firstName)
     *  (lastName)
     *  (gender)               // M F
     *  (phone)
     *  (email)
     *  (birthDate)            // ISO 8601
     *  (isSubscribed)         // bool
     *  (hasTransacted)        // bool
     *  (lastTransactionDate)  // ISO 8601
     */
    protected function fillDataUserObject()
    {
        if ($GLOBALS['USER']->isAuthorized()) {

            /** Пользователь авторизован, нужен флаг об этом факте */
            $this->digitalData->user = ['isLoggedIn' => true];

            /** Персональные данные пользователя */
            $userParams = [
                'select' => [
                    'ID',
                    'NAME',
                    'LAST_NAME',
                    'PERSONAL_GENDER',
                    'PERSONAL_PHONE',
                    'PERSONAL_MOBILE',
                    'EMAIL',
                    'PERSONAL_BIRTHDAY'
                ],
                'filter' => [
                    'ID' => $GLOBALS['USER']->getID()
                ],
                'limit' => 1
            ];
            $rsUser = UserTable::getList($userParams);

            foreach (array_filter($rsUser->fetch()) as $key => $value) {
                switch ($key) {
                    case 'ID':
                        $this->digitalData->user = ['userId' => $value];
                        break;
                    case 'NAME':
                        $this->digitalData->user = ['firstName' => $value];
                        break;
                    case 'LAST_NAME':
                        $this->digitalData->user = ['lastName' => $value];
                        break;
                    case 'PERSONAL_GENDER':
                        $this->digitalData->user = ['gender' => $value];
                        break;
                    case 'PERSONAL_PHONE':
                    case 'PERSONAL_MOBILE':
                        $this->digitalData->user = ['phone' => $value];
                        break;
                    case 'EMAIL':
                        $this->digitalData->user = ['email' => $value];
                        break;
                    case 'PERSONAL_BIRTHDAY':
                        $this->digitalData->user = ['birthday' => is_object($value) ? $value->format('c') : $value];
                        break;
                }
            }

            /** Сведения о подписке
             *  - есть ли активные подтвержденные подписки
             */
            $subscribeFilterParams = [
                'USER_ID'   => $this->digitalData->user['userId'],
                'ACTIVE'    => 'Y',
                'CONFIRMED' => 'Y'
            ];

            if (!Loader::IncludeModule('subscribe')) {
                throw new LoaderException('Module subscribe is not loaded');
            }

            $rsSubscribe = \CSubscription::GetList([], $subscribeFilterParams);
            $this->digitalData->user = ['isSubscribed' => $rsSubscribe->affectedRowsCount() ? true : false ];

            /** Сведения о заказах
             *  - есть ли заказы
             *  - дата последнего заказа
             */
            $orderParams = [
                'select' => [
                    'ID',
                    'DATE_PAYED'
                ],
                'filter' => [
                    'USER_ID' => $this->digitalData->user['userId'],
                    'LID'     => SITE_ID,
                    'PAYED'   => true
                ],
                'order' => [
                    'DATE_PAYED' => 'DESC'
                ],
                'limit' => 1
            ];

            $rsOrders = Sale\Order::getList($orderParams);

            if ($arOrder = $rsOrders->fetch()) {
                $this->digitalData->user = [
                    'hasTransacted'      => true,
                    'hasTransactionDate' => is_object($arOrder['DATE_PAYED']) ? $arOrder['DATE_PAYED']->format('c') : $arOrder['DATE_PAYED']
                ];
            } else {
                $this->digitalData->user = [
                    'hasTransacted'      => false
                ];
            }

            /** Необходимо добавить отметку о входе */
            if (isset($GLOBALS['APPLICATION']->arAuthResult) && !is_array($GLOBALS['APPLICATION']->arAuthResult) && $GLOBALS['APPLICATION']->arAuthResult === true) {
                $this->addContent('onLoginEvent', '<script>digitalData.events.push({"category":"Auth","name":"Logged In"});</script>');
            }

            /** Необходимо добавить отметку о регистрации */
            if ($GLOBALS['USER']->getParam('IS_REGISTER_EVENT')) {
                $this->addContent('onRegisterEvent', '<script>digitalData.events.push({"category":"Auth","name":"Registered"});</script>');
                $GLOBALS['USER']->setParam('IS_REGISTER_EVENT', false);
            }
        }
    }

    /**
     * Заполнение объекта cart начальными данными
     * (id) id корзины
     * (currency) валюта корзины
     * (subtotal) общая стоимость без скидок
     * (total) стоимость со скидками
     * (lineItems) список товаров
     *    (product) Товар, который добавили в корзину. Заполняется также, как и digitalData.product
     *    (quantity) Необходимое количество товаров для покупки
     *    (subtotal) Стоимость LineItem (Цена товара умноженная на количество).
     *
     * Поля на странице заказа, и на страницах подтверждения заказа на объектах transaction:
     * (shippingMethod) Тип доставки. При оформлении через корзину значение всегда "Курьером".
     * (shippingCost) Стоимость доставки для текущей корзины
     * (paymentMethod) Способ оплаты для текущей корзины "Наличными курьеру", "Банковской картой"
     * (vouchers) Массив из примененных к корзине купонов
     * (contactInfo) Контактные данные получателя заказа
     *     (firstName)
     *     (lastName)
     *     (phone)
     *     (email)
     *     (city)
     *     (address)
     */
    protected function fillDataCartObject()
    {
        $this->digitalData->cart = [
            'currency' => static::DEFAULT_CURRENCY,
            'total' => 0,
            'subtotal' => 0,
            'lineItems' => []
        ];
    }

    /**
     *  Возвращает массив заполненных объектов product
     *
     * @param array $itemIds массив id
     *
     * @return array $products
     */
    public function fillProductObject($itemIds)
    {
        Loader::IncludeModule('iblock');
        Loader::IncludeModule('sale');
        Loader::IncludeModule('catalog');
        Loader::IncludeModule('highloadblock');

        /** Названия цвета */
        $colorNames = [];
        if ($arData = HLBT::getById(static::COLOR_HLBLOCK_ID)->fetch()) {
            $colorEntity = HLBT::compileEntity($arData);
            $colors = $colorEntity->getDataClass();

            $params = [
                'select' => [
                    'ID',
                    'UF_NAME',
                    'UF_XML_ID'
                ]
            ];
            $rsColors = $colors::GetList($params);
            while ($arColor = $rsColors->Fetch()) {
                $colorNames[$arColor['UF_XML_ID']] = $arColor['UF_NAME'];
            }
        }

        /** Данные о продуктах */
        $products = [];
        $itemIds = array_unique($itemIds);

        foreach ($itemIds as $itemId) {

            $cache = Cache::createInstance();
            if ($cache->initCache(3600, $itemId, '/s1/item/')) {
                $product = $cache->getVars();
            } else {
                $cache->startDataCache();

                $product = [];
                $rsSku = \CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => static::SKU_IBLOCK_ID,
                        'ID'        => $itemId
                    ],
                    false,
                    ['nTopCount' => 1],
                    [
                        'ID',
                        'NAME',
                        'PREVIEW_PICTURE',
                        'DETAIL_PAGE_URL',
                        'PROPERTY_COLOR',
                        'PROPERTY_SIZES_SHOES',
                        'PROPERTY_SIZES_CLOTHES',
                        'PROPERTY_SIZES_RINGS',
                        'PROPERTY_ARTNUMBER',
                        'PROPERTY_CML2_LINK',
                        'CATALOG_GROUP_1',
                        'CATALOG_QUANTITY'
                    ]
                );
                if ($skuParams = $rsSku->GetNext()) {

                    $availableForPickup = false;
                    $store = \CCatalogStoreProduct::GetList([], ['PRODUCT_ID' => $itemId, 'STORE_ID' => self::RETAIL_STORE_ID]);
                    if ($arStore = $store->Fetch()) {
                        if ($arStore['AMOUNT'] > 0) {
                            $availableForPickup = true;
                        }
                    }
                    $availableForDelivery = $skuParams['CATALOG_QUANTITY'] > 0 ? true : false;

                    $productInfo = [
                        'skuCode'              => $skuParams['ID'],
                        'article'              => $skuParams['PROPERTY_ARTNUMBER_VALUE'] ?: $skuParams['NAME'],
                        //'color'                => $colorNames[$skuParams['PROPERTY_COLOR_VALUE']] ?: $skuParams['PROPERTY_COLOR_VALUE'], // по ТЗ отсутствует
                        //'size'                 => $skuParams['PROPERTY_SIZES_SHOES_VALUE'] ?: $skuParams['PROPERTY_SIZES_CLOTHES_VALUE'] ?: $skuParams['PROPERTY_SIZES_RINGS_VALUE'], // по ТЗ отсутствует
                        'url'                  => $skuParams['DETAIL_PAGE_URL'],
                        'unitPrice'            => (float)$skuParams['CATALOG_PRICE_1'],
                        'availableForPickup'   => $availableForPickup,
                        'availableForDelivery' => $availableForDelivery,
                        'stock'                => (int)$skuParams['CATALOG_QUANTITY'] ?: 0
                    ];

                    if ($previewPicture = \CFile::GetFileArray($skuParams['PREVIEW_PICTURE'])) {
                        $productInfo['imageUrl'] = $previewPicture['SRC'];

                        // превью
                        if ($resizePreview = \CFile::ResizeImageGet(
                            $skuParams['PREVIEW_PICTURE'],
                            ['width' => static::PREVIEW_PICTURE_WIDTH, 'height' => static::PREVIEW_PICTURE_HEIGHT]
                        )
                        ) {
                            $productInfo['thumbnailUrl'] = $resizePreview['src'];
                        }
                    }

                    // price
                    $productPrice = \CCatalogProduct::GetOptimalPrice($skuParams['ID'], 1, $GLOBALS['USER']->GetUserGroupArray());

                    if (!$productInfo['unitPrice']) {
                        $productInfo['unitPrice'] = $productPrice['RESULT_PRICE']['BASE_PRICE'] ?: 0;
                    }
                    $productInfo['unitSalePrice'] = $productPrice['RESULT_PRICE']['DISCOUNT_PRICE'] ?: 0;
                    $productInfo['currency'] = $productPrice['RESULT_PRICE']['CURRENCY'];

                    $product['product'] = $productInfo;

                    /** Дополнение данными от родителя */
                    $rsProduct = \CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID' => static::CATALOG_IBLOCK_ID,
                            'ID'        => $skuParams['PROPERTY_CML2_LINK_VALUE']
                        ],
                        false,
                        ['nTopCount' => 1],
                        [
                            'ID',
                            'NAME',
                            'IBLOCK_SECTION_ID',
                            'DETAIL_PAGE_URL',
                            'PROPERTY_ARTNUMBER',
                            //'PROPERTY_RETAIL_QUANTITY'
                        ]
                    );
                    if ($productParams = $rsProduct->GetNext()) {
                        $product['product']['id'] = $productParams['ID'];
                        $product['product']['name'] = $productParams['NAME'];
                        $product['product']['categoryId'] = $productParams['IBLOCK_SECTION_ID'];
                        $product['product']['url'] = $productParams['DETAIL_PAGE_URL'];
                        //$product['product']['stock'] = (int)$productParams['PROPERTY_RETAIL_QUANTITY_VALUE'] ?: 0;

                        /** Данные о разделах */
                        $parentSections = [];
                        $rsSections = SectionTable::getList(
                            [
                                'select'  => [
                                    'NAME'              => 'SECTION_SECTION.NAME',
                                    'SECTION_ID'        => 'SECTION_SECTION.ID',
                                    'IBLOCK_SECTION_ID' => 'SECTION_SECTION.IBLOCK_SECTION_ID',
                                ],
                                'filter'  => [
                                    '=ID' => $productParams['IBLOCK_SECTION_ID']
                                ],
                                'runtime' => [
                                    'SECTION_SECTION' => [
                                        'data_type' => '\Bitrix\Iblock\SectionTable',
                                        'reference' => [
                                            '=this.IBLOCK_ID'     => 'ref.IBLOCK_ID',
                                            '>=this.LEFT_MARGIN'  => 'ref.LEFT_MARGIN',
                                            '<=this.RIGHT_MARGIN' => 'ref.RIGHT_MARGIN',
                                        ],
                                        'join_type' => 'inner'
                                    ],
                                ],
                            ]
                        );
                        while ($parentSection = $rsSections->fetch()) {
                            $parentSections[$parentSection['SECTION_ID']] = $parentSection;
                        }

                        $sectionPath = [];
                        $sectionId = $productParams['IBLOCK_SECTION_ID'];
                        while ($sectionId > 0) {
                            $sectionPath[] = $parentSections[$sectionId]['NAME'];
                            $sectionId = $parentSections[$sectionId]['IBLOCK_SECTION_ID'];
                        }
                        $product['product']['category'] = array_reverse($sectionPath);
                    }
                }
                $cache->endDataCache($product);
            }

            $products[$itemId] = $product;
        }

        return $products;
    }

    /**
     *  Возвращает объект cart с полями lineItems, subtotal, total
     *
     * @param array $items
     * @param int $orderId
     *
     * @return array $lineItems
     */
    public function doProcessCartObject($items, $orderId = 0)
    {
        $total = 0;
        $subtotal = 0;
        $products = [];
        $productIds = [];
        $productsInfo = [];

        if (count($items) > 0) {
            // сбор цен уже со всеми скидками из компонента корзины
            foreach ($items as $arItem) {

                $productIds[] = $arItem['PRODUCT_ID'];

                $products[$arItem['PRODUCT_ID']] = [
                    'price'    => $arItem['PRICE'],
                    'quantity' => (int)$arItem['QUANTITY'],
                    'subtotal' => $arItem['PRICE'] * $arItem['QUANTITY'],
                ];
            }
        } else {
            // если готовых данных нет, тогда нужно их получить
            // в зависимости от ситуации по номеру заказа или у текущего пользователя

            Loader::IncludeModule('sale');

            $fUserID = (int)\CSaleBasket::GetBasketUserID(true);

            if ($fUserID > 0 || $orderId > 0) {

                $filter = [
                    'LID' => SITE_ID,
                    'ORDER_ID' => (($orderId > 0) ? $orderId : 'NULL')
                ];
                if (!$orderId) {
                    $filter['FUSER_ID'] = $fUserID;
                }

                $rsBasket = \CSaleBasket::GetList(
                    [
                        'NAME' => 'ASC',
                        'ID' => 'ASC'
                    ],
                    $filter,
                    false,
                    false,
                    ['ID', 'CALLBACK_FUNC', 'MODULE', 'PRODUCT_ID', 'PRICE', 'QUANTITY', 'PRODUCT_PROVIDER_CLASS']
                );

                if ($orderId > 0) {
                    // Корзина определенного заказа
                    while ($arItem = $rsBasket->Fetch()) {

                        $productIds[] = $arItem['PRODUCT_ID'];

                        /** В корзине стоимость товара уже со скидкой */
                        $subtotalItem = $arItem['QUANTITY'] * $arItem['PRICE'];

                        /** необходимо сохранить кол-во и стоимость для дальнейших расчетов */
                        $products[$arItem['PRODUCT_ID']]['price'] += $arItem['PRICE'];
                        $products[$arItem['PRODUCT_ID']]['quantity'] += $arItem['QUANTITY'];
                        $products[$arItem['PRODUCT_ID']]['subtotal'] += $subtotalItem;
                    }
                } else {
                    // Корзина текущего пользователя
                    $arOrder = [
                        'SITE_ID' => SITE_ID,
                        'USER_ID' => $GLOBALS['USER']->GetID(),
                        'ORDER_PRICE' => 0,
                        'ORDER_WEIGHT' => 0,
                        'BASKET_ITEMS' => []
                    ];

                    while ($arItem = $rsBasket->Fetch()) {

                        $productIds[] = $arItem['PRODUCT_ID'];

                        $arOrder['BASKET_ITEMS'][] = $arItem;
                        $arOrder['ORDER_PRICE'] += ($arItem['PRICE'] * $arItem['QUANTITY']);
                        $arOrder['ORDER_WEIGHT'] += ($arItem['WEIGHT'] * $arItem['QUANTITY']);
                    }

                    $arErrors = [];
                    // применение всех скидок к товарам
                    \CSaleDiscount::DoProcessOrder($arOrder, [], $arErrors);

                    foreach ($arOrder['BASKET_ITEMS'] as $arItem) {
                        $products[$arItem['PRODUCT_ID']] = [
                            'price' => (float)$arItem['PRICE'],
                            'quantity' => (int)$arItem['QUANTITY'],
                            'subtotal' => (float)$arItem['PRICE'] * $arItem['QUANTITY'],
                        ];
                    }
                }
            }
        }

        if (!empty($productIds)) {

            $productsInfo = $this->fillProductObject($productIds);

            foreach ($products as $key => $product) {

                // корректировка количества и суммы у продукта
                $productsInfo[$key]['quantity'] = $product['quantity'];
                $productsInfo[$key]['subtotal'] = $productsInfo[$key]['product']['unitSalePrice'] * $product['quantity'];

                // subtotal равна сумме всех subtotal товаров в корзине
                $subtotal += $productsInfo[$key]['subtotal'];

                // Добавление к общей стоимости корзины
                $total += $product['subtotal'];
            }
        }

        return [
            'orderId' => $orderId,
            'subtotal' => (float)$subtotal,
            'total' => (float)$total,
            'lineItems' => array_values($productsInfo)
        ];
    }
}
