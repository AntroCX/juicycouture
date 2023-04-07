<?php
\Bitrix\Main\Loader::registerAutoLoadClasses(
    "jamilco.main",
    array(
        "Jamilco\\Main\\Utils"         => "lib/utils.php",
        "Jamilco\\Main\\Common"        => "lib/common.php",
        "Jamilco\\Main\\Events"        => "lib/events.php",
        "Jamilco\\Main\\Handlers"      => "lib/handlers.php",
        "Jamilco\\Main\\Oracle"        => "lib/oracle.php",
        "Jamilco\\Main\\Retail"        => "lib/retail.php",
        "Jamilco\\Main\\Manzana"       => "lib/manzana.php",
        "Jamilco\\Main\\Update"        => "lib/update.php",
        "Jamilco\\Main\\Progress"      => "lib/progress.php",
        "Jamilco\\Main\\CancelOrder"   => "lib/cancelorder.php",
        "Jamilco\\Main\\DeliveryOCS"   => "lib/deliveryocs.php",
        'DigitalDataLayer\\Manager'    => 'lib/DigitalDataLayer/Manager.php',
        'DigitalDataLayer\\Data'       => 'lib/DigitalDataLayer/Data.php',
        'Adspire\\Manager'             => 'lib/adspire.php',
        'SoapXmlCreator'               => 'lib/soap/xmlcreator.php',
        "Jamilco\\Main\\Subscribers"   => "lib/subscribers.php",
        "Jamilco\\Main\\OnlinePayment" => "lib/onlinepayment.php"
    )
);
