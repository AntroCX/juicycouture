<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.googlecatalog/admin/jamilco_googlecatalog.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.googlecatalog/admin/jamilco_googlecatalog.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.googlecatalog/admin/jamilco_googlecatalog.php");
}