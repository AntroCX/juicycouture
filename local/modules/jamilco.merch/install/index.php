<?php

use \Bitrix\Main\Loader;

class jamilco_merch extends CModule
{
    var $MODULE_ID = "jamilco.merch";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $MODULE_CSS;

    function __construct()
    {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->PARTNER_NAME = 'Jamilco IT';
        $this->MODULE_NAME = "jamilco.merch";
        $this->MODULE_DESCRIPTION = "Мерчендайзинг каталога";
    }

    function DoInstall()
    {
        RegisterModule($this->MODULE_ID);
        $this->InstallFiles();
        $this->InstallEvents();
        $this->InstallDB();
    }

    function DoUninstall()
    {
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallDB();
        UnRegisterModule($this->MODULE_ID);
    }

    function InstallEvents()
    {
        // пункт меню
        RegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\Merch\\Events", "addMenuItem");

        // добавление & изменение элемента каталога
        RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", $this->MODULE_ID, "Jamilco\\Merch\\Events", "checkItem");
        RegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", $this->MODULE_ID, "Jamilco\\Merch\\Events", "checkItem");
        RegisterModuleDependences("iblock", "OnAfterIBlockElementDelete", $this->MODULE_ID, "Jamilco\\Merch\\Events", "deleteItem");

        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\Merch\\Events", "addMenuItem");
        UnRegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", $this->MODULE_ID, "Jamilco\\Merch\\Events", "checkItem");
        UnRegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", $this->MODULE_ID, "Jamilco\\Merch\\Events", "checkItem");
        UnRegisterModuleDependences("iblock", "OnAfterIBlockElementDelete", $this->MODULE_ID, "Jamilco\\Merch\\Events", "deleteItem");

        return true;
    }

    function InstallFiles($arParams = array())
    {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);

        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", $_SERVER["DOCUMENT_ROOT"]."/local/modules/".$this->MODULE_ID."/install/admin/");

        return true;
    }

    function InstallDB()
    {

        return true;
    }

    function UnInstallDB()
    {

        return true;
    }
}