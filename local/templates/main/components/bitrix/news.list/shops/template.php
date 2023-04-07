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

<h1>Магазины</h1>
<div class="b-retail-shops">
	<div class="b-retail-shops__select">
		<select class="b-retail-shops__select-city">
			<option value="-1">Выберите город</option>
			<?foreach ($arResult['CITIES'] as $city):?>
				<option value="<?=$city?>"><?=$city?></option>
			<?endforeach;?>
		</select>
	</div>
</div>
<div class="b-retail-shops__map" data-zoom="3" id="YMapsID">

</div>
<div class="b-retail-shops__list">
	<div class="row">
		<?foreach ($arResult['ITEMS'] as $arItem):?>
            <?
            $shop_name = $arItem['NAME'];
            if($arItem['PROPERTIES']['ADDRESS']['VALUE'])
                $shop_name .= ', '.$arItem['PROPERTIES']['ADDRESS']['VALUE'];
            if($arItem['PROPERTIES']['PHONE']['VALUE'])
                $shop_name .= ', '.$arItem['PROPERTIES']['PHONE']['VALUE'];
            ?>
			<div class="col-sm-3">
				<div class="b-retail-shops__list-item i-yandex-map__item"
					 data-name="<?=$shop_name?>"
					 data-coords="<?=$arItem['PROPERTIES']['COORDS']['VALUE']?>"
					 data-city="<?=$arItem['PROPERTIES']['CITY']['VALUE']?>">
					<div class="b-retail-shops__list-city"><?=$arItem['PROPERTIES']['CITY']['VALUE']?></div>
					<div class="b-retail-shops__list-address"><?=$shop_name?></div>
					<a class="b-retail-shops__to-map">Показать на карте</a>
				</div>
			</div>
		<?endforeach;?>
	</div>
</div>
