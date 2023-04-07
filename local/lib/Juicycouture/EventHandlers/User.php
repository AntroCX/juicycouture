<?php

namespace Juicycouture\EventHandlers;

use Bitrix\Main\Context;
use Juicycouture\Google\ReCaptcha\ReCaptcha;
use Juicycouture\Helpers\PageHelper;


class User
{

    public static function onBeforeUserLogin(&$arFields)
    {
        try {
            self::checkCaptcha();
        } catch (\Exception $e) {
            global $APPLICATION;
            $APPLICATION->throwException($e->getMessage());
            return false;
        }
    }

    public static function onBeforeUserSendPassword(&$arFields)
    {
        try {
            self::checkCaptcha();
        } catch (\Exception $e) {
            global $APPLICATION;
            $APPLICATION->throwException($e->getMessage());
            return false;
        }
    }

    private static function checkCaptcha()
    {
        $request = Context::getCurrent()->getRequest();
        if (!PageHelper::isAdminPage()) {
            (new ReCaptcha($request['TOKEN_CAPTCHA']))->verify();
        }
    }
}
