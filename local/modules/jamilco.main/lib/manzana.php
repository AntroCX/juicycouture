<?

namespace Jamilco\Main;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Sale;
use \Bitrix\Sale\Internals\BasketTable;
use \Bitrix\Sale\Internals\BasketPropertyTable;
use \Jamilco\Main\Soap;

class Manzana
{
    /**
     * 1. - интеграция по запросам ЛК
     * 2. - интеграция по заказам
     */
    const VERSION = '2.11.3';


    // пустое место для разделения областей в коммитах

    const SITE = 'juicycouture'; // ID сайта из массива $arSitesData

    // пустое место для разделения областей в коммитах

    private static $arSitesData = [
        'wolford'      => [
            'org'        => 'WOLFORD',
            'pos'        => 'iw',
            'brand'      => ['WOLFORD', 'IM WOLFORD'], // бренд для проверки карт по бренду
            'card'       => '9990000001910', // системная карта
            'partnerId'  => 'C3F50000-5BC9-E711-80CE-005056011FF7',
            'orgUnitId'  => 'B1E046C4-5F07-E811-80CE-005056011FF7',
            'cardTypeId' => '084EBDE6-B3C7-E811-80D2-005056011FF7',
            'orderUrl'   => 'https://wolford.ru/personal/orders/detail.php?ID=#ORDER_ID#',
        ],
        'timberland'   => [
            'org'        => 'TIMBERLAND',
            'pos'        => 'itim',
            'brand'      => ['TIMBERLAND', 'IM TIM'],
            'card'       => '9990000001897',
            'partnerId'  => 'B9F50000-5BC9-E711-80CE-005056011FF7',
            'orgUnitId'  => 'A64C833A-EC07-E811-80CE-005056011FF7',
            'cardTypeId' => '524ABC87-B3C7-E811-80D2-005056011FF7',
            'orderUrl'   => 'https://timberland.ru/personal/order/detail/#ORDER_ID#/',
        ],
        'newbalance'   => [
            'org'        => 'NEW BALANCE',
            'pos'        => 'inb',
            'brand'      => ['NWB', 'IM NB', 'NBRUN'],
            'card'       => '9990000001880',
            'partnerId'  => 'A9F50000-5BC9-E711-80CE-005056011FF7',
            'orgUnitId'  => '26DB72BF-3407-E811-80CE-005056011FF7',
            'cardTypeId' => 'C9ECB35A-ACC7-E811-80D2-005056011FF7',
            'orderUrl'   => 'https://newbalance.ru/personal/profile/orders/?ID=#ORDER_ID#',
        ],
        'juicycouture' => [
            'org'        => 'JUICY COUTURE',
            'pos'        => 'ijc',
            'brand'      => ['JC', 'IM JUICY COUTURE'],
            'card'       => '9990000001927',
            'partnerId'  => '9FF50000-5BC9-E711-80CE-005056011FF7',
            'orgUnitId'  => 'CF615E84-EC07-E811-80CE-005056011FF7',
            'cardTypeId' => '95D879FC-B3C7-E811-80D2-005056011FF7',
            'orderUrl'   => 'https://juicycouture.ru/personal/orders/?ID=#ORDER_ID#',
        ],
        'marcopolo'    => [
            'org'        => 'MARC O\'POLO',
            'org-coupon' => 'Marc\'O\'Polo',
            'pos'        => 'imp',
            'brand'      => ['MOP', 'IM MARC O’POLO'],
            'card'       => '9990000001903',
            'partnerId'  => 'A3F50000-5BC9-E711-80CE-005056011FF7',
            'orgUnitId'  => '9560F90E-EC07-E811-80CE-005056011FF7',
            'cardTypeId' => '364C8CB2-B3C7-E811-80D2-005056011FF7',
            'orderUrl'   => 'https://marc-o-polo.ru/personal/orders/##ORDER_ID#',
        ],
        'dkny'         => [
            'org'        => 'DKNY',
            'pos'        => 'idkny',
            'brand'      => ['DKNY', 'IM DKNY'],
            'card'       => '9990000001934',
            'partnerId'  => '87F50000-5BC9-E711-80CE-005056011FF7',
            'orgUnitId'  => '5E5758D5-3837-E811-80D0-005056011FF7',
            'cardTypeId' => 'D69672C7-B3C7-E811-80D2-005056011FF7',
            'orderUrl'   => 'https://dkny.ru/personal/orders/detail.php?ID=#ORDER_ID#',
        ],
        'st-james'     => [
            'org'        => 'ST. JAMES',
            'pos'        => 'ist',
            'brand'      => ['ST JAMES'],
            'card'       => '9990000001941',
            'partnerId'  => 'B7F50000-5BC9-E711-80CE-005056011FF7',
            'orgUnitId'  => '1B688BA9-EC07-E811-80CE-005056011FF7',
            'cardTypeId' => '',
            'orderUrl'   => '',
        ],
        'ferragamo'    => [
            'org'        => 'SALVATORE FERRAGAMO',
            'pos'        => 'ifg',
            'brand'      => ['SALVATORE FERRAGAMO'],
            'card'       => '9990000001972',
            'partnerId'  => 'AFF50000-5BC9-E711-80CE-005056011FF7',
            'orgUnitId'  => '',
            'cardTypeId' => '',
            'orderUrl'   => '',
        ],
        'elenamiro'    => [
            'org'        => 'ELENA MIRO',
            'pos'        => 'inem',
            'brand'      => ['IM JAMILCO', 'JAM', 'JAMILCO'],
            'card'       => '9990000001965',
            'partnerId'  => '53624E93-74CC-E811-80D2-005056011FF7',
            'orgUnitId'  => 'DB5E8F64-5C1D-E911-80D2-005056011FF7',
            'cardTypeId' => '732C7DAD-6B7C-E911-80D2-005056011FF7',
            'orderUrl'   => 'https://elenamiro.ru/personal/orders/#ORDER_ID#/',
        ],
        'kiko'         => [
            'org'        => 'KIKO',
            'pos'        => 'imkiko',
            'brand'      => ['IM KIKO', 'KIKO', 'KIKO_MILANO_CARDS'],
            'card'       => '9990000002276',
            'partnerId'  => '6F15572C-FBE0-E911-80D2-005056011FF7',
            'orgUnitId'  => '57DD6CF1-7D38-EA11-80D2-005056011FF7',
            'cardTypeId' => 'A2D51FFE-8037-EA11-80D2-005056011FF7',
            'orderUrl'   => 'https://kikocosmetics.ru/personal/order/?ID=#ORDER_ID#',
        ],
    ];

    // свободные системные карты запрашивать у Fanyan Diana [mailto:fanyan@jamilco.ru]

    private static $arSessions = [
        'LKU'    => '22E0B57A-DD27-4775-A9F8-447CB1AF3E3E',
        'MOBILE' => '45A33696-EBD6-4C2A-8818-DF4807133225',
        'LKM'    => 'C97D1B52-6C90-40C5-A6BC-A2BF56488AF3',
        'ADMIN'  => 'A6E6E175-0BF0-472B-B22D-88FCAE7B966F',
    ];

    private static $arAccess = [
        "LK"  => [
            "FULL_PATH_ADMIN" => 'http://127.0.0.1:1013/AdministratorOfficeService/',
            "FULL_PATH"       => 'http://127.0.0.1:1011/CustomerOfficeService/',
        ],
        "POS" => [
            "FULL_PATH" => 'http://127.0.0.1:1014/POSProcessing.asmx',
            "LOGIN"     => 'jam\LoyaltySystem',
            "PASSWORD"  => 'LK@mnz2222',
        ]
    ];

    const CARD_STATUS_CODE_NEW = 1;     // статус карты "Новая"
    const CARD_STATUS_CODE_ACTIVE = 2;  // статус карты "Активная"
    const ROUND_TO = 5;

    const REQUEST_ID_PROP = 'MANZANA_REQUEST';
    private static $logFileDir = '/local/log/manzana/';
    private static $instance;

    private function __construct()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        Loader::includeModule('sale');

    }

    public static function getInstance()
    {
        if (!is_object(self::$instance)) self::$instance = new self();

        return self::$instance;
    }

    /**
     * возвращает номер сессии по типу
     *
     * @param string $type - LKM, LKU, ADMIN, MOBILE
     *
     * @return mixed
     */
    public static function getSessionID($type = 'LKM')
    {
        return self::$arSessions[$type];
    }

    /**
     * возвращает параметры по типу запроса
     *
     * @param string $param
     *
     * @return mixed
     */
    public static function getConfigParams($param = 'POS')
    {
        $config = self::$arAccess;

        $siteData = self::$arSitesData[self::SITE];

        $config[$param]['Organization'] = ($siteData['org']) ?: $siteData['pos'];
        $config[$param]['OrgCoupon'] = ($siteData['org-coupon']) ?: $config[$param]['Organization'];
        $config[$param]['BusinessUnit'] = $siteData['pos'];
        $config[$param]['POS'] = $siteData['pos'];
        $config[$param]['Card'] = $siteData['card'];
        $config[$param]['PartnerId'] = $siteData['partnerId'];
        $config[$param]['VirtualCardTypeId'] = $siteData['cardTypeId'];

        return $config[$param];
    }

    /**
     * отправляет запрос на LK-сервер
     *
     * @param string $path
     * @param array  $arParams
     * @param string $method - [get | post]
     *
     * @return array
     */
    public static function sendLKRequest($path = '', $arParams = [], $method = 'get', $role = '', $format = '')
    {
        $start = microtime(true);

        $configParams = self::getConfigParams('LK');

        $originalPath = $path;

        $arSendParams = [];
        if ($role == 'admin') {
            $path = $configParams['FULL_PATH_ADMIN'].$path;
            if ($format == 'json') {
                $arSendParams['sessionId'] = self::getSessionID('ADMIN');
            } else {
                $arSendParams['sessionId'] = "'".self::getSessionID('ADMIN')."'";
            }
        } else {
            $path = $configParams['FULL_PATH'].$path;
            if ($format == 'json') {
                $arSendParams['sessionId'] = self::getSessionID();
            } else {
                $arSendParams['sessionId'] = "'".self::getSessionID()."'";
            }
        }

        $arSendParams = array_merge($arSendParams, $arParams);

        $arOut = [
            "RESULT" => "ERROR",
            "DATA"   => [],
        ];

        if ($method == 'get') $path .= '?'.http_build_query($arSendParams);

        if ($_REQUEST['debug'] == 'Y') pr(['PATH' => $path, 'PARAMS' => $arSendParams], 1);

        if ($ch = curl_init()) {
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            //curl_setopt($ch, CURLOPT_USERPWD, $configParams['LOGIN'].":".$configParams['PASSWORD']);
            //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            if ($method == 'post') {
                curl_setopt($ch, CURLOPT_POST, 1);
                if ($format == 'json') {
                    $jsonParams = Json::encode($arSendParams);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParams);
                    $headers = [
                        'Content-Type: application/json',
                        'Content-Length: '.strlen($jsonParams),
                    ];
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $arSendParams);
                }
            }
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $data = curl_exec($ch);

            if (curl_errno($ch) || substr_count($data, 'Server Error')) {
                $arOut['RESULT'] = 'ERROR';
                $arOut['MESSAGE'] = curl_error($ch);
                if (substr_count($data, 'Server Error')) $arOut['MESSAGE'] = 'Server Error';
            } else {
                curl_close($ch);
                $arOut['RESULT'] = 'OK';

                $arOut['OUT'] = trim(html_entity_decode(html_entity_decode($data)));
                if (substr_count($arOut['OUT'], '{')) $arOut['OUT_JSON'] = Json::decode($arOut['OUT'], 1);
            }
        }

        self::writeLog($originalPath, $arSendParams, $arOut);

        $arOut['PATH'] = $path;
        $arOut['PARAMS'] = $arSendParams;
        $arOut['AUTH'] = [$configParams['LOGIN'], $configParams['PASSWORD']];

        $arOut['TIME'] = microtime(true) - $start;

        //pr($arOut);

        return $arOut;
    }

    /**
     * отправляет запрос на POS-сервер
     *
     * @param        $path
     * @param        $method
     * @param        $namespace
     * @param string $requestType
     * @param array  $params
     * @param array  $authParams
     *
     * @return array
     */
    private static function sendRequest($path, $method, $namespace, $requestType = '', $params = [], $authParams = [], $orderId = 0, $loy = false)
    {
        $request = new Soap\Request($method, $namespace, $params);

        $arOut = [
            "RESULT" => "ERROR",
            "DATA"   => [],
        ];
        $xml = $request->payload();

        if ($_REQUEST['debug'] == 'Y') pr($xml, 1);

        if ($ch = curl_init()) {
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            if (!empty($authParams)) {
                curl_setopt($ch, CURLOPT_USERPWD, $authParams['LOGIN'].":".$authParams['PASSWORD']);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            }
            $headers = [
                "Content-type: text/xml",
                "Accept: text/xml",
                "Content-length: ".strlen($xml),
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $data = curl_exec($ch);

            if (curl_errno($ch) || !$data) {
                $arOut['RESULT'] = 'ERROR';
                $arOut['MESSAGE'] = curl_error($ch);
                if (!$data) $arOut['MESSAGE'] = 'Empty request';
            } else {
                curl_close($ch);
                $arOut['RESULT'] = 'OK';
            }

            self::writeLog($requestType, $xml, $data, $orderId);

            if ($data) {
                $xml = new \CDataXML();
                $xml->LoadString($data);
                $dom = $xml->GetTree();
                if (is_object($dom)) {

                    $body = $dom->elementsByName("Body");
                    $body = $body[0];
                    if (is_object($body)) {
                        $response = $body->children();
                        $response = $response[0];

                        $responseAccessors = $response->children();
                        if (count($responseAccessors) > 0) {
                            foreach ($responseAccessors as $arChild) {
                                $value = $arChild->decodeDataTypes();
                                $arOut['DATA'] = array_merge($arOut['DATA'], $value);
                            }
                        }
                    }
                }
            }
        }

        return $arOut;
    }

    /**
     * проверяет баланс карты
     *
     * @param $cardnumber
     *
     * @return array
     */
    public static function cardBalance($cardnumber, $cacheEnable = true, $clearCache = false)
    {
        $arOut = ["RESULT" => "ERROR"];

        $cacheManager = \Bitrix\Main\Data\Cache::createInstance();
        $cacheTime = 300; // 5 мин
        $cachePath = '/manzana/balance/'.$cardnumber.'/';
        $cacheParams = ['card' => $cardnumber];
        $cacheId = [SITE_ID, LANGUAGE_ID, 'manzana:balance'.$cardnumber];
        $cacheId = implode('|', $cacheId)."|".serialize($cacheParams);

        if ($clearCache) {
            $cacheManager->clean($cacheId, $cachePath);
        }
        if (!$cacheEnable || $cacheManager->startDataCache($cacheTime, $cacheId, $cachePath)) {
            // запросим баланс по карте
            $configParams = self::getConfigParams('POS');

            $result = self::sendRequest(
                $configParams['FULL_PATH'],
                'ProcessRequest',
                "http://loyalty.manzanagroup.ru/loyalty.xsd",
                'balance',
                [
                    'request' => [
                        'BalanceRequest xmlns="http://loyalty.manzanagroup.ru/loyalty.xsd"' => [
                            'RequestID'    => self::getRequestId(),
                            'DateTime'     => date('c'),
                            'Organization' => $configParams['Organization'],
                            'BusinessUnit' => $configParams['BusinessUnit'],
                            'POS'          => $configParams['POS'],
                            'Card'         => ['CardNumber' => $cardnumber]
                        ]
                    ],
                    'orgName' => 'jamilco',
                ],
                [
                    'LOGIN'    => $configParams['LOGIN'],
                    'PASSWORD' => $configParams['PASSWORD'],
                ]
            );

            // карта активна если статус "Новая" или "Активная"
            $cardActive = (
                $result['DATA']['BalanceResponse']['CardStatus'] == self::CARD_STATUS_CODE_NEW ||
                $result['DATA']['BalanceResponse']['CardStatus'] == self::CARD_STATUS_CODE_ACTIVE
            ) ? true : false;

            if ($cardActive && $result["RESULT"] == "OK" && $result['DATA']['BalanceResponse']['ReturnCode'] == 0) {
                $arOut['RESULT'] = 'OK';

                $cardContact = false;
                if (array_key_exists('Email', $result['DATA']['BalanceResponse']) || array_key_exists('Phone', $result['DATA']['BalanceResponse'])) {
                    $cardContact = [
                        'FirstName'  => $result['DATA']['BalanceResponse']['FirstName'],
                        'LastName'   => $result['DATA']['BalanceResponse']['LastName'],
                        'MiddleName' => $result['DATA']['BalanceResponse']['MiddleName'],
                        'Phone'      => $result['DATA']['BalanceResponse']['Phone'],
                        'Email'      => $result['DATA']['BalanceResponse']['Email'],
                        'BirthDate'  => $result['DATA']['BalanceResponse']['BirthDate'],
                    ];
                }

                $arOut['DATA'] = [
                    'CardBalance'       => floor($result['DATA']['BalanceResponse']['CardBalance']),       // Баланс по карте, бонусные баллы
                    'CardActiveBalance' => floor($result['DATA']['BalanceResponse']['CardActiveBalance']), // Активный баланс по карте, бонусные баллы
                    'CardSumm'          => floor($result['DATA']['BalanceResponse']['CardSumm']),          // Сумма операций по карте, деньги
                    'CardType'          => $result['DATA']['BalanceResponse']['CardType'],
                    'CONTACT'           => $cardContact
                ];

                if ($arOut['DATA']['CardActiveBalance'] < 0) $arOut['DATA']['CardActiveBalance'] = 0;
                if ($arOut['DATA']['CardBalance'] < 0) $arOut['DATA']['CardBalance'] = 0;

                $cacheManager->endDataCache($arOut);
            } else {
                if ($result["RESULT"] == "ERROR") {
                    $arOut['RESULT'] = "SERVICE_ERROR";
                }
                $arOut['MESSAGE'] = $result['DATA']['BalanceResponse']['Message'];
                $cacheManager->abortDataCache();
            }
        } else {
            $arOut = $cacheManager->GetVars();
        }

        return $arOut;
    }

    /**
     * отправляет данные по заказу (перед заказом)
     *
     * @param int    $orderId            - если 0, то отправляются данные по текущей корзине
     * @param string $chequeType         -
     * @param string $operationType      -
     * @param string $orderOperationType -
     */
    public static function sendOrder($orderId = 0, $chequeType = 'Soft', $operationType = 'Sale', $orderOperationType = 'Calc', $cardNumber = '')
    {
        if (!Loader::includeModule('jamilco.loyalty')) return false;
        $configParamsLK = self::getConfigParams('LK');

        global $USER, $manzanaPaymentId, $manzanaDeliveryId;
        $userId = $USER->GetID();

        $canUseBonuses = $canUseDiscounts = true;
        $orderProps = [];
        $arOrder = [];
        if ($orderId > 0) {
            $arOrder = Sale\Order::load($orderId)->getFields()->getValues();
            $manzanaDeliveryId = $arOrder['DELIVERY_ID'];
            $manzanaPaymentId = $arOrder['PAY_SYSTEM_ID'];

            $pr = \CSaleOrderPropsValue::GetOrderProps($orderId);
            while ($arProp = $pr->Fetch()) {
                $orderProps[$arProp['CODE']] = $arProp['VALUE'];
            }

            if (!$operationType || !$orderOperationType) {
                if ($arOrder['PAYED'] == 'Y' && $chequeType == 'Fiscal' && $orderOperationType != 'Close' && $orderOperationType != 'Create') {
                    $orderOperationType = 'Payment';
                }
                if ($arOrder['CANCELED'] == 'Y') {
                    $chequeType = 'Fiscal';
                    if ($arOrder['PAYED'] == 'Y') {
                        $operationType = $orderOperationType = 'Return';
                    } else {
                        $operationType = 'Rollback';
                        $orderOperationType = 'RollbackCreate';
                    }
                }
            }
        }
        if ($manzanaDeliveryId && $manzanaDeliveryId == PICKUP_DELIVERY) $canUseBonuses = $canUseDiscounts = false;

        if ($operationType == 'Roolback' || $operationType == 'Return') {
            if (!self::checkOrderSended($orderId)) {
                // заказ не был отправлен в манзану, отменять его не надо
                return ['RESULT' => 'ERROR'];
            }
        }

        $arBaskets = [];
        $basketFilter = [];

        if ($orderId > 0) {
            $basketFilter['ORDER_ID'] = $orderId;
        } else {
            //if ($_SESSION['MANZANA_BASKET']) $arBaskets = $_SESSION['MANZANA_BASKET'];
            $basketFilter['FUSER_ID'] = Sale\Fuser::getId();
            $basketFilter['ORDER_ID'] = null;
            $basketFilter['DELAY'] = 'N';
        }
        if (!$arBaskets) {
            $ba = BasketTable::getList(['order' => ['ID' => 'ASC'], 'filter' => $basketFilter]);
            while ($arItem = $ba->Fetch()) {
                $arBaskets[] = $arItem;
            }
        }
        $_SESSION['MANZANA_BASKET'] = $arBaskets;

        foreach ($arBaskets as $key => $arItem) {
            if ($arItem['CAN_BUY'] != 'Y' || $arItem['DELAY'] == 'Y' || \CSaleBasketHelper::isSetItem($arItem)) {
                unset($arBaskets[$key]);
            }

            $arItem['PROPS'] = $arItem['PROPS_CODE'] = [];
            $pr = BasketPropertyTable::getList(['filter' => ["BASKET_ID" => $arItem['ID']]]);
            while ($arProp = $pr->fetch()) {
                $arItem['PROPS'][] = $arProp;
                $arItem['PROPS_CODE'][$arProp['CODE']] = $arProp['VALUE'];

                if (!$orderId && $arProp['CODE'] == 'LOYALTY_BONUS') $orderProps['PROGRAMM_LOYALTY_WRITEOFF'] += $arProp['VALUE'] * $arItem['QUANTITY'];
                if (!$orderProps['PROGRAMM_LOYALTY_CARD'] && $arProp['CODE'] == 'LOYALTY_BONUS_CART') $orderProps['PROGRAMM_LOYALTY_CARD'] = $arProp['VALUE'];
            }
            // пропускаем подарки, добавленные на сайте
            if ($arItem['PROPS_CODE']['GIFT'] > 0) {
                unset($arBaskets[$key]);
                continue;
            }

            if ($arItem['PROPS_CODE']['LOYALTY_BONUS_REDUCE'] == 'Y' && $arItem['PROPS_CODE']['LOYALTY_BONUS'] > 0) {
                // бонусы уже списаны из цены, но для манзаны надо передать полную сумму
                $arItem['PRICE'] += $arItem['PROPS_CODE']['LOYALTY_BONUS'];
                $arItem['PRICE'] = \Bitrix\Catalog\Product\Price::roundPrice($arItem['PRICE_TYPE_ID'], $arItem['PRICE'], 'RUB');
            }

            $pr = \CPrice::GetList([], ['CATALOG_GROUP_ID' => 1, 'PRODUCT_ID' => $arItem['PRODUCT_ID']]);
            $arBasePrice = $pr->Fetch();

            $pr = \CPrice::GetList([], ['CATALOG_GROUP_ID' => 2, 'PRODUCT_ID' => $arItem['PRODUCT_ID']]);
            $arSalePrice = $pr->Fetch();

            $arPrices = [
                'BASE' => ($arItem['PROPS_CODE']['PRICE_WITHOUT_DISCOUNT']) ?: $arBasePrice['PRICE'],
                'SALE' => ($arSalePrice['PRICE']) ?: $arBasePrice['PRICE'],
            ];

            if (!\Jamilco\Loyalty\Common::discountsAreMoved()) {
                if ($arItem['PRICE'] < $arPrices['SALE']) $arPrices['SALE'] = $arItem['PRICE'];
            }

            if (!$arItem['PROPS_CODE']['PRICE_WITHOUT_DISCOUNT']) $arItem['PROPS_CODE']['PRICE_WITHOUT_DISCOUNT'] = $arPrices['BASE'];
            if (!$arItem['PROPS_CODE']['PRICE_WITH_DISCOUNT']) $arItem['PROPS_CODE']['PRICE_WITH_DISCOUNT'] = $arPrices['SALE'];

            if ($chequeType == 'Soft' || !\Jamilco\Loyalty\Common::discountsAreMoved()) {
                // отправлять в манзану скидку на товары (если она задана "старой ценой", то отправлять)
                $arItem['BASE_PRICE'] = (\COption::GetOptionInt("jamilco.loyalty", "manzanasalesend", 0)) ? $arPrices['BASE'] : $arPrices['SALE'];
                $arItem['PRICE'] = $arPrices['SALE'];

                $arItem['SALE'] = ($arPrices['BASE'] > $arPrices['SALE']) ? 'Y' : 'N';
            }

            $arBaskets[$key] = $arItem;
        }

        if (!$orderId && $_SESSION['LOYALTY_CARD_NUMBER']) $orderProps['PROGRAMM_LOYALTY_CARD'] = $_SESSION['LOYALTY_CARD_NUMBER'];
        if ($cardNumber) $orderProps['PROGRAMM_LOYALTY_CARD'] = $cardNumber;

        if (!$arBaskets) {
            return [
                'RESULT'  => 'ERROR',
                //'ReturnCode' => 80060,
                'Message' => 'Не удалось получить товарный состав заказа',
            ];
        };

        if (!$orderId && !\Jamilco\Loyalty\Common::discountsAreMoved()) {
            // calculate order
            $totalPrice = 0;
            $totalWeight = 0;
            foreach ($arBaskets as $arItem) {
                $totalPrice += $arItem["PRICE"] * $arItem["QUANTITY"];
                $totalWeight += $arItem["WEIGHT"] * $arItem["QUANTITY"];
            }

            $arOrderCalc = array(
                'SITE_ID'      => SITE_ID,
                'ORDER_PRICE'  => $totalPrice,
                'ORDER_WEIGHT' => $totalWeight,
                'BASKET_ITEMS' => $arBaskets
            );

            $arOrderCalc['USER_ID'] = $userId;
            $arErrors = [];
            \CSaleDiscount::DoProcessOrder($arOrderCalc, [], $arErrors);

            $arBaskets = $arOrderCalc['BASKET_ITEMS'];
        }

        $configParams = self::getConfigParams('POS');

        $needOrderPosNumber = ($orderOperationType == 'Payment' || $orderOperationType == 'Return') ? true : false;

        $productsSort = \Jamilco\Loyalty\Common::productsSort();
        if ($productsSort > \Jamilco\Loyalty\Common::NO_SORT) {
            uasort(
                $arBaskets,
                $productsSort === \Jamilco\Loyalty\Common::SORT_PRICE_ASC ?
                    "\Jamilco\Loyalty\Common::cmpPriceAsc" : "\Jamilco\Loyalty\Common::cmpPriceDesc"
            );
        }

        $arItems = [];
        $n = 0;
        foreach ($arBaskets as $key => $arItem) {

            if ($chequeType == 'Soft' && $arItem['PROPS_CODE']['MANZANA_GIFT'] > '') {
                // для подарка передаем цену в 1руб
                $arItem['PRICE'] = 1; // связано с правкой в \Jamilco\Loyalty\Bonus::saveBasketPrice // выставить минимальную цену в 1руб
            }

            $arOrder['BASKET_FULL_PRICE'] += $arItem['BASE_PRICE'] * $arItem['QUANTITY'];

            $n++;

            $summ = $arItem['BASE_PRICE'] * $arItem['QUANTITY']; // полная стоимость товара
            $summDiscounted = $arItem['PRICE'] * $arItem['QUANTITY']; // цена со скидкой

            $arOrder['BASKET_PRICE'] += $arItem['PRICE'] * $arItem['QUANTITY'];
            $arOrder['BASKET_DISCOUNT_PRICE'] += ($arItem['BASE_PRICE'] - $arItem['PRICE']) * $arItem['QUANTITY'];

            $discountPercent = 0;
            if ($summ > $summDiscounted) {
                $discountPercent = ($summ - $summDiscounted) / $summ; // процент скидки
                $discountPercent = roundEx($discountPercent, self::ROUND_TO) * 100;
            }

            $arBaskets[$key]['OUT_NAME'] = $arItem['OUT_NAME'] = $arItem['NAME'];
            if ($arProp = \CIBlockElement::GetProperty(IBLOCK_SKU_ID, $arItem['PRODUCT_ID'], [], ['CODE' => 'OCS_ID'])->Fetch()) {
                if (!$arProp['VALUE']) $arProp['VALUE'] = \Jamilco\Main\Utils::updateOcsId($arItem['PRODUCT_ID'], $arItem['NAME']);
                $arBaskets[$key]['OUT_NAME'] = $arItem['OUT_NAME'] = $arProp['VALUE'];
            }

            $arOne = [
                'PositionNumber' => $n,
                'Article'        => $arItem['OUT_NAME'],
                'Price'          => $arItem['BASE_PRICE'],
                'Quantity'       => (int)$arItem['QUANTITY'],
                'Summ'           => $summ,
                'Discount'       => $discountPercent,
                'SummDiscounted' => $summDiscounted,
            ];
            if ($arItem['SALE'] == 'Y') $arOne['ExtendedAttribute'] = ['Key' => 'Sale', 'Value' => 'Y'];
            if ($arItem['PROPS_CODE']['MANZANA_GIFT'] > '') $arOne['ExtendedAttribute'] = ['Key' => 'Gift', 'Value' => '1'];

            if ($needOrderPosNumber) $arOne['OrderPositionNumber'] = $arOne['PositionNumber'];

            $arItems[] = $arOne;
        }

        $requestId = self::getRequestId();
        $orderNumber = ($orderId) ?: 'BASKET_'.$requestId;

        $summ = $arOrder['BASKET_FULL_PRICE']; // полная стоимость заказа
        $summDiscounted = $arOrder['BASKET_PRICE']; // цена со скидкой

        $discountPercent = 0;
        if ($summ > $summDiscounted) {
            $discountPercent = ($summ - $summDiscounted) / $summ; // процент скидки
            $discountPercent = roundEx($discountPercent, self::ROUND_TO) * 100;
        }

        $PaidByBonus = ($chequeType == 'Soft') ? $summDiscounted : $orderProps['PROGRAMM_LOYALTY_WRITEOFF'];
        if (!$canUseBonuses) $PaidByBonus = 0;
        $PaidByBonus = (int)$PaidByBonus;

        // В возвратном чеке нельзя указывать оплату бонусами
        if ($operationType == 'Return' && $PaidByBonus > 0) $PaidByBonus = 0;

        $date = ($orderId && $chequeType != 'Soft') ? date('c', $arOrder['DATE_INSERT']->getTimestamp()) : date('c');
        $arParams = [
            'RequestID'          => $requestId,
            'DateTime'           => $date,
            'Timeout'            => 50,
            'Organization'       => $configParams['Organization'],
            'BusinessUnit'       => $configParams['BusinessUnit'],
            'POS'                => $configParams['POS'],
            'Number'             => $orderNumber,
            'OperationType'      => $operationType,
            'OrderOperationType' => $orderOperationType,
            'Card'               => ['CardNumber' => $orderProps['PROGRAMM_LOYALTY_CARD']],
        ];

        if ($operationType != 'Rollback') {
            $arParams['Summ'] = $summ;
            $arParams['Discount'] = $discountPercent;
            $arParams['SummDiscounted'] = $summDiscounted;
            $arParams['PaidByBonus'] = $PaidByBonus;
        }

        $arCoupons = [];
        if (\Jamilco\Loyalty\Common::discountsAreMoved()) {
            if ($operationType != 'Rollback' && $operationType != 'Return' && $manzanaPaymentId == ONLINE_PAYSYSTEM && $canUseDiscounts) {
                $arParams['ExtendedAttribute'] = [
                    'Key'   => 'OnlinePayment',
                    'Value' => '1',
                ];
            }

            if ($orderId) $arCoupons = explode(',', $orderProps['COUPONS']);
            if (!defined('ADMIN_SECTION') && !$arCoupons && $_SESSION['MANZANA_COUPONS'] && $canUseDiscounts) {
                $arCoupons = array_keys($_SESSION['MANZANA_COUPONS']);
            }

            TrimArr($arCoupons);

            if ($orderProps['PROGRAMM_LOYALTY_WRITEOFF'] > 0) $arCoupons = []; // если списаны бонусы, то нельзя использовать купоны
            if ($orderOperationType == 'Close') $arCoupons = []; // не нужно передавать купоны при завершении заказа
        }

        //if ($arCoupons && $arParams['PaidByBonus']) $arParams['PaidByBonus'] = '0';

        if ($operationType != 'Rollback' && $arCoupons) {
            foreach ($arCoupons as $key => $val) {
                $val = trim($val);
                $arParams['Coupons'][($key + 1).':Coupon'] = ['Number' => $val];
            }
        }

        if ($chequeType == 'Fiscal' && $orderOperationType != 'Create') {
            // добавим OrderReference

            $date = $arOrder['DATE_INSERT']->getTimestamp(); // дата создания

            $orderReferenceTag = ($orderOperationType == 'Return') ? 'ChequeReference' : 'OrderReference';
            $arParams[$orderReferenceTag] = [
                'Number'       => $orderNumber,
                'Organization' => $configParamsLK['Organization'],
                'BusinessUnit' => $configParamsLK['BusinessUnit'],
                'POS'          => $configParamsLK['POS'],
                'DateTime'     => date('c', $date),
            ];

            if ($orderOperationType == 'Return') $arParams[$orderReferenceTag]['Number'] = $orderNumber.'_PAY'; // номер платежа, а не заказа

            // Number - теперь это номер чека
            $arParams['Number'] .= '_'.ToUpper(substr($orderOperationType, 0, 3));
        }

        if ($operationType != 'Rollback') {
            $n = 0;
            foreach ($arItems as $arItem) {
                $n++;
                $arParams[$n.':Item'] = $arItem;
            }
        }

        // запрос кешируется, если ID заказа не указано, запрос - Calc, купоны и онлайн-оплата кешируются
        $cacheEnable = ($arParams['OperationType'] == 'Sale' && $arParams['OrderOperationType'] == 'Calc') ? true : false;

        // используем системную карту (получим общие скидки)
        if (!$arParams['Card']['CardNumber']) $arParams['Card']['CardNumber'] = $configParamsLK['Card'];

        global $logStr;
        $logStr = '_'.$orderOperationType;

        $cacheManager = \Bitrix\Main\Data\Cache::createInstance();
        $cacheTime = 5 * 60; // 5 минут
        $cachePath = '/manzana/order-calc/';
        $cacheParams = $arParams;
        unset($cacheParams['RequestID']);
        unset($cacheParams['DateTime']);
        if (!$orderId) unset($cacheParams['Number']);
        $cacheId = [SITE_ID, LANGUAGE_ID, 'manzana:sendorder-calc'];
        $cacheId = implode('|', $cacheId)."|".serialize($cacheParams);

        $cacheGet = false;
        //$cacheEnable = false; // насильно выключить кеш
        if (!$cacheEnable || $cacheManager->startDataCache($cacheTime, $cacheId, $cachePath)) {
            $result = self::sendRequest(
                $configParams['FULL_PATH'],
                'ProcessRequest',
                "http://loyalty.manzanagroup.ru/loyalty.xsd",
                'order',
                [
                    'request' => [
                        'OrderRequest ChequeType="'.$chequeType.'"' => $arParams
                    ],
                    'orgName' => 'jamilco',
                ],
                [
                    'LOGIN'    => $configParams['LOGIN'],
                    'PASSWORD' => $configParams['PASSWORD'],
                ],
                ($chequeType == 'Soft' && !$orderId) ? '' : $orderId
            );
            if ($chequeType == 'Fiscal' && $orderOperationType == 'Create' && $manzanaPaymentId != ONLINE_PAYSYSTEM) {
                // отправляем дополнительный чек - фиктивный, для того, чтобы сдвинуть срок блокировки карты
                $arParamsEmptyCheque = $arParams;
                $arParamsEmptyCheque['Summ'] = 0;
                $arParamsEmptyCheque['Discount'] = 0;
                $arParamsEmptyCheque['SummDiscounted'] = 0;
                $arParamsEmptyCheque['PaidByBonus'] = 0;
                foreach ($arParamsEmptyCheque as $param => $value) {
                    if (substr_count($param, ':Item')) {
                        unset($arParamsEmptyCheque[$param]);
                    }
                }

                $logStr .= '_ChequeRequest';

                self::sendRequest(
                    $configParams['FULL_PATH'],
                    'ProcessRequest',
                    "http://loyalty.manzanagroup.ru/loyalty.xsd",
                    'order',
                    [
                        'request' => [
                            'ChequeRequest ChequeType="'.$chequeType.'"' => $arParamsEmptyCheque
                        ],
                        'orgName' => 'jamilco',
                    ],
                    [
                        'LOGIN'    => $configParams['LOGIN'],
                        'PASSWORD' => $configParams['PASSWORD'],
                    ],
                    $orderId
                );
            }

            if ($chequeType == 'Soft') {

                // если добавлен подарок, то отправим в манзану запрос заново
                if (self::checkGifts($result['DATA'], $canUseDiscounts)) {
                    $cacheManager->abortDataCache();
                    return self::sendOrder($orderId);
                }

                // для софт-чека, чтобы получить информацию о том, сколько бонуснов будет начислено, нужно передать точное количество на списание
                if ($result['DATA']['OrderResponse']['ReturnCode'] == '0' || $result['DATA']['OrderResponse']['WriteoffBonus'] > 0) {
                    if ($arParams['PaidByBonus'] > $result['DATA']['OrderResponse']['WriteoffBonus']) {
                        $arParams['PaidByBonus'] = $result['DATA']['OrderResponse']['WriteoffBonus'];

                        $requestId = self::getRequestId();
                        $orderNumber = ($orderId) ?: 'BASKET_'.$requestId;
                        $arParams['RequestID'] = $requestId;
                        $arParams['Number'] = $orderNumber;

                        $result = self::sendRequest(
                            $configParams['FULL_PATH'],
                            'ProcessRequest',
                            "http://loyalty.manzanagroup.ru/loyalty.xsd",
                            'order',
                            [
                                'request' => [
                                    'OrderRequest ChequeType="'.$chequeType.'"' => $arParams
                                ],
                                'orgName' => 'jamilco',
                            ],
                            [
                                'LOGIN'    => $configParams['LOGIN'],
                                'PASSWORD' => $configParams['PASSWORD'],
                            ],
                            ($chequeType == 'Soft' && !$orderId) ? '' : $orderId
                        );
                    }
                }
            }

            $cacheManager->endDataCache($result);
        } else {
            $cacheGet = true;
            $result = $cacheManager->GetVars();

            if ($chequeType == 'Soft') {
                // если добавлен подарок, то отправим в манзану запрос заново
                if (self::checkGifts($result['DATA'], $canUseDiscounts)) {
                    return self::sendOrder($orderId);
                }
            }
        }

        if ($_REQUEST['debug'] == 'OUT') {
            ppr(['cacheGet' => $cacheGet, 'cacheId' => $cacheId]);
            ppr($chequeType);
            ppr($arParams);
            ppr($result, 1);
        }

        $arItems = []; // данные по товарам
        foreach ($result['DATA']['OrderResponse'] as $key => $val) {
            if (substr_count($key, 'Item')) {
                foreach ($arBaskets as $arItem) {
                    if ($arItem['OUT_NAME'] == $val['Article']) {
                        $arItems[$arItem['ID']] = [
                            'WriteoffBonus' => $val['WriteoffBonus'] / $val['Quantity'],
                            'BasePrice'     => $val['Price'],
                            'Price'         => $val['SummDiscounted'] / $val['Quantity'],
                        ];
                    }
                }
            }
        }

        $arCouponsText = [];
        foreach ($result['DATA']['OrderResponse']['Coupons'] as $key => $val) {
            $val['Number'] = ($val['Number']) ?: $val['TypeID'];
            $_SESSION['MANZANA_COUPONS'][$val['Number']]['TEXT'] = $val['ApplicabilityMessage'];
            $arCouponsText[] = $val['ApplicabilityMessage'];
            $_SESSION['MANZANA_COUPONS'][$val['Number']]['TYPE'] = ($val['ApplicabilityCode'] == 1) ? 'OK' : 'ERROR';

            $_SESSION['MANZANA_COUPONS'][$val['Number']]['TEXT'] = str_replace(
                [
                    'Купон неприменим, вернуть покупателю.',
                    'Причина:',
                ],
                '',
                $_SESSION['MANZANA_COUPONS'][$val['Number']]['TEXT']
            );
            $_SESSION['MANZANA_COUPONS'][$val['Number']]['TEXT'] = trim($_SESSION['MANZANA_COUPONS'][$val['Number']]['TEXT']);
        }

        // если заказ уже был создан, запишем информацию о купоне в сам заказ
        if ($arCouponsText && $orderId) {
            $arCouponsText = implode(', ', $arCouponsText);
            $pr = \CSaleOrderPropsValue::GetList(
                [],
                [
                    'ORDER_ID' => $orderId,
                    'CODE'     => 'COUPON_ANSWER',
                ],
                false,
                ['nTopCount' => 1],
                ['ID', 'CODE', 'VALUE']
            );
            if ($arProp = $pr->Fetch()) {
                if ($arProp['VALUE'] != $arCouponsText) {
                    \CSaleOrderPropsValue::Update($arProp['ID'], ['VALUE' => $arCouponsText]);
                }
            } else {
                $rsOrder = \CSaleOrderProps::GetList([], ['CODE' => 'COUPON_ANSWER']);
                if ($arOrderProp = $rsOrder->Fetch()) {
                    \CSaleOrderPropsValue::Add(
                        [
                            "ORDER_ID"       => $orderId,
                            "ORDER_PROPS_ID" => $arOrderProp['ID'],
                            "NAME"           => $arOrderProp['NAME'],
                            "CODE"           => $arOrderProp['CODE'],
                            "VALUE"          => $arCouponsText,
                        ]
                    );
                }
            }
        }

        $res = 'OK';
        if ($operationType == 'Rollback') {
            if ($result['DATA']['OrderResponse']['ReturnCode'] == 81380) $res = 'ERROR'; // Чек не найден
        }

        // пометим заказ как отправленный в манзану
        if ($orderId && $chequeType == 'Fiscal') self::checkOrderSended($orderId, true);

        return [
            'RESULT'           => $res,
            'ChargedBonus'     => $result['DATA']['OrderResponse']['ChargedBonus'], // Всего начислено баллов по заказу
            'AvailablePayment' => $result['DATA']['OrderResponse']['AvailablePayment'], // Максимально доступная для списания часть заказа
            'WriteoffBonus'    => $result['DATA']['OrderResponse']['WriteoffBonus'], // Всего списано баллов
            'Items'            => $arItems,
        ];
    }

    /**
     * @param array $arResponse
     *
     * @return mixed
     */
    public static function checkGifts($arResponse = [], $canUseDiscounts = true)
    {
        if ($arResponse['OrderResponse']) $arResponse = $arResponse['OrderResponse'];

        $arInnerGifts = [];
        foreach ($arResponse as $key => $val) {
            if (substr_count($key, 'CCValue')) {
                $arInnerGifts[$val['RuleExternalID']][$val['Article']] = (int)$val['Quantity'];
            }
        }

        if (!$canUseDiscounts) $arInnerGifts = [];

        $_SESSION['MANZANA_GIFT'] = false;
        $return = false;
        foreach ($arInnerGifts as $ruleId => $arInner) {
            $arGifts = [];
            $of = \CIblockElement::GetList(
                [
                    'SORT'                    => 'ASC',
                    'PROPERTY_AVAILABLE_SORT' => 'DESC',
                ],
                [
                    'IBLOCK_ID'                 => IBLOCK_SKU_ID,
                    'ACTIVE'                    => 'Y',
                    'PROPERTY_CML2_LINK.ACTIVE' => 'Y',
                    'PROPERTY_OCS_ID'           => array_keys($arInner),
                    '!PROPERTY_DELIVERY_CAN'    => false,
                ],
                false,
                ['nTopCount' => count($arInner)], // получаем все варианты подарков
                ['ID', 'PROPERTY_OCS_ID']
            );
            while ($arOffer = $of->Fetch()) {
                $arGifts[$arOffer['ID']] = $arInner[$arOffer['PROPERTY_OCS_ID_VALUE']];
            }

            if (method_exists('\Jamilco\Main\Utils', 'addGift') && \Jamilco\Main\Utils::addGift($arGifts, $ruleId)) $return = true;
        }

        if (method_exists('\Jamilco\Main\Utils', 'deleteGifts')) \Jamilco\Main\Utils::deleteGifts(array_keys($arInnerGifts));

        return $return;
    }

    /**
     * поиск контактов по одному из параметров
     *
     * @param string $phone - телефон (89999999999 или +79999999999)
     * @param string $email - емейл
     * @param string $card  - номер карты
     *
     * @return array
     */
    public static function findContact($phone = '', $email = '', $card = '', $cacheEnable = true, $clearCache = false)
    {
        if (!$phone && !$email && !$card) return false;

        // +79998887766
        if ($phone) {
            $phone = str_replace(['-', '(', ')', ' ', '+'], '', $phone);
            if (substr($phone, 0, 1) == '8') $phone = '7'.substr($phone, 1);
            if (strlen($phone) == 10) $phone = '7'.$phone;
            $phone = '+'.$phone;
        }

        $cacheManager = \Bitrix\Main\Data\Cache::createInstance();
        $cacheTime = 60 * 30; // 30 мин
        $cachePath = '/manzana/find-card/';
        $cacheParams = ['card' => $card, 'phone' => $phone, 'email' => $email];
        $cacheId = [SITE_ID, LANGUAGE_ID, 'manzana:find-card'.$card];
        $cacheId = implode('|', $cacheId)."|".serialize($cacheParams);

        if ($clearCache) {
            $cacheManager->clean($cacheId, $cachePath);
        }
        if (!$cacheEnable || $cacheManager->startDataCache($cacheTime, $cacheId, $cachePath)) {
            $arData = self::getInstance()->sendLKRequest(
                'Contact/FilterByPhoneAndEmailAndCardNumber',
                [
                    'mobilePhone'  => '\''.$phone.'\'',
                    'emailAddress' => '\''.$email.'\'',
                    'cardNumber'   => '\''.$card.'\'',
                ],
                'get',
                'admin'
            );
            $cacheManager->endDataCache($arData);
        } else {
            $arData = $cacheManager->GetVars();
        }

        return $arData['OUT_JSON']['value'];
    }

    /**
     * поиск карты по указанным контактным данным
     *
     * @param string $phone
     * @param string $email
     * @param bool   $getAll    - вернуть все данные
     * @param string $contactId - если не указан контактИд, то он будет получен по контактным данным
     *
     * @return array
     */
    public static function findCard($phone = '', $email = '', $getAll = false, $contactId = '')
    {
        if (!$phone && !$email && !$contactId) return false;

        $arCards = [];
        $arContacts = [];

        if ($contactId) {
            $arContacts[] = $contactId;
        } else {
            $arData = self::getInstance()->findContact($phone, $email, '');
            foreach ($arData as $arOne) {
                $arContacts[] = $arOne['Id'];
            }
        }

        if (count($arContacts)) {
            foreach ($arContacts as $contactId) {
                $arData = self::getInstance()->sendLKRequest('Card/GetAllByContact', ['contactid' => '\''.$contactId.'\'',], 'get', 'admin');

                foreach ($arData['OUT_JSON']['value'] as $arCard) {
                    // пропускаем "новые" и "активные" карты
                    if ($arCard['StatusCode'] != self::CARD_STATUS_CODE_NEW && $arCard['StatusCode'] != self::CARD_STATUS_CODE_ACTIVE) continue;

                    if ($getAll) {
                        $arCards[$arCard['Id']] = $arCard;
                    } else {
                        $arCards[$arCard['Id']] = $arCard['Number'];
                    }
                }
            }
        }

        return $arCards;
    }

    /**
     * получает cardId по номеру карты
     *
     * @param string $card
     * @param bool   $cacheEnable
     *
     * @return bool
     */
    public static function getCardId($card = '', $cacheEnable = true, $returnAll = false)
    {
        $cacheManager = \Bitrix\Main\Data\Cache::createInstance();
        $cacheTime = 86400 * 30; // 30 дней
        $cachePath = '/manzana/card/'.$card.'/';
        $cacheParams = ['card' => $card, 'return' => $returnAll];
        $cacheId = [SITE_ID, LANGUAGE_ID, 'manzana:card'.$card];
        $cacheId = implode('|', $cacheId)."|".serialize($cacheParams);

        if (!$cacheEnable || $cacheManager->startDataCache($cacheTime, $cacheId, $cachePath)) {
            $arData = self::getInstance()->findContact('', '', $card, $cacheEnable);
            $arCards = self::getInstance()->findCard('', '', true, $arData[0]['Id']);
            $cardId = false;
            foreach ($arCards as $arCard) {
                if ($arCard['Number'] == $card) {
                    if ($returnAll) {
                        $cardId = $arCard;
                    } else {
                        $cardId = $arCard['Id'];
                    }
                    break;
                }
            }

            $cacheManager->endDataCache($cardId);
        } else {
            $cardId = $cacheManager->GetVars();
        }

        return $cardId;
    }

    /**
     * возвращает историю чеков по карте
     *
     * @param string $card
     * @param bool   $cacheEnable
     *
     * @return array
     */
    public static function getCheckHistory($card = '', $cacheEnable = true)
    {
        $cardId = self::getInstance()->getCardId($card, $cacheEnable);
        if ($cardId) {
            $cacheManager = \Bitrix\Main\Data\Cache::createInstance();
            $cacheTime = 86400 * 1; // 1 день
            $cachePath = '/manzana/check-history/'.$card.'/'.$cardId.'/';
            $cacheParams = ['card' => $card, 'cardId' => $cardId];
            $cacheId = [SITE_ID, LANGUAGE_ID, 'manzana:check-history'.$card.$cardId];
            $cacheId = implode('|', $cacheId)."|".serialize($cacheParams);

            if (!$cacheEnable || $cacheManager->startDataCache($cacheTime, $cacheId, $cachePath)) {
                $arData = self::getInstance()->sendLKRequest(
                    'Cheque/GetAllByCard',
                    [
                        'cardId' => '\''.$cardId.'\'',
                    ],
                    'get',
                    'admin'
                );

                $cacheManager->endDataCache($arData);
            } else {
                $arData = $cacheManager->GetVars();
            }

            if ($arData['OUT_JSON']['odata.error']['code']) {
                return [
                    'RESULT'     => 'ERROR',
                    'ERROR_CODE' => $arData['OUT_JSON']['odata.error']['code'],
                    'MESSAGE'    => $arData['OUT_JSON']['odata.error']['message']['value'],
                ];
            } else {
                $arOrders = [];
                foreach ($arData['OUT_JSON']['value'] as $arOne) {

                    $arOrder = [
                        'DATE'            => new DateTime($arOne['Date'], 'Y-m-d\TH:i:s'),
                        'ID'              => $arOne['Id'],
                        'NUMBER'          => $arOne['Number'],
                        'SHOP'            => ($arOne['OrgUnitAddress']) ?: $arOne['OrgUnitName'],
                        'URL'             => '',
                        'SUMM'            => $arOne['Summ'],
                        'SUMM_DISCOUNTED' => $arOne['SummDiscounted'],
                        'PAID_BY_BONUS'   => $arOne['PaidByBonus'],
                        'BONUS'           => $arOne['Bonus'],
                        'PARTNER_NAME'    => $arOne['PartnerName'],
                        'ITEM_COUNT'      => $arOne['ChequeItemCount'],
                    ];
                    if (substr_count($arOne['Number'], '_PAY') || substr_count($arOne['Number'], '_RET')) {
                        // онлайн-заказ
                        $orderId = str_replace(['_PAY', '_RET'], '', $arOne['Number']);

                        $arOrder['NUMBER'] = $orderId;
                        $arOrder['URL'] = self::getOrderUrl($orderId, $arOne['PartnerName']);
                    } else {
                        // оффлайн-заказ
                        //$arOrder['ITEMS'] = self::getCheckItems($arOrder['ID']); // получение списка товаров по чеку
                    }

                    $arOrders[] = $arOrder;
                }

                return [
                    'RESULT' => 'OK',
                    'VALUE'  => $arOrders,
                ];
            }
        }


        return ['RESULT' => 'ERROR'];
    }

    /**
     * позиции по чеку
     *
     * @param string $checkId
     */
    public static function getCheckItems($checkId = '')
    {
        if (!$checkId) return false;

        $arData = self::getInstance()->sendLKRequest(
            'ChequeItem/GetAllByCheque',
            [
                'ChequeId' => '\''.$checkId.'\'',
            ],
            'get',
            'admin'
        );

        $arItems = [];
        $arOcsIDs = [];
        foreach ($arData['OUT_JSON']['value'] as $arItem) {
            $arOcsIDs[] = $arItem['ArticleNumber'];
            $arItems[] = [
                'OCS_ID'          => $arItem['ArticleNumber'],
                'QUANTITY'        => (int)$arItem['Quantity'],
                'SUMM'            => $arItem['Summ'],
                'DISCOUNTED_SUMM' => $arItem['DiscountedSumm'],
                'PAID_BY_BONUS'   => $arItem['PaidByBonus'],
            ];
        }

        $of = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID'        => IBLOCK_SKU_ID,
                '=PROPERTY_OCS_ID' => $arOcsIDs,
            ],
            false,
            ['nTopCount' => count($arOcsIDs)],
            [
                'ID',
                'NAME',
                'PROPERTY_OCS_ID',
                'PROPERTY_CML2_LINK',
            ]
        );
        while ($arOffer = $of->Fetch()) {
            foreach ($arItems as $key => $arOne) {
                if ($arOne['OCS_ID'] != $arOffer['PROPERTY_OCS_ID_VALUE']) continue;

                $arOne['ID'] = $arOffer['ID'];
                $arOne['NAME'] = $arOffer['NAME'];

                $it = \CIblockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => IBLOCK_CATALOG_ID,
                        'ID'        => $arOffer['PROPERTY_CML2_LINK_VALUE'],
                    ],
                    false,
                    ['nTopCount' => 1],
                    ['ID', 'NAME', 'DETAIL_PAGE_URL']
                );
                if ($arProduct = $it->GetNext()) {
                    $arOne['PRODUCT'] = [
                        'ID'              => $arProduct['ID'],
                        'NAME'            => $arProduct['NAME'],
                        'DETAIL_PAGE_URL' => $arProduct['DETAIL_PAGE_URL'],
                    ];
                }

                $arItems[$key] = $arOne;
            }
        }

        return $arItems;
    }

    /**
     * возвращает УРЛ до заказа в личном кабинете на определенном сайте
     *
     * @param int    $orderId
     * @param string $partnerName
     *
     * @return mixed|string
     */
    public static function getOrderUrl($orderId = 0, $partnerName = '')
    {
        $orderUrl = '';
        foreach (self::$arSitesData as $arSite) {
            if (ToUpper($arSite['org']) == ToUpper($partnerName)) {
                $orderUrl = $arSite['orderUrl'];
            }
        }

        $orderUrl = str_replace('#ORDER_ID#', $orderId, $orderUrl);

        return $orderUrl;
    }

    /**
     * возвращает историю баллов по карте
     *
     * @param string $card
     * @param bool   $cacheEnable
     *
     * @return array
     */
    public static function getHistory($card = '', $cacheEnable = true)
    {
        $arData = self::getInstance()->findContact('', '', $card, $cacheEnable);
        if ($contactId = $arData[0]['Id']) {
            $cacheManager = \Bitrix\Main\Data\Cache::createInstance();
            $cacheTime = 86400 * 1; // 1 день
            $cachePath = '/manzana/bonus-history/'.$card.'/'.$contactId.'/';
            $cacheParams = ['card' => $card, 'contact' => $contactId];
            $cacheId = [SITE_ID, LANGUAGE_ID, 'manzana:bonus-history'.$card.$contactId];
            $cacheId = implode('|', $cacheId)."|".serialize($cacheParams);

            if (!$cacheEnable || $cacheManager->startDataCache($cacheTime, $cacheId, $cachePath)) {
                $arData = self::getInstance()->sendLKRequest(
                    'Bonus/GetAllByContact',
                    [
                        'contactId' => '\''.$contactId.'\'',
                    ],
                    'get',
                    'admin'
                );

                $cacheManager->endDataCache($arData);
            } else {
                $arData = $cacheManager->GetVars();
            }

            if ($arData['OUT_JSON']['odata.error']['code']) {
                return [
                    'RESULT'     => 'ERROR',
                    'ERROR_CODE' => $arData['OUT_JSON']['odata.error']['code'],
                    'MESSAGE'    => $arData['OUT_JSON']['odata.error']['message']['value'],
                ];
            } else {
                return [
                    'RESULT' => 'OK',
                    'VALUE'  => $arData['OUT_JSON']['value'],
                ];
            }
        }


        return ['RESULT' => 'ERROR'];
    }

    /**
     * добавляет новую свободную карту указанному контакту
     *
     * @param string $contactId
     *
     * @return array
     */
    public static function addCard($contactId = '')
    {
        if (!$contactId) return false;

        $configParams = self::getConfigParams('LK');
        $partnerId = $configParams['PartnerId'];
        $virtualCardType = $configParams['VirtualCardTypeId'];

        if (!$virtualCardType) {
            $arData = self::getInstance()->sendLKRequest(
                'VirtualCardType/GetAllByPartner',
                [
                    'partnerId' => '\''.$partnerId.'\'',
                ],
                'get',
                'admin'
            );
            if ($arData['OUT_JSON']['value'][0]['AvailableCards'] > 0) {
                $virtualCardType = $arData['OUT_JSON']['value'][0]['Id'];
            }
            if (!$virtualCardType) {
                $partnerId = '';
                $arData = self::getInstance()->sendLKRequest(
                    'VirtualCardType/GetAllWithNoPartner',
                    [],
                    'get',
                    'admin'
                );
                if ($arData['OUT_JSON']['value'][0]['AvailableCards'] > 0) {
                    $virtualCardType = $arData['OUT_JSON']['value'][0]['Id'];
                }
            }
        }

        $arData = self::getInstance()->sendLKRequest(
            'Card/BindVirtualCard',
            [
                'contactId'         => $contactId,
                'PartnerId'         => $partnerId,
                'VirtualCardTypeId' => $virtualCardType,
            ],
            'post',
            'admin',
            'json'
        );

        if ($arData['OUT_JSON']['odata.error']['code'] > '') {
            return [
                'RESULT'     => 'ERROR',
                'ERROR_CODE' => $arData['OUT_JSON']['odata.error']['code'],
                'MESSAGE'    => $arData['OUT_JSON']['odata.error']['message']['value'],
            ];
        } else {
            return [
                'RESULT' => 'OK',
                'VALUE'  => $arData['OUT_JSON']['value'],
            ];
        }
    }

    /**
     * получаем номер карты по её ID
     *
     * @param string $cardId
     *
     * @return mixed
     */
    public static function cardGet($cardId = '')
    {
        $arData = self::getInstance()->sendLKRequest(
            'Card/Get',
            [
                'id' => '\''.$cardId.'\'',
            ],
            'get',
            'admin'
        );

        return $arData['OUT_JSON']['Number'];
    }

    /**
     * добавить новый контакт
     *
     * @param string $lastName
     * @param string $firstName
     * @param string $middleName
     * @param string $gender
     * @param string $birthDate
     * @param string $email
     * @param string $phone
     *
     * @return array
     */
    public static function contactAdd(
        $lastName = '',
        $firstName = '',
        $middleName = '',
        $gender = 'M',
        $birthDate = '',
        $email = '',
        $phone = ''
    ) {
        $arParams = [
            'Entity' => [
                "RegistrationDate"    => date('Y-m-d'),
                "LastName"            => $lastName,
                "FirstName"           => $firstName,
                "MiddleName"          => $middleName,
                "GenderCode"          => ($gender == 'M') ? "1" : "2",
                "BirthDate"           => $birthDate,
                "FamilyStatusCode"    => "1",
                "HasChildrenCode"     => "1",
                "EmailAddress"        => $email,
                "MobilePhone"         => self::formatPhone($phone),
                "AllowEmail"          => true,
                "AllowSms"            => true,
                //"PartnerId"           => self::$arSitesData[self::SITE]['partnerId'],
                "OrgUnitId"           => self::$arSitesData[self::SITE]['orgUnitId'],
                "CommunicationMethod" => 1, // любой
                "AllowPhone"          => true,
                "AllowNotification"   => true,
                "EmailVerified"       => true,
                "MobilePhoneVerified" => true,
            ],
        ];

        $arData = self::getInstance()->sendLKRequest('Contact/Create', $arParams, 'post', 'admin', 'json');

        if ($arData['OUT_JSON']['odata.error']['code']) {
            return [
                'RESULT'     => 'ERROR',
                'ERROR_CODE' => $arData['OUT_JSON']['odata.error']['code'],
                'MESSAGE'    => $arData['OUT_JSON']['odata.error']['message']['value'],
            ];
        } else {
            return [
                'RESULT' => 'OK',
                'VALUE'  => $arData['OUT_JSON']['value'],
            ];
        }
    }

    /**
     * обновить контакт
     *
     * @param string $contactId
     * @param string $lastName
     * @param string $firstName
     * @param string $middleName
     * @param string $gender
     * @param string $birthDate
     * @param string $email
     * @param string $phone
     *
     * @return array
     */
    public static function contactUpdate(
        $contactId = '',
        $lastName = '',
        $firstName = '',
        $middleName = '',
        $gender = 'M',
        $birthDate = '',
        $email = '',
        $phone = ''
    ) {
        $phone = self::formatPhone($phone);
        if (!$contactId) {
            $arRes = self::getInstance()->findContact($phone, $email);
            $contactId = $arRes[0]['Id'];
        }
        if (!$contactId) {
            return [
                'RESULT'  => 'ERROR',
                'MESSAGE' => 'Не удалось определить контакт',
            ];
        }
        $arParams = [
            'Entity' => [
                "Id"                  => $contactId,
                "LastName"            => $lastName,
                "FirstName"           => $firstName,
                "MiddleName"          => $middleName,
                "GenderCode"          => ($gender == 'M') ? "1" : "2",
                "BirthDate"           => $birthDate,
                "FamilyStatusCode"    => "1",
                "HasChildrenCode"     => "1",
                "EmailAddress"        => $email,
                "MobilePhone"         => $phone,
                "AllowEmail"          => true,
                "AllowSms"            => true,
                //"PartnerId"           => self::$arSitesData[self::SITE]['partnerId'],
                "OrgUnitId"           => self::$arSitesData[self::SITE]['orgUnitId'],
                "CommunicationMethod" => 1,
                "AllowNotification"   => true,
                "AllowPhone"          => true,
                "AgreeToTerms"        => true,
                "EmailVerified"       => true,
                "MobilePhoneVerified" => true,
            ],
        ];

        $arData = self::getInstance()->sendLKRequest('Contact/Update', $arParams, 'post', 'admin', 'json');

        if ($arData['OUT_JSON']['odata.error']['code']) {
            return [
                'RESULT'     => 'ERROR',
                'ERROR_CODE' => $arData['OUT_JSON']['odata.error']['code'],
                'MESSAGE'    => $arData['OUT_JSON']['odata.error']['message']['value'],
            ];
        } else {
            return [
                'RESULT' => 'OK',
                'VALUE'  => $arData['OUT_JSON']['value'],
            ];
        }
    }

    /**
     * проверяет соответствие карты с текущим сайтом
     *
     * @param string $card      - номер карты
     * @param string $cardBrand - будет возвращен бренд карты
     * @param bool   $cacheEnable
     *
     * @return bool
     */
    public static function checkCardType($card = '', &$cardBrand = '', $cacheEnable = true)
    {
        global $skipCheckCardType;
        if ($skipCheckCardType) return true;

        $arBalance = self::getInstance()->cardBalance($card, $cacheEnable);

        $cardBrand = ToUpper($arBalance['DATA']['CardType']);

        $siteData = self::$arSitesData[self::SITE];
        if (in_array($cardBrand, $siteData['brand'])) return true;

        return false;
    }

    /**
     * проверяет карту по номеру и пин-коду
     * возвращает также контактные данные
     *
     * @param string $card
     * @param string $pinCode
     * @param bool   $contactId
     * @param bool   $cacheEnable
     *
     * @return array
     */
    public static function checkCardPin($card = '', $pinCode = '', $contactId = false, $cacheEnable = true)
    {
        $arOut = ['RESULT' => 'ERROR', 'FIELD' => ''];
        if (empty($card)) {
            $arOut['FIELD'] = 'CARD';
            return $arOut;
        } else if (empty($pinCode)) {
            $arOut['FIELD'] = 'PINCODE';
            return $arOut;
        }

        if (!$contactId) {
            $arData = self::getInstance()->findContact('', '', $card, $cacheEnable);
            $arContact = $arData[0];
            $contactId = $arContact['Id'];
            if ($contactId) {
                $arCard = self::getInstance()->getCardId($card, $cacheEnable, true);
                $cardBrand = '';
                if (!self::getInstance()->checkCardType($card, $cardBrand, $cacheEnable)) {
                    $arOut['FIELD'] = 'CARD'; // неправильно введена карта

                    return $arOut;
                }
            } else {
                // контакт по карте не найден, проверим, существует ли сама карта
                $arBalance = self::getInstance()->cardBalance($card, $cacheEnable);
                $cardBrand = '';
                $checkCardType = self::getInstance()->checkCardType($card, $cardBrand, $cacheEnable);
                if ($arBalance['RESULT'] == 'OK' && $checkCardType && !$arBalance['CONTACT']) {
                    $arOut['FIELD'] = 'CONTACT'; // карта есть, но к ней не привязан контакт
                } else {
                    $arOut['FIELD'] = 'CARD'; // неправильно введена карта
                }

                return $arOut;
            }
        }

        $arData = self::getInstance()->sendLKRequest(
            'Contact/BindCard',
            [
                //'contactId'  => $contactId,
                'Id'         => $contactId,
                'CardNumber' => $card,
                'CodeWord'   => $pinCode,
            ],
            'post',
            'admin',
            'json'
        );

        /**
         * error codes
         *  - 100961 // Пин-код неверен
         *  - 1000048 // Карта уже принадлежит клиенту (значит пин-код верен)
         *  - 100962 // Card is not active (новая карта)
         */

        if ($arData['OUT_JSON']['odata.error']['code'] == 100961) {
            $arOut['FIELD'] = 'PINCODE';

        } elseif ($arData['OUT_JSON']['odata.error']['code'] == 1000048) {
            // Карта уже принадлежит клиенту (значит пин-код верен)

            $arOut['RESULT'] = 'OK';
            if (!isset($arContact)) {
                $arData = self::getInstance()->findContact('', '', $card, $cacheEnable);
                $arContact = $arData[0];
            }
            $arOut['CONTACT'] = $arContact;

        } elseif ($arData['OUT_JSON']['odata.error']['code'] == 100962) {
            // Новая карта
            $arOut['FIELD'] = 'NEW_CARD';

        } elseif ($arData['OUT_JSON']['odata.error']['code'] > '') {
            // некая другая ошибка
            $arOut['FIELD'] = 'CARD';

        } else {
            // Карта привязана к контакту
            $arOut['RESULT'] = 'OK';

            $clearCache = true; // чистим кэш, чтобы по карте стал отдаваться новый контакт
            self::getInstance()->cardBalance($card, $cacheEnable, $clearCache);
            $arData = self::getInstance()->findContact('', '', $card, $cacheEnable, $clearCache);
            $arContact = $arData[0];
            $arOut['CONTACT'] = $arContact;
        }

        return $arOut;
    }

    /**
     * запрашивает у Манзаны купон на указанный тип скидки
     *
     * @param string $card
     * @param string $type  - тип купона
     *                      - coupon500 - скидка 500руб
     *                      - coupon10 - скидка 10%
     *
     * @return array
     */
    public static function generateCoupon($cardnumber = '', $type = 'coupon500')
    {
        if ($type != 'coupon10' && $type != 'coupon5') $type = 'coupon500'; // доступны только два вида купонов

        $configParams = self::getConfigParams('POS');
        if (!$cardnumber) $cardnumber = $configParams['Card']; // системная карта

        $requestID = self::getRequestId();
        $arParams = [
            'RequestID'         => $requestID,
            'Card'              => ['CardNumber' => $cardnumber],
            'DateTime'          => date('c'),
            'Organization'      => $configParams['Organization'],
            'BusinessUnit'      => $configParams['BusinessUnit'],
            'POS'               => $configParams['POS'],
            'Number'            => $requestID.'_create_coupon',
            'Campaign'          => $configParams['OrgCoupon'],
            'Partner'           => $configParams['Organization'],
            'Value'             => 0,
            'AwardType'         => 'Bonus',
            'ChargeType'        => 'Charge',
            'AccountingType'    => 'Debet',
            'ActionTime'        => 'Immediately',
            'isStatus'          => '0',
            'ExtendedAttribute' => [
                'Key'   => $type,
                'Value' => '1',
            ],
            'Description'       => '',
        ];

        $result = self::sendRequest(
            $configParams['FULL_PATH'],
            'ProcessRequest',
            "http://loyalty.manzanagroup.ru/loyalty.xsd",
            'order',
            [
                'request' => [
                    'BonusRequest' => $arParams
                ],
                'orgName' => 'jamilco'
            ],
            [
                'LOGIN'    => $configParams['LOGIN'],
                'PASSWORD' => $configParams['PASSWORD'],
            ],
            '',
            true
        );

        return $result['DATA']['BonusResponse']['InstantCoupons']['InstantCoupon']['Number'];
    }

    public static function formatPhone($phone = '')
    {
        // +79998887766
        $phone = str_replace(['+', '(', ')', '-', ' '], '', $phone);
        if (strlen($phone) == 10) $phone = '7'.$phone;

        if (substr($phone, 0, 1) == '8') $phone = '7'.substr($phone, 1);

        $phone = '+'.$phone;

        return $phone;
    }

    /**
     * логируем запросы и ответы
     *
     * @param string $requestType
     * @param string $request
     * @param string $response
     *
     * @return bool
     */
    public static function writeLog($requestType = '', $request = '', $response = '', $subDir = '')
    {
        $type = 'order';
        if (substr_count($requestType, '/')) {
            $type = 'lk';
            $requestType = explode('/', $requestType);
            $subDir = $requestType[1];
            $requestType = $requestType[0];
        }

        if ($type == 'order' && !$subDir) {
            if ($_SESSION['DEBUG_BASKET'] == 'Y') {
                $subDir = '!basket!';
            } else {
                return false;
            }
        }

        if (is_array($request)) $request = print_r($request, 1);
        if (is_array($response)) $response = print_r($response, 1);

        $request = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $request);
        $response = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $response);

        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $server = $context->getServer();
        $logDir = $server->getDocumentRoot().self::$logFileDir;
        if ($requestType) {
            $logDir .= $requestType.'/';
        } else {
            $logDir .= 'other/';
        }
        if ($subDir) $logDir .= $subDir.'/';

        if ($type == 'order') {
            $requestId = (int)\COption::GetOptionInt("main", self::REQUEST_ID_PROP, 1000);

            global $logStr;
            $logFile = $logDir.$requestId.$logStr.'_'.date('Y-m-d').'.xml';
        } elseif ($type == 'lk') {
            $logFile = $logDir.date('Y-m-d_H-i-s').'_'.$server->getServerAddr().'.xml';
        }

        CheckDirPath($logDir);
        file_put_contents(
            $logFile,
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n<root><request>\r\n".$request."\r\n</request>\r\n<response>\r\n".$response."\r\n</response></root>"
        );

        return true;
    }

    /**
     * возвращает requestId - постоянно наращиваемый индекс
     *
     * @return int
     */
    public static function getRequestId()
    {
        $requestId = \COption::GetOptionInt("main", self::REQUEST_ID_PROP, 1000);
        $requestId = (int)$requestId;
        if (!$requestId || $requestId < 1000) $requestId = 1000;
        $requestId++;
        \COption::SetOptionInt("main", self::REQUEST_ID_PROP, $requestId);

        return $requestId;
    }

    /**
     * @desc возвращает IP адрес текущего пользователя
     *
     * @return mixed
     */
    public static function getIP()
    {
        $ip = false;
        if ($_SERVER['HTTP_CLIENT_IP']) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * проверяет, отправлен ли заказ в манзану
     *
     * @param int  $orderId   - номер заказа
     * @param bool $setSended - надо ли установить, что заказ отправлен
     *
     * @return bool
     */
    public static function checkOrderSended($orderId = 0, $setSended = false)
    {
        Loader::includeModule('sale');

        if (!$orderId) return false;

        $propCode = 'SEND'; // код свойства "Отправлено в Манзану"

        $arSendProp = [
            'ID'    => false,
            'VALUE' => 'N',
        ];
        $pr = \CSaleOrderPropsValue::GetList(
            [],
            [
                'ORDER_ID' => $orderId,
                'CODE'     => $propCode,
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'CODE', 'VALUE']
        );
        if ($arProp = $pr->Fetch()) $arSendProp = $arProp;

        if ($setSended && $arSendProp['VALUE'] != 'Y') {
            if ($arSendProp['ID']) {
                \CSaleOrderPropsValue::Update($arSendProp['ID'], ['VALUE' => 'Y']);
            } else {
                $rsOrder = \CSaleOrderProps::GetList([], ['CODE' => $propCode]);
                if ($arOrderProp = $rsOrder->Fetch()) {
                    \CSaleOrderPropsValue::Add(
                        [
                            "ORDER_ID"       => $orderId,
                            "ORDER_PROPS_ID" => $arOrderProp['ID'],
                            "NAME"           => $arOrderProp['NAME'],
                            "CODE"           => $arOrderProp['CODE'],
                            "VALUE"          => 'Y',
                        ]
                    );
                }
            }

            return true; // значение было установлено
        }

        return ($arSendProp['VALUE'] == 'Y') ? true : false;
    }
}