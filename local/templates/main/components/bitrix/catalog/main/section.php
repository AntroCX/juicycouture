<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

if($_REQUEST['TAG_PAGE'] == 'Y') {
    include "filter.php";
    return;
}

$this->setFrameMode(true);

if (!isset($arParams['FILTER_VIEW_MODE']) || (string)$arParams['FILTER_VIEW_MODE'] == '') {
    $arParams['FILTER_VIEW_MODE'] = 'VERTICAL';
}
$arParams['USE_FILTER'] = (isset($arParams['USE_FILTER']) && $arParams['USE_FILTER'] == 'Y' ? 'Y' : 'N');

$isVerticalFilter = ('Y' == $arParams['USE_FILTER'] && $arParams["FILTER_VIEW_MODE"] == "VERTICAL");
$isSidebar = ($arParams["SIDEBAR_SECTION_SHOW"] == "Y" && isset($arParams["SIDEBAR_PATH"]) && !empty($arParams["SIDEBAR_PATH"]));
$isFilter = ($arParams['USE_FILTER'] == 'Y');

global $Rees46_CatalogSectionId;
$Rees46_CatalogSectionId = $arResult["VARIABLES"]["SECTION_ID"];

if ($GLOBALS['APPLICATION']->GetCurPage() == '/catalog/gifts/') {
    include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_gifts.php");
} else {
    if ($_REQUEST['ajax'] == 'Y') $APPLICATION->RestartBuffer(); // ajax запрос

    if ($arParams['SEF_FOLDER'] == '/blackfriday/') {
        include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_blackfriday.php");
    } elseif ($arParams['SEF_FOLDER'] == '/preorder/') {
        include($_SERVER["DOCUMENT_ROOT"] . "/" . $this->GetFolder() . "/preorder.php");
    }elseif ($arParams['SEF_FOLDER'] == '/sale/') {
        include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_sale.php");
    } elseif ($arParams['SEF_FOLDER'] == '/new/') {
        include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_new.php");
    } elseif ($arParams['SEF_FOLDER'] == '/brand/') {
        include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_brand.php");
    } else {
        include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_vertical.php");
    }

    if ($_REQUEST['ajax'] == 'Y') die(); // ajax запрос
}
?>
<link rel="stylesheet" type="text/css" href="/local/blocks/b-catalog-detail/b-catalog-detail.css?<?=md5_file($_SERVER["DOCUMENT_ROOT"]."/local/blocks/b-catalog-detail/b-catalog-detail.css")?>">
<script src="/local/blocks/b-fastview/b-fastview.js?<?=md5_file($_SERVER["DOCUMENT_ROOT"]."/local/blocks/b-fastview/b-fastview.js")?>"></script>
<script src="/local/blocks/b-shops/b-shops.min.js"></script>
