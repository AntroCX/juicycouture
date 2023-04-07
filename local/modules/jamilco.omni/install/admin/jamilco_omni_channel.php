<?php

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.omni/admin/channel.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.omni/admin/channel.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.omni/admin/channel.php");
}