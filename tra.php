<?

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Sale;
use \Bitrix\Sale\Location\LocationTable;
use \Jamilco\Main\Oracle;
use \Jamilco\Main\Manzana;
use \Jamilco\Merch\Common;
use \Jamilco\Loyalty\Card;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

global $USER;
//if (!$USER->isAdmin()) die('error');

Loader::includeModule("iblock");
Loader::includeModule("catalog");
Loader::includeModule("sale");
Loader::includeModule("jamilco.ocs");
Loader::includeModule("jamilco.merch");

$ba = Sale\Internals\BasketTable::getList(
    [
        'filter' => ['FUSER_ID' => Sale\Fuser::getId(), 'ORDER_ID' => false, 'LID' => SITE_ID],
        'select' => ['ID', 'PRODUCT_ID', 'NAME', 'QUANTITY', 'PRICE', 'CUSTOM_PRICE', 'DELAY']
    ]
);
while ($arBasket = $ba->Fetch()) {
    if ($_GET['clear'] == 'Y') Sale\Internals\BasketTable::delete($arBasket['ID']);

    $pr = Sale\Internals\BasketPropertyTable::getList(['filter' => ["BASKET_ID" => $arBasket['ID']]]);
    while ($arProp = $pr->Fetch()) {
        $arBasket['PROPS'][$arProp['CODE']] = $arProp['VALUE'];
    }

    $arBaskets[] = $arBasket;
}
pr($arBaskets, 1, 1);


//$_SESSION['DEBUG_BASKET'] = 'Y';

//\Jamilco\Main\Utils::deleteGifts([]);

/*
$arData = [];
$el = CIblockElement::GetList(
    [],
    [
        'IBLOCK_ID' => 15,
    ],
    false,
    false,
    ['ID', 'NAME', 'PROPERTY_MAIN_PRODUCT', 'PROPERTY_PRODUCTS']
);
while ($arItem = $el->Fetch()) {
    $arOne = [
        [
            'VALUE'       => $arItem['PROPERTY_MAIN_PRODUCT_VALUE'],
            'DESCRIPTION' => 'Y',
        ]
    ];
    foreach ($arItem['PROPERTY_PRODUCTS_VALUE'] as $val) {
        $arOne[] = [
            'VALUE'       => $val,
            'DESCRIPTION' => '',
        ];
    }
    $arData[$arItem['NAME']] = $arOne;
}

$element = new \CIBlockElement();
foreach ($arData as $name => $arOne) {
    $res = $element->Add(
        [
            'IBLOCK_ID'       => 24,
            'ACTIVE'          => 'N',
            'NAME'            => $name,
            'PROPERTY_VALUES' => [
                'ITEMS' => $arOne
            ]
        ]
    );
}
*/


//$orderId = 610000335;
//Manzana::getInstance()->sendOrder($orderId, 'Fiscal', 'Sale', 'Create');
//\Jamilco\Main\Handlers::reCreateManzanaOrder($orderId, '', true);

// 031035969544, 031015457416
//$coupon = Manzana::getInstance()->generateCoupon('', 'coupon500');
//pr($coupon);

//Jamilco\Main\Utils::addToBasket(14296);

//\Jamilco\Merch\Agents::updateSortIndex();

//$arStores = $stores = \Jamilco\Main\Retail::getCityStores(true);
//pr($arStores, 1, 1);

//$arLog = \Jamilco\Main\Utils::checkItemType(); // обновляет свойство "Категория" в товарах (для фильтра)
//$arLog = \Jamilco\Main\Utils::checkItemPrices(); // обновляет свойство "Минимальная цена" в товарах (для сортировки)
//pr($arLog, 1, 1);

//CEvent::ExecuteEvents();

//Common::resortItemsByAvailability();
//Common::checkAllNewTimeInItems();
//Common::reCheckAllSeasonInItems();
//\Jamilco\Merch\Agents::checkNewTimes();

/*
$ca = CCatalogProduct::GetList();
while ($arCatalog = $ca->Fetch()) {
    if ($arCatalog['CAN_BUY_ZERO_ORIG'] != 'D') {
        $n++;
        CCatalogProduct::Update($arCatalog['ID'], ['CAN_BUY_ZERO' => 'D']);
    }
}
echo $n;
*/

/*
if ($_REQUEST['code']) {
    header("Content-type: text/xml");
    \Jamilco\OCS\Orders::detail();
}
*/

$_SESSION['LOYALTY_CLIENT_DATA']['7060000603338']['CONFIRM'] = 'Y';

if ($_GET['card']) {
    $number = $_GET['card'];
    if (!$_SESSION['LOYALTY_CLIENT_DATA'][$number]) Card::getClientData($number);

    $_SESSION['LOYALTY_CLIENT_DATA'][$number]['CONFIRM'] = 'N';
    $_SESSION['LOYALTY_CLIENT_DATA'][$number]['PHONE'] = $_GET['phone'];
    $_SESSION['LOYALTY_CLIENT_DATA'][$number]['EMAIL'] = $_GET['email'];

    $_SESSION['LOYALTY_CLIENT_DATA'][$number]['PHONE'] = Card::MakePhoneNumber($_SESSION['LOYALTY_CLIENT_DATA'][$number]['PHONE']);
    $_SESSION['LOYALTY_CLIENT_DATA'][$number]['MASK'] = Card::getMasked(
        $_SESSION['LOYALTY_CLIENT_DATA'][$number]['PHONE'],
        $_SESSION['LOYALTY_CLIENT_DATA'][$number]['EMAIL']
    );

    pr($_SESSION['LOYALTY_CLIENT_DATA']);
}

/*
// удалить все скидочные цены, которые равны обычной цене
$of = CIblockElement::GetList(
    [],
    [
        'IBLOCK_ID'        => 2,
        '>CATALOG_PRICE_2' => 0,
    ],
    false,
    false,
    ['ID', 'CATALOG_GROUP_1', 'CATALOG_GROUP_2']
);
$n = 0;
while ($arOffer = $of->Fetch()) {
    if ($arOffer['CATALOG_PRICE_2'] == $arOffer['CATALOG_PRICE_1']) {
        CPrice::Delete($arOffer['CATALOG_PRICE_ID_2']);
        $n++;
    }
}
pr($n, 1);
*/

/*
$arr = [];
$discountsId = [];

$r = \CCatalogDiscount::GetList(
    array("SORT" => "ASC"),
    array(
        "NAME%"  => "Автоматическая скидка",
        "ACTIVE"    => "Y"
    ),
    false,
    false,
    array(
        "ID", "NAME",
        "VALUE", "PRODUCT_ID"
    )
);
while ($arProductDiscounts = $r->Fetch()) {
    $arr[$arProductDiscounts['PRODUCT_ID']] = $arProductDiscounts["VALUE"];
    $discountsId[] = $arProductDiscounts['ID'];
}

//\Bitrix\Main\Diag\Debug::Dump($arr);

$price = new \CPrice();

$updated = 0;
$added = 0;
$disabled = 0;

foreach ($arr as $id => $discount){

    $obPrice = $price->GetList(
        array(),
        array(
            "CATALOG_GROUP_ID" => 2,
            "PRODUCT_ID" => $id
        )
    );

    if($arPriceRes = $obPrice->Fetch()) {
        $price->Update($arPriceRes["ID"],
                       Array(
                           "CATALOG_GROUP_ID" => 2,
                           "PRICE"            => $discount
                       ));
        $updated++;
    } else {
        if($discount > 0) {
            $price->Add(Array("CATALOG_GROUP_ID" => 2, "PRODUCT_ID" => $id, "PRICE" => $discount, "CURRENCY" => "RUB"));
            $added++;
        }
    }
}

foreach ($discountsId as $item) {
    $res = \CCatalogDiscount::Update($item, ['ACTIVE' => 'N']);
    if (!$res) {
        $ex = $APPLICATION->GetException();
        \Bitrix\Main\Diag\Debug::Dump($ex->GetString());
    } else {
        $disabled++;
    }
}


\Bitrix\Main\Diag\Debug::Dump($updated);
\Bitrix\Main\Diag\Debug::Dump($added);
\Bitrix\Main\Diag\Debug::Dump($disabled);
*/