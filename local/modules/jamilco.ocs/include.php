<?
\Bitrix\Main\Loader::registerAutoLoadClasses(
    "jamilco.ocs",
    array(
        "Jamilco\\OCS\\Api" => "lib/api.php",
        "Jamilco\\OCS\\Auth" => "lib/auth.php",
        "Jamilco\\OCS\\Router" => "lib/router.php",
        "Jamilco\\OCS\\Xml" => "lib/xml.php",
        "Jamilco\\OCS\\Json" => "lib/json.php",
        'Jamilco\\Ocs\\ProductConfig' => 'config/product.php'
        // контроллеры
        //"Jamilco\\OCS\\Orders" => "lib/controllers/orders.php",
    )
);