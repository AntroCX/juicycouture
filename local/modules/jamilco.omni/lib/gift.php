<?php
namespace Jamilco\Omni;

use \Bitrix\Main\Loader,
    \Bitrix\Sale\Basket,
    \Bitrix\Sale\Fuser,
    \Bitrix\Sale\Discount\Gift\Manager as GiftManager,
    \Bitrix\Sale\Compatible\DiscountCompatibility;

class Gift
{
    /**
     * проверяет возможность добавления подарка в корзину
     * добавляет его, если это возможно
     *
     * @return bool
     */
    public static function checkGift()
    {
        return true; // после обновления 17.5 блокирует применение купонов
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        Loader::includeModule('sale');

        global $USER;
        $userId = $USER->GetID();
        $basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);

        $arOrder = array(
            'SITE_ID'      => SITE_ID,
            'USER_ID'      => $userId,
            'ORDER_PRICE'  => 0,
            'ORDER_WEIGHT' => 0,
            'BASKET_ITEMS' => array(),
        );

        foreach ($basket as $key => $basketItem) {
            $arBasketItem = $basketItem->getFieldValues();
            $arOrder['ORDER_PRICE'] += $arBasketItem['PRICE'] * $arBasketItem['QUANTITY'];
            $arOrder['ORDER_WEIGHT'] += $arBasketItem['WEIGHT'] * $arBasketItem['QUANTITY'];
            $arOrder['BASKET_ITEMS'][] = $arBasketItem;
        }

        $arOptions = array();
        $arErrors = array();

        \CSaleDiscount::DoProcessOrder($arOrder, $arOptions, $arErrors);

        if ($arOrder['FULL_DISCOUNT_LIST']) {
            $giftManager = GiftManager::getInstance()->setUserId($userId);
            //DiscountCompatibility::stopUsageCompatible();
            $collections = $giftManager->getCollectionsByBasket($basket, $arOrder['FULL_DISCOUNT_LIST'], $arOrder['DISCOUNT_LIST']);
            //DiscountCompatibility::revertUsageCompatible();

            $productIds = array();
            foreach ($collections as $collection) {
                foreach ($collection as $gift) {
                    $productIds[] = $gift->getProductId();
                }
            }

            if (count($productIds)) {
                // получим случайное ТП и добавим его в корзину (оно будет подарком)
                $el = \CIblockElement::GetList(
                    array('RAND' => 'ASC'),
                    array(
                        'PROPERTY_CML2_LINK' => $productIds,
                        'ACTIVE'             => 'Y',
                        'ACTIVE_DATE'        => 'Y',
                        'CATALOG_AVAILABLE'  => 'Y',
                    ),
                    false,
                    array('nTopCount' => 1),
                    array('IBLOCK_ID', 'ID')
                );
                if ($arItem = $el->Fetch()) {
                    $arProps = self::getBasketProps($arItem);

                    Add2BasketByProductID($arItem['ID'], 1, array(), $arProps);

                    return true;
                }
            }
        }

        return false;
    }

    static function getBasketProps($arItem = array())
    {
        $arPropCode = array(
            'PRICE_WITHOUT_DISCOUNT' => 'Цена без скидки',
            'SIZES_SHOES'            => 'Размеры обуви',
            'SIZES_CLOTHES'          => 'Размеры одежды',
        );

        $arProps = array();
        foreach ($arPropCode as $propCode => $propName) {
            $pr = \CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], array(), array("CODE" => $propCode));
            if ($arProp = $pr->Fetch()) {
                $val = ($arProp['VALUE_ENUM']) ?: $arProp['VALUE'];
                if ($val) {
                    $arProps[] = array(
                        'NAME'  => $propName,
                        'CODE'  => $propCode,
                        'VALUE' => $val,
                        'SORT'  => '1',
                    );
                }
            }
        }

        return $arProps;
    }
}