<?php
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";

use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Main\Web\Json;
use \Jamilco\Main\Utils;

define('COURIER_DELIVERY_ID', 18);

$request = Context::getCurrent()->getRequest();

$skuId = (int)$request->get('skuId');
$article = (string)$request->get('artnumber');
$selectTemplate = (string)$request->get('template') === 'select';
$deniedReservation = $request->get('denied');
$cityName = (string)$_COOKIE['city_name'] ?: 'Москва';
$arShops = [];

$deliveryFromRM = 'N';
if ($skuId) {
    $deliveryFromRM = 'N';
    $arOffer = [
        'ID' => $skuId,
    ];

    if (!empty($article)) {
        $arOffer['ARTICLE'] = $article;
    }

    $arFlags = \Jamilco\Omni\Channel::getDeliveryData($arOffer, [], true, true, $cityName);
    $deliveryFromRM = (count($arFlags['SHOP']['DELIVERY']) > 0 || count($arFlags['SHOP']['DAY_DELIVERY']) > 0) ? 'Y' : 'N';

    if ($deniedReservation != 'Y') {
        // результирующий массив $arShops будет состоять из таких частей
        $arParts = [
            'RETAIL', // самовывоз из РМ
            'PICKUP', // доставка в РМ
            '2SHOP',  // доставка из РМ в другой РМ
        ];

        $arShops = [];
        foreach ($arParts as $type) {
            foreach ($arFlags['SHOP'][$type] as $flag) {
                $flag['TYPE'] = $type;
                $arShops[] = [
                    'INFO' => $flag
                ];
            }
        }
    }
}

$html = '';
if (count($arShops) > 0) {
    foreach ($arShops as $arStore) {
        $quantityText = '';
        /*if($arStore['QUANTITY'] > 5) {
            $quantityText = 'В наличии > 5 шт.';
        } else {
            $quantityText = 'В наличии < 5 шт.';
        }*/

        if ($selectTemplate) {
            $html .= "
                <option data-id='{$arStore['INFO']['XML_ID']}' data-coords='{$arStore['INFO']['GPS_N']},{$arStore['INFO']['GPS_S']}' data-type='{$arStore['INFO']['TYPE']}'>
                    {$arStore['INFO']['ADDRESS']}
                </option>
            ";
        } else {
            $html .= "
                <div class=\"b-shops__list-item\">
                    <div class=\"row\">
                        <div class=\"col-sm-9 col-md-6\">
                            <div class=\"b-shops__list-item-address\">{$arStore['INFO']['ADDRESS']}</div>
                            <div class=\"b-shops__list-item-prop\">{$arStore['INFO']['PHONE']}</div>
                            <div class=\"b-shops__list-item-prop\">Время работы: {$arStore['INFO']['SCHEDULE']}</div>
                        </div>
                        <div class=\"col-sm-3 col-md-3\">
                            <div class=\"b-shops__list-item-count\">{$quantityText}</div>
                        </div>
                        <div class=\"col-sm-12 col-md-3  text-right m-center\">
                            <a data-coords=\"{$arStore['INFO']['GPS_N']},{$arStore['INFO']['GPS_S']}\" data-id=\"{$arStore['INFO']['XML_ID']}\"
                               data-type=\"{$arStore['INFO']['TYPE']}\" class=\"btn btn-primary btn-reserved\">Забронировать</a>
                        </div>
                    </div>
                </div>
            ";
        }
    }
} else {
    $html = $selectTemplate ? '<option>Нет магазинов</option>' : '<div class="text-center">Доступен только для доставки</div>';
}

if ($skuId) {
    // Информация о стоимости и сроках доставки для данного товара
    $orderInfo = Utils::getPreOrderData(COURIER_DELIVERY_ID, '', $skuId);
    $deliveryInfo['deliveryPrice'] = $orderInfo['data']['deliveryPrice'];

    $dayCount = (int)$orderInfo['data']['deliveryPeriod'];
    if (substr_count($orderInfo['data']['deliveryPeriod'], '-')) {
        $dayCount = explode('-', $orderInfo['data']['deliveryPeriod']);
        if ((int)$dayCount[1] > 0) {
            $dayCount = (int)$dayCount[1];
        }
    }

    // Если время на сервере больше чем 16.00, то нужно прибавить 1 день к сроку доставки
    $serverDateTime = getdate();
    if ($serverDateTime['hours'] >= 16) {
        $dayCount++;
    }
    if ($dayCount == 0) {
        $dayCountFormat = 'сегодня';
    } elseif ($dayCount == 1) {
        $dayCountFormat = 'завтра';
    } else {
        $dayCountFormat = date('d.m.y', time() + 86400 * $dayCount);;
    }
    $deliveryInfo['deliveryPeriod'] = $dayCountFormat;
    $deliveryInfo['pickupPeriod'] = ($serverDateTime['hours'] >= 20) ? 'завтра' : 'сегодня';
}

header('Content-Type: application/json');
die(Json::encode(
    [
        'html'     => $html,
        'count'    => count($arShops),
        'delivery' => $deliveryInfo
    ],
    JSON_UNESCAPED_UNICODE
));
