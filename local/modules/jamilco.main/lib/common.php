<?php

namespace Jamilco\Main;

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

class Common
{
    public static function init() {
        if(\COption::GetOptionString("jamilco.main", "autoload_module", 1)) {
            self::autoLoad();
        }
        EventManager::getInstance()->addEventHandler('main', 'OnAfterUserRegister', ['Jamilco\\Main\\Handlers', 'onAfterUserRegister']);

        EventManager::getInstance()->addEventHandler('sale', 'OnSaleStatusOrderChange', ['Jamilco\\Main\\Handlers', 'onSaleStatusOrderChange']);
        EventManager::getInstance()->addEventHandler('sale', 'OnSalePayOrder', ['Jamilco\\Main\\Handlers', 'onSalePayOrder']);
        EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderCanceled', ['Jamilco\\Main\\Handlers', 'OnOrderCancel']);
        EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderSaved', ["Jamilco\\Main\\Handlers", "OnOrderSave"]);

        EventManager::getInstance()->addEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnBeforeUpdate', ['Jamilco\\Main\\Handlers', 'onBeforeBasketUpdate']);

        EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementAdd', ['Jamilco\\Main\\Handlers', 'OnAfterIBlockElementAddHandler']);
        EventManager::getInstance()->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', ['Jamilco\\Main\\Handlers', 'OnAfterIBlockElementUpdateHandler']);
        EventManager::getInstance()->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', ['Jamilco\\Main\\Handlers', 'OnBeforeIBlockElementUpdateHandler']);

        EventManager::getInstance()->addEventHandler('main', 'OnAdminSaleOrderViewDraggable', ["Jamilco\\Main\\CancelOrder", "onInit"]);
        EventManager::getInstance()->addEventHandler('main', 'OnAdminListDisplay', ["Jamilco\\Main\\Handlers", "OnAdminListDisplayHandler"]);

        EventManager::getInstance()->addEventHandler('sale', 'OnSaleBeforeOrderCanceled', ['Jamilco\\Main\\Handlers', 'OnSaleBeforeOrderCanceledHandler']);

        EventManager::getInstance()->addEventHandler('sale', 'OnSalePropertyValueSetField', ['Jamilco\\Main\\Handlers', 'OnSalePropertyValueSetFieldHandler']);
        EventManager::getInstance()->addEventHandler('sale', 'OnSalePropertyValueEntitySaved', ['Jamilco\\Main\\Handlers', 'OnSalePropertyValueEntitySavedHandler']);

        EventManager::getInstance()->addEventHandler('sale', 'OnSalePaymentSetField', ['Jamilco\\Main\\Handlers', 'OnSalePaymentSetFieldHandler']);
        EventManager::getInstance()->addEventHandler('sale', 'OnSalePaymentEntitySaved', ['Jamilco\\Main\\Handlers', 'OnSalePaymentEntitySavedHandler']);

        EventManager::getInstance()->addEventHandler('main', 'OnBeforeLocalRedirect', ['Jamilco\\Main\\Handlers', 'OnBeforeLocalRedirectHandler']);

        // защита от регистрации со ссылкой в Имя\Фамилия
        EventManager::getInstance()->addEventHandler('main', 'OnBeforeUserAdd', ['Jamilco\\Main\\Handlers', 'OnBeforeUserAddHandler']);
        EventManager::getInstance()->addEventHandler('main', 'OnBeforeUserRegister', ['Jamilco\\Main\\Handlers', 'OnBeforeUserAddHandler']);

        EventManager::getInstance()->addEventHandler('iblock', 'OnIBlockPropertyBuildList', ['Jamilco\\Main\\ItemComplect', 'GetUserTypeDescription']);
        EventManager::getInstance()->addEventHandler("iblock", "OnIBlockPropertyBuildList", ["Jamilco\\Main\\Property\\Order", "GetUserTypeDescription"]);
        EventManager::getInstance()->addEventHandler("iblock", "OnIBlockPropertyBuildList", ["Jamilco\\Main\\Property\\Changebasketprice", "GetUserTypeDescription"]);

        EventManager::getInstance()->addEventHandler("iblock", "OnIBlockPropertyBuildList", ["Jamilco\\Main\\Property\\Location", "GetUserTypeDescription"]);
        EventManager::getInstance()->addEventHandler("sale", "OnSaleOrderBeforeSaved", ["Jamilco\\Main\\Handlers", "OnSaleOrderBeforeSavedHandler"]);
    }

    public static function autoLoad() {
        return Loader::includeModule('jamilco.main');
    }
}
