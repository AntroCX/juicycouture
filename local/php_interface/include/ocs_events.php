<?php
use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;

//--AdSpire--
EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderSaved', 'OnNewOrderSave');
function OnNewOrderSave($event)
{
    \Bitrix\Main\Diag\Debug::writeToFile('OnSaleOrderSaved', 'event', "/dump321.txt");
    //////
    $AdspireGroupId = 5;
    /////
    $order = $event->getParameter("ENTITY");
    $isNew = $event->getParameter("IS_NEW");
    $orderId = $order->getId();

    Loader::includeModule('sale');

    if ($isNew) {
        $propertyCollection = $order->getPropertyCollection();

        foreach ($propertyCollection->getGroupProperties($AdspireGroupId) as $property) {
            $p = $property->getProperty();
            $val = "";

            switch ($p["CODE"]) {
                case 'atm_marketing':
                case 'atm_remarketing':
                case 'atm_closer':
                    $val = $_COOKIE[$p["CODE"]];
                    break;
                case 'USER_IP':
                    $val = ip2long($_SERVER["REMOTE_ADDR"]);
                    break;
            }
            if ($val) {
                $property->setValue($val);
                $property->save(); // сохраняем только свойства
            }
        }
    }
}
//--/AdSpire--