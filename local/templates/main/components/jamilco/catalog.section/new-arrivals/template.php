<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Web\Json;

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
<?
if (!empty($arResult['ITEMS']))
{?>
    <script>
        var newArrivalItems = <?= Json::encode($arResult['JS_OBJ']['windowDataLayer']) ?>;
    </script>
	<!-- новые поступления -->
	<div class="container hidden-xs">
		<div class="b-recommendation b-section b-recommendation_new">
			<div class="b-section__title-wrapper text-center">
				<h2 class="b-section__title">Новые поступления</h2>
			</div>
			<div class="b-recommendation__goods">
				<?foreach ($arResult['ITEMS'] as $id => $arItem):?>
				<div>
          <?php /** DigitalDataLayer class="ddl_product" data-product-id="..." data-list-id="new_arrivals" */ ?>
					<div class="b-catalog__goods-item ddl_product" data-product-id="<?=$arItem['ID']?>" data-list-id="new_arrivals">
						<div class="b-catalog__goods-item-wrapper">
							<ul class="b-catalog__goods-item-wrapper-sku">
								<?$firstSKU = true?>
								<?foreach ($arItem['SKU_ITEMS'] as $arSKU):?>
									<li class="b-catalog__goods-item-wrapper-sku-element <?if($firstSKU):?>active<?$firstSKU = false?><?endif?>" data-sku="<?=$arSKU['ID']?>">
										<div class="b-catalog__goods-item-wrapper-sku-element-wrapper">
											<img data-src="<?=$arSKU['FIRST_PICTURE']['src']?>" width="320" height="399" class="lzy_img" data-product-id="<?=$arItem['ID']?>">
											<img src="<?=$arSKU['SECOND_PICTURE']['src']?>" width="320" height="399">
                      <?php /** DigitalDataLayer class="ddl_product_link" data-product-id="..." data-list-id="new_arrivals" */ ?>
											<a class="b-catalog__goods-item-link ddl_product_link" href="<?=$arSKU['URL']?>" data-product-id="<?=$arItem['ID']?>" data-list-id="new_arrivals"
                                               onclick="window.GENERAL.catalog.dataLayerClicks(this, newArrivalItems)"
                                            ></a>
											<?/*?><a data-href="<?=$arSKU['URL']?>" class="b-catalog__goods-item-quick hidden-xs hidden-sm ddl_product_link" data-product-id="<?=$arItem['ID']?>" data-list-id="new_arrivals">Быстрый просмотр</a><?*/?>
											<?if($arSKU['VIDEO']):?>
												<a href="#video" class="b-catalog__goods-item-video">Видео</a>
											<?endif?>
										</div>
										<div class="b-catalog__goods-item-name">
											<?=$arItem['NAME']?>
										</div>
										<div class="b-catalog__goods-item-price">
                                            <?if($arSKU['PRICE_DISCOUNT']) {?>
												<span class="price-sale"><?=$arSKU['PRICE_DISCOUNT']?></span>
												<span class="price-base"><?=$arSKU['PRICE']?></span>
                                            <?} else {?>
												<span class="price-base"><?=$arSKU['PRICE']?></span>
                                            <? } ?>
										</div>
									</li>
								<?endforeach;?>
							</ul>
						</div>
						<div class="b-catalog__goods-item-colors">
							<?$firstSKU = true?>
							<?foreach ($arItem['SKU_ITEMS'] as $arSKU):?>
								<a class="b-catalog__goods-item-colors-item <?if($firstSKU):?>active<?$firstSKU = false?><?endif?>" data-href="<?=$arSKU['ID']?>">
									<img src="<?=$arSKU['COLOR_IMAGE']?>" width="20" height="20">
								</a>
							<?endforeach;?>
						</div>
					</div>
				</div>
				<?endforeach;?>
			</div>
		</div>
	</div>
	<!-- новые поступления -->
	<div class="modal fade b-catalog__modal-quick-view" id="quickViewProduct" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"></span></button>
			</div>
			<div class="modal-content">

			</div>
		</div>
	</div>
<?}
?>
<script>
    const imageObserver = new IntersectionObserver((entries, imgObserver) => {
        var productIds = [];
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const lazyImage = entry.target;
                productIds.push(lazyImage.getAttribute('data-product-id'));
                lazyImage.src = lazyImage.dataset.src;
                lazyImage.classList.remove("lzy_img");
                imgObserver.unobserve(lazyImage);
            }
        });
        window.GENERAL.catalog.dataLayerImpressions(productIds, mainThreeItems);
    });
    const arr = document.querySelectorAll('img.lzy_img');
    arr.forEach((v) => {
        imageObserver.observe(v);
    })
</script>
