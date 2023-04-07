<?
namespace Jamilco\Loyalty;

use \Bitrix\Main\Loader;
use \Bitrix\Sale\Fuser;
use \Bitrix\Sale\Internals;

class Gift
{
    /**
     * добавляет подарок в корзину
     *
     * @param int|array  $deliveryId
     * @param int|array  $paymentId
     * @param bool|false $returnGift - вернуть массив данных подарка, без его добавления
     *
     * @return bool|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Exception
     */
    static public function checkGifts($deliveryId, $paymentId, $returnGift = false)
    {
        $gotAction = false; // сделано действие
        $giftAdded = false;
        $basketSum = 0;
        $ba = Internals\BasketTable::getList(
            [
                'filter' => ['FUSER_ID' => Fuser::getId(), 'ORDER_ID' => null, 'LID' => SITE_ID],
                'select' => ['ID', 'PRODUCT_ID', 'PRICE', 'DISCOUNT_PRICE', 'QUANTITY']
            ]
        );
        while ($arBasket = $ba->Fetch()) {
            $pr = Internals\BasketPropertyTable::getList(['filter' => ["BASKET_ID" => $arBasket['ID']]]);

            while ($arProp = $pr->fetch()) {
                $arBasket['PROPS'][$arProp['CODE']] = $arProp['VALUE'];
            }

            if ($arBasket['PROPS']['GIFT'] > 0) $giftAdded = $arBasket; // подарок уже добавлен
            $basketSum += $arBasket['PRICE'] * $arBasket['QUANTITY'];
        }

        $arRules = self::getRules();
        $arOffers = self::getOffers();

        $arCheckOffers = [];
        foreach ($arOffers as $arOne) {
            if ($arOne['CATALOG_QUANTITY'] < 1) continue;
            $price = ($arOne['CATALOG_PRICE_2']) ?: $arOne['CATALOG_PRICE_1'];

            foreach ($arRules as $arRule) {
                if ($arRule['DISCOUNT_SUM'] > $basketSum) continue;
                if ($arRule['DELIVERY'] &&
                    (
                        (!is_array($deliveryId) && !in_array($deliveryId, $arRule['DELIVERY'])) ||
                        (is_array($deliveryId) && !array_intersect($deliveryId, $arRule['DELIVERY']))
                    )
                ) {
                    continue;
                }
                if ($arRule['PAYMENT'] &&
                    (
                        (!is_array($paymentId) && !in_array($paymentId, $arRule['PAYMENT'])) ||
                        (is_array($paymentId) && !array_intersect($paymentId, $arRule['PAYMENT']))
                    )
                ) {
                    continue;
                }
                if ($arRule['GIFT_PRICE'] != $price) continue;

                $arCheckOffers[$arOne['ID']] = $arOne;
            }
        }

        // подарок был добавлен, но больше он не предоставляется - удаляем его
        if ($giftAdded && !$arCheckOffers[$giftAdded['PRODUCT_ID']]) {
            Internals\BasketTable::Delete($giftAdded['ID']);
            $giftAdded = false;
            $gotAction = true;
        }

        // тут остаются только те ТП, которые в наличие и которые совпадают по условию с существующими "правилами"
        if (!$giftAdded && $arCheckOffers) {
            $arGiftOffer = array_shift($arCheckOffers);

            if ($returnGift) return $arGiftOffer;

            $price = ($arGiftOffer['CATALOG_PRICE_2']) ?: $arGiftOffer['CATALOG_PRICE_1'];
            \Jamilco\Main\Utils::addToBasket(
                $arGiftOffer['ID'],
                [
                    [
                        "NAME"  => "Подарок",
                        "CODE"  => "GIFT",
                        "VALUE" => (int)$price,
                        "SORT"  => 500,
                    ]
                ]
            );
            $gotAction = true;

        }

        return $gotAction;
    }

    /**
     * возвращает массив правил, работающих в рамках класса
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    static public function getRules()
    {
        $arResult = [];
        $disc = Internals\DiscountTable::getList(
            [
                'filter' => ['=ACTIVE' => 'Y'],
                'select' => ['ID', 'NAME', 'CONDITIONS_LIST'],
                'order'  => ['ID' => 'DESC'],
            ]
        );
        while ($arDiscount = $disc->Fetch()) {
            $arCond = $arDiscount['CONDITIONS_LIST'];
            $discountSum = $giftPrice = false;
            $delivery = $payment = [];

            foreach ($arCond['CHILDREN'] as $arOne) {
                $arChild = array_shift($arOne['CHILDREN']);
                if ($arOne['CLASS_ID'] == 'CondBsktAmtGroup' && $arChild['CLASS_ID'] == 'BX:CondBsktProp' && $arChild['DATA']['Name'] == 'GIFT') {
                    $discountSum = $arOne['DATA']['Value'];
                    $giftPrice = $arChild['DATA']['Value'];
                }
                if ($arOne['CLASS_ID'] == 'CondSaleDelivery') {
                    $delivery = $arOne['DATA']['value'];
                }
                if ($arOne['CLASS_ID'] == 'CondSalePaySystem') {
                    $payment = $arOne['DATA']['value'];
                }
            }

            if (!$discountSum || !$giftPrice) continue;

            $arResult[$arDiscount['ID']] = [
                'ID'           => $arDiscount['ID'],
                'NAME'         => $arDiscount['NAME'],
                'DISCOUNT_SUM' => $discountSum,
                'GIFT_PRICE'   => $giftPrice,
                'DELIVERY'     => $delivery,
                'PAYMENT'      => $payment,
            ];
        }

        return $arResult;
    }

    /**
     * возвраащет массив ТП-подарков
     *
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    static public function getOffers()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        $arResult = [];

        $of = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID'      => IBLOCK_SKU_ID,
                '!PROPERTY_GIFT' => false,
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'CATALOG_GROUP_1',
                'CATALOG_GROUP_2',
                'PROPERTY_CML2_LINK',
                'PROPERTY_CML2_LINK.NAME',
                'PROPERTY_CML2_LINK.IBLOCK_ID',
                'PROPERTY_CML2_LINK.PROPERTY_HIDE',
            ]
        );
        while ($arOffer = $of->Fetch()) {
            $pr = \CIBlockElement::GetProperty($arOffer['IBLOCK_ID'], $arOffer['ID'], [], ['CODE' => 'RETAIL_CITIES']);
            while ($arProp = $pr->Fetch()) {
                $arOffer['RETAIL_CITIES'][] = $arProp['VALUE'];
            }

            $arResult[] = $arOffer;
        }

        return $arResult;
    }
}