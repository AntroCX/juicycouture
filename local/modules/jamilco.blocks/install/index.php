<?php
Class jamilco_blocks extends CModule
{
    var $MODULE_ID = "jamilco.blocks";
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

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->PARTNER_NAME = 'Jamilco IT';
        $this->MODULE_NAME = "jamilco.blocks – модуль для работы с блоками (css/js), аля БЭМ";
        $this->MODULE_DESCRIPTION = "Модуль для jamilco";
    }

    function InstallEvents()
    {
        RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\Blocks\\Common", "init");
        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\Blocks\\Common", "init");
        return true;
    }

    function InstallFiles($arParams = array())
    {
        return true;
    }

    function UnInstallFiles()
    {
        return true;
    }

    function InstallDB($arParams = array())
    {
        \COption::SetOptionInt("jamilco.blocks", "autoload_module", 1);
        return true;
    }

    function UnInstallDB($arParams = array())
    {
        \COption::RemoveOption("jamilco.blocks", "autoload_module");
        return true;
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
}
?>