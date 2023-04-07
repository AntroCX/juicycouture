<?php
namespace Jamilco\Blocks;

class Common {

    public static function init() {
        if(\COption::GetOptionString("jamilco.blocks", "autoload_module", 1)) {
            self::autoLoad();
        }
        //Перехватываем отправку письма
        AddEventHandler('main', 'OnBeforeEventSend', array("Jamilco\\Blocks\\Block", "OnBeforeEventSendHandler"));
        //Перехватываем изменение статуса
        AddEventHandler("sale", "OnSaleStatusOrder", array("Jamilco\\Blocks\\Block", "OnSaleStatusOrderHandler"));
    }

    public static function autoLoad() {
        return \Bitrix\Main\Loader::includeModule('jamilco.blocks');
    }
}