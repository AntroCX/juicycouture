<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.adminorderform/admin/jamilco_admin_order_form_ajax.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.adminorderform/admin/jamilco_admin_order_form_ajax.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.adminorderform/admin/jamilco_admin_order_form_ajax.php");
}

