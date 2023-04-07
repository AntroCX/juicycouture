<?
use Bitrix\Main\Loader;
use Jamilco\Main\Update;

if (!$_SERVER['DOCUMENT_ROOT']) {
    $_SERVER['DOCUMENT_ROOT'] = str_replace('/local/api', '', __DIR__);
} else {
    ini_set("max_execution_time", "600");
}
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loader::includeModule('iblock');
Loader::includeModule('sale');
Loader::includeModule('catalog');

define('LOG_PATH_DELAY', '/local/api/log/delay/');
define('BLOCK_FILE', 'ocs_cron_block.log');

$arLogDirs = [
    'CHANGE_SKU_QUANTITIES'        => 'sku_quantities',
    'CHANGE_RETAIL_SKU_QTY_SHOP'   => 'retail_sku_quantities_full',
    'CHANGE_RETAIL_SKU_QUANTITIES' => 'retail_sku_quantities',
    'CHANGE_SKU_PRICES'            => 'sku_prices',
];

$arFilesGet = [];
foreach ($arLogDirs as $command => $path) {
    $dir = $_SERVER['DOCUMENT_ROOT'].LOG_PATH_DELAY.$path.'/';
    CheckDirPath($dir);

    $arFiles = scandir($dir);
    foreach ($arFiles as $file) {
        if ($file == '.' || $file == '..') continue;
        $arFilesGet[$command][] = $dir.$file;
    }
}

$fileBlock = __DIR__.'/'.BLOCK_FILE;
if (count($arFilesGet) && !file_exists($fileBlock)) {

    file_put_contents($fileBlock, print_r($arFilesGet, 1));

    foreach ($arFilesGet as $command => $arFiles) {
        foreach ($arFiles as $file) {
            $data = file_get_contents($file);

            $arLog = [];

            if ($command == 'CHANGE_SKU_QUANTITIES') {

                $res = Update::changeQuantity($data, $arLog);

            } elseif ($command == 'CHANGE_RETAIL_SKU_QTY_SHOP') {

                $res = Update::changeRetailSku($data);

            } elseif ($command == 'CHANGE_RETAIL_SKU_QUANTITIES') {

                // устаревший метод

            } elseif ($command == 'CHANGE_SKU_PRICES') {

                $res = Update::changePrices($data);

            }

            $logFile = str_replace('/delay/', '/', $file);
            if (copy($file, $logFile)) {
                unlink($file);
            }

            //pr($arLog, 1);
        }
    }

    unlink($fileBlock);
}