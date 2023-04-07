<?php

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.omni/admin/sale_discount_coupons_load.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.omni/admin/sale_discount_coupons_load.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/jamilco.omni/admin/sale_discount_coupons_load.php");
}