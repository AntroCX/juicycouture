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
if(BLACK_FRIDAY == 'Y' && strpos($APPLICATION->GetCurPage(false), '/sale/') !== false){
    $title = 'SALE';
}
else {
    if ($arResult['NAME']) $title = $arResult['NAME']; elseif ($arParams['NAME']) $title = $arParams['NAME'];
}
?>
<script>
    var mainThreeItems = <?= Json::encode($arResult['JS_OBJ']['windowDataLayer']) ?>;
</script>
	<div class="b-catalog__title">
		<h1><?=$title?></h1>
		<div class="b-catalog__title-count"><?=$arResult['NAV_RESULT']->nSelectedCount?> товаров</div>
	</div>


	<?/*=$arResult["NAV_STRING"];*/?>
<div class="container-fluid"><div class="row">
    <div class="b-catalog__filter col-sm-3">
        <?=$GLOBALS["FILTER_BLOCK_HTML"]?>
    </div>
	<div class="b-catalog__goods col-sm-9">
		<div class="row">
<?if (!empty($arResult['ITEMS'])):?>
	<?foreach ($arResult['ITEMS'] as $id => $arItem):?>
        <? if (!$arItem['SKU_ITEMS']) continue; ?>
			<div class="col-xs-6 col-sm-4">
        <?php /** DigitalDataLayer class="ddl_product" data-product-id="..." data-list-id="main" */ ?>
				<div class="b-catalog__goods-item ddl_product" data-product-id="<?=$arItem['ID']?>" data-list-id="main" style="position: relative;">
                    <div class="b-catalog__goods-item-item">
                        <div class="b-catalog__goods-item-wrapper">
                            <ul class="b-catalog__goods-item-wrapper-sku">
                                <?$firstSKU = true?>
                                <?foreach ($arItem['SKU_ITEMS'] as $arSKU):?>
                                <li class="b-catalog__goods-item-wrapper-sku-element <?if($firstSKU):?>active<?$firstSKU = false?><?endif?>" data-sku="<?=$arSKU['ID']?>">
                                    <div class="b-catalog__goods-item-icons">
                                        <?php
                                        if (!in_array(1165, $arItem['IBLOCK_SECTION_ID_ALL'])) {
                                            if ($arItem['PROPERTIES']['PREORDER']['VALUE'] == 'Y') {
                                                ?>
                                                <div class="icon icon_presale">Скоро в продаже</div>
                                                <?php
                                            }elseif ($arItem['PROPERTIES']['NEW']['VALUE'] == 'Y') {
                                                ?>
                                                <div class="icon icon_new"></div>
                                                <?php
                                            }

                                            if ($arItem['PROPERTIES']['SALE_3_2']['VALUE'] == 'Y') {
                                                ?>
                                                <div class="icon icon_sale">3=2</div>
                                                <?php
                                            }

                                            if ($arItem['PROPERTIES']['SALE20_30']['VALUE'] == 'Y') {
                                                ?>
                                                <div class="icon icon_supersale"></div>
                                                <?php
                                            } else if ($arSKU['PRICE_DISCOUNT_VALUE'] < $arSKU['PRICE_VALUE']) {
                                                ?>
                                                <div class="icon icon_sale">
                                                    -<?=round(($arSKU['PRICE_VALUE'] - $arSKU['PRICE_DISCOUNT_VALUE']) / $arSKU['PRICE_VALUE'] * 100)?>%
                                                </div>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <div class="icon_luxe-velour"></div>
                                            <?php
                                        }
                                        ?>
                                    </div>

                                    <div class="b-catalog__goods-item-wrapper-sku-element-wrapper">
                                        <img data-src="<?=$arSKU['FIRST_PICTURE']['src']?>" width="320" height="399" class="lzy_img" data-product-id="<?=$arItem['ID']?>">
                      <?php /** DigitalDataLayer class="ddl_product_link" data-product-id="..." data-list-id="main" */ ?>
                                        <a class="b-catalog__goods-item-link ddl_product_link" href="<?=$arSKU['URL']?>" data-product-id="<?=$arItem['ID']?>" data-list-id="main"
                                           onclick="window.GENERAL.catalog.dataLayerClicks(this, mainThreeItems)"
                                        ></a>
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
                        <?/*<div class="b-catalog__goods-item-colors">
                            <?$firstSKU = true?>
                            <?foreach ($arItem['SKU_ITEMS'] as $arSKU):?>
                                <a class="b-catalog__goods-item-colors-item <?if($firstSKU):?>active<?$firstSKU = false?><?endif?>" data-href="<?=$arSKU['ID']?>">
                                    <img src="<?=$arSKU['COLOR_IMAGE']?>" width="20" height="20">
                                </a>
                            <?endforeach;?>
                        </div>*/?>
                    </div>
                    <div class="b-catalog__goods-item-hover">
                        <div class="b-catalog__goods-item-wrapper">
                            <ul class="b-catalog__goods-item-wrapper-sku">
                                <?$firstSKU = true?>
                                <?foreach ($arItem['SKU_ITEMS'] as $arSKU):?>
                                    <li class="b-catalog__goods-item-wrapper-sku-element <?if($firstSKU):?>active<?$firstSKU = false?><?endif?>" data-sku="<?=$arSKU['ID']?>">
                                        <div class="b-catalog__goods-item-icons">
                                            <?php
                                            if (!in_array(1165, $arItem['IBLOCK_SECTION_ID_ALL'])) {
                                                if ($arItem['PROPERTIES']['PREORDER']['VALUE'] == 'Y') {
                                                    ?>
                                                    <div class="icon icon_presale">Скоро в продаже</div>
                                                    <?php
                                                }elseif ($arItem['PROPERTIES']['NEW']['VALUE'] == 'Y') {
                                                    ?>
                                                    <div class="icon icon_new"></div>
                                                    <?php
                                                }

                                                if ($arItem['PROPERTIES']['SALE_3_2']['VALUE'] == 'Y') {
                                                    ?>
                                                    <div class="icon icon_sale">3=2</div>
                                                    <?php
                                                }

                                                if ($arItem['PROPERTIES']['SALE20_30']['VALUE'] == 'Y') {
                                                    ?>
                                                    <div class="icon icon_supersale"></div>
                                                    <?php
                                                } else if ($arSKU['PRICE_DISCOUNT_VALUE'] < $arSKU['PRICE_VALUE']) {
                                                    ?>
                                                    <div class="icon icon_sale">
                                                        -<?=round(($arSKU['PRICE_VALUE'] - $arSKU['PRICE_DISCOUNT_VALUE']) / $arSKU['PRICE_VALUE'] * 100)?>%
                                                    </div>
                                                    <?php
                                                }
                                            } else {
                                                ?>
                                                <div class="icon_luxe-velour"></div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="b-catalog__goods-item-wrapper-sku-element-wrapper">
                                            <img src="<?=$arSKU['SECOND_PICTURE']['src']?>" width="320" height="399">
                                            <?php /** DigitalDataLayer class="ddl_product_link" data-product-id="..." data-list-id="main" */ ?>
                                            <a class="b-catalog__goods-item-link ddl_product_link" href="<?=$arSKU['URL']?>" data-product-id="<?=$arItem['ID']?>" data-list-id="main"
                                               onclick="window.GENERAL.catalog.dataLayerClicks(this, mainThreeItems)"
                                            ></a>
                                            <a href="<?=$arSKU['URL']?>" class="b-catalog__goods-item-quick hidden-xs hidden-sm" data-product-id="<?=$arItem['ID']?>" data-list-id="main"
                                               onclick="window.GENERAL.catalog.dataLayerClicks(this, mainThreeItems)"
                                            >Быстрый просмотр</a>
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
                                        <?if(!empty($arSKU['SIZES'])):?>
                                            <div class="b-catalog__sizes-list">
                                                <div class="b-catalog__sizes-ttl">Размер:</div>
                                                <?foreach($arSKU['SIZES'] as $offerId => $size):?>
                                                    <div class="b-catalog__sizes-item" data-offerid="<?=$offerId?>" data-url="" data-toggle="tooltip" data-placement="bottom" title="Добавить в корзину"><?=$size?></div>
                                                <?endforeach;?>
                                            </div>
                                        <?endif?>
                                    </li>
                                <?endforeach;?>
                            </ul>
                        </div>
                    </div>
                </div>
			</div>
	<?endforeach;?>
	<?else:?>

	<div class="text-center">
		<h4>К сожалению, товаров в данном разделе пока нет</h4>
	</div>

<?endif?>
		</div>
	</div></div>
</div>
	<?=$arResult["NAV_STRING"];?>
<br><br>
    <? if ($arResult['ID']) { ?>
        <div data-retailrocket-markup-block="5bd1bcb197a52525d8ad080c" data-category-id="<?= $arResult['ID'] ?>" data-stock-id="<?= \Jamilco\Main\Retail::getStoreName(true) ?>"></div>
    <? } elseif ($arParams['BLOCK'] == 'NEW') { ?>
        <div data-retailrocket-markup-block="5bd1bd0197a528207806f25a" data-stock-id="<?= \Jamilco\Main\Retail::getStoreName(true) ?>"></div>
    <? } elseif ($arParams['BLOCK'] == 'SALE') { ?>
        <div data-retailrocket-markup-block="5bd1bcfa97a528207806f259" data-stock-id="<?= \Jamilco\Main\Retail::getStoreName(true) ?>"></div>
    <? } ?>

<?if($arResult['DESCRIPTION']&&!$_REQUEST['PAGEN_1']):?>
<div class="b-catalog__description">
	<?=$arResult['DESCRIPTION']?>
</div>
<?endif?>
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

<script type="text/javascript">
    (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
        try { rrApi.categoryView(<?=$arParams['SECTION_ID']?>); } catch(e) {}
    })
</script>
