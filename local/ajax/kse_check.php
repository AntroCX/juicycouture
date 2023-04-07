<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("CHK_EVENT", false);
define("BX_CRONTAB", true);

ini_set("max_execution_time", 0);

use \Bitrix\Main\Loader;
use \Jamilco\Main\Progress;
use \Jamilco\TaggedPages\PagesTable;
use \Jamilco\TaggedPages\SectionFilter;
use \Sale\Handlers\Delivery\KceHandler;

$console = false;
$limit = 0;
if (!$_SERVER['DOCUMENT_ROOT']) {
    $_SERVER['DOCUMENT_ROOT'] = str_replace('/local/ajax', '', __DIR__);
    $console = true;
    if ($argv[1]) $limit = (int)$argv[1];
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

while (@ob_end_flush()) ;

Loader::includeModule('iblock');
Loader::includeModule('sale');
Loader::includeModule('catalog');

$start = microtime(true);

require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/sale_delivery/kce/handler.php';

/*
// удалим привязку к местоположениям
$el = \CIblockElement::GetList(
    [],
    [
        'IBLOCK_ID' => IBLOCK_TARIFS_KCE,
        [
            'LOGIC'                 => 'OR',
            '!PROPERTY_LOCATION_ID' => false,
            '!PROPERTY_NOT_FOUND'   => false,
        ]
    ],
    false,
    false,
    ['ID']
);
while ($arItem = $el->Fetch()) {
    \CIBlockElement::SetPropertyValuesEx($arItem['ID'], IBLOCK_TARIFS_KCE, ['LOCATION_ID' => false, 'NOT_FOUND' => false]);
    $arLog['CLEAR']++;
}
*/

// обновление LOCATION_ID
//$arLog = KceHandler::checkLocationID($console);


/*
// устанавливаем тарифы из файла
$file = __DIR__.'/kse_tariff.csv';

if (($handle = fopen($file, "r")) !== false) {
    while (($data = fgetcsv($handle, 1000, ";")) !== false) {
        $data[0] = KceHandler::checkRegionName($data[0]);
        $data[1] = ($data[1] == 'областной центр') ? 'center' : 'others';
        $data[2] = str_replace(',', '.', $data[2]);
        $arData[$data[0]][$data[1]] = ceil($data[2]);
    }
    fclose($handle);
}

//pr($arData, 1);

foreach ($arData as $region => $arOne) {
    $el = \CIblockElement::GetList(
        [],
        [
            'IBLOCK_ID'               => IBLOCK_TARIFS_KCE,
            'PROPERTY_address_region' => [
                $region,
                '%'.$region,
                $region.'%',
            ],
        ],
        false,
        false,
        [
            'ID',
            'NAME',
            'PROPERTY_address_region',
            'PROPERTY_value',
            'PROPERTY_CENTER',
        ]
    );
    while ($arLoc = $el->Fetch()) {
        $tariff = ($arLoc['PROPERTY_CENTER_VALUE'] == 'Y') ? $arOne['center'] : $arOne['others'];
        if ($tariff > 0) {
            if ($tariff != $arLoc['PROPERTY_VALUE_VALUE']) {
                \CIBlockElement::SetPropertyValuesEx($arLoc['ID'], IBLOCK_TARIFS_KCE, ['value' => $tariff]);
                $arLog['SET']++;
            } else {
                $arLog['EXIST']++;
            }
        } else {
            $arLoc['NULL']++;
        }
    }
}
*/

/*
// установить центры регионов
$arCitiesCenter = [
    'Майкоп',
    'Горно-Алтайск',
    'Уфа',
    'Улан-Удэ',
    'Махачкала',
    'Магас',
    'Нальчик',
    'Элиста',
    'Черкесск',
    'Петрозаводск',
    'Сыктывкар',
    'Симферополь',
    'Йошкар-Ола',
    'Саранск',
    'Якутск',
    'Владикавказ',
    'Казань',
    'Кызыл',
    'Ижевск',
    'Абакан',
    'Грозный',
    'Чебоксары',
    'Барнаул',
    'Чита',
    'Петропавловск-Камчатский',
    'Краснодар',
    'Красноярск',
    'Пермь',
    'Владивосток',
    'Ставрополь',
    'Хабаровск',
    'Благовещенск',
    'Архангельск',
    'Астрахань',
    'Белгород',
    'Брянск',
    'Владимир',
    'Волгоград',
    'Вологда',
    'Воронеж',
    'Иваново',
    'Иркутск',
    'Калининград',
    'Калуга',
    'Кемерово',
    'Киров',
    'Кострома',
    'Курган',
    'Курск',
    'Санкт-Петербург',
    'Липецк',
    'Магадан',
    'Мурманск',
    'Нижний Новгород',
    'Великий Новгород',
    'Новосибирск',
    'Омск',
    'Оренбург',
    'Орёл',
    'Пенза',
    'Псков',
    'Ростов-на-Дону',
    'Рязань',
    'Самара',
    'Саратов',
    'Южно-Сахалинск',
    'Екатеринбург',
    'Смоленск',
    'Тамбов',
    'Тверь',
    'Томск',
    'Тула',
    'Тюмень',
    'Ульяновск',
    'Челябинск',
    'Ярославль',
    'Нарьян-Мар',
    'Ханты-Мансийск',
    'Анадырь',
    'Салехард'
];

$el = \CIblockElement::GetList(
    [],
    [
        'IBLOCK_ID'       => IBLOCK_TARIFS_KCE,
        '=NAME'           => $arCitiesCenter,
        'PROPERTY_CENTER' => false,
    ],
    false,
    false,
    ['ID', 'NAME']
);
while ($arItem = $el->Fetch()) {
    \CIBlockElement::SetPropertyValuesEx($arItem['ID'], IBLOCK_TARIFS_KCE, ['CENTER' => 'Y']);
    $arLog['SET']++;
}
$arLog['ALL'] = count($arCitiesCenter);
*/

$file = __DIR__.'/kse_tariff.csv';
$arData = [];
if (($handle = fopen($file, "r")) !== false) {
    while (($data = fgetcsv($handle, 1000, ";")) !== false) {
        $name = $data[1];
        $type = ' '.$data[2];
        if (substr_count($name, $type)) {
            $name = explode($type, $name);
            array_pop($name);
            $name = implode($type, $name);
        }
        $arData[] = [
            'REGION'    => $data[0],
            'NAME'      => $name,
            'NAME_FULL' => $data[1],
            'TYPE'      => $data[2],
            'TIME'      => [
                'MIN' => intval($data[3]) + 2,
                'MAX' => intval($data[4]) + 2
            ],
            'DAYS'      => [
                'MON' => sheckBool($data[5]),
                'TUE' => sheckBool($data[6]),
                'WED' => sheckBool($data[7]),
                'THU' => sheckBool($data[8]),
                'FRI' => sheckBool($data[9]),
                'SAT' => sheckBool($data[10]),
                'SUN' => sheckBool($data[11]),
            ],
            //'CARD'      => sheckBool($data[12]),
            //'CASSA'     => sheckBool($data[13]),
            'CASH'      => sheckBool($data[12]),
            'PART'      => sheckBool($data[12]),
            'WEAR'      => sheckBool($data[13]),
            //'PERSON'    => sheckBool($data[17]),
        ];
    }
    fclose($handle);
}
$arLog['IN_FILE'] = count($arData);

$arItems = [];
$el = \CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => IBLOCK_TARIFS_KCE,
    ],
    false,
    false,
    [
        'ID',
        'NAME',
        'PROPERTY_address_region',
        'PROPERTY_VR_GOR',
    ]
);
while ($arItem = $el->Fetch()) {
    $arItems[ToUpper($arItem['PROPERTY_ADDRESS_REGION_VALUE'])][ToUpper($arItem['NAME'])] = $arItem['ID'];
    $arItems[ToUpper($arItem['PROPERTY_ADDRESS_REGION_VALUE'])][ToUpper($arItem['PROPERTY_VR_GOR_VALUE'])] = $arItem['ID'];
    $arLog['IN_SITE']++;
}

$arEnums = [
    'fitting'      => '',
    'half_take'    => '',
    'card_payment' => '',
    'prepayment'   => '',
    'cash_payment' => '',
    'weekend'      => '',
    'evening'      => '',
];
foreach ($arEnums as $code => $val) {
    $en = \CIBlockPropertyEnum::GetList(
        [],
        [
            'IBLOCK_ID' => IBLOCK_TARIFS_KCE,
            'CODE'      => $code,
            'VALUE'     => ['Да', 'да'],
        ]
    );
    if ($arEnum = $en->Fetch()) {
        $arEnums[$code] = $arEnum['ID'];
    }
}

$progress = new Progress(count($arData));
foreach ($arData as $arOne) {
    $progress->step();

    $itemId = $arItems[ToUpper($arOne['REGION'])][ToUpper($arOne['NAME'])];
    if (!$itemId) $itemId = $arItems[ToUpper($arOne['REGION'])][ToUpper($arOne['NAME_FULL'])];
    if ($itemId) {

        $arProps = [
            'terms'        => implode('-', $arOne['TIME']),
            'fitting'      => ($arOne['WEAR']) ? $arEnums['fitting'] : false,
            'half_take'    => ($arOne['PART']) ? $arEnums['half_take'] : false,
            //'card_payment' => ($arOne['CARD']) ? $arEnums['card_payment'] : false,
            'cash_payment' => ($arOne['CASH']) ? $arEnums['cash_payment'] : false,
            'weekend'      => ($arOne['DAYS']['SAT'] || $arOne['DAYS']['SUN']) ? $arEnums['weekend'] : false,
            'work_days'    => getDayNames($arOne['DAYS']),
            'VR_TYPE'      => $arOne['TYPE'],
            'VR_GOR'       => $arOne['NAME_FULL'],
            'VR_REG'       => $arOne['REGION'],
        ];

        \CIBlockElement::SetPropertyValuesEx($itemId, IBLOCK_TARIFS_KCE, $arProps);

        $arLog['UPDATE']++;
    } else {
        $arLog['NOT_FOUND']++;
    }

}

pr($arLog, 1, 1);

function sheckBool($val = '')
{
    $val = ToLower($val);
    $val = trim($val);

    return ($val == 'да') ? true : false;
}

function getDayNames($arDays = [])
{
    $days = [];
    if ($arDays['MON']) $days[] = 'Понедельник';
    if ($arDays['TUE']) $days[] = 'Вторник';
    if ($arDays['WED']) $days[] = 'Среда';
    if ($arDays['THU']) $days[] = 'Четверг';
    if ($arDays['FRI']) $days[] = 'Пятница';
    if ($arDays['SAT']) $days[] = 'Суббота';
    if ($arDays['SUN']) $days[] = 'Воскресенье';

    return implode(',', $days);
}