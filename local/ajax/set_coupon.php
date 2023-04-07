<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if ($_REQUEST['coupon'] && check_bitrix_sessid()) {
    \CModule::IncludeModule('catalog');
    \CModule::IncludeModule('sale');

    if (method_exists('\Jamilco\Loyalty\Common', 'discountsAreMoved') && \Jamilco\Loyalty\Common::discountsAreMoved()) {
        if (!array_key_exists($coupon, $_SESSION['MANZANA_COUPONS'])) {
            $_SESSION['MANZANA_COUPONS'][$coupon] = ['TEXT' => 'Купон не обработан', 'TYPE' => 'ERROR'];
        }
    } else {
        if (\Bitrix\Sale\DiscountCouponsManager::add($_REQUEST['coupon'])) {
            $dbBasketItems = \CSaleBasket::GetList(
                array(),
                array(
                    'FUSER_ID' => \CSaleBasket::GetBasketUserID(),
                    'ORDER_ID' => false
                )
            );
            while ($arItems = $dbBasketItems->Fetch()) {
                $arOrder["BASKET_ITEMS"][] = $arItems;
            }
            $arOrder['SITE_ID'] = SITE_ID;
            $arOrder['USER_ID'] = $GLOBALS['USER']->GetID();
            \CSaleDiscount::DoProcessOrder($arOrder, array(), $arErrors);
        }
    }
}