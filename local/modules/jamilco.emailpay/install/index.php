<?php
Class jamilco_emailpay extends CModule
{
    var $MODULE_ID = "jamilco.emailpay";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $MODULE_CSS;
    var $MAIL_EVENT_NAME = "JC_SET_STATUS_FOR_EMAIL_PAY";

    function __construct()
    {
        \Bitrix\Main\Loader::includeModule('sale');

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
        $this->MODULE_NAME = "jamilco.emailpay – модуль для реализации онлайн оплаты из email";
        $this->MODULE_DESCRIPTION = "Модуль для jamilco";
    }

    function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->registerEventHandler(
            'main',
            'OnBeforeProlog',
            $this->MODULE_ID,
            'Jamilco\\EmailPay\\Common',
            'init'
        );

        $eventManager->registerEventHandler(
            'sale',
            'OnSaleStatusOrder',
            $this->MODULE_ID,
            'Jamilco\\EmailPay\\Handler',
            'onSaleStatusOrderMail'
        );

        return true;
    }

    function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnBeforeProlog',
            $this->MODULE_ID,
            'Jamilco\\EmailPay\\Common',
            'init'
        );

        $eventManager->unRegisterEventHandler(
            'sale',
            'OnSaleStatusOrder',
            $this->MODULE_ID,
            'Jamilco\\EmailPay\\Handler',
            'onSaleStatusOrderMail'
        );

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
        $this->AddOrderStatus();
        $this->AddEmailEvent();
        \COption::SetOptionInt($this->MODULE_ID, "autoload_module", 1);
        return true;
    }

    function UnInstallDB($arParams = array())
    {
        \COption::RemoveOption($this->MODULE_ID, "autoload_module");
        \COption::RemoveOption($this->MODULE_ID, "action_status_id");
        $this->RemoveEmailEvent();
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

    function AddOrderStatus()
    {
        $arStatusLang = [];

        $dbLang = \CLangAdmin::GetList(
            $by = "sort",
            $order = "asc",
            ["ACTIVE" => "Y"]
        );

        while ($arLang = $dbLang->Fetch()) {
            $arStatusLang[] = [
                'LID' => $arLang["LID"],
                'NAME' => 'Онлайн оплата по уведомлению',
                'DESCRIPTION' => 'После перевода в статус отправляется уведомление на email для онлайн оплаты'
            ];
        }

        $new_status = array(
            'ID' => 'EP',
            'SORT' => 1000,
            'LANG' => $arStatusLang
        );

        $arStatus = \CSaleStatus::GetByID($new_status['ID']);

        if (!$arStatus) {

            if ($statusID = \CSaleStatus::Add($new_status)) {
                \COption::SetOptionString($this->MODULE_ID, "action_status_id", $statusID);
            }
        }
    }

    function AddEmailEvent()
    {
        $obEventType = new \CEventType;

        $eventId = $obEventType->Add([
            "LID"           => "ru",
            "EVENT_NAME"    => $this->MAIL_EVENT_NAME,
            "NAME"          => "Установлен статус для онлайн оплаты заказа",
            "DESCRIPTION"   => "
    #EMAIL_TO# - EMail получателя сообщения (#OWNER_EMAIL#)
    #ORDER_ID# - Идентификатор заказа
    #LINK_TO_PAY# - Ссылка на страницу оплаты
    #NAME# - ФИО
    #CUR_DATE# - Дата заказа
    "
        ]);

        if ($eventId) {
            $obEvenetMessage = new \CEventMessage;

            $hoverColor = "'#ab1229'";
            $mainColor = "'#e21836'";

            $obEvenetMessage->Add([
                "ACTIVE" => "Y",
                "EVENT_NAME" => $this->MAIL_EVENT_NAME,
                "LID" => "s1",
                "EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
                "EMAIL_TO" => "#EMAIL_TO#",
                //"BCC" => "#BCC#",
                "SUBJECT" => "Оплата заказа",
                "BODY_TYPE" => "html",
                "MESSAGE" => 'Здравствуйте, #NAME#!<br />
У Вас есть неоплаченный заказ #ORDER_ID# от #CUR_DATE#<br />
Для оплаты заказа вы можете пройти по следующей ссылке<br />
<a href="#LINK_TO_PAY#" onmouseover="this.style.borderColor=' . $hoverColor . '" onmouseout="this.style.borderColor=' . $mainColor . '" style="color: #ffffff; background-color: #e21836; border: 3px solid #e21836; text-decoration: none; display: inline-block; padding: 3px 6px; margin-top: 10px;">Оплатить</a>
'
            ]);

            \COption::SetOptionString($this->MODULE_ID, "mail_event_name", $this->MAIL_EVENT_NAME);
        }
    }

    function RemoveEmailEvent()
    {
        $dbMess = \CEventMessage::GetList(
            $by = "site_id",
            $order = "desc",
            ["TYPE_ID" => $this->MAIL_EVENT_NAME]
        );

        while($arMess = $dbMess->GetNext())
        {
            \CEventMessage::Delete($arMess["ID"]);
        }

        $obEventType = new \CEventType;
        $obEventType->Delete($this->MAIL_EVENT_NAME);

        \COption::RemoveOption($this->MODULE_ID, "mail_event_name");
    }
}
?>