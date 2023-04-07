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


<?php if($arResult['SECTIONS']):?>
    <div class="panel panel-default b-filter__block   ">
    <div class="panel-heading b-filter__header" role="tab" id="headingSections">
        <a role="button" class="collapsed" data-toggle="collapse" href="#collapseSections" aria-expanded="false" aria-controls="collapseSections">
            Категория
        </a>
    </div>
    <div id="collapseSections" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingSections">
    <div class="panel-body b-filter__body">
        <?php foreach ($arResult['SECTIONS'] as $section):?>

                    <a class="section_filter" href="<?=$section['SECTION_PAGE_URL']?>" ><?=$section['NAME']?></a>

        <?php endforeach;?>
    </div>
    </div>
    </div>
<?php endif; ?>
