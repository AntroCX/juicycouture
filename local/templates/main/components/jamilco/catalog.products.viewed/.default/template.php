<?php

use \Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<? if (count($arResult['ITEMS'])): ?>
  <div class="b-section products-viewed">
      <div class="b-section__title-wrapper">
          <h2 class="b-section__title products-viewed__title"><?= Loc::getMessage('CPV_TITLE') ?></h2>
      </div>
      <div class="products-viewed-slider js-products-viewed-slider">
          <? foreach($arResult['ITEMS'] as $index => $item):
              $arOffer = false;
              foreach ($item['OFFERS'] as $offer) {
                  if ($offer['ID'] == $item['OFFER_ID_SELECTED']) $arOffer = $offer;
              }
              if (!$arOffer) $arOffer = $item['OFFERS'][0];
              ?>
               <a href="<?= $item['DETAIL_PAGE_URL'] ?>" class="products-viewed-item">
                    <div class="products-viewed-item__image">
                        <? if (isset($item['RESIZE_IMAGE'])): ?>
                            <img src="<?= $item['RESIZE_IMAGE']['src'] ?>" alt="">
                        <? endif ?>
                    </div>
                    <div class="products-viewed-item__name"><?= $item['NAME'] ?></div>
                    <div class="products-viewed-item__price">
                        <? if ($arOffer['ITEM_PRICES'][0]['BASE_PRICE'] > $arOffer['ITEM_PRICES'][0]['PRICE']): ?>
                          <span class="discount-price"><?= $arOffer['ITEM_PRICES'][0]['PRINT_PRICE'] ?> </span>
                          <span class="old-price"><?= $arOffer['ITEM_PRICES'][0]['PRINT_BASE_PRICE'] ?> </span>
                        <? else: ?>
                            <?= $arOffer['ITEM_PRICES'][0]['PRINT_PRICE'] ?>
                        <? endif ?>
                    </div>
                </a>
          <? endforeach ?>
      </div>
  </div>
<? endif ?>
