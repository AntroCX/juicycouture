<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("CHK_EVENT", false);
define("BX_CRONTAB", true);

use \Bitrix\Main\Loader;
use \Jamilco\Main\Oracle;

if (!$_SERVER['DOCUMENT_ROOT']) $_SERVER['DOCUMENT_ROOT'] = str_replace('/local/ajax', '', __DIR__);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

Loader::includeModule('jamilco.main');

$res = Oracle::getInstance()->checkServer();