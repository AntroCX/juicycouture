<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 18.07.16
 * Time: 10:58
 */
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.reports/admin/goods.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.reports/admin/goods.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.reports/admin/goods.php");
}
