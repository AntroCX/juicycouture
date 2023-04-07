<?
use \Bitrix\Main\Loader;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Sale\Internals;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;

Loader::includeModule('iblock');
Loader::includeModule('sale');

define('DISCOUNT_ID_500', 12); // скидка 500руб за подписку
define('DISCOUNT_ID_10', 0); // скидка 10% за подписку


$arDiscountTypes = [
    DISCOUNT_ID_500 => 'coupon500',
    DISCOUNT_ID_10  => 'coupon10',
];

$arOut = [];

$date = new DateTime();
$date->add("-1 month");

$disc = Internals\DiscountTable::getList(
    [
        'filter' => [
            'ACTIVE' => 'Y',
            'ID'     => array_keys($arDiscountTypes),
        ],
        'select' => ['ID'],
    ]
);
while ($arDiscount = $disc->Fetch()) {
    $type = $arDiscountTypes[$arDiscount['ID']];

    $co = Internals\DiscountCouponTable::getList(
        [
            'filter' => [
                'ACTIVE'        => 'Y',
                'DATE_APPLY'    => false,
                'DISCOUNT_ID'   => $arDiscount['ID'],
                '>=DATE_CREATE' => $date,
            ],
            'select' => ['COUPON'],
        ]
    );
    while ($arCoupon = $co->Fetch()) {
        $arOut[$type][] = $arCoupon['COUPON'];
    }
}

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename={$_SERVER['HTTP_HOST']}_coupons_".date('Y_m_d_H_i_s').".csv");
foreach ($arOut as $type => $arCoupons) {
    foreach ($arCoupons as $coupon) {
        echo $type.';'.$coupon."\r\n";
    }
}