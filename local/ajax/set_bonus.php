<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(check_bitrix_sessid()&&$_REQUEST['number']) {
    \CModule::IncludeModule('sale');
    $sumBasket = 0;
    $balance = Jamilco\Main\Oracle::getInstance()->getBonus($_REQUEST['number']);
    $rsBasket = \CSaleBasket::GetList(
        array(),
        array(
            'FUSER_ID' => \CSaleBasket::GetBasketUserID()
        )
    );
    while ($arrBasket = $rsBasket->Fetch()) {
        $sumBasket += $arrBasket['PRICE'];
    }
    $writeOff = $sumBasket*0.2;
    if($writeOff > $balance) {
        $writeOff = -1;
    }
    echo json_encode(array(
        'BALANCE' => $balance,
        'BALANCE_FORMATTED' => CurrencyFormat($balance, 'RUB'),
        'WRITE_OFF' => $writeOff,
        'WRITE_OFF_FORMATTED' => ($writeOff != -1) ? CurrencyFormat($writeOff, 'RUB'): -1,
        'ADD_TO_CARD' => $sumBasket*0.05,
        'ADD_TO_CARD_FORMATTED' => CurrencyFormat($sumBasket*0.05, 'RUB'),
    ));
} else {
    echo '-1';
}