<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("CHK_EVENT", false);
define("BX_CRONTAB", true);

use \Bitrix\Main\Loader;
use \Jamilco\Delivery\Ozon;

$_SERVER['DOCUMENT_ROOT'] = str_replace('/local/ajax', '', __DIR__);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

Loader::includeModule('jamilco.delivery');

/**
 * сохраняет список вариантов доставки:
 *  - запрашивает данные в Озоне
 *  - выбирает "Самовывоз" и "Постомат"
 *  - сохраняет в инфоблок "Ozon Delivery. Пункты выдачи" в виде:
 *  - - раздел = Регион
 *  - - подраздел = Город
 *  - - элемент = конкретный ПВЗ
 *
 *  - все разделы и подразделы получают привязку к одному из Местоположений (основываясь на названиях и иерархии)
 */

$ozon = new Ozon();
$ozon->saveDeliveryVariants();
pr($ozon->arLog, 1, 1);