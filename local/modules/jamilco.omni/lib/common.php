<?
namespace Jamilco\Omni;

class Common
{

    public static function init()
    {
        if (\COption::GetOptionString("jamilco.omni", "autoload_module", 1)) {
            self::autoLoad();

            \RegisterModuleDependences("main", "OnAdminSaleOrderViewDraggable", 'jamilco.omni', "Jamilco\\Omni\\ChangeDelivery", "onInit");
            \RegisterModuleDependences("catalog", "OnProductUpdate", 'jamilco.omni', "Jamilco\\Omni\\Events", "OnProductUpdateHandler");
        }
    }

    public static function autoLoad()
    {
        return \Bitrix\Main\Loader::includeModule('jamilco.omni');
    }
}

?>