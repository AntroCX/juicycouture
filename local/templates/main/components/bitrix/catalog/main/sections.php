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

$this->setFrameMode(true);

// ajax запрос
if ($_REQUEST['ajax'] == 'Y') $APPLICATION->RestartBuffer();

if($_REQUEST['TAG_PAGE'] == 'Y') {
    include "filter.php";
    return;
}

if ($APPLICATION->GetCurPage() == '/blackfriday/') {
    include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_blackfriday.php");
} elseif ($APPLICATION->GetCurPage() == '/preorder/') {
    include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/preorder.php");
} elseif ($APPLICATION->GetCurPage() == '/sale/') {
    include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_sale.php");
} elseif ($APPLICATION->GetCurPage() == '/brand/') {
    include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_brand.php");
} elseif ($APPLICATION->GetCurPage() == '/new/') {
    include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_new.php");
} elseif ($APPLICATION->GetCurPage() == '/vip-presale/') {
    include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_vertical.php");
} else {
    $rsSection = \CIBlockSection::GetList(array('SORT' => 'ASC'), array('DEPTH_LEVEL' => 1, 'ACTIVE' => 'Y'), false, array(), array('nTopCount' => 1));
    $arrSection = $rsSection->GetNext();
    LocalRedirect($arrSection['SECTION_PAGE_URL'], false, '301');
}

// ajax запрос
if ($_REQUEST['ajax'] == 'Y') die();