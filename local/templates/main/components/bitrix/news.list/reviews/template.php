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
<!-- блок с отзывами -->
<?if(count($arResult['ITEMS']) > 0):?>
<div class="b-reviews b-section">
	<div class="b-section__title-wrapper">
		<h2 class="b-section__title">Отзывы покупателей</h2>
	</div>
	<div class="b-reviews__header">
		<div class="row">
			<div class="col-xs-6">
				<?if($arResult['NAV_RESULT']->nSelectedCount > 0):?>
				<div class="b-reviews__count"><?=$arResult['NAV_RESULT']->nSelectedCount?> отзыв</div>
				<?else:?>
				<div class="b-reviews__count">Отзывы отсутствуют или находятся на модерации</div>
				<?endif?>
			</div>
			<div class="col-xs-6 text-right">
				<a class="btn btn-primary b-reviews__btn-write" data-toggle="modal" data-target="#writeReview">Написать отзыв</a> <!--  -->
			</div>
		</div>
	</div>
	<?foreach ($arResult['ITEMS'] as $arItem):?>
	<div class="b-reviews__item">
		<div class="row">
			<div class="col-xs-1 hidden-xs">
				<div class="b-reviews__user-image"></div>
			</div>
			<div class="col-sm-7">
				<div class="b-reviews__item-header">
					<div class="b-reviews__item-stars">
						<div class="b-reviews__item-stars-item"></div>
						<div class="b-reviews__item-stars-item"></div>
						<div class="b-reviews__item-stars-item"></div>
						<div class="b-reviews__item-stars-item"></div>
						<div class="b-reviews__item-stars-item"></div>
					</div>
					<span><?=$arItem['PROPERTIES']['NAME']['VALUE']?></span>
					<span class="b-reviews__item-date"><?=date('d.m.Y', strtotime($arItem['TIMESTAMP_X']))?></span>
				</div>
				<div class="b-reviews__item-title">
					<?=$arItem['NAME']?>
				</div>
				<div class="b-reviews__item-text">
					<?=$arItem['PREVIEW_TEXT']?>
				</div>
				<!--<div class="b-reviews__item-rating">
					<span>Отзыв был полезен?</span>
					<span class="b-reviews__item-rating-selector">
                            <span class="b-reviews__item-rating-selector-yes">Да</span><span class="b-reviews__item-rating-selector-yes-count">7</span>
                            <span class="b-reviews__item-rating-selector-delimiter">|</span>
                            <span class="b-reviews__item-rating-selector-no">Нет</span><span class="b-reviews__item-rating-selector-no-count">9</span>
                        </span>
				</div>-->
			</div>
			<div class="col-md-4">
				<?if($arItem['PROPERTIES']['QUALITY']['VALUE']):?>
				<div class="b-reviews__line-rating">
					<div class="b-reviews__line-title">
						Качество товара
					</div>
					<div class="b-reviews__line-rating-score">
						<?for($i = 0; $i < $arItem['PROPERTIES']['QUALITY']['VALUE']; $i++):?>
							<span class="b-reviews__line-rating-score-item"></span>
						<?endfor?>
					</div>
				</div>
				<?endif;?>
				<?if($arItem['PROPERTIES']['TOTAL']['VALUE']):?>
				<div class="b-reviews__line-rating">
					<div class="b-reviews__line-title">
						Общая оценка
					</div>
					<div class="b-reviews__line-rating-score">
						<?for($i = 0; $i < $arItem['PROPERTIES']['TOTAL']['VALUE']; $i++):?>
						<span class="b-reviews__line-rating-score-item"></span>
						<?endfor;?>
					</div>
				</div>
				<?endif?>
			</div>
		</div>
	</div>
	<?endforeach;?>
</div>
<?endif;?>
<!-- ! блок с отзывами -->

