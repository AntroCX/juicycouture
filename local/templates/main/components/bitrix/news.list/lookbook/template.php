<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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
?>

<h1>Lookbook</h1>
<div class="b-lookbook">
	<br /><?=$arResult["NAV_STRING"]?><br>
	<div class="row nopadding">
		<?foreach ($arResult['ITEMS'] as $id => $arItem):?>
		<div class="col-xs-6 col-sm-4 nopadding">
			<div class="b-lookbook__item">
                <?if($arItem['PROPERTIES']['HREF']['VALUE']):?>
                <a href="<?=$arItem['PROPERTIES']['HREF']['VALUE']?>">
                <?endif;?>
                    <img src="<?=$arItem['RESIZE_PICTURE']['src']?>" alt="<?=$arItem['NAME']?>" title="<?=$arItem['NAME']?>">
                <?if($arItem['PROPERTIES']['HREF']['VALUE']):?>
                </a>
                <?endif;?>
			</div>
		</div>
		<?endforeach;?>
	</div>
</div>


<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
	<br /><?=$arResult["NAV_STRING"]?><br>
<?endif;?>

