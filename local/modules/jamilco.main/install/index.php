<?php
Class jamilco_main extends CModule
{
    var $MODULE_ID = "jamilco.main";
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
        $this->MODULE_NAME = "jamilco.main – модуль для работы с сайтом juicycouture.ru";
        $this->MODULE_DESCRIPTION = "Модуль для juicycouture.ru";
    }

    function InstallEvents()
    {
        RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\Main\\Common", "init");
        RegisterModuleDependences("sale", "OnBuildAccountNumberTemplateList", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "OnBuildAccountNumberTemplateList");
        RegisterModuleDependences("sale", "OnBeforeOrderAccountNumberSet", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "OnBeforeOrderAccountNumberSet");
        RegisterModuleDependences("main", "OnBeforeUserRegister", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "OnBeforeUserRegister");
        RegisterModuleDependences("main", "OnBeforeUserAdd", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "OnBeforeUserAdd");
        RegisterModuleDependences("main", "OnProductAdd", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "UpdateProductQuantity");
        RegisterModuleDependences("main", "OnProductUpdate", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "UpdateProductQuantity");
        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\Main\\Common", "init");
        UnRegisterModuleDependences("sale", "OnBuildAccountNumberTemplateList", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "OnBuildAccountNumberTemplateList");
        UnRegisterModuleDependences("sale", "OnBeforeOrderAccountNumberSet", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "OnBeforeOrderAccountNumberSet");
        UnRegisterModuleDependences("main", "OnBeforeUserRegister", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "OnBeforeUserRegister");
        UnRegisterModuleDependences("main", "OnBeforeUserAdd", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "OnBeforeUserAdd");
        UnRegisterModuleDependences("main", "OnProductAdd", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "UpdateProductQuantity");
        UnRegisterModuleDependences("main", "OnProductUpdate", $this->MODULE_ID, "Jamilco\\Main\\Handlers", "UpdateProductQuantity");
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
        \COption::SetOptionInt("jamilco.main", "autoload_module", 1);
        return true;
    }

    function UnInstallDB($arParams = array())
    {
        \COption::RemoveOption("jamilco.main", "autoload_module");
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