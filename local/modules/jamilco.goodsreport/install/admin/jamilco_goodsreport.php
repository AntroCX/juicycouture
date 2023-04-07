<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.goodsreport/admin/jamilco_goodsreport.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.goodsreport/admin/jamilco_goodsreport.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.goodsreport/admin/jamilco_goodsreport.php");
}