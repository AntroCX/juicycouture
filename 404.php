<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("404");
$APPLICATION->setPageProperty('ddlPageCategory', '404');
header("HTTP/1.0 404 Not Found");
?>

<div style="text-align: center; padding-top: 30px; padding-bottom: 30px; font-size: 150px">
    404
</div>

<div data-retailrocket-markup-block="5bd1bced97a52525d8ad0812" data-stock-id="<?= \Jamilco\Main\Retail::getStoreName(true) ?>"></div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>