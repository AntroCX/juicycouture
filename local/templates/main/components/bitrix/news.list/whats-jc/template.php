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
<!-- whats jc -->
<div class="container">
	<div class="b-tabs">
	<?/*
		<h4 class="b-tabs__title hidden-xs">Гид по покупкам</h4>
		
		<div class="b-tabs__nav hidden-xs">
			<?foreach ($arResult['ITEMS'] as $key => $arItem):?>
				<a class="b-tabs__nav-item <?if($key == 0):?>active<?endif?>"><?=$arItem['NAME']?></a>
			<?endforeach;?>
		</div>
		*/?>
		<div class="b-tabs__content">
			<?foreach ($arResult['ITEMS'] as $arItem):?>
        <?php /** DigitalDataLayer data-campaign-id="...", class="ddl_campaign" */ ?>
				<div class="b-tabs__content-item ddl_campaign <?=$arParams['TEXT_LEFT'] == 'Y'? 'b-tabs__content-item__text-left': ''?>" data-campaign-id="<?=$arItem['DDL_CAMPAIGN_ID']?>">
					<div class="row">
						<div class="col-sm-6 text-center">
							<img class="b-tabs__content-item-image" src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>">
						</div>
						<div class="col-sm-6">
							<div class="b-tabs__content-item-name">
								<?=$arItem['NAME']?>
							</div>
							<div class="b-tabs__content-item-description">
								<?=$arItem['PREVIEW_TEXT']?>
							</div>
							<div class="b-tabs__content-item-buttons text-center">
                <?php /** DigitalDataLayer data-campaign-id="...", class="ddl_campaign_link" */ ?>
								<a href="<?=$arItem['DISPLAY_PROPERTIES']['BUTTON']['DESCRIPTION']?>" class="btn btn-primary-black ddl_campaign_link" data-campaign-id="<?=$arItem['DDL_CAMPAIGN_ID']?>">
									<?=$arItem['DISPLAY_PROPERTIES']['BUTTON']['VALUE']?>
								</a>
							</div>
						</div>
					</div>
				</div>
			<?endforeach;?>
		</div>
	</div>
</div>
<!-- ! whats jc -->
