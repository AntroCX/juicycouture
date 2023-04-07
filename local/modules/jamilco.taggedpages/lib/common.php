<?php
namespace Jamilco\TaggedPages;

class Common {

    public static function init() {
        if(\COption::GetOptionString("jamilco.taggedpages", "autoload_module", 1)) {
            self::autoLoad();
        }
    }

    public static function autoLoad() {
        return \Bitrix\Main\Loader::includeModule('jamilco.taggedpages');
    }
}