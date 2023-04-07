<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("CHK_EVENT", false);
define("BX_CRONTAB", true);

ini_set("max_execution_time", 0);

$console = false;
$limit = 0;
if (!$_SERVER['DOCUMENT_ROOT']) {
    $_SERVER['DOCUMENT_ROOT'] = str_replace('/local/ajax', '', __DIR__);
    $console = true;
    if ($argv[1]) $limit = (int)$argv[1];
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\IO,
    Bitrix\Main\Application;

global $arLog;

$start = microtime(true);

// массив директорий для очистки
$arLogDirs = [
    '/local/api/log/',
    '/local/log/',
    '/upload/log/',
];

foreach ($arLogDirs as $checkDir) {
    checkDir(Application::getDocumentRoot().$checkDir);
}

$arLog['TIME'] = microtime(true) - $start;
pr($arLog, 1, 1);

function checkDir($checkDir = '')
{
    global $arLog;
    $checkDir = trim($checkDir);
    if (!$checkDir) return false;
    $checkDir .= '/';
    $checkDir = str_replace('//', '/', $checkDir);

    // массив пропускаемых директорий
    $arSkipDirs = [
        '/api/log/product',
        '/api/log/sku_prices',
    ];

    // разделы, которые можно удалять (если в них не осталось файлов)
    $deleteDirs = '/local/log/manzana/order/';

    foreach ($arSkipDirs as $skipDir) {
        if (substr_count($checkDir, $skipDir)) {

            $arLog['SKIP'][] = $checkDir;

            return false;
        }
    }

    $checkTime = time() - 30 * 86400;

    $dir = new IO\Directory($checkDir);
    if(!$dir->isExists()) return;

    $files = $dir->getChildren();

    foreach ($files as $file) {
        $class = get_class($file);

        // Bitrix\Main\IO\Directory
        if (substr_count($class, 'IO\Directory')) {
            checkDir($file->getPath());
        }
        if (substr_count($class, 'IO\File')) {
            if ($checkTime > $file->getModificationTime()) {
                $file->delete();
                $arLog['DELETE']++;
            }
        }
    }

    if (!count($files) && substr_count($checkDir, $deleteDirs)) {
        $dir->delete();
        $arLog['DELETE_DIR']++;
    }

    return true;
}