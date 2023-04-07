<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
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

if ($arResult["FILE"] <> '') {?>
    <div class="row row-seo row-seo-<?=$arParams['PLACE']?>">
        <div class="col-sm-12 col-lg-12">
        <?include($arResult["FILE"]);?>
        </div>
    </div>
    <?
}