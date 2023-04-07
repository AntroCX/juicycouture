<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 18.07.17
 * Time: 10:58
 */
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.taggedpages/admin/list.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.taggedpages/admin/list.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.taggedpages/admin/list.php");
}
