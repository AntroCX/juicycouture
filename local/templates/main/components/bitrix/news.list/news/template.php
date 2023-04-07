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

<h1>Новости</h1>
<div class="b-news">
	<?foreach ($arResult['ITEMS'] as $arItem):?>
	<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="b-news__item">
		<div class="row">
			<div class="col-sm-3">
				<div class="b-news__item-image">
					<img width="220" src="<?=$arItem['RESIZE_PICTURE']['src']?>">
				</div>
			</div>
			<div class="col-sm-9">
				<div class="b-news__item-date"><?=$arItem['ACTIVE_FROM']?></div>
				<div class="b-news__item-name"><?=$arItem['NAME']?></div>
				<div class="b-news__item-preview">
					<?=$arItem['PREVIEW_TEXT']?>
				</div>
			</div>
		</div>
	</a>
	<?endforeach;?>
</div>


<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
	<br /><?=$arResult["NAV_STRING"]?><br>
<?endif;?>

