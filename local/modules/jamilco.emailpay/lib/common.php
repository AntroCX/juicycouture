<?php
namespace Jamilco\EmailPay;

class Common
{
    public static function init()
    {
        if(\COption::GetOptionString("jamilco.emailpay", "autoload_module", 1)) {
            self::autoLoad();
        }
    }

    public static function autoLoad()
    {
        return \Bitrix\Main\Loader::includeModule('jamilco.emailpay');
    }
}
