<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

define('ADMIN_SECTION', true); // пропуск проверок безопасности

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Grid\Declension;
use \Bitrix\Sale;
use \Jamilco\Main\Manzana;
use \Jamilco\Loyalty\Card;
use \Jamilco\Loyalty\Bonus;
use \Jamilco\Loyalty\Events;

Loader::IncludeModule('iblock');
Loader::IncludeModule('catalog');
Loader::IncludeModule('sale');

global $skipCheckCardType, $acceptOrderPriceChanging;
$skipCheckCardType = true; // пропустить проверку на бренд карты
$acceptOrderPriceChanging = true;

$request = Application::getInstance()->getContext()->getRequest();

$arResult = [
    'RESULT'  => 'ERROR',
    'MESSAGE' => '',
];

$bonusDeclension = new Declension('бонус', 'бонуса', 'бонусов');

if ($request->isPost() || 1) {
    $orderId = $request->get('order');
    $action = $request->get('action');
    $card = $request->get('card');
    $coupon = $request->get('coupon');

    if ($orderId > '' && $action > '') {

        $order = Sale\Order::load($orderId);

        // получим свойства заказа
        $arOrderProps = [];
        $pr = \CSaleOrderPropsValue::GetOrderProps($orderId);
        while ($arProp = $pr->Fetch()) {
            $arOrderProps[$arProp['CODE']] = $arProp;
        }

        if ($action == 'reCreateInManzana') {
            $step = $request->get('step');
            \Jamilco\Main\Handlers::reCreateManzanaOrder($orderId, '', true, $step);
        }

        if ($coupon) {
            if ($action == 'addCoupon') {

                $propCode = 'COUPONS';
                if ($arOrderProps[$propCode]['VALUE'] != $coupon) {
                    if ($arOrderProps[$propCode]) {
                        \CSaleOrderPropsValue::Update($arOrderProps[$propCode]['ID'], ['VALUE' => $coupon]);
                    } else {
                        $rsProps = \CSaleOrderProps::GetList([], ['CODE' => $propCode]);
                        if ($arrProp = $rsProps->Fetch()) {
                            \CSaleOrderPropsValue::Add(
                                [
                                    "ORDER_ID"       => $orderId,
                                    "ORDER_PROPS_ID" => $arrProp['ID'],
                                    "NAME"           => $arrProp['NAME'],
                                    "CODE"           => $arrProp['CODE'],
                                    "VALUE"          => $coupon
                                ]
                            );
                        }
                    }

                    \Jamilco\Main\Handlers::reCreateManzanaOrder($orderId, '', true);
                }

            }
        }

        if ($card) {
            if ($action == 'checkCard') {
                $arCard = Card::getClientData($card);
                if ($arCard['CARD']) {
                    $arCard['BALANCE'] = Card::getBalance($card, true);
                    $arResult['RESULT'] = 'OK';

                    $arCard['BALANCE']['WRITEOFF_BONUS'] = 0;
                    if ($arCard['BALANCE']['AVAILABLE'] > 0) {
                        $arRes = Manzana::getInstance()->sendOrder($orderId, 'Soft', 'Sale', 'Calc', $card);
                        $arCard['BALANCE']['WRITEOFF_BONUS'] = $arRes['WriteoffBonus'];
                    }

                    $arResult['CARD'] = $arCard;
                } else {
                    $arResult['MESSAGE'] = 'Карта не найдена';
                }
            } elseif ($action == 'sendCode') {
                $type = $request->get('type');
                Card::checkClientSend($card, $type);

                $action = 'addCard'; // добавим карту в заказ
            } elseif ($action == 'checkCode') {
                $check = $request->get('check');
                if (Card::confirmCode($card, $check, false)) {
                    if ($order->isPaid()) {
                        // для оплаченных заказов просто добавляем карту в заказ
                        // карта уже добавлена в ходе запроса sendCode
                        $arResult['RESULT'] = 'OK';
                    } else {
                        // списываем бонусы только для не-оплаченных заказов
                        $arResult['RESULT'] = 'OK';
                        Bonus::getData($card, 'Y', $orderId); // добавим данные по бонусной карты в элементы корзины
                        Events::OnOrderSaveHandler($orderId); // спишем бонусы из цен

                        \Jamilco\Main\Handlers::reCreateManzanaOrder($orderId);
                    }
                } else {
                    $arResult['MESSAGE'] = 'Код неверен';
                }
            }

            if ($action == 'addCard') {
                // добавляем карту в заказ (бонусы не списываем)
                $propCode = 'PROGRAMM_LOYALTY_CARD';
                if ($arOrderProps[$propCode]['VALUE'] != $card) {
                    if ($arOrderProps[$propCode]) {
                        \CSaleOrderPropsValue::Update($arOrderProps[$propCode]['ID'], ['VALUE' => $card]);
                    } else {
                        $rsProps = \CSaleOrderProps::GetList([], ['CODE' => $propCode]);
                        if ($arrProp = $rsProps->Fetch()) {
                            \CSaleOrderPropsValue::Add(
                                [
                                    "ORDER_ID"       => $orderId,
                                    "ORDER_PROPS_ID" => $arrProp['ID'],
                                    "NAME"           => $arrProp['NAME'],
                                    "CODE"           => $arrProp['CODE'],
                                    "VALUE"          => $card
                                ]
                            );
                        }
                    }

                    \Jamilco\Main\Handlers::reCreateManzanaOrder($orderId, $arOrderProps[$propCode]['VALUE'], true);
                }
            }
        }
    }
}

if ($arResult['RESULT'] == 'ERROR' && !$arResult['MESSAGE']) $arResult['MESSAGE'] = 'Произошла ошибка';
echo Json::encode($arResult);