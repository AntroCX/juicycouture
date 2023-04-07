<?php
Class jamilco_taggedpages extends CModule
{
    var $MODULE_ID = "jamilco.taggedpages";
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
        $this->MODULE_NAME = "jamilco.taggedpages – модуль для создания тегированных страниц";
        $this->MODULE_DESCRIPTION = "Модуль для генерации тегированных страниц из элементов каталога";
    }

    function InstallEvents()
    {
        RegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\TaggedPages\\Events", "addMenuItem");
        RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\TaggedPages\\Common", "init");
        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\TaggedPages\\Events", "addMenuItem");
        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\TaggedPages\\Common", "init");
        return true;
    }

    function InstallFiles($arParams = array())
    {
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.taggedpages/install/admin/",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/",
            true,
            true
        );


        return true;
    }

    function UnInstallFiles()
    {

        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/",
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.taggedpages/install/admin/"
        );

        return true;
    }

    function InstallDB($arParams = array())
    {
        global $DB;
        \COption::SetOptionInt("jamilco.taggedpages", "autoload_module", 1);
        $DB->Query("CREATE TABLE `j_taggedpages` (`ID` int NOT NULL AUTO_INCREMENT, `TITLE` varchar(255) NOT NULL, `URL` varchar(255) NOT NULL, `RULE_URL` varchar(255), `RULE_PARAMS` varchar(255), `NUM_PAGES` int NOT NULL, `ACTIVE` varchar(1) NOT NULL, `SHOW_FILTER` varchar(1) NOT NULL, `TOP_HTML` text NOT NULL, `BOTTOM_HTML` text NOT NULL, `SEO_DESCRIPTION` varchar(255) NOT NULL, `SEO_KEYWORDS` varchar(255) NOT NULL, `SECTIONS` varchar(255) NOT NULL, `SEO_TITLE` varchar(255), PRIMARY KEY(`ID`))");

        return true;
    }

    function UnInstallDB($arParams = array())
    {
        global $DB;
        \COption::RemoveOption("jamilco.taggedpages", "autoload_module");
        $DB->Query("DROP TABLE IF EXISTS `j_taggedpages`");

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