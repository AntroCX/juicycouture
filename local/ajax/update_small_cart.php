<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Web\Json;
use \Jamilco\Main\Utils;

global $APPLICATION;

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if ($request->get('action') == 'delivery') {
    $arData = Utils::getPreOrderData(DELIVERY_COURIER_ID);
    if ($arData['data']['totalSum'] > 10000) $arData['data']['deliveryPrice'] = 0;

    echo Json::encode($arData);

} elseif ($request->get('action') == 'ADD2BASKET') {

    $id = (int)$request->get('id');

    \Jamilco\Main\Utils::addToBasket($id);

} elseif ($request->get('action') == 'ADD2BASKETGIFT') {

    $offerId = (int)$request->get('id');
    $quantity = (int)$request->get('quantity');
    $ruleId = $request->get('rule');

    \Jamilco\Main\Utils::addToBasket(
        $offerId,
        [
            [
                'NAME'  => 'Подарок из Манзаны',
                'CODE'  => 'MANZANA_GIFT',
                'VALUE' => $ruleId,
                'SORT'  => 200,
            ]
        ],
        $quantity,
        0,
        true
    );

} elseif ($request->get('action') == 'ADD2BASKETLIST') {

    $ids = $request->get('ids');
    foreach ($ids as $id) {
        \Jamilco\Main\Utils::addToBasket($id);
    }

} elseif ($request->get('update_small') == 'Y') {
    $APPLICATION->IncludeComponent(
        "bitrix:sale.basket.basket.line",
        "main",
        Array(
            "PATH_TO_BASKET"     => SITE_DIR."personal/basket/",
            "SHOW_NUM_PRODUCTS"  => "Y",
            "SHOW_TOTAL_PRICE"   => "Y",
            "SHOW_EMPTY_VALUES"  => "Y",
            "SHOW_PERSONAL_LINK" => "N",
            "PATH_TO_PERSONAL"   => SITE_DIR."personal/",
            "SHOW_AUTHOR"        => "N",
            "PATH_TO_REGISTER"   => SITE_DIR."login/",
            "PATH_TO_PROFILE"    => SITE_DIR."personal/",
            "SHOW_PRODUCTS"      => "Y",
            "SHOW_DELAY"         => "N",
            "SHOW_NOTAVAIL"      => "N",
            "SHOW_SUBSCRIBE"     => "Y",
            "SHOW_IMAGE"         => "Y",
            "SHOW_PRICE"         => "Y",
            "SHOW_SUMMARY"       => "Y",
            "PATH_TO_ORDER"      => SITE_DIR."personal/basket/",
            "POSITION_FIXED"     => "N"
        )
    );
}