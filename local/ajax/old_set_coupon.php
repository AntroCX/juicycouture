<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if($_REQUEST['coupon']&&check_bitrix_sessid()) {
    \CModule::IncludeModule('catalog');
    \CModule::IncludeModule('sale');
    if(\Bitrix\Sale\DiscountCouponsManager::add($_REQUEST['coupon'])) {
        $dbBasketItems = \CSaleBasket::GetList(
                                        array(),
                                        array(
                                            'FUSER_ID' => \CSaleBasket::GetBasketUserID(),
                                            'ORDER_ID' => "NULL")
                        );
        while ($arItems = $dbBasketItems->Fetch()) {
            $arOrder["BASKET_ITEMS"][] = $arItems;
        }
        $arOrder['SITE_ID'] = SITE_ID;
        $arOrder['USER_ID'] = $GLOBALS['USER']->GetID();
        \CSaleDiscount::DoProcessOrder($arOrder,array(),$arErrors);
        /*foreach ($arOrder["BASKET_ITEMS"] as $arBasketItem) {
            \CSaleBasket::Add($arBasketItem);
        }*/
    }
}