<?php
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

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);
?>
<? if (!empty($arResult['ITEMS'])): ?>
<a name="looks"></a>
<div class="b-look b-section">
    <div class="b-section__title-wrapper">
        <h2 class="b-section__title">Купить образ</h2>
    </div>
	  <div class="b-catalog__goods js-look-slider">
	      <? foreach ($arResult['ITEMS'] as $id => $arItem): ?>
	          <div class="item-container">
                <div class="look-popup"></div>
		            <div class="b-catalog__goods-item" data-product-id="<?=$arItem['ID']?>">
			              <div class="b-catalog__goods-item-wrapper">
				                <ul class="b-catalog__goods-item-wrapper-sku">
					                  <? $firstSKU = true; ?>
                            <? foreach ($arItem['SKU_ITEMS'] as $arSKU): ?>
					                      <li class="b-catalog__goods-item-wrapper-sku-element <? if($firstSKU): ?>active<? $firstSKU = false ?><? endif ?>" data-sku="<?= $arSKU['ID'] ?>">
						                        <div class="b-catalog__goods-item-wrapper-sku-element-wrapper">
							                          <img src="<?= $arSKU['FIRST_PICTURE']['src'] ?>" width="320" height="399">
							                          <img src="<?= $arSKU['SECOND_PICTURE']['src'] ?>" width="320" height="399">
							                          <a class="b-catalog__goods-item-link" href="<?= $arSKU['URL'] ?>" data-product-id="<?= $arItem['ID'] ?>"></a>
						                        </div>
						                        <div class="b-catalog__goods-item-name"><?=$arItem['NAME']?></div>
						                        <div class="b-catalog__goods-item-price">
							                          <? if($arSKU['PRICE'] != $arSKU['PRICE_DISCOUNT']): ?>
							                              <span class="price-sale-look"><?= $arSKU['PRICE_DISCOUNT'] ?></span>
							                          <?endif?>
							                          <span class="price-base-look"><?= $arSKU['PRICE'] ?></span>
						                        </div>
					                      </li>
					                  <? endforeach ?>
				                </ul>
			              </div>
		            </div>
                <div style="text-align: center;"><a class="btn btn-primary js-to-basket" href="<?=$arSKU['URL']?>">Добавить в корзину</a></div>
	          </div>
	      <? endforeach ?>
	  </div>
</div>
<? endif ?>
