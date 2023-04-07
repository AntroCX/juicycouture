<?php
/**
 * Created by PhpStorm.
 * User: Eaa
 * Date: 06.10.20
 * Time: 10:58
 */
use \Bitrix\Main\Loader;
$_SERVER["DOCUMENT_ROOT"] = '/var/www/juicycouture/juicycouture.ru/www';

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('BX_NO_ACCELERATOR_RESET', true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

Loader::IncludeModule('iblock');
IncludeModuleLangFile(__FILE__);

$fileName = 'user_emails.csv';
$pathFile = '/local/cron/result/';
$real_file_path = $_SERVER['DOCUMENT_ROOT'].$pathFile;

    CheckDirPath($real_file_path);
    
    // удаляем старый файл
    unlink($_SERVER["DOCUMENT_ROOT"] . $pathFile);
    
$fp = fopen($real_file_path.$fileName, 'w');
fputcsv($fp, ['E_MAIL', 'CODE', 'ACTIVE', 'DATE_CREATE', 'gen by: '.date("d M Y H:i:s")], ';');
$db_res = \CIBlockElement::GetList(
    ['ID' => "ASC"],
    ['IBLOCK_ID' => SUBSCRIBE_IBLOCK],
    false,
    false,
    ['NAME', 'PROPERTY_UNIC_CODE', "ACTIVE", "DATE_CREATE"]
);
while ($arItem = $db_res->fetch()) {
    fputcsv($fp, [$arItem['NAME'], $arItem['PROPERTY_UNIC_CODE_VALUE'], $arItem['ACTIVE'], $arItem['DATE_CREATE']], ';');
}
