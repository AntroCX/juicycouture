<?
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);
Class jamilco_googlecatalog extends CModule
{
	function __construct()
	{
		$arModuleVersion = array();
		include(__DIR__."/version.php");

        $this->MODULE_ID = 'jamilco.googlecatalog';
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("JAMILCO_GOOGLECATALOG_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("JAMILCO_GOOGLECATALOG_MODULE_DESC");

		$this->PARTNER_NAME = Loc::getMessage("JAMILCO_GOOGLECATALOG_PARTNER_NAME");
		$this->PARTNER_URI = Loc::getMessage("JAMILCO_GOOGLECATALOG_PARTNER_URI");

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
        global $DB, $APPLICATION;
        Loader::includeModule('iblock');

        // проверим существование инфоблока
        // в случае отстутсвия добавим тип  и пользоват. поле
        $res = CIBlockType::GetByID('google_product_category');
        $arIblockType = $res->GetNext();

        if(!$arIblockType){

            $arFields = Array(
                'ID'=>'google_product_category',
                'SECTIONS'=>'Y',
                'IN_RSS'=>'N',
                'SORT'=>100,
                'LANG'=>Array(
                    'en'=>Array(
                        'NAME'=>'Google product category',
                        'SECTION_NAME'=>'Sections',
                        'ELEMENT_NAME'=>''
                    ),
                    'ru'=>Array(
                        'NAME'=>'Google product category',
                        'SECTION_NAME'=>'Раздел',
                        'ELEMENT_NAME'=>''
                    )
                )
            );

            $obBlocktype = new CIBlockType;
            $DB->StartTransaction();
            $res = $obBlocktype->Add($arFields);
            if(!$res) {
                $DB->Rollback();
                $APPLICATION->ThrowException($obBlocktype->LAST_ERROR);
            }
            else
                $DB->Commit();
        }

        $res = CIBlock::GetList(array(), array("TYPE" => 'google_product_category'));
        $arIblock = $res->GetNext();
        if(!$arIblock){

            $ib = new CIBlock;
            $arFields = Array(
                "ACTIVE" => 'Y',
                "NAME" => 'Google product category',
                "CODE" => 'google_product_category',
                "IBLOCK_TYPE_ID" => 'google_product_category',
                "SITE_ID" => "s1",
                "GROUP_ID" => Array("2"=>"D", "8"=>"R")
            );
            $DB->StartTransaction();
            $ID = $ib->Add($arFields);
            if(!$ID){
                $DB->Rollback();
                $APPLICATION->ThrowException($ib->LAST_ERROR);
            }
            else
                $DB->Commit();
        }

        if($ID) {
            $arFields = Array(
                "ENTITY_ID" => 'IBLOCK_'.$ID.'_SECTION',
                "FIELD_NAME" => "UF_GOOGLE_ID",
                "USER_TYPE_ID" => "integer"
            );
            $obUserField = new CUserTypeEntity;
            $obUserField->Add($arFields);
        }
    }

    function UnInstallDB()
    {
        Loader::includeModule('iblock');

        $res = CIBlock::GetList(array(), array("TYPE" => 'google_product_category'));
        $arIblock = $res->GetNext();
        if($arIblock) {

            if (!CIBlock::Delete($arIblock["ID"])) {
                echo CAdminMessage::ShowMessage(
                    array(
                        'DETAILS' => '',
                        'TYPE' => 'ERROR',
                        'MESSAGE' => Loc::getMessage("JAMILCO_GOOGLECATALOG_INSTALL_ERROR_IBLOCK_DELETE"),
                        'HTML' => true
                    )
                );
            }
        }

        if(!CIBlockType::Delete('google_product_category')) {
            echo CAdminMessage::ShowMessage(
                array(
                    'DETAILS' => '',
                    'TYPE' => 'ERROR',
                    'MESSAGE' => Loc::getMessage("JAMILCO_GOOGLECATALOG_INSTALL_ERROR_IBLOCK_TYPE_DELETE"),
                    'HTML' => true
                )
            );
        }

        Option::delete($this->MODULE_ID);
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

        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bitrix/themes/.default/icons/jamilco.googlecatalog');

        \Bitrix\Main\IO\File::deleteFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/themes/.default/jamilco.googlecatalog.css');

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
            $APPLICATION->ThrowException(Loc::getMessage("JAMILCO_GOOGLECATALOG_INSTALL_ERROR_VERSION"));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage("JAMILCO_GOOGLECATALOG_INSTALL_TITLE"), $this->GetPath()."/install/step.php");
	}

	function DoUninstall()
	{
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if($request["step"]<2)
        {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("JAMILCO_GOOGLECATALOG_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep1.php");
        }
        elseif($request["step"]==2)
        {
            $this->UnInstallFiles();

            if($request["savedata"] != "Y")
                $this->UnInstallDB();

            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage("JAMILCO_GOOGLECATALOG_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep2.php");
        }
	}

}
?>