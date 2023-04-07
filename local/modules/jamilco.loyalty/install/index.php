<?php

/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 19.10.16
 * Time: 15:49
 */
class jamilco_loyalty extends CModule
{
    var $MODULE_ID = "jamilco.loyalty";
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
        $this->MODULE_NAME = "jamilco.loyalty – модуль для работы с программой лояльности Jamilco";
        $this->MODULE_DESCRIPTION = "Модуль позволяет использовать бонусные карты компании Jamilco на сайте";
    }

    function InstallEvents()
    {
        RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\Loyalty\\Common", "init");
        RegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "addMenuItem");

        RegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "OnBeforeOrderAddHandler");
        //RegisterModuleDependences("sale", "OnOrderSave", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "OnOrderSaveHandler");
        RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepComplete", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "OnOrderSaveHandler");
        RegisterModuleDependences("sale", "OnOrderSave", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "OnOrderUpdateHandler");

        RegisterModuleDependences("main", "OnAdminSaleOrderViewDraggable", $this->MODULE_ID, "Jamilco\\Loyalty\\BonusOrder", "onInit");

        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\Loyalty\\Common", "init");
        UnRegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "addMenuItem");

        UnRegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "OnBeforeOrderAddHandler");
        //UnRegisterModuleDependences("sale", "OnOrderSave", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "OnOrderSaveHandler");
        UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepComplete", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "OnOrderSaveHandler");
        UnRegisterModuleDependences("sale", "OnOrderSave", $this->MODULE_ID, "Jamilco\\Loyalty\\Events", "OnOrderUpdateHandler");

        UnRegisterModuleDependences("main", "OnAdminSaleOrderViewDraggable", $this->MODULE_ID, "Jamilco\\Loyalty\\BonusOrder", "onInit");

        return true;
    }

    function InstallFiles($arParams = array())
    {

        // настройки
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.loyalty/install/admin/",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/",
            true,
            true
        );

        // темы
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.loyalty/themes/",
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
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.loyalty/install/admin/"
        );

        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/",
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/jamilco.loyalty/themes/"
        );

        return true;
    }

    function InstallDB($arParams = array())
    {
        \COption::SetOptionInt("jamilco.loyalty", "autoload_module", 1);

        \CModule::IncludeModule('sale');
        \CModule::IncludeModule('highloadblock');

        // группа свойств заказа и сами свойства
        $rsOrderPropsGroup = \CSaleOrderPropsGroup::GetList(
            array(),
            array(
                'PERSON_TYPE_ID' => 1,
                'NAME'           => 'Программа лояльности'
            )
        );
        if ($rsOrderPropsGroup->SelectedRowsCount() == 0) {
            $groupId = \CSaleOrderPropsGroup::Add(
                array(
                    'PERSON_TYPE_ID' => 1,
                    'NAME'           => 'Программа лояльности',
                    'SORT'           => 500,
                )
            );
        }

        $rsOrder = \CSaleOrderProps::GetList(
            array(),
            array(
                'CODE' => 'PROGRAMM_LOYALTY_CARD'
            )
        );

        if ($rsOrder->SelectedRowsCount() == 0) {
            \CSaleOrderProps::Add(
                array(
                    'PERSON_TYPE_ID' => 1,
                    'NAME'           => 'Номер карты',
                    'TYPE'           => 'TEXT',
                    'REQUIED'        => 'N',
                    'USER_PROPS'     => 'N',
                    'IS_LOCATION'    => 'N',
                    'PROPS_GROUP_ID' => $groupId,
                    'CODE'           => 'PROGRAMM_LOYALTY_CARD',
                    'UTIL'           => 'Y',
                    'SORT'           => 200,
                )
            );
        }

        $rsOrder = \CSaleOrderProps::GetList(
            array(),
            array(
                'CODE' => 'PROGRAMM_LOYALTY_WRITEOFF'
            )
        );

        if ($rsOrder->SelectedRowsCount() == 0) {
            \CSaleOrderProps::Add(
                array(
                    'PERSON_TYPE_ID' => 1,
                    'NAME'           => 'Бонусов на списание',
                    'TYPE'           => 'TEXT',
                    'REQUIED'        => 'N',
                    'USER_PROPS'     => 'N',
                    'IS_LOCATION'    => 'N',
                    'PROPS_GROUP_ID' => $groupId,
                    'CODE'           => 'PROGRAMM_LOYALTY_WRITEOFF',
                    'UTIL'           => 'Y',
                    'SORT'           => 200,
                )
            );
        }

        // HL-блок для лога запросов
        $result = \Bitrix\Highloadblock\HighloadBlockTable::getList(
            array(
                'filter' => array(
                    'TABLE_NAME' => 'loyalty_log'
                )
            )
        );
        if (!$arHl = $result->Fetch()) {
            $result = \Bitrix\Highloadblock\HighloadBlockTable::add(
                array(
                    'NAME'       => 'LoyaltyLog',
                    'TABLE_NAME' => 'loyalty_log',
                )
            );

            $entityId = 'HLBLOCK_'.$result->getId();

            // создадим поля
            $arFields = array(
                'DATE'   => array('NAME' => 'Дата', 'TYPE' => 'datetime'),
                'IP'     => array('NAME' => 'IP-адрес', 'TYPE' => 'string'),
                'USER'   => array('NAME' => 'Пользователь', 'TYPE' => 'string'),
                'CARD'   => array('NAME' => 'Карта', 'TYPE' => 'string'),
                'TYPE'   => array('NAME' => 'Тип', 'TYPE' => 'string'),
                'RESULT' => array('NAME' => 'Результат', 'TYPE' => 'string'),
            );

            $oUserTypeEntity = new \CUserTypeEntity();
            foreach ($arFields as $name => $arOne) {
                $aUserFields = array(
                    'ENTITY_ID'         => $entityId,
                    'FIELD_NAME'        => 'UF_'.$name,
                    'USER_TYPE_ID'      => $arOne['TYPE'],
                    'SORT'              => 500,
                    'MULTIPLE'          => 'N',
                    'MANDATORY'         => 'N',
                    'SHOW_FILTER'       => 'I',
                    'SHOW_IN_LIST'      => '',
                    'EDIT_IN_LIST'      => '',
                    'IS_SEARCHABLE'     => 'N',
                    'EDIT_FORM_LABEL'   => array(
                        'ru' => $arOne['NAME'],
                    ),
                    'LIST_COLUMN_LABEL' => array(
                        'ru' => $arOne['NAME'],
                    ),
                    'LIST_FILTER_LABEL' => array(
                        'ru' => $arOne['NAME'],
                    ),
                );
                $iUserFieldId = $oUserTypeEntity->Add($aUserFields);
            }
        }

        return true;
    }

    function UnInstallDB($arParams = array())
    {
        \COption::RemoveOption("jamilco.loyalty", "autoload_module");

        \CModule::IncludeModule('sale');
        \CModule::IncludeModule('highloadblock');

        // удалим группу свойств заказа и сами свойства
        $rsOrder = \CSaleOrderProps::GetList(
            array(),
            array(
                'CODE' => array('PROGRAMM_LOYALTY_CARD', 'PROGRAMM_LOYALTY_WRITEOFF')
            )
        );

        while ($arrOrder = $rsOrder->Fetch()) {
            \CSaleOrderProps::Delete($arrOrder['ID']);
        }

        $rsOrderPropsGroup = \CSaleOrderPropsGroup::GetList(
            array(),
            array(
                'PERSON_TYPE_ID' => 1,
                'NAME'           => 'Программа лояльности'
            )
        );
        while ($arOrderPropsGroup = $rsOrderPropsGroup->Fetch()) {
            \CSaleOrderPropsGroup::Delete($arOrderPropsGroup['ID']);
        }

        // удалим HL-блок лога
        $result = \Bitrix\Highloadblock\HighloadBlockTable::getList(
            array(
                'filter' => array(
                    'TABLE_NAME' => 'loyalty_log'
                )
            )
        );
        if ($arHl = $result->Fetch()) {
            \Bitrix\Highloadblock\HighloadBlockTable::delete($arHl['ID']);
        }

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