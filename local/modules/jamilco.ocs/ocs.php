<?php
use Bitrix\Main\Loader;

date_default_timezone_set('Europe/Moscow');
define('BX_TIME_ZONE', '+1 hour');

global $isConsole;
$isConsole = false;
if (!$_SERVER['DOCUMENT_ROOT']) {
    $rootDir = str_replace('/local/modules/jamilco.ocs', '', __DIR__);
    $_SERVER['DOCUMENT_ROOT'] = $rootDir;

    $isConsole = true;

    $_REQUEST['command'] = $argv[1];
    $_REQUEST['file'] = $argv[2];
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
/* Подключение класса RBS */
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/rbs.payment/config.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/rbs.payment/payment/rbs.php");

Loader::includeModule('iblock');
Loader::includeModule('sale');
Loader::includeModule('catalog');
Loader::includeModule('jamilco.ocs');

define('LOG_PATH', '/local/api/log/');
define('LOG_PATH_DELAY', '/local/api/log/delay/');

$obApi = new \Jamilco\OCS\Api();
$obApi->init();