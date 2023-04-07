<?php

namespace Juicycouture\Helpers;


class PageHelper
{
    public static function isAdminPage(): bool
    {
        global $APPLICATION;

        $firstDir = current(preg_split('/[\/]+/', $APPLICATION->GetCurDir(), 0, PREG_SPLIT_NO_EMPTY));
        return $firstDir == 'bitrix';
    }
}
