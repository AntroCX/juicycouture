<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 18.07.16
 * Time: 10:26
 */
\Bitrix\Main\Loader::registerAutoLoadClasses(
    "jamilco.reports",
    array(
        "Jamilco\\Reports\\Events" => "lib/events.php",
    )
);