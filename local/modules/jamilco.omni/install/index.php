<?php

use \Bitrix\Main\Loader;
use \Bitrix\Iblock\TypeTable;
use \Bitrix\Iblock\IblockTable;
use \Bitrix\Main\GroupTable;
use \Bitrix\Highloadblock\HighloadBlockTable;
use \Jamilco\Omni\Channel;

class jamilco_omni extends CModule
{
    var $MODULE_ID = "jamilco.omni";
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
        $this->MODULE_NAME = "jamilco.omni";
        $this->MODULE_DESCRIPTION = "Omni.Tablet, Omni.Channel";
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
        // инициализация
        RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\Omni\\Common", "init");

        // пункт меню (Omni Channel)
        RegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\Omni\\Events", "addMenuItem");

        // свойство для пользовательских полей (привязка к магазину)
        RegisterModuleDependences('main', 'OnUserTypeBuildList', $this->MODULE_ID, "Jamilco\\Omni\\ElementMainProperty", "GetUserTypeDescription", 1000);

        // свойство для элементов инфоблока (привязка к магазину)
        RegisterModuleDependences('iblock', 'OnIBlockPropertyBuildList', $this->MODULE_ID, 'Jamilco\\Omni\\ElementIblockProperty', 'GetUserTypeDescription');

        // автоматическое добавление подарка
        RegisterModuleDependences('sale', 'OnSaleBasketSaved', $this->MODULE_ID, 'Jamilco\\Omni\\Gift', 'checkGift');

        // после создания заказа
        RegisterModuleDependences('sale', 'OnSaleComponentOrderOneStepComplete', $this->MODULE_ID, 'Jamilco\\Omni\\Events', 'OnSaleComponentOrderOneStepCompleteHandler');

        // после обновления \ создания заказа
        RegisterModuleDependences('sale', 'OnOrderSave', $this->MODULE_ID, 'Jamilco\\Omni\\Events', 'OnOrderSaveHandler');

        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "Jamilco\\Omni\\Common", "init");
        UnRegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "Jamilco\\Omni\\Events", "addMenuItem");
        UnRegisterModuleDependences('main', 'OnUserTypeBuildList', $this->MODULE_ID, "Jamilco\\Omni\\ElementMainProperty", "GetUserTypeDescription");
        UnRegisterModuleDependences('iblock', 'OnIBlockPropertyBuildList', $this->MODULE_ID, 'Jamilco\\Omni\\ElementIblockProperty', 'GetUserTypeDescription');
        UnRegisterModuleDependences('sale', 'OnSaleBasketSaved', $this->MODULE_ID, 'Jamilco\\Omni\\Gift', 'checkGift');
        UnRegisterModuleDependences('sale', 'OnSaleComponentOrderOneStepComplete', $this->MODULE_ID, 'Jamilco\\Omni\\Events', 'OnSaleComponentOrderOneStepCompleteHandler');
        UnRegisterModuleDependences('sale', 'OnOrderSave', $this->MODULE_ID, 'Jamilco\\Omni\\Events', 'OnOrderSaveHandler');

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
        Loader::includeModule('iblock');
        Loader::includeModule('sale');
        Loader::includeModule('highloadblock');

        \COption::SetOptionInt("jamilco.omni", "autoload_module", 1);

        // данные для Omni Channel
        // hl-блок
        $hl = HighloadBlockTable::getList(array('filter' => array('NAME' => 'OmniChannel')));
        if ($arHL = $hl->Fetch()) {
            $hlId = $arHL['ID'];
        } else {
            $result = HighloadBlockTable::add(
                array(
                    'NAME'       => 'OmniChannel',
                    'TABLE_NAME' => 'jml_omni_channel',
                )
            );
            if (!$result->isSuccess()) {
                $errors = $result->getErrorMessages();
            } else {
                $hlId = $result->getId();
            }
        }

        if ($hlId) {
            $entityId = 'HLBLOCK_'.$hlId;
            $arFields = array(
                'UF_TYPE'  => array('TYPE' => 'string'),
                'UF_ID'    => array('TYPE' => 'string'),
                'UF_FLAG'  => array('TYPE' => 'string'),
            );
            $sort = 100;
            $userType = new CUserTypeEntity();
            foreach ($arFields as $fieldName => $arOne) {
                $res = CUserTypeEntity::getList(array(), array('ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldName));
                if (!$arProp = $res->Fetch()) {
                    $res = $userType->Add(
                        array(
                            'ENTITY_ID'    => $entityId,
                            'FIELD_NAME'   => $fieldName,
                            'USER_TYPE_ID' => $arOne['TYPE'],
                            'SORT'         => $sort,
                            'SETTINGS'     => array()
                        )
                    );
                }
                $sort += 100;
            }
        }

        if (!class_exists('\\Jamilco\\Omni\\Channel')) {
            include_once $_SERVER["DOCUMENT_ROOT"] . '/local/modules/jamilco.omni/lib/channel.php';
        }
        Channel::restoreDefaults();

        // группа свойств заказа и сами свойства
        $rsOrderPropsGroup = \CSaleOrderPropsGroup::GetList(
            array(),
            array(
                'PERSON_TYPE_ID' => 1,
                'NAME'           => 'Omni'
            )
        );
        if (!$arGroupOrderProps = $rsOrderPropsGroup->Fetch()) {
            $arGroupOrderProps['ID'] = \CSaleOrderPropsGroup::Add(
                array(
                    'PERSON_TYPE_ID' => 1,
                    'NAME'           => 'Omni',
                    'SORT'           => 600,
                )
            );
        }

        $rsOrder = \CSaleOrderProps::GetList(array(), array('CODE' => 'OMNI_CHANNEL'));

        if ($rsOrder->SelectedRowsCount() == 0) {
            \CSaleOrderProps::Add(
                array(
                    'PERSON_TYPE_ID' => 1,
                    'NAME'           => 'Omni Channel',
                    'TYPE'           => 'TEXT',
                    'REQUIED'        => 'N',
                    'USER_PROPS'     => 'N',
                    'IS_LOCATION'    => 'N',
                    'PROPS_GROUP_ID' => $arGroupOrderProps['ID'],
                    'CODE'           => 'OMNI_CHANNEL',
                    'UTIL'           => 'Y',
                    'SORT'           => 200,
                )
            );
        }

        // данные для Omni Tablet
        // группа пользователей
        $gr = GroupTable::getList(array('filter' => array('STRING_ID' => 'tablet')));
        if (!$arGroup = $gr->Fetch()) {
            $arGroup['ID'] = GroupTable::Add(
                array(
                    'STRING_ID'   => 'tablet',
                    'ACTIVE'      => 'Y',
                    'C_SORT'      => 200,
                    'NAME'        => 'Сотрудники РМ',
                    'DESCRIPTION' => 'Сотрудники розничных магазинов, указывающие табельный номер при оформлении быстрых заказов',
                )
            );
        }

        // свойства для пользователей
        $userType = new CUserTypeEntity();

        // определим ID инфоблока с Магазинами
        $shopsIblockId = false;
        $ib = TypeTable::getList(
            array(
                'filter' => array(
                    'ID' => array('shops', 'stores')
                ),
                'limit'  => 1
            )
        );
        if ($arType = $ib->Fetch()) {
            $res = IblockTable::getList(
                array(
                    'filter' => array('IBLOCK_TYPE_ID' => $arType['ID'], 'NAME' => 'Магазины'),
                    'limit'  => 1,
                    'select' => array('ID')
                )
            );
            if ($arIblock = $res->Fetch()) {
                $shopsIblockId = $arIblock['ID'];
            }
        }

        // привязка к магазину
        $res = CUserTypeEntity::getList(array(), array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_SHOP'));
        if (!$arProp = $res->Fetch()) {
            $res = $userType->Add(
                array(
                    'ENTITY_ID'    => 'USER',
                    'FIELD_NAME'   => 'UF_SHOP',
                    'USER_TYPE_ID' => 'iblock_element_group',
                    'SORT'         => 500,
                    'SETTINGS'     => array(
                        'DISPLAY'       => 'LIST',
                        'LIST_HEIGHT'   => 1,
                        'IBLOCK_ID'     => $shopsIblockId,
                        'ACTIVE_FILTER' => 'Y',
                    )
                )
            );
        }

        /*
        // в пользователе хранится только привязка к магазину, табельные номера - в инфоблоке
        // табельный номер
        $res = CUserTypeEntity::getList(array(), array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_TABLET_ID'));
        if (!$arProp = $res->Fetch()) {
            $res = $userType->Add(
                array(
                    'ENTITY_ID'    => 'USER',
                    'FIELD_NAME'   => 'UF_TABLET_ID',
                    'USER_TYPE_ID' => 'string',
                    'SORT'         => 500,
                    'SETTINGS'     => array(
                        'SIZE' => 20,
                        'ROWS' => 1,
                    )
                )
            );
        }
        */

        // группа свойств заказа и сами свойства
        $rsOrderPropsGroup = \CSaleOrderPropsGroup::GetList(
            array(),
            array(
                'PERSON_TYPE_ID' => 1,
                'NAME'           => 'Omni'
            )
        );
        if (!$arGroupOrderProps = $rsOrderPropsGroup->Fetch()) {
            $arGroupOrderProps['ID'] = \CSaleOrderPropsGroup::Add(
                array(
                    'PERSON_TYPE_ID' => 1,
                    'NAME'           => 'Omni',
                    'SORT'           => 600,
                )
            );
        }

        $rsOrder = \CSaleOrderProps::GetList(array(), array('CODE' => 'OMNI_TABLET_ID'));

        if ($rsOrder->SelectedRowsCount() == 0) {
            \CSaleOrderProps::Add(
                array(
                    'PERSON_TYPE_ID' => 1,
                    'NAME'           => 'Omni Tablet ID',
                    'TYPE'           => 'TEXT',
                    'REQUIED'        => 'N',
                    'USER_PROPS'     => 'N',
                    'IS_LOCATION'    => 'N',
                    'PROPS_GROUP_ID' => $arGroupOrderProps['ID'],
                    'CODE'           => 'OMNI_TABLET_ID',
                    'UTIL'           => 'Y',
                    'SORT'           => 200,
                )
            );
        }


        // инфоблок "Табельные номера сотрудников"
        $ib = TypeTable::getList(
            array(
                'filter' => array(
                    'ID' => array('reference', 'references', 'technical', 'users')
                ),
                'limit'  => 1
            )
        );
        if ($arType = $ib->Fetch()) {
            $res = IblockTable::getList(
                array(
                    'filter' => array('IBLOCK_TYPE_ID' => $arType['ID'], 'CODE' => 'tablet')
                )
            );
            if (!$arIblock = $res->Fetch()) {
                $iblock = new CIBlock();
                $arIblock['ID'] = $iblock->Add(
                    array(
                        "ACTIVE"          => 'Y',
                        "NAME"            => 'Табельные номера сотрудников',
                        "CODE"            => 'tablet',
                        "LIST_PAGE_URL"   => '',
                        "DETAIL_PAGE_URL" => '',
                        "IBLOCK_TYPE_ID"  => $arType['ID'],
                        "SITE_ID"         => array('s1'),
                        "SORT"            => 500,
                        "GROUP_ID"        => Array("2" => "R"),
                        "INDEX_ELEMENT"   => "N",
                        "INDEX_SECTION"   => "N",
                        "VERSION"         => 1,
                    )
                );

                $ibp = new CIBlockProperty();
                $ibp->Add(
                    array(
                        "NAME"          => "Номер",
                        "ACTIVE"        => "Y",
                        "SORT"          => "100",
                        "CODE"          => "TABLET",
                        "PROPERTY_TYPE" => "S",
                        "IS_REQUIRED"   => "Y",
                        "IBLOCK_ID"     => $arIblock['ID'],
                    )
                );
                $ibp->Add(
                    array(
                        "NAME"          => "Должность",
                        "ACTIVE"        => "Y",
                        "SORT"          => "200",
                        "CODE"          => "DOLZH",
                        "PROPERTY_TYPE" => "S",
                        "IS_REQUIRED"   => "N",
                        "IBLOCK_ID"     => $arIblock['ID'],
                    )
                );
                $ibp->Add(
                    array(
                        "NAME"          => "Город",
                        "ACTIVE"        => "Y",
                        "SORT"          => "300",
                        "CODE"          => "CITY",
                        "PROPERTY_TYPE" => "S",
                        "IS_REQUIRED"   => "N",
                        "IBLOCK_ID"     => $arIblock['ID'],
                    )
                );
                $ibp->Add(
                    array(
                        "NAME"           => "Магазин",
                        "ACTIVE"         => "Y",
                        "SORT"           => "400",
                        "CODE"           => "SHOP",
                        "PROPERTY_TYPE"  => "E",
                        'USER_TYPE'      => 'JmlShopElements',
                        "IS_REQUIRED"    => "Y",
                        "IBLOCK_ID"      => $arIblock['ID'],
                        "LINK_IBLOCK_ID" => $shopsIblockId,
                        "MULTIPLE_CNT"   => 1,
                    )
                );
            }
        }


        return true;
    }

    function UnInstallDB()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('sale');
        Loader::includeModule('highloadblock');

        \COption::RemoveOption("jamilco.omni", "autoload_module");

        // данные для Omni Tablet
        /*
        // удалим группу пользователей
        $gr = GroupTable::getList(array('filter' => array('STRING_ID' => 'tablet')));
        if ($arGroup = $gr->Fetch()) {
            GroupTable::Delete($arGroup['ID']);
        }
        */

        // удалим свойства пользователей
        $userType = new CUserTypeEntity();
        $res = CUserTypeEntity::getList(array(), array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_SHOP'));
        if ($arProp = $res->Fetch()) $userType->Delete($arProp['ID']);
        /*
        $res = CUserTypeEntity::getList(array(), array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_TABLET_ID'));
        if ($arProp = $res->Fetch()) $userType->Delete($arProp['ID']);
        */

        // удалим группу свойств заказа и сами свойства
        $rsOrder = \CSaleOrderProps::GetList(array(), array('CODE' => array('OMNI_TABLET_ID', 'OMNI_CHANNEL')));

        while ($arrOrder = $rsOrder->Fetch()) {
            \CSaleOrderProps::Delete($arrOrder['ID']);
        }

        $rsOrderPropsGroup = \CSaleOrderPropsGroup::GetList(array(), array('PERSON_TYPE_ID' => 1, 'NAME' => 'Omni'));
        while ($arOrderPropsGroup = $rsOrderPropsGroup->Fetch()) {
            \CSaleOrderPropsGroup::Delete($arOrderPropsGroup['ID']);
        }

        return true;
    }
}