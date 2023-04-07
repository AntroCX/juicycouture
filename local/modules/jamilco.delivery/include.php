<?php
\Bitrix\Main\Loader::registerAutoLoadClasses(
    "jamilco.delivery",
    array(
        "Jamilco\\Delivery\\Events"   => "lib/events.php",
        "Jamilco\\Delivery\\Coupon"   => "lib/coupon.php",
        "Jamilco\\Delivery\\Ozon"     => "lib/ozon.php",
        "Jamilco\\Delivery\\Location" => "lib/location.php",
    )
);