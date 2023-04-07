<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 18.07.16
 * Time: 10:23
 */
Class jamilco_reports extends CModule
{
    var $MODULE_ID = "jamilco.reports";
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
        $this->MODULE_NAME = "Модуль для генерации доп отчетов для e-com отдела Jamilco";
        $this->MODULE_DESCRIPTION = "Модуль предназначен для вывода нестанартных отчетов для e-com отдела компании Jamilco";
    }

    function InstallEvents()
    {
        RegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\Reports\\Events", "addMenuItem");
        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\Reports\\Events", "addMenuItem");
        return true;
    }

    function InstallFiles($arParams = array())
    {
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.reports/install/admin/",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/",
            true,
            true
        );

        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.reports/themes/",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/",
            true,
            true
        );

        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/",
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.reports/install/admin/"
        );

        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/",
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.reports/themes/"
        );

        return true;
    }

    function InstallDB($arParams = array())
    {
        \COption::SetOptionInt("jamilco.reports", "autoload_module", 1);
        return true;
    }

    function UnInstallDB($arParams = array())
    {
        \COption::SetOptionInt("jamilco.reports", "autoload_module", 0);
        return true;
    }

    function DoInstall()
    {
        $this->InstallFiles();
        $this->InstallEvents();
        $this->InstallDB();
        RegisterModule($this->MODULE_ID);
    }

    function DoUninstall()
    {
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallDB();
        UnRegisterModule($this->MODULE_ID);

    }
}