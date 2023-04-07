<?
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);
Class jamilco_goodsreport extends CModule
{
	function __construct()
	{
		$arModuleVersion = array();
		include(__DIR__."/version.php");

        $this->MODULE_ID = 'jamilco.goodsreport';
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("JAMILCO_GOODSREPORT_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("JAMILCO_GOODSREPORT_MODULE_DESC");

		$this->PARTNER_NAME = Loc::getMessage("JAMILCO_GOODSREPORT_PARTNER_NAME");
		$this->PARTNER_URI = Loc::getMessage("JAMILCO_GOODSREPORT_PARTNER_URI");

        $this->MODULE_SORT = 1;
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS='Y';
        $this->MODULE_GROUP_RIGHTS = "Y";
	}

    //Определяем место размещения модуля
    public function GetPath($notDocumentRoot=false)
    {
        if($notDocumentRoot)
            return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
        else
            return dirname(__DIR__);
    }

    //Проверяем что система поддерживает D7
    public function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
    }

    function InstallDB()
    {
    }

    function UnInstallDB()
    {
    }

	function InstallFiles($arParams = array())
	{

        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin'))
        {
            $path=$this->GetPath()."/install/themes";

            if(\Bitrix\Main\IO\Directory::isDirectoryExists($path))
                CopyDirFiles($path, $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
            else
                throw new \Bitrix\Main\IO\InvalidPathException($path);

            CopyDirFiles($this->GetPath() . "/install/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin", true);

        }

        return true;
	}

	function UnInstallFiles()
	{

        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bitrix/themes/.default/icons/jamilco.goodsreport');

        \Bitrix\Main\IO\File::deleteFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/themes/.default/jamilco.goodsreport.css');

        DeleteDirFiles($this->GetPath() . "/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");

        return true;
	}

	function DoInstall()
	{
		global $APPLICATION;
        if($this->isVersionD7())
        {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

            $this->InstallDB();
            $this->InstallFiles();
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage("JAMILCO_GOODSREPORT_INSTALL_ERROR_VERSION"));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage("JAMILCO_GOODSREPORT_INSTALL_TITLE"), $this->GetPath()."/install/step.php");
	}

	function DoUninstall()
	{
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if($request["step"]<2)
        {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("JAMILCO_GOODSREPORT_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep1.php");
        }
        elseif($request["step"]==2)
        {
            $this->UnInstallFiles();

            if($request["savedata"] != "Y")
                $this->UnInstallDB();

            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage("JAMILCO_GOODSREPORT_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep2.php");
        }
	}

}
?>