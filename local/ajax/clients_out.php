<?
use \Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;

set_time_limit(0);

Loader::includeModule('iblock');
Loader::includeModule('catalog');
Loader::includeModule('sale');

if (!$USER->isAdmin()) die('access denied');


$arData = [];
$us = \Bitrix\Main\UserTable::getList(
    [
        //'limit'  => 100,
        'order'  => ['ID' => 'DESC'],
        'filter' => [
            'ACTIVE' => 'Y',
        ],
        'select' => [
            'ID',
            'DATE_REGISTER',
            'NAME',
            'LAST_NAME',
            'EMAIL',
            'PERSONAL_MOBILE',
            'PERSONAL_BIRTHDAY',
            'PERSONAL_GENDER',
        ]
    ]
);
while ($arUser = $us->Fetch()) {
    $arUser['DATE_REGISTER'] = $arUser['DATE_REGISTER']->format('d.m.Y');
    $arUser['PERSONAL_BIRTHDAY'] = ($arUser['PERSONAL_BIRTHDAY']) ? $arUser['PERSONAL_BIRTHDAY']->format('d.m.Y') : '';
    $arUser['LOCATION'] = '';
    $arUser['ORDERS'] = [
        'PAYED' => ['COUNT' => 0, 'PRICE' => 0],
        'ALL'   => ['COUNT' => 0, 'PRICE' => 0],
    ];
    $arData['USERS'][$arUser['ID']] = $arUser;
}

$pr = CSaleOrderUserProps::GetList(
    ["DATE_UPDATE" => "DESC"],
    [
        "USER_ID" => array_keys($arData['USERS']),
    ]
);
while ($arProp = $pr->Fetch()) {
    if (in_array($arProp['USER_ID'], $arData['PROFILES'])) continue;
    $arData['PROFILES'][$arProp['ID']] = $arProp['USER_ID'];
}

$pr = CSaleOrderUserPropsValue::GetList(
    [],
    [
        "USER_PROPS_ID"    => array_keys($arData['PROFILES']),
        'PROP_IS_LOCATION' => 'Y',
    ]
);
while ($arProp = $pr->Fetch()) {
    $userId = $arData['PROFILES'][$arProp['USER_PROPS_ID']];
    $arData['LOCATIONS'][$userId] = $arProp['VALUE'];
}

if ($arData['LOCATIONS']) {
    $arLocs = array_values($arData['LOCATIONS']);
    $arLocs = array_unique($arLocs);
    $loc = \Bitrix\Sale\Location\LocationTable::getList(
        [
            'filter' => [
                [
                    'LOGIC' => 'OR',
                    'ID'    => $arLocs,
                    'CODE'  => $arLocs,
                ]
            ],
            'select' => ['ID', 'CODE']
        ]
    );
    while ($arMainLoc = $loc->Fetch()) {
        $path = \Bitrix\Sale\Location\LocationTable::getPathToNodeByCode(
            $arMainLoc['CODE'],
            [
                'filter' => [
                    '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    '!TYPE.CODE'        => 'COUNTRY',
                ],
                'select' => [
                    '*',
                    'NAME_RU'   => 'NAME.NAME',
                    'TYPE_CODE' => 'TYPE.CODE',
                ],
            ]
        );
        while ($arLoc = $path->Fetch()) {
            $arMainLoc['PATH'][$arLoc['ID']] = $arLoc['NAME_RU'];
        }
        $arMainLoc['PATH'] = array_reverse($arMainLoc['PATH'], true);

        foreach ($arData['LOCATIONS'] as $userId => $locId) {
            if ($locId == $arMainLoc['ID']) $arData['USERS'][$userId]['LOCATION'] = implode(', ', $arMainLoc['PATH']);
        }
    }
}

$or = \Bitrix\Sale\Internals\OrderTable::getList(
    [
        'filter' => [
            'USER_ID'  => array_keys($arData['USERS']),
            'CANCELED' => 'N',
        ],
    ]
);
while ($arOrder = $or->Fetch()) {

/*
    // Оплаченные
    if ($arOrder['PAYED'] == 'Y') {
        $arData['USERS'][$arOrder['USER_ID']]['ORDERS']['PAYED']['COUNT']++;
        $arData['USERS'][$arOrder['USER_ID']]['ORDERS']['PAYED']['PRICE'] += $arOrder['PRICE'] + $arOrder['PRICE_DELIVERY'];
    }
*/
    // Выполненые
    if ($arOrder['STATUS_ID'] == 'F') {
        $arData['USERS'][$arOrder['USER_ID']]['ORDERS']['PAYED']['COUNT']++;
        $arData['USERS'][$arOrder['USER_ID']]['ORDERS']['PAYED']['PRICE'] += $arOrder['PRICE'] + $arOrder['PRICE_DELIVERY'];
    }

    $arData['USERS'][$arOrder['USER_ID']]['ORDERS']['ALL']['COUNT']++;
    $arData['USERS'][$arOrder['USER_ID']]['ORDERS']['ALL']['PRICE'] += $arOrder['PRICE'] + $arOrder['PRICE_DELIVERY'];

}

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename={$_SERVER['HTTP_HOST']}_clients_".date('Y_m_d_H_i_s').".csv");

/**
 * Id клиента
 * E-mail
 * № телефона
 * Пол
 * Дата рождения
 * Дата регистрации
 * Субъект РФ
 * Кол-во сделанных заказов
 * Кол-во выкупленных заказов
 * Сумма сделанных заказов руб.
 * Сумма выкупленных заказов руб.
 */

$arPrint = [
    implode(
        ';',
        [
            'Id клиента',
            'E-mail',
            '№ телефона',
            'Пол',
            'Дата рождения',
            'Дата регистрации',
            'Субъект РФ',
            'Кол-во сделанных заказов',
            'Кол-во выкупленных заказов',
            'Сумма сделанных заказов руб.',
            'Сумма выкупленных заказов руб.',
        ]
    ),
];
foreach ($arData['USERS'] as $arUser) {
    $arOne = [
        $arUser['ID'],
        $arUser['EMAIL'],
        $arUser['PERSONAL_MOBILE'],
        $arUser['PERSONAL_GENDER'],
        $arUser['PERSONAL_BIRTHDAY'],
        $arUser['DATE_REGISTER'],
        $arUser['LOCATION'],
        $arUser['ORDERS']['ALL']['COUNT'],
        $arUser['ORDERS']['PAYED']['COUNT'],
        $arUser['ORDERS']['ALL']['PRICE'],
        $arUser['ORDERS']['PAYED']['PRICE'],
    ];

    $arPrint[] = implode(';', $arOne);
}

echo implode("\r\n", $arPrint);