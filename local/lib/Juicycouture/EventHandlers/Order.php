<?php

namespace Juicycouture\EventHandlers;

use Bitrix\Main\Context;
use Bitrix\Main\EventResult;
use Bitrix\Sale\ResultError;
use Juicycouture\Google\ReCaptcha\ReCaptcha;


class Order
{
    public static function onSaleOrderBeforeSaved($event)
    {
        try {
            self::checkCaptcha();
        } catch (\Exception $e) {
            return new EventResult(
                EventResult::ERROR,
                new ResultError($e->getMessage()),
                'sale'
            );
        }
    }

    private static function checkCaptcha()
    {
        $request = Context::getCurrent()->getRequest();
        if ($request['confirmorder'] === 'Y') {
            (new ReCaptcha($request['TOKEN_CAPTCHA']))->verify();
        }
    }
}
