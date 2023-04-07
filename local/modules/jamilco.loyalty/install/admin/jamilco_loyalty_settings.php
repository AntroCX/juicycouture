<?php

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.loyalty/admin/settings.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.loyalty/admin/settings.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.loyalty/admin/settings.php");
}
