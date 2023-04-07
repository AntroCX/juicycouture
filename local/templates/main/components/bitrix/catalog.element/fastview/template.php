<?php

use \Bitrix\Catalog\Product\Price;
use \Bitrix\Catalog\RoundingTable;
use \Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

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
<?php
// RetailRocket
$storeName = \Jamilco\Main\Retail::getStoreName(true);
?>
<div class="b-catalog-detail">
        <div class="row" itemprop="offerDetails" itemscope itemtype="http://data-vocabulary.org/Offer">
            <meta itemprop="currency" content="RUB" />
            <meta itemprop="availability" content="<?if($arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['QUANTITY'] > 0):?>in_stock<?else:?>out_of_stock<?endif?>" />
            <div class="col-xs-12 visible-xs">
                <div class="b-catalog-detail__photos">
                    <?$first = true?>
                    <?foreach ($arResult['COLOR_IMAGES'] as $color => $arImages):?>
                        <div class="b-catalog-detail__photos-current <?if($first):?><?$first = false?>active<?endif?>" data-color="<?=$color?>">
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="b-catalog-detail__photos-list">
                                        <?foreach ($arImages as $image):?>
                                            <div class="b-catalog-detail__photos-list-item">
                                                <img src="<?=$image['SRC']?>"
                                                     title="<?=$arResult['NAME']?>"
                                                     alt="<?=$arResult['NAME']?>">
                                            </div>
                                        <?endforeach;?>
                                    </div>
                                </div>
                                <div class="col-xs-12 hidden-xs">
                                    <div class="b-catalog-detail__photos-list-nav">
                                        <?foreach ($arImages as $image):?>
                                            <div class="b-catalog-detail__photos-list-nav-item">
                                                <img src="<?=$image['SRC']?>"
                                                     title="<?=$arResult['NAME']?>"
                                                     alt="<?=$arResult['NAME']?>">
                                            </div>
                                        <?endforeach;?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?endforeach;?>
                </div>
            </div>
            <div class="col-sm-8 hidden-xs">
                <div class="b-catalog-detail__photos">
                    <?$first = true?>
                    <?foreach ($arResult['COLOR_IMAGES'] as $color => $arImages):?>
                        <div class="b-catalog-detail__photos-current <?if($first):?><?$first = false?>active<?endif?>" data-color="<?=$color?>">
                            <div class="row">
                                <div class="col-sm-2 hidden-xs">
                                    <div class="b-catalog-detail__photos-list-nav">
                                        <?foreach ($arImages as $image):?>
                                            <?if($image['SRC']):?>
                                                <div class="b-catalog-detail__photos-list-nav-item">
                                                    <img src="<?=$image['SRC']?>"
                                                         title="<?=$arResult['NAME']?>"
                                                         alt="<?=$arResult['NAME']?>">
                                                </div>
                                            <?endif?>
                                        <?endforeach;?>
                                    </div>
                                </div>
                                <div class="col-sm-10">
                                    <div class="b-catalog-detail__photos-list">
                                        <?foreach ($arImages as $image):?>
                                            <?if($image['SRC']):?>
                                                <div class="b-catalog-detail__photos-list-item">
                                                    <img src="<?=$image['SRC']?>"
                                                         title="<?=$arResult['NAME']?>"
                                                         alt="<?=$arResult['NAME']?>">
                                                </div>
                                            <?endif?>
                                        <?endforeach;?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?endforeach;?>
                </div>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h1><?=$arResult['NAME']?></h1>
                <span itemprop="price" class="hidden">
                    <?if($arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PRICES']['SALE']['PRINT_VALUE']){?>
                        <?=$arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PRICES']['SALE']['PRINT_VALUE']?>
                    <?} else {?>
                        <?=$arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PRICES']['BASE']['PRINT_VALUE']?>
                    <? } ?>
                </span>
                <div class="b-catalog-detail__price">
                    <span class="price-sale_no">
                        <?if($arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PRICES']['SALE']['PRINT_VALUE']){?>
                            <?=$arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PRICES']['SALE']['PRINT_VALUE']?>
                        <?}?>
                    </span>
                    <span class="price-base"><?=$arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PRICES']['BASE']['PRINT_VALUE']?></span>
                </div>
                <div class="b-catalog-detail__price-discount b-catalog-detail__price-discount_fast-view">
                    <div class="b-catalog-detail__price-discount-text">
                        <?php
                        $productPrice = $arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PRICES']['SALE']['VALUE'] ?: $arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PRICES']['BASE']['VALUE'];
                        $discountPrice = Price::roundPrice(1, ($productPrice * ONLINE_PAY_DISCOUNT), 'RUB');
                        ?>
                        (<?= \CCurrencyLang::CurrencyFormat($productPrice - $discountPrice, 'RUB') ?> при оплате онлайн, скидка <?= \CCurrencyLang::CurrencyFormat($discountPrice, 'RUB') ?>)
                        <div class="b-catalog-detail__price-discount-tip">
                            <div class="b-catalog-detail__price-discount-tip-icon js-fast-view-discount-tip">?</div>
                            <div class="b-catalog-detail__price-discount-tip-text">
                                <div class="tip-close"></div>
                                <b>Скидка <?= ONLINE_PAY_DISCOUNT * 100 ?>%, при оплате онлайн!</b><br>
                                <ol>
                                    <li>Действует при выбранном способе "Оплата онлайн"</li>
                                    <li>Рассчитывается автоматически</li>
                                    <li>Распространяется на весь заказ</li>
                                    <li>Действует для способов доставки:</li>
                                </ol>
                                <ul>
                                    <li>Доставка курьером</li>
                                    <li>Забрать в пункте выдачи</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="b-catalog-detail__artnum">Артикул: <span class="b-catalog-detail__artnum-data"><?=$arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['NAME']?></span></div>
                <div class="b-catalog-detail__sizes">
                    <div class="b-catalog-detail__sizes-titles">
                        <div class="row">
                            <div class="col-xs-6 text-left">
                                Размер:
                                <span class="b-catalog-detail__sizes-select">
                                    <?$arSize = $arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PROPERTIES']['SIZES_SHOES']?>
                                    <?if($arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PROPERTIES']['SIZES_CLOTHES']['VALUE']):?>
                                        <?$arSize = $arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PROPERTIES']['SIZES_CLOTHES']?>
                                    <?endif?>
                                    <?=$arSize['VALUE']?>
                                </span>
                            </div>
                            <div class="col-xs-6 text-right">
                                <a class="b-catalog-detail__sizes-link-table" data-toggle="modal" data-target="#modalSizeChart">Таблица размеров</a>
                            </div>
                        </div>
                        <ul class="b-catalog-detail__sizes-list">
							<?if(in_array('SIZES_CLOTHES', $arResult['PRODUCT_PROPS'])):?>
								<?foreach($arResult['SKU_PROPS']['SIZES_CLOTHES']['VALUES'] as $arProps):?>
									<?if($arProps['ID'] > 0):?>
										<li class="b-catalog-detail__sizes-list-item" data-id="<?=$arProps['ID']?>"><?=$arProps['NAME']?></li>
									<?endif?>
								<?endforeach?>
							<?elseif(in_array('SIZES_SHOES', $arResult['PRODUCT_PROPS'])):?>
								<?foreach($arResult['SKU_PROPS']['SIZES_SHOES']['VALUES'] as $arProps):?>
									<?if($arProps['ID'] > 0):?>
										<li class="b-catalog-detail__sizes-list-item" data-id="<?=$arProps['ID']?>"><?=$arResult['SIZES_TABLE'][$arProps['NAME']]?></li>
									<?endif?>
								<?endforeach?>
                            <?else:?>
                                <?foreach($arResult['SKU_PROPS']['SIZES_RINGS']['VALUES'] as $arProps):?>
                                    <?if($arProps['ID'] > 0):?>
                                        <li class="b-catalog-detail__sizes-list-item" data-id="<?=$arProps['ID']?>"><?=$arProps['NAME']?></li>
                                    <?endif?>
                                <?endforeach?>
							<?endif?>
                        </ul>
                    </div>
                    <div class="b-catalog-detail-no hidden">
                        Данный товар закончился, выберите другой цвет.
                    </div>
                </div>
                
                <div class="b-catalog-detail__colors <?=(count($arResult['SKU_PROPS']['COLOR']['VALUES']) <= 1)?'hidden':'';?>">
                    <div class="b-catalog-detail__colors-title">
                        Цвет:
                        <span class="b-catalog-detail__colors-select">
                            <?=$arResult['COLOR_NAMES'][$arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PROPERTIES']['COLOR']['VALUE']]?>
                        </span>
                    </div>
                    <ul class="b-catalog-detail__colors-list">
						<?$first = true?>
						<?foreach($arResult['SKU_PROPS']['COLOR']['VALUES'] as $arProps):?>
							<?if($arProps['ID'] > 0):?>
								<li class="b-catalog-detail__colors-list-item <?if($first && $arProps['XML_ID'] == $arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['PROPERTIES']['COLOR']['VALUE']):?><?$first = false?>active<?endif?>" data-id="<?=$arProps['ID']?>"><img src="<?=$arProps['PICT']['SRC']?>"></li>
                        	<?endif?>
						<?endforeach;?>
                    </ul>
                </div>
                <?php /** DigitalDataLayer data-product-id="..." */?>
                <?php
                $firstOfferId = $arResult['OFFERS'][$arResult['FIRST_OFFER_ID']]['ID'];
                ?>
                <a class="btn b-catalog-detail__add2basket"
                   href="#"
                   data-url=""
                   data-product-id="<?= $arResult['ID'] ?>"
                   data-sku-id="<?=$firstOfferId?>"
                   id="add2basketlink"
                    <?= ($arResult['DENIED_DELIVERY'] === 'Y') ? 'disabled="+++++disabled"' : '' ?>
                   onmousedown="window.GENERAL.catalog.rrApiAddToBasket(this, <?=$storeName?>)"
                >Добавить в корзину</a>
                <a href="<?=$arResult["DETAIL_PAGE_URL"]?>" class="b-catalog-detail__page_url">Подробнее о товаре</a>
                <?if($USER->IsAdmin()){?>
                <a class="btn btn-primary b-catalog-detail__reservation-all btn-reserved"
                    <?php
                    if ($arResult['DENIED_DELIVERY'] === 'Y') {
                        echo 'disabled="disabled"';
                    }
                    ?>
                >Забронировать в магазине</a>
                <?}?>
                <!--<a class="btn b-catalog-detail__add2favourite">Добавить в избранное</a>-->
                <div class="b-catalog-detail__description">
                    <div class="b-catalog-detail__description-preview">
                        <b>Чтобы узнать о скидках и специальных условиях доставки, добавьте товар в корзину!</b>
                        <br>
                        <br>
						<?=$arResult['PREVIEW_TEXT']?>
                    </div>
                    <div class="b-catalog-detail__description-detail">
						<?=$arResult['DETAIL_TEXT']?>
                    </div>
                </div>
                <div class="b-catalog-detail__delivery">
                    <?/*?><h5>Как получить</h5><?*/?>
                    <div class="b-catalog-detail__delivery-delivery">
                        <span class="i-delivery">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 41.05 20.19"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path d="M38.78,17.4H37.21a.75.75,0,0,1,0-1.5h1.57a.78.78,0,0,0,.78-.78V12c-.29-.57-2.32-2.89-6.23-6.77A1,1,0,0,0,32.8,5H29.46a.75.75,0,0,1-.75-.75v-2a.78.78,0,0,0-.78-.78H10a.78.78,0,0,0-.78.78V15.12a.78.78,0,0,0,.78.78h1.05a.75.75,0,0,1,0,1.5H10a2.28,2.28,0,0,1-2.28-2.28V2.28A2.28,2.28,0,0,1,10,0H27.93a2.28,2.28,0,0,1,2.28,2.28V3.49H32.8a2.39,2.39,0,0,1,1.53.65c6.72,6.67,6.72,7.4,6.72,7.79v3.19A2.28,2.28,0,0,1,38.78,17.4Z"></path><path d="M30.72,17.4H18a.75.75,0,0,1,0-1.5H30.72a.75.75,0,0,1,0,1.5Z"></path><path d="M34.17,20.19a3.54,3.54,0,1,1,3.54-3.54A3.54,3.54,0,0,1,34.17,20.19Zm0-5.58a2,2,0,1,0,2,2A2,2,0,0,0,34.17,14.61Z"></path><path d="M14.52,20.19a3.54,3.54,0,1,1,3.54-3.54A3.54,3.54,0,0,1,14.52,20.19Zm0-5.58a2,2,0,1,0,2,2A2,2,0,0,0,14.52,14.61Z"></path><path d="M30.63,10.49H29.41a.75.75,0,1,1,0-1.5h1.22a.75.75,0,1,1,0,1.5Z"></path><path d="M3.95,3.81H.75a.75.75,0,0,1,0-1.5h3.2a.75.75,0,0,1,0,1.5Z"></path><path d="M3.95,8.6H2A.75.75,0,0,1,2,7.1H3.95a.75.75,0,0,1,0,1.5Z"></path></g></g></svg>
                        </span>
                        Доставка - <span class="price"></span><span class="period"></span>
                    </div>
                    <div class="b-catalog-detail__delivery-pickup">
                        <span class="i-pickup">
                            <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="459px" height="459px" viewBox="0 0 459 459" style="enable-background:new 0 0 459 459;" xml:space="preserve"><g id="store"><path d="M433.5,25.5h-408v51h408V25.5z M459,280.5v-51L433.5,102h-408L0,229.5v51h25.5v153h255v-153h102v153h51v-153H459zM229.5,382.5h-153v-102h153V382.5z"/></g></svg>
                        </span>
                        Самовывоз - <span class="price">бесплатно</span><span class="period"></span>
                    </div>
                </div>
                <div class="b-catalog-detail__count">
                    <div class="row">
                        <div class="col-xs-12 text-left">
                            <span class="b-catalog-detail__count-to-shops hidden">Можно забрать в <a href="#stores"><span class="b-catalog-detail__count-stores-num">0</span> магазинах</a></span>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="offerId" value="<?=$firstOfferId?>">
                <script>
                    var addToBasketlink = document.getElementById('add2basketlink');
                    var offerId = document.getElementById('offerId');
                    var offerIdConfig = {
                        attributes: true,
                    };
                    var offerIdCallback = function(mutationsList, observer) {
                        for (var mutation of mutationsList) {
                            if (mutation.type === 'attributes') {
                                addToBasketlink.setAttribute('data-sku-id', offerId.value);
                            }
                        }
                    };
                    var linkObserver = new MutationObserver(offerIdCallback);
                    linkObserver.observe(offerId, offerIdConfig);
                </script>
                <input type="hidden" id="deniedBuy" value="<?= $arResult['DENIED_DELIVERY'] ?>" />
                <input type="hidden" id="deniedReservation" value="<?= $arResult['DENIED_RESERVATION'] ?>" />
               <?/*if($arResult['REVIEWS_COUNT'] > 0):?>
                    <div class="b-catalog-detail__reviews">
                        <div class="b-catalog-detail__reviews-stars">
                            <?for ($i = 0; $i < $arResult['REVIEWS_EVALUATION']; $i++):?>
                                <div class="b-catalog-detail__reviews-stars-item active"></div>
                            <?endfor;?>
                            <?for ($j = $i; $j < 5; $j++):?>
                                <div class="b-catalog-detail__reviews-stars-item"></div>
                            <?endfor;?>
                        </div>
                        <p><?=$arResult['REVIEWS_COUNT']?> оценок</p>
                        <div>
                            <a class="btn btn-primary" data-toggle="modal" data-target="#writeReview">Написать отзыв</a> <!--  -->
                        </div>
                    </div>
                <?endif;*/?>
                <div class="b-catalog-detail__social">
                    <a href="http://vkontakte.ru/share.php?url=http://<?=$_SERVER['SERVER_NAME']?><?=$GLOBALS['APPLICATION']->GetCurPage()?>" target="_blank" class="b-catalog-detail__social-item b-catalog-detail__social-vk"></a>
                    <a href="mailto:?Subject=<?=$arResult['NAME']?>&amp;Body=Посмотри http://<?=$_SERVER['SERVER_NAME']?><?=$GLOBALS['APPLICATION']->GetCurPage()?>" class="b-catalog-detail__social-item b-catalog-detail__social-email"></a>
                </div>
            </div>
        </div>

    <script type="text/javascript">
        var sku_tree = <?=json_encode($arResult['OFFERS_TREE'])?>,
            in_stores = <?=json_encode($arResult['IN_STORES'])?>,
            color_available = <?=json_encode($arResult['COLORS_AVAILABLE'])?>,
            hide_retail = '<?=$arResult['OMNI']['HIDE_RETAIL']?>';
    </script>
    <script>
        window.GENERAL.catalog.dataLayerDetails(<?=$arResult['ID']?>, <?= Json::encode($arResult['JS_OBJ']['WDL_ELEMENT_ITEM']) ?>);
    </script>

<?php
print '<script type="text/javascript">
      (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() { ';
$resOffers = CCatalogSKU::getOffersList($arResult["ID"]);
if ($resOffers[$arResult['ID']]) {
    foreach ($resOffers[$arResult['ID']] as $arItem) {
        $skus[] = $arItem['ID'];
    }
    $skuList = implode(",", $skus);
    print 'try{ rrApi.groupView([' . $skuList . '],{"stockId": "' . $storeName . '"}); } catch(e) {}';
}
else {
    print 'try{ rrApi.view(' . $arResult['ID'] . ',{"stockId": "' . $storeName . '"}); } catch(e) {}';
}
print ' })
      </script>';


