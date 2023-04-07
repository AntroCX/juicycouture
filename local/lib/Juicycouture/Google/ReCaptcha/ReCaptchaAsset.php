<?php

namespace Juicycouture\Google\ReCaptcha;

use Bitrix\Main\Config\Option;
use Oneway\FooterAsset;


class ReCaptchaAsset
{
    public static function getScriptPath(): string
    {
        $siteKey = self::getSiteKey();

        if (!$siteKey) {
            return '';
        }

        return "https://www.google.com/recaptcha/api.js?render={$siteKey}";
    }

    public static function addJs()
    {
        $siteKey = self::getSiteKey();

        if ($siteKey) {
            FooterAsset::addJsCode(
            /** @lang JavaScript */
                "grecaptcha.ready(function () {
        $('.g-recaptcha').each(function () {
            var include = $(this);
            let gParams = {action: include.data('action')};
            grecaptcha.execute('{$siteKey}', gParams).then(function (token) {
                include.val(token);
            });
        })
    });"
            );
        }
    }

    public static function getSiteKey()
    {
        return getenv('RECAPTCHA.SITE_KEY');
    }
}
