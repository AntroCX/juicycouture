<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 04.08.16
 * Time: 13:06
 */
Class jamilco_delivery extends CModule
{
    var $MODULE_ID = "jamilco.delivery";
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
        $this->MODULE_NAME = "Jamilco.Delivery";
        $this->MODULE_DESCRIPTION = "Ozon + Время и дата в заказе";
    }

    function InstallEvents()
    {
        RegisterModuleDependences("main", "OnProlog", $this->MODULE_ID, "Jamilco\\Delivery\\Events", "OnProlog");
        RegisterModuleDependences("main", "OnAdminTabControlBegin", $this->MODULE_ID, "Jamilco\\Delivery\\Events", "OnAdminTabControlBegin");
        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("main", "OnProlog", $this->MODULE_ID, "Jamilco\\Delivery\\Events", "OnProlog");
        UnRegisterModuleDependences("main", "OnAdminTabControlBegin", $this->MODULE_ID, "Jamilco\\Delivery\\Events", "OnAdminTabControlBegin");
        return true;
    }

    function InstallFiles($arParams = array())
    {
        return true;
    }

    function UnInstallFiles($arParams = array())
    {
        return true;
    }

    function InstallDB($arParams = array())
    {
        \CModule::IncludeModule('sale');
        $groupId = \CSaleOrderPropsGroup::Add(array(
            'PERSON_TYPE_ID' => 1,
            'NAME' => 'Дата и время доставки',
            'SORT' => 500
        ));
        $dateId = \CSaleOrderProps::Add(array(
            'PERSON_TYPE_ID' => 1,
            'NAME' => 'Дата доставки',
            'TYPE' => 'TEXT',
            'REQUIED' => 'N',
            'USER_PROPS' => 'N',
            'CODE' => 'DELIVERY_DATE',
            'PROPS_GROUP_ID' => $groupId
        ));

        $timeId = \CSaleOrderProps::Add(array(
            'PERSON_TYPE_ID' => 1,
            'NAME' => 'Время доставки',
            'TYPE' => 'TEXT',
            'REQUIED' => 'N',
            'USER_PROPS' => 'N',
            'CODE' => 'DELIVERY_TIME',
            'PROPS_GROUP_ID' => $groupId
        ));

        \COption::SetOptionInt("jamilco.delivery", "delivery_date", $dateId);
        \COption::SetOptionInt("jamilco.delivery", "delivery_time", $timeId);


        return true;
    }

    function UnInstallDB($arParams = array())
    {
        \CModule::IncludeModule('sale');
        $dateId = \COption::GetOptionInt("jamilco.delivery", "delivery_date");
        $timeId = \COption::GetOptionInt("jamilco.delivery", "delivery_time");

        \CSaleOrderProps::Delete($dateId);
        \CSaleOrderProps::Delete($timeId);

        $rsGroups = \CSaleOrderPropsGroup::GetList(
            array(),
            array(
                'PERSON_TYPE_ID' => 1,
                'NAME' => 'Дата и время доставки'
            )
        );
        $arGroups = $rsGroups->Fetch();
        \CSaleOrderPropsGroup::Delete($arGroups['ID']);

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