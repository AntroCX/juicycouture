<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 13.05.16
 * Time: 13:14
 */
Class jamilco_ocs extends CModule
{
    var $MODULE_ID = "jamilco.ocs";
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
        $this->MODULE_NAME = "Модуль синхронизации данных с OCS Jamilco";
        $this->MODULE_DESCRIPTION = "Модуль предназначен для синхронизации заказов, товаров с OCS системой компании Jamilco";
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
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
        \COption::SetOptionInt("jamilco.ocs", "autoload_module", 1);
        return true;
    }

    function UnInstallDB($arParams = array())
    {
        \COption::SetOptionInt("jamilco.ocs", "autoload_module", 0);
        return true;
    }

    function DoInstall()
    {
        RegisterModule($this->MODULE_ID);
        $this->InstallFiles();
        $this->InstallEvents();
        $this->InstallDB();

        if(file_exists($_SERVER['DOCUMENT_ROOT'].'/.htaccess')){
            $ht_content = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/.htaccess');
            $new_ht_content = str_replace("RewriteEngine On","RewriteEngine On \n #jamilco.ocs module handler \n RewriteCond %{HTTP_HOST} ^(.*)$ \n RewriteRule ^api/ocs/ /local/modules/jamilco.ocs/ocs\.php [L] \n #!jamilco.ocs",$ht_content);
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/.htaccess',$new_ht_content);
        }

    }

    function DoUninstall()
    {
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallDB();
        UnRegisterModule($this->MODULE_ID);

        if(file_exists($_SERVER['DOCUMENT_ROOT'].'/.htaccess')){
            $ht_content = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/.htaccess');
            $new_ht_content = preg_replace("/#jamilco.ocs(.*?)#!jamilco.ocs/s", '', $ht_content);
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/.htaccess',$new_ht_content);
        }
    }
}