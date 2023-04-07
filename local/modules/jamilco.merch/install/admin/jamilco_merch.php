<?php

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.merch/admin/merch.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.merch/admin/merch.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.merch/admin/merch.php");
}