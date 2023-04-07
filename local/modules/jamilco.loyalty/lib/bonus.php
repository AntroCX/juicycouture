<?

namespace Jamilco\Loyalty;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Sale;
use \Bitrix\Sale\Order;
use \Bitrix\Sale\Internals\BasketTable;
use \Bitrix\Sale\Internals\BasketPropertyTable;
use \Bitrix\Sale\Internals\DiscountTable;
use \Bitrix\Sale\DiscountCouponsManager;
use \Jamilco\Main\Manzana;
use \Jamilco\Loyalty;
use \Jamilco\Loyalty\Card;
use \Jamilco\Loyalty\Log;

class Bonus
{
    const CASH_PAY_SYSTEM = 8;
    const PICKUP_CURIER = 18;   // доставка курьером
    const PICKUP_DELIVERY = 31;

    /**
     * возвращает данные о введенной карте и бонусах
     *
     * @return array
     */
    public static function getData($number = false, $applyBonuses = '', $orderId = 0, $skipConfirm = false)
    {
        $active = (\COption::GetOptionInt("jamilco.loyalty", "active")) ? 'Y' : 'N';
        $bonus = new Bonus();
        $arResult = [
            'ACTIVE' => $active,
            'SECURE' => (Log::getInstance()->checkSecure()) ? 'Y' : 'N',
        ];

        if ($arResult['ACTIVE'] == 'Y' && $arResult['SECURE'] == 'N') {

            if ($skipConfirm) {
                global $acceptOrderPriceChanging;
                $acceptOrderPriceChanging = true;
            }

            $apply = false; // карта уже применена
            if ($basketNumber = self::checkBasketForBonusCard($orderId)) {
                $apply = true; // бонусы уже списаны с цен корзины
                if (!$number) $number = $basketNumber;
                if (!$applyBonuses) $applyBonuses = 'Y';
            }

            if (!$number) $number = $_SESSION['LOYALTY_CARD_NUMBER'];
            if ($number) {
                if ($arData = Card::getClientData($number, $apply)) {
                    $arResult['CARD'] = $number;
                    $arResult['MASK'] = $arData['MASK'];
                    $arResult['CONFIRM'] = ($arData['CONFIRM'] == 'Y' || $skipConfirm) ? 'Y' : 'N'; // карта подтверждена
                    if ($arResult['CONFIRM'] != 'Y' && $applyBonuses == 'Y') $applyBonuses = ''; // нельзя применить бонусы, если карта еще не подтверждена
                    $arResult['APPLY'] = ($apply || $applyBonuses == 'Y') ? 'Y' : 'N'; // карта была или будет применена

                    // получим количество бонусов, которые можно списать
                    $arResult['BONUSES'] = $bonus->compileResult($number, $applyBonuses, $orderId, $skipConfirm);
                } else {
                    $arResult['ERROR'] = 'NOT_FOUND';
                    $bonus->additionalSum($arResult['BONUSES']);
                }
            } else {
                $arResult['ERROR'] = 'NOT_ENTER';
            }
        }

        // запрашивает Манзану даже для случая невведенной карты (будут применены общие скидки)
        if ($arResult['ERROR'] && Loyalty\Common::discountsAreMoved()) {
            $bonus->compileResult('', 'N', $orderId, false);
        }

        return $arResult;
    }

    /**
     * возвращает номер карты, если бонусы с нее были списаны в счет заказа
     *
     * @param int $orderId
     *
     * @return string
     */
    public static function checkCardInOrder($orderId = 0)
    {
        if (!$orderId) return false;

        // ищем номер карты в элементах корзины
        $rsBasket = \CSaleBasket::GetList(
            [],
            ['ORDER_ID' => $orderId],
            false,
            false,
            ['ID', 'PRICE', 'BASE_PRICE', 'QUANTITY', 'CURRENCY', 'PRICE_TYPE_ID']
        );
        while ($arBasket = $rsBasket->Fetch()) {
            $pr = \CSaleBasket::GetPropsList(
                [
                    "SORT" => "ASC",
                    "NAME" => "ASC"
                ],
                ["BASKET_ID" => $arBasket['ID']]
            );
            while ($arProp = $pr->Fetch()) {
                unset($arProp['BASKET_ID']);
                unset($arProp['ID']);
                $arBasket['PROPS'][$arProp['CODE']] = $arProp;
            }

            if ($arBasket['PROPS']['LOYALTY_BONUS_REDUCE']['VALUE'] == 'Y') {
                return $arBasket['PROPS']['LOYALTY_BONUS_CART']['VALUE'];
            }
        }

        // ищем номер карты в свойствах заказа
        $pr = \CSaleOrderPropsValue::GetOrderProps($orderId);
        while ($arProp = $pr->Fetch()) {
            $arOrderProps[$arProp['CODE']] = $arProp;
        }
        if ($arOrderProps['PROGRAMM_LOYALTY_WRITEOFF']['VALUE'] > 0) {
            return $arOrderProps['PROGRAMM_LOYALTY_CARD']['VALUE'];
        }

        return false;
    }

    /**
     * возвращает количество бонусов и примененную карту по заказу (из элементов корзины)
     *
     * @param int  $orderId
     * @param bool $reducePrice     - снизить цену элементам корзины на величину списанных бонусов
     * @param bool $clearBonusProps - удалить всю информацию о бонусной карте из свойств корзины
     *
     * @return array
     */
    public static function getCardByOrder($orderId = 0, $reducePrice = false, $clearBonusProps = false)
    {
        $arResult = [
            'CARD'    => '',
            'BONUSES' => 0,
        ];

        $arBaskets = [];
        $rsBasket = \CSaleBasket::GetList(
            [],
            ['ORDER_ID' => $orderId],
            false,
            false,
            ['ID', 'PRICE', 'BASE_PRICE', 'QUANTITY', 'CURRENCY', 'PRICE_TYPE_ID']
        );
        while ($arBasket = $rsBasket->Fetch()) {
            $pr = \CSaleBasket::GetPropsList(["SORT" => "ASC", "NAME" => "ASC"], ["BASKET_ID" => $arBasket['ID']]);
            while ($arProp = $pr->Fetch()) {
                unset($arProp['BASKET_ID']);
                unset($arProp['ID']);
                $arBasket['PROPS'][$arProp['CODE']] = $arProp;
            }

            $arBaskets[] = $arBasket;
        }

        foreach ($arBaskets as $arBasket) {
            // если бонусы уже возвращены, то обратно их не списываем никогда
            if ($arBasket['PROPS']['LOYALTY_BONUS_RETURN']['VALUE'] == 'Y') return false;

            if ($arBasket['PROPS']['LOYALTY_BONUS_CART']['VALUE'] > '' && !$arResult['CARD']) $arResult['CARD'] = $arBasket['PROPS']['LOYALTY_BONUS_CART']['VALUE'];
            $arCheck = [
                'REDUCE' => $arBasket['PROPS']['LOYALTY_BONUS_REDUCE']['VALUE'],
                'PRICE'  => $arBasket['PRICE'],
            ];

            if ($arBasket['PROPS']['LOYALTY_BONUS']['VALUE'] > 0) {
                $arResult['BONUSES'] += $arBasket['PROPS']['LOYALTY_BONUS']['VALUE'] * $arBasket['QUANTITY'];

                $newPrice = $arBasket['PRICE'];

                if ($reducePrice) {
                    // спишем бонусы, если они еще не были списаны
                    if ($arBasket['PROPS']['LOYALTY_BONUS_REDUCE']['VALUE'] != 'Y') $newPrice = $arBasket['PRICE'] - $arBasket['PROPS']['LOYALTY_BONUS']['VALUE'];
                } else {
                    // вернем списанное, если бонусы уже были списаны
                    if ($arBasket['PROPS']['LOYALTY_BONUS_REDUCE']['VALUE'] == 'Y') $newPrice = $arBasket['PRICE'] + $arBasket['PROPS']['LOYALTY_BONUS']['VALUE'];
                }

                $arBasket['PROPS']['LOYALTY_BONUS_REDUCE'] = [
                    'CODE'  => 'LOYALTY_BONUS_REDUCE',
                    'VALUE' => ($reducePrice) ? 'Y' : 'N',
                    'NAME'  => 'Бонусы списаны из цены',
                    'SORT'  => 200,
                ];

                if ($arCheck['REDUCE'] == 'Y' && $arBasket['PROPS']['LOYALTY_BONUS_REDUCE']['VALUE'] == 'N') {
                    $arBasket['PROPS']['LOYALTY_BONUS_RETURN'] = [
                        'CODE'  => 'LOYALTY_BONUS_RETURN',
                        'VALUE' => 'Y',
                        'NAME'  => 'Осуществлен возврат бонусов',
                        'SORT'  => 200,
                    ];
                }

                if ($arCheck['REDUCE'] != $arBasket['PROPS']['LOYALTY_BONUS_REDUCE']['VALUE'] || $arCheck['PRICE'] != $newPrice) {
                    $newPrice = \Bitrix\Catalog\Product\Price::roundPrice($arBasket['PRICE_TYPE_ID'], $newPrice, $arBasket['CURRENCY']);
                    $arFields = [
                        'CUSTOM_PRICE' => 'Y',
                        'PRICE'        => $newPrice,
                        'PROPS'        => $arBasket['PROPS'],
                    ];

                    if ($clearBonusProps) {
                        foreach ($arFields['PROPS'] as $codeProp => $arProp) {
                            if (substr_count($codeProp, 'LOYALTY_BONUS')) {
                                unset($arFields['PROPS'][$codeProp]);
                            }
                        }

                        if (!\Jamilco\Loyalty\Common::discountsAreMoved()) {
                            unset($arFields['PRICE']);
                            $arFields['CUSTOM_PRICE'] = 'N';
                        }
                    }

                    global $mayToChangePrice;
                    $mayToChangePrice = true; // для того чтобы пропустил обработчик, запрещающий изменять цены в заказах

                    self::delBasketProps($arBasket['ID']);
                    \CSaleBasket::Update($arBasket['ID'], $arFields);

                    $mayToChangePrice = false;
                }
            }
        }

        return $arResult;
    }

    /**
     * агент
     * проверяет заказы не("Самовывоз") + "Оплата онлайн"
     * если в заказе списаны бонусы И если через 10 минут после создания заказ все еще не оплачен
     * ТО делаем возврат списанным бонусам
     *
     * @return string
     */
    public static function checkPayForPickup()
    {
        Loader::IncludeModule('sale');
        $minutes10 = new DateTime();
        $minutes10->add('-10 minutes'); // запросим все заказы по фильтру старше 10 минут

        $start = new DateTime();
        $start->add('-1 day'); // заказы за сутки

        $or = Order::getList(
            [
                'order'  => ['ID' => 'DESC'],
                'filter' => [
                    'DELIVERY_ID'    => self::PICKUP_DELIVERY,
                    '!PAY_SYSTEM_ID' => self::CASH_PAY_SYSTEM,
                    'PAYED'          => 'N',
                    '<DATE_INSERT'   => $minutes10,
                    '>DATE_INSERT'   => $start,
                ],
                'select' => ['ID']
            ]
        );
        while ($arOrder = $or->Fetch()) {
            self::getCardByOrder($arOrder['ID'], false);
        }

        return '\Jamilco\Loyalty\Bonus::checkPayForPickup();';
    }

    public static function checkBasketForBonusCard($orderId = 0)
    {
        Loader::includeModule('sale');

        $arBasketFilter = self::getBasketFilter($orderId);
        $rsBasket = \CSaleBasket::GetList([], $arBasketFilter, false, false, ['ID']);

        while ($arrBasket = $rsBasket->Fetch()) {
            $pr = \CSaleBasket::GetPropsList(["SORT" => "ASC", "NAME" => "ASC"], ["BASKET_ID" => $arrBasket['ID']]);
            $arProps = [];
            while ($arProp = $pr->Fetch()) {
                $arProps[$arProp['CODE']] = $arProp['VALUE'];
            }

            if ($arProps['LOYALTY_BONUS_CART'] > '' && $arProps['LOYALTY_BONUS'] > 0) {
                return $arProps['LOYALTY_BONUS_CART'];
            }
        }

        return false;
    }

    /**
     * цены товаров в заказе могут быть изменены на значения из Манзаны
     *
     * @param int $orderId
     *
     * @return bool
     */
    public static function canChangeOrder($orderId = 0)
    {
        if (!$orderId) return false;

        global $acceptOrderPriceChanging;
        if (!$acceptOrderPriceChanging) return false;

        $order = Order::load($orderId);

        $isPaid = $order->isPaid();
        $isCanceled = $order->isCanceled();

        if ($isPaid || $isCanceled) return false;

        $status = $order->getField('STATUS_ID');
        $arStatusChange = ['N', 'EP', 'J']; // статусы, в которых можно менять цены
        if (!in_array($status, $arStatusChange)) return false;

        return true;
    }

    /**
     * отдает информацию о колличестве баллов для начисления исходя из содержимого корзины
     * @return array
     */
    public function getAdditionalSum($orderId = 0, $skipChangePrices = false)
    {
        Loader::includeModule('sale');

        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0);          // включена Манзана
        $manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0); // отправляем заказы в Манзану

        // манзану не запрашиваем (правки по отправке заказов еще не слиты)
        if ($manzanaUse && $manzanaOrders) {
            $arData = Manzana::getInstance()->sendOrder($orderId);

            if (Loyalty\Common::discountsAreMoved() && !$skipChangePrices) {
                global $manzanaDeliveryId;
                if ($orderId) $manzanaDeliveryId = Order::load($orderId)->getField('DELIVERY_ID');

                if (!$orderId || self::canChangeOrder($orderId)) {
                    // запишем цены на товары, вернувшиеся из манзаны
                    self::saveBasketPrice($arData['Items'], $manzanaDeliveryId, $orderId);

                    if ($orderId) {
                        $order = Order::load($orderId);
                        $order->refreshData();
                        $order->save();
                    }
                }
            }

            $arWriteOffItems = [];
            foreach ($arData['Items'] as $basketId => $arItem) {
                $arWriteOffItems[$basketId] = $arItem['WriteoffBonus'];
            }

            return [
                'ADDITIONAL_SUM'   => (int)$arData['ChargedBonus'],
                'WRITEOFF_SUM_MAX' => (int)$arData['AvailablePayment'],
                'WRITEOFF_SUM'     => (int)$arData['AvailablePayment'],
                'WRITEOFF_ITEMS'   => $arWriteOffItems,
            ];
        } else {

            $intBasketSum = 0;

            $arOrder = self::getOrder($orderId);

            $arSum = [];
            foreach ($arOrder['BASKET_ITEMS'] as $arrBasket) {
                if ($this->checkParticipation($arrBasket, $arOrder['DISCOUNT_LIST'])) {
                    if (!$arrBasket['BASE_PRICE'] && $arrBasket['PRODUCT_PRICE_ID']) {
                        $arPrice = \CPrice::GetByID($arrBasket['PRODUCT_PRICE_ID']);
                        $arrBasket['BASE_PRICE'] = $arPrice['PRICE'];
                    }

                    $arSum[$arrBasket['PRODUCT_ID']] = ($arrBasket['BASE_PRICE'] - $arrBasket['DISCOUNT_PRICE']) * $arrBasket['QUANTITY'];
                    $intBasketSum += ($arrBasket['BASE_PRICE'] - $arrBasket['DISCOUNT_PRICE']) * $arrBasket['QUANTITY'];
                }
            }

            $writeoff = \COption::GetOptionInt("jamilco.loyalty", "writeoff", 20);
            $addbonus = \COption::GetOptionInt("jamilco.loyalty", "addbonus", 5);
            $double = \COption::GetOptionString("jamilco.loyalty", "double", "");
            $double = explode(',', $double);

            if (count($double)) {
                // проверим каждый товар на двойные баллы
                $addSum = 0;
                foreach ($arSum as $productId => $sum) {
                    $getDouble = false;
                    $el = \CIblockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID' => MODEL_IBLOCK,
                            'ID'        => \CIblockElement::SubQuery('PROPERTY_CML2_LINK', ['ID' => $productId])
                        ],
                        false,
                        ['nTopCount' => 1],
                        ['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID']
                    );
                    if ($arItem = $el->Fetch()) {
                        if (in_array($arItem['IBLOCK_SECTION_ID'], $double)) $getDouble = true;
                    }

                    if ($getDouble) {
                        $addSum += $sum * 2;
                    } else {
                        $addSum += $sum;
                    }
                }
                $intAddSum = round($addSum * $addbonus / 100);
            } else {
                $intAddSum = round($intBasketSum * $addbonus / 100);
            }

            $intWriteOffSum = round($intBasketSum * $writeoff / 100);

            return [
                'ADDITIONAL_SUM'   => $intAddSum,
                'WRITEOFF_SUM_MAX' => $intWriteOffSum,
                'WRITEOFF_SUM'     => $intWriteOffSum,
            ];
        }
    }

    private static function saveBasketPrice($arItems = [], $deliveryId = 0, $orderId = 0)
    {
        if (!$arItems) return false;

        global $priceChangedCustom, $mayToChangePrice;
        $priceChangedCustom = false;
        $mayToChangePrice = true;

        Loader::includeModule('sale');
        if ($orderId > 0) {
            $order = Order::load($orderId);
            if (!$order) return false;
            $basket = $order->getBasket();
        } else {
            $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID);
        }
        /** @var Sale\BasketItem $basketItem */
        foreach ($basket as $basketItem) {
            $arItem = $arItems[$basketItem->getId()];
            $arItem['BasePrice'] = roundEx($arItem['BasePrice'], 2);
            $arItem['Price'] = roundEx($arItem['Price'], 2);
            if ($deliveryId != PICKUP_DELIVERY && $arItem['BasePrice'] > $arItem['Price']) {
                // установим кастомную цену
                if ($basketItem->getField('CUSTOM_PRICE') == 'N' || $basketItem->getPrice() != $arItem['Price']) {
                    $basketItem->setField('CUSTOM_PRICE', 'Y');
                    $basketItem->setField('PRICE', $arItem['Price']);
                    $priceChangedCustom = 'Y';
                }
            } else {
                // вернем дефолтную цену
                if ($basketItem->getField('CUSTOM_PRICE') == 'Y') {
                    $basketItem->setField('CUSTOM_PRICE', 'N');
                    $priceChangedCustom = 'N';
                }
            }

            if ($_REQUEST['debug'] == 'items') {
                $arBasket = $basketItem->getFieldValues();
                $arBasket['PROPS'] = $basketItem->getPropertyCollection()->getPropertyValues();
                ppr($arBasket);
            }
        }
        if ($priceChangedCustom) {
            $basket->save();
        }

        if ($_REQUEST['debug'] == 'items') pr($arItems, 1);
    }

    /**
     * проверяет участвует ли товар в программе лояльности
     *
     * @param $productId
     *
     * @return bool
     */
    private function checkParticipation($arBasket = [], $arDiscountList = [])
    {
        Loader::includeModule('iblock');

        $productId = $arBasket['PRODUCT_ID'];

        $result = false;

        // галочка "Исключить товары с Presale-скидкой"
        $presale = \COption::GetOptionInt("jamilco.loyalty", "presale");
        if ($presale) {
            // товары, на которые действуют presale-скидки, не участвуют в программе лояльности
            foreach ($arDiscountList as $arDiscount) {
                foreach ($arDiscount['RESULT']['BASKET'] as $arOne) {
                    if ($arOne['BASKET_ID'] == $arBasket['ID']) {
                        // проверим скидку, является ли она PRESALE
                        $d = DiscountTable::getList(
                            [
                                'filter' => ['ID' => $arDiscount['ID']],
                                'select' => ['ID', 'NAME', 'XML_ID'],
                                'limit'  => 1,
                            ]
                        );
                        $arDiscountCheck = $d->Fetch();
                        if (substr_count($arDiscountCheck['XML_ID'], 'presale')) return false;
                    }
                }
            }
        }

        $sale = \COption::GetOptionInt("jamilco.loyalty", "sale");
        $saleProperty = \COption::GetOptionString("jamilco.loyalty", "saleproperty");

        $arSelect = [
            'PROPERTY_CML2_LINK',
            'PROPERTY_PRICE_WITHOUT_DISCOUNT',  // используется для определения "sale"-метки
            'CATALOG_GROUP_1',                  // используется для определения "sale"-метки
            'CATALOG_GROUP_2',                  // используется для определения "sale"-метки
        ];

        $rsOffer = \CIBlockElement::GetList([], ['ID' => $productId], false, ['nTopCount' => 1], $arSelect);
        $arOffer = $rsOffer->Fetch();

        // если распродажные товары не должны участвовать в бонусной программе
        if (!$sale) {
            $arPrices = [
                'MAIN' => $arOffer['CATALOG_PRICE_1'],
                'SUB'  => $arOffer['CATALOG_PRICE_2'],
            ];
            if ($arOffer['PROPERTY_PRICE_WITHOUT_DISCOUNT_VALUE'] > 0 && $arOffer['PROPERTY_PRICE_WITHOUT_DISCOUNT_VALUE'] > $arOffer['CATALOG_PRICE_1']) {
                $arPrices['MAIN'] = $arOffer['PROPERTY_PRICE_WITHOUT_DISCOUNT_VALUE'];
                $arPrices['SUB'] = $arOffer['CATALOG_PRICE_1'];
            }

            // если у ТП задана скидочная цена, то он признан sale-товаром
            if ($arPrices['SUB'] > 0) return false;
        }

        $rsSections = \CIBlockElement::GetElementGroups($arOffer['PROPERTY_CML2_LINK_VALUE']);
        while ($arrSections = $rsSections->Fetch()) {
            $arSections[] = $arrSections['ID'];
        }

        $selected = \COption::GetOptionString("jamilco.loyalty", "selected");

        $arSelected = explode(',', $selected);

        foreach ($arSections as $section) {
            if (in_array($section, $arSelected)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @param $arResult - все доступные данные
     * @param $do       - применить бонус или нет
     * @param $number   - номер карты
     */
    private function applyLoyalty($arResult, $do, $number, $orderId = 0)
    {
        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0); // отправим данные по заказу в Манзану
        $manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0); // отправляем заказы в Манзану

        $manzana = ($manzanaUse && $manzanaOrders) ? true : false;

        $arOrder = self::getOrder($orderId);
        $arBasket = $arOrder['BASKET_ITEMS'];

        $arAllProps = [];
        $isLoyalty = false; // в корзине уже учтены бонусы

        foreach ($arBasket as $arBasketItem) {
            $pr = BasketPropertyTable::getList(['filter' => ['BASKET_ID' => $arBasketItem['ID']]]);
            while ($arProp = $pr->Fetch()) {
                unset($arProp['ID']);
                unset($arProp['BASKET_ID']);
                $arAllProps[$arBasketItem['ID']][$arProp['CODE']] = $arProp;

                if ($arProp['CODE'] == 'LOYALTY_BONUS' && $arProp['VALUE'] > 0) {
                    $isLoyalty = true;
                }
            }
        }

        if ($manzana) {
            // манзана уже вернула, сколько и по какому товару можно списать бонусов

        } else {
            $writeoffSettings = \COption::GetOptionInt("jamilco.loyalty", "writeoff");
            $algorithm = \COption::GetOptionInt("jamilco.loyalty", "algorithm");

            $writeOffSum = $arResult['WRITEOFF_SUM'];
            $writeOfPercent = $writeoffSettings / 100;

            $firstItem = true;
            $arParticipationIDs = []; // ID товаров, которые можно оплачивать
            $countBasketElements = 0; // количество товаров, которые можно оплачивать (не ПОЗИЦИЙ, а ТОВАРОВ)
            $summPriceOfElements = 0; // сумма цен товаров, которые можно оплачивать
            foreach ($arBasket as $arBasketItem) {
                if ($this->checkParticipation($arBasketItem, $arOrder['DISCOUNT_LIST'])) {
                    $arParticipationIDs[] = $arBasketItem['PRODUCT_ID'];
                    $countBasketElements += $arBasketItem['QUANTITY'];
                    $summPriceOfElements += $arBasketItem['PRICE'] * $arBasketItem['QUANTITY'];
                }
            }
        }

        foreach ($arBasket as $arBasketItem) {
            if ($do == 'Y') {
                $writeOff = 0;
                $arBasketItem['PROPS'] = $arAllProps[$arBasketItem['ID']];

                if ($manzana) {

                    $writeOff = $arResult['WRITEOFF_ITEMS'][$arBasketItem['ID']];

                } else {
                    $writeOff = floor($writeOffSum / $countBasketElements);

                    if (in_array($arBasketItem['PRODUCT_ID'], $arParticipationIDs)) {
                        $thisLoyality = round($arBasketItem['PRICE'] * $writeOfPercent);

                        if ($algorithm == 2) {
                            if ($thisLoyality < $writeOffSum) {
                                $writeOff = $thisLoyality;
                                $writeOffSum = $writeOffSum - $writeOff;
                            } elseif ($writeOffSum > 0) {
                                $writeOff = $writeOffSum;
                                $writeOffSum = 0;
                            }
                        }

                        if ($algorithm == 3) {
                            if ($thisLoyality < $writeOffSum) {
                                $writeOff = $thisLoyality;
                                $writeOffSum = -$thisLoyality;
                            } else {
                                $writeOff = $writeOffSum;
                                $writeOffSum = 0;
                            }
                        }

                        if ($algorithm == 4) {
                            if ($firstItem) {
                                if ($thisLoyality < $writeOffSum) {
                                    $writeOff = $thisLoyality;
                                } else {
                                    $writeOff = $writeOffSum;
                                }
                                $firstItem = false;
                            } else {
                                $writeOff = 0;
                            }
                        }

                        if ($algorithm == 1) {
                            // на все товары поровну (в пропорции их цен)
                            $writeOff = ($arBasketItem['PRICE'] * $arBasketItem['QUANTITY'] / $summPriceOfElements) * $writeOffSum / $arBasketItem['QUANTITY'];
                        }

                    } else {
                        // товар не может быть оплачен бонусами
                    }
                }

                if ($writeOff > 0) {
                    $arBasketItem['PROPS']['LOYALTY_BONUS'] = [
                        'NAME'  => 'Бонусные баллы',
                        'CODE'  => 'LOYALTY_BONUS',
                        'VALUE' => $writeOff,
                        'SORT'  => 200,
                    ];
                }

                $arBasketItem['PROPS']['LOYALTY_BONUS_CART'] = [
                    'NAME'  => 'Номер бонусной карты',
                    'CODE'  => 'LOYALTY_BONUS_CART',
                    'VALUE' => $number,
                    'SORT'  => 200,
                ];

                self::saveBasketProps($arBasketItem);
            } elseif ($do == 'N') {
                foreach ($arAllProps[$arBasketItem['ID']] as $key => $props) {
                    if ($props['CODE'] == 'LOYALTY_BONUS' || $props['CODE'] == 'LOYALTY_BONUS_CART') {
                        $arAllProps[$arBasketItem['ID']][$key]['VALUE'] = false;
                    }
                }
                $arBasketItem['PROPS'] = $arAllProps[$arBasketItem['ID']];

                self::delBasketProps($arBasketItem['ID']);
            }
        }
    }

    private static function getOrder($orderId = false)
    {
        global $USER;

        $allSum = 0;
        $allWeight = 0;
        $arBaskets = [];

        $arBasketFilter = self::getBasketFilter($orderId);
        $rsBasket = \CSaleBasket::GetList([], $arBasketFilter);

        while ($arrBasket = $rsBasket->Fetch()) {
            $allSum += ($arrBasket["PRICE"] * $arrBasket["QUANTITY"]);
            $allWeight += ($arrBasket["WEIGHT"] * $arrBasket["QUANTITY"]);
            $arBaskets[] = $arrBasket;
        }

        $arOrder = [
            'SITE_ID'      => SITE_ID,
            'USER_ID'      => $USER->GetID(),
            'ORDER_PRICE'  => $allSum,
            'ORDER_WEIGHT' => $allWeight,
            'BASKET_ITEMS' => $arBaskets,
            'DELIVERY_ID'  => ($_REQUEST['DELIVERY_ID']) ?: self::PICKUP_CURIER,
        ];
        if ($orderId) {
            $order = Order::load($orderId);
            $arOrder['USER_ID'] = $order->getUserId();
        }

        $arOptions = $arErrors = [];

        $arCoupons = DiscountCouponsManager::get();
        foreach ($arCoupons as $coupon => $arCoupon) {
            DiscountCouponsManager::add($coupon);
        }
        DiscountCouponsManager::finalApply();
        \CSaleDiscount::DoProcessOrder($arOrder, $arOptions, $arErrors);

        return $arOrder;
    }

    private static function getBasketFilter($orderId = 0)
    {
        $arBasketFilter = [
            'FUSER_ID' => \CSaleBasket::GetBasketUserID(),
            'LID'      => SITE_ID,
            'DELAY'    => 'N',
            'ORDER_ID' => false,
            'CAN_BUY'  => 'Y'
        ];
        if ($orderId) $arBasketFilter = ['ORDER_ID' => $orderId];

        return $arBasketFilter;
    }

    public static function saveBasketProps($arBasketItem = [])
    {
        self::delBasketProps($arBasketItem['ID']);
        foreach ($arBasketItem['PROPS'] as $arProp) {
            if ($arProp['CODE'] != 'LOYALTY_BONUS' && $arProp['CODE'] != 'LOYALTY_BONUS_CART') continue;
            $arProp['BASKET_ID'] = $arBasketItem['ID'];
            BasketPropertyTable::Add($arProp);
        }
        //\CSaleBasket::Update($arBasketItem['ID'], ['PROPS' => $arBasketItem['PROPS']]);
    }

    public static function delBasketProps($basketId = 0)
    {
        $pr = BasketPropertyTable::getList(['filter' => ['BASKET_ID' => $basketId]]);
        while ($arProp = $pr->Fetch()) {
            if (!substr_count($arProp['CODE'], 'LOYALTY_BONUS')) continue;
            BasketPropertyTable::Delete($arProp['ID']);
        }

        return true;
    }

    /**
     * @param $number - номер карты
     * @param $type   - тип запроса, либо применить карту, либо получить информацию по карте
     *
     * @return array
     */
    private function compileResult($number, $type, $orderId = 0, $skipConfirm = false)
    {
        $arResult = [];
        if ($number) {
            $arResult['CARD_NUMBER'] = $number;
            $arResult['CARD_BALANCE'] = Card::getBalance($number);

            if ($arResult['CARD_BALANCE'] > 0) {
                $arData = Card::getClientData($number);
                if ($arData['CONFIRM'] != 'Y' && !$skipConfirm) $type = 'N'; // если владение картой не подтверждено, то бонусы не списываются
            }
        }

        $this->additionalSum($arResult, $orderId);

        if ($number) $this->applyLoyalty($arResult, $type, $number, $orderId);

        $arResult['TYPE'] = \COption::GetOptionInt("jamilco.loyalty", "type");

        return $arResult;
    }

    private function additionalSum(&$arResult, $orderId = 0)
    {
        $arAddSum = $this->getAdditionalSum($orderId);
        $arResult = array_merge($arResult, $arAddSum);

        if ($arResult['CARD_BALANCE'] < $arAddSum['WRITEOFF_SUM_MAX']) {
            $arResult['WRITEOFF_SUM'] = $arResult['CARD_BALANCE'];
        }

        $arResult['ADDITIONAL_SUM_FORMAT'] = number_format($arResult['ADDITIONAL_SUM'], 0, '.', ' ');
        $arResult['CARD_BALANCE_FORMAT'] = number_format($arResult['CARD_BALANCE'], 0, '.', ' ');
        $arResult['WRITEOFF_SUM_FORMAT'] = CurrencyFormat($arResult['WRITEOFF_SUM'], 'RUB');
    }
}