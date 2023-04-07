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
<!-- баннер главной -->
<!--<div class="b-mainpage-banner" style="background: url('<?=$arResult['ITEMS'][0]['PREVIEW_PICTURE']['SRC']?>')">
	<div class="b-mainpage-banner__content">
		<div class="b-mainpage-banner__text">
			<?=$arResult['ITEMS'][0]['NAME']?>
		</div>
		<div class="b-mainpage-banner__buttons">
			<?foreach ($arResult['ITEMS'][0]['DISPLAY_PROPERTIES']['BUTTONS']['VALUE'] as $key => $button):?>
				<a href="<?=$arResult['ITEMS'][0]['DISPLAY_PROPERTIES']['BUTTONS']['DESCRIPTION'][$key]?>" class="btn btn-primary"><?=$button?></a>
			<?endforeach;?>
		</div>
	</div>
</div>-->
<!-- ! баннер главной -->

<div id="b-mainpage-banner-carousel" class="b-mainpage-banner-carousel carousel slide" data-ride="carousel">

    <!-- Indicators -->
    <?if(count($arResult['ITEMS'])>1):?>
        <ol class="carousel-indicators">
            <?foreach ($arResult['ITEMS'] as $key => $arItem):?>
                <li data-target="#b-mainpage-banner-carousel" data-slide-to="<?=$key?>" class="<?if($key == 0):?>active<?endif;?>"></li>
            <?endforeach;?>
        </ol>
    <?endif;?>
    <!-- Wrapper for slides -->
    <div class="carousel-inner" role="listbox">

        <?foreach ($arResult['ITEMS'] as $key => $arItem):?>
            <?php /** DigitalDataLayer data-campaign-id="...", class="ddl_campaign" */ ?>
            <div class="item <? if ($key == 0): ?>active<? endif; ?> ddl_campaign" data-campaign-id="<?= $arItem['DDL_CAMPAIGN_ID'] ?>">
                <a <?= ($arItem['DISPLAY_PROPERTIES']['HREF']['VALUE']) ? 'href="'.$arItem['DISPLAY_PROPERTIES']['HREF']['VALUE'].'"' : ''; ?>
                    class="b-mainpage-banner"
                    style="background: url('<?= $arItem['PREVIEW_PICTURE']['SRC'] ?>')"
                    data-bg0="<?= $arItem['PREVIEW_PICTURE']['SRC'] ?>"
                    data-bg1="<?= $arItem['DISPLAY_PROPERTIES']['TABLET']['FILE_VALUE']['SRC'] ?>"
                    data-bg2="<?= $arItem['DISPLAY_PROPERTIES']['MOBILE']['FILE_VALUE']['SRC'] ?>"
                    >
                    <div class="b-mainpage-banner__content">
                        <div class="b-mainpage-banner__text">
                            <div class="b-mainpage-banner__text-title"
                                 style='color: #<?= $arItem['PROPERTIES']['TITLE_COLOR']['VALUE'] ? $arItem['PROPERTIES']['TITLE_COLOR']['VALUE'] : "000"; ?>'
                                ><?= $arItem['PROPERTIES']['TITLE_TEXT']['VALUE'] ?></div>
                            <div class="b-mainpage-banner__text-subtitle"
                                 style='color: #<?= $arItem['PROPERTIES']['SUBTITLE_COLOR']['VALUE'] ? $arItem['PROPERTIES']['SUBTITLE_COLOR']['VALUE'] : "000"; ?>'
                                ><?= $arItem['PROPERTIES']['SUBTITLE_TEXT']['VALUE'] ?></div>
                        </div>
                    </div>
                </a>
                <div class="b-mainpage-banner__buttons">
                    <? foreach ($arItem['DISPLAY_PROPERTIES']['BUTTONS']['VALUE'] as $key => $button): ?>
                        <?php /** DigitalDataLayer data-campaign-id="...", class="ddl_campaign_link" */ ?>
                        <a href="<?= $arItem['DISPLAY_PROPERTIES']['BUTTONS']['DESCRIPTION'][$key] ?>" class="btn btn-primary ddl_campaign_link" data-campaign-id="<?= $arItem['DDL_CAMPAIGN_ID'] ?>"
                           style='color: #<?= $arItem['PROPERTIES']['BUTTON_TEXT_COLOR']['VALUE'] ? $arItem['PROPERTIES']['BUTTON_TEXT_COLOR']['VALUE'] : "000"; ?>;
                               background-color: #<?= $arItem['PROPERTIES']['BUTTON_COLOR']['VALUE'] ? $arItem['PROPERTIES']['BUTTON_COLOR']['VALUE'] : "fff"; ?>;
                               border-color: #<?= $arItem['PROPERTIES']['BUTTON_COLOR']['VALUE'] ? $arItem['PROPERTIES']['BUTTON_COLOR']['VALUE'] : "fff"; ?>;'
                            ><?= $button ?></a>
                    <? endforeach; ?>
                </div>
            </div>
        <?endforeach;?>
    </div>

    <!-- Controls -->
    <?if(count($arResult['ITEMS'])>1):?>
        <a class="left carousel-control" href="#b-mainpage-banner-carousel" role="button" data-slide="prev">
            <span class="glyphicon glyphicon-chevron-left fa fa-angle-left" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="right carousel-control" href="#b-mainpage-banner-carousel" role="button" data-slide="next">
            <span class="glyphicon glyphicon-chevron-right fa fa-angle-right" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    <?endif;?>
</div>

