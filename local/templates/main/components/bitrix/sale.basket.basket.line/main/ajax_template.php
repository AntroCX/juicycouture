<?php

use \Bitrix\Catalog\Product\Price;
use \Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->IncludeLangFile('template.php');

$cartId = $arParams['cartId'];

$arUser = [];
if ($USER->isAuthorized()) {
    $arUser = CUser::GetByID($USER->GetID())->Fetch();
    $arUser['FULL_NAME'] = [$arUser['NAME'], $arUser['LAST_NAME']];
    TrimArr($arUser['FULL_NAME'], true);
    $arUser['FULL_NAME'] = implode(' ', $arUser['FULL_NAME']);
}

require(realpath(dirname(__FILE__)).'/top_template.php');

global $APPLICATION;
$page = $APPLICATION->GetCurPage(false);
if (strpos($page, 'order') === false) {
    $basketRecordId = array_keys($_SESSION['SEND_DATA_LAYER_BASKET_REMOVE'])[0];
    if ($basketRecordId) {
        echo "
            <script>
                window.dataLayer = window.dataLayer || [];
                dataLayer.push(" . Json::encode($_SESSION['SEND_DATA_LAYER_BASKET_REMOVE'][$basketRecordId]) . ");
            </script>
        ";
        unset($_SESSION['SEND_DATA_LAYER_BASKET_REMOVE']);
    }
}

if ($arParams["SHOW_PRODUCTS"] == "Y" && $arResult['NUM_PRODUCTS'] > 0) {
?>

    <?php
    $basketId = array_keys($_SESSION['SEND_DATA_LAYER_BASKET'])[0];
    if ($basketId) {
        echo "
            <script>
            window.dataLayer = window.dataLayer || [];
            dataLayer.push(" . Json::encode($_SESSION['SEND_DATA_LAYER_BASKET'][$basketId]) . ");
            </script>
        ";
        unset($_SESSION['SEND_DATA_LAYER_BASKET']);
    }
    ?>

    <script>
        var basketItems = <?= Json::encode($arResult['JS_OBJ']['WDL_BASKET_LINE']) ?>;
    </script>
	<div class="b-fast-cart" id="<?=$cartId?>products">
		<div class="b-fast-cart__header">
			Корзина
			<div class="pull-right b-fast-cart__header-count">
				<?=$arResult['NUM_PRODUCTS']?> <?=$arResult['PRODUCT(S)']?>
			</div>
		</div>
		<div class="b-fast-cart__body">
			<?foreach ($arResult["CATEGORIES"] as $category => $items):
			if (empty($items)) continue;
			?>
				<?foreach ($items as $itemId => $v):?>
					<div class="b-fast-cart__body-item">
						<div class="b-fast-cart__body-item-menu">
							<!--<a href="#2" class="b-fast-cart__body-item-menu-edit">Редактировать</a>-->
              <?php /** DigitalDataLayer  добавлен второй параметр (skuId) в removeItemFromCart */?>
							<a data-product-id="<?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['PRODUCT_ID']?>"
                                onclick="<?=$cartId?>.removeItemFromCart(<?=$v['ID']?>, <?=$v['PRODUCT_ID']?>);"
							   class="b-fast-cart__body-item-menu-close"></a>
						</div>
						<div class="b-fast-cart__body-item-data">
							<div class="row">
								<div class="col-xs-5">
									<a href="<?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['DETAIL_PAGE_URL']?>" data-product-id="<?=$v['PRODUCT_ID']?>"
                                       onclick="window.GENERAL.catalog.dataLayerClicks(this, basketItems)"
                                    >
										<img src="<?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['PREVIEW_PICTURE']['src']?>"
											 title="<?=$v["NAME"]?>"
											 alt="<?=$v["NAME"]?>">
									</a>
								</div>
								<div class="col-xs-7">
									<div class="b-fast-cart__body-item-data-name">
										<?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['NAME']?>
									</div>
                                    <div class="b-fast-cart__body-item-data-color">Цвет: <span style="text-transform: none;"><?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['COLOR']?></span></div>
                                    <div class="b-fast-cart__body-item-data-size">Размер: <span style="text-transform: none;"><?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['SIZE']?></span></div>
                                    <div class="b-fast-cart__body-item-data-quantity">Количество: <span style="text-transform: none;"><?=$v["QUANTITY"]?></span></div>
                                    <div>Цена: <span style="text-transform: none;"><?=$v['FULL_PRICE']?></span></div>
								</div>
							</div>
						</div>
					</div>
				<?endforeach;?>
			<?endforeach;?>
		</div>
		<div class="b-fast-cart__footer">
			<div class="row">
				<div class="col-xs-6">
					Сумма заказа
				</div>
				<div class="col-xs-6 b-fast-cart__footer-cost">
					<?=FormatCurrency($arResult['DDL_CART_PROPERTIES']['subtotal'], 'RUB')?>
				</div>
			</div>
            <a href="<?=$arParams["PATH_TO_ORDER"]?>" class="b-fast-cart__footer-checkout btn btn-primary-black left-btn" data-step-id="1"
               onclick="window.GENERAL.catalog.dataLayerCheckoutStep(this, basketItems);"
            >
                Оформить заказ
            </a>
            <?/*?>
			<a data-toggle="modal" data-target="#basket-fastbuy" class="b-fast-cart__footer-checkout btn btn-primary-black" data-step-id="1"
               onclick="window.GENERAL.catalog.dataLayerCheckoutStep(this, basketItems);"
            >
				Оформить заказ
			</a>
            <?*/?>
      <div class="row">
          <div class="col-xs-12 b-fast-cart__footer-price-discount">
              Получите скидку <?= \CCurrencyLang::CurrencyFormat(Price::roundPrice(1, ($arResult['DDL_CART_PROPERTIES']['subtotal'] * ONLINE_PAY_DISCOUNT), 'RUB'), 'RUB') ?> при оплате онлайн
          </div>
          <?/*if($arResult['DDL_CART_PROPERTIES']['subtotal'] >= 15000):?>
              <div class="col-xs-12 b-fast-cart__footer-gift">
                  <span class="and">и</span><br>
                  <div><span><img src="/images/gift.jpg"></span>Подарок &laquo;Маска для сна&raquo;</div>
              </div>
          <?endif;*/?>
      </div>
		</div>
	</div>

	<script>
		BX.ready(function(){
			<?=$cartId?>.fixCart();
		});
	</script>
<?
}?>
</div>
</ul>
<?if ($arResult['NUM_PRODUCTS']): ?>
    <div class="modal fade popup popup-basket-small popup-basket-fast-buy js-popup-basket-fast-buy" id="basket-fastbuy" tabindex="-1" role="dialog">
        <div class="modal-dialog popup__inner">
            <div class="modal-content popup__box">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    <h4 class="modal-title">Оформить заказ</h4>
                    <span class="popup-basket-fast-buy__total">на сумму <span class="js-popup-basket-fast-buy-total js-order-total"><?=$arResult['TOTAL_PRICE']?></span>
                        </span>
                </div>
                <div class="js-basket-fast-buy-content">
                    <div class="popup-swiper-container">
                        <?foreach ($arResult["CATEGORIES"] as $category => $items):
                            if (empty($items))
                                continue;
                        ?>
                            <?foreach ($items as $v):?>
                                <div class="basket-small-item slick-slide">
                                    <a class="basket-small-item__delete js-delete-item" data-id="<?=$v['ID']?>" title="удалить" onclick="<?=$cartId?>.removeItemFromCart(<?=$v['ID']?>, <?=$v['PRODUCT_ID']?>)"></a>
                                    <div class="basket-small-item__image">
                                        <img src="<?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['PREVIEW_PICTURE']['src']?>" title="<?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['NAME']?>" alt="<?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['NAME']?>">
                                    </div>
                                    <a href="<?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['DETAIL_PAGE_URL']?>" class="basket-small-item__name"><?=$arResult['SKU_PROPS'][$v['PRODUCT_ID']]['NAME']?></a>
                                    <div class="basket-small-item__price">
                                        <?=$v['SUM']?>
                                    </div>
                                    <div class="basket-small-item__article">Артикул: <?=$v['NAME']?></div>
                                    <!--div class="basket-small-item__delivery">
                                        <div class="basket-small-item__delivery-delivery">
                                            <span class="i-delivery"></span> Доcтупен для доставки
                                        </div>
                                    </div-->
                                </div>
                            <?endforeach;?>
                        <?endforeach;?>
                    </div>
                    <div class="popup-basket-block">
                        <? if (isset($arResult['IS_PARTIAL_DELIVERY']) && $arResult['IS_PARTIAL_DELIVERY'] == 'Y'): ?>
                            <div class="fast-buy-form__warning">Обратите внимание! Не все товары могут быть доставлены.</div>
                        <? endif ?>
                        <p>Оформите заказ, заполнив форму, и получите консультацию о действующих акциях и скидках</p>
                        <form class="popup-basket-fast-buy-form js-basket-fast-buy-form">
                            <?= bitrix_sessid_post() ?>
                            <div>
                                <label for="fastBasketFio" class="fast-buy-form__label">Имя <sup>*</sup></label>
                                <input id="fastBasketFio" type="text" name="fio" class="fast-buy-form__input form-control" data-rule-required="true" placeholder="Ваше имя*" value="<?=$arUser['FULL_NAME']?>">
                                <label id="fastBasketFio-error" class="error-notify" for="fastBasketFio"></label>
                            </div>
                            <div>
                                <label for="fastBasketPhone" class="fast-buy-form__label">Телефон <sup>*</sup></label>
                                <input id="fastBasketPhone" type="tel" name="phone" class="fast-buy-form__input form-control" data-rule-required="true" placeholder="Номер телефона*" value="<?=$arUser['PERSONAL_MOBILE']?>">
                                <label id="fastBasketPhone-error" class="error-notify" for="fastBasketPhone"></label>
                            </div>
                            <div>
                                <label for="fastBasketEmail" class="fast-buy-form__label">E-mail <sup>*</sup></label>
                                <input id="fastBasketEmail" type="email" name="email" class="fast-buy-form__input form-control" data-rule-required="true" data-rule-email="true" placeholder="Ваш E-mail*" value="<?=$arUser['EMAIL']?>">
                                <label id="fastBasketEmail-error" class="error-notify" for="fastBasketEmail"></label>
                            </div>

                            <div class="fast-buy-form__coupon">
                                <span class="fast-buy-form__coupon-link js-coupon-toggle">У меня есть промокод</span>
                                <div class="fast-buy-form__coupon-input-wrap js-coupon-form none">
                                    <input type="text" name="coupon" class="fast-buy-form__coupon-input js-coupon-value form-control" placeholder="Введите промо-код"><input type="button" class="btn btn-primary fast-buy-form__coupon-btn js-coupon-apply" value="Применить">
                                </div>
                                <div class="js-coupon-error"></div>
                            </div>
                            <div class="fast-buy-form__summary">
                                <div>
                                    <span class="label">Стоимость доставки:</span> <span class="js-delivery-cost"><?= ($arResult['ORDER_INFO']['data']['deliveryPrice'] > 0) ? CurrencyFormat($arResult['ORDER_INFO']['data']['deliveryPrice'], "RUB") : '0' ?></span>
                                </div>
                                <div class="js-discount-wrap">
                                    <span class="label">Скидка:</span>
                                    <span class="js-discount"><?= ($arResult['DDL_CART_PROPERTIES']['total'] < $arResult['DDL_CART_PROPERTIES']['subtotal']) ? CurrencyFormat(($arResult['DDL_CART_PROPERTIES']['total'] - $arResult['DDL_CART_PROPERTIES']['subtotal']), "RUB") : '0' ?></span>
                                </div>
                                <div>
                                    <span class="label">Сумма заказа:</span> <span class="js-order-total"><?= CurrencyFormat(($arResult['DDL_CART_PROPERTIES']['total'] + $arResult['ORDER_INFO']['data']['deliveryPrice']), "RUB") ?></span>
                                </div>
                            </div>
                            <div class="popup__buttons">
                                <input type="submit" class="btn btn-primary fast-buy-form__btn-buy" value="Оформить заказ">
                            </div>
                            <div class="fast-buy-form__checkbox checkbox">
                                <label>
                                    <input type="checkbox" checked="" name="policyAccept" id="policyAccept"> я соглашаюсь с условиями <a href="/reference/contract-offer/" target="_blank">публичной оферты и обработкой моих персональных данных в порядке, предусмотренном публичной офертой</a>
                                </label>
                                <label id="policyAccept-error" class="error-notify" for="policyAccept"></label>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="popup-basket-fast-buy-success js-basket-fast-buy-success" style="display: none;">
                    <div class="b-order__confirm-ok"></div>
                    <div class="popup__title">ЗАКАЗ № <span class="js-order-number"></span> УСПЕШНО СОЗДАН</div>
                    <p>В ближайшее время менеджер свяжется с вами для уточнения деталей заказа.</p>
                    <div class="popup__buttons">
                        <div class="btn btn-primary js-continue-shopping">Продолжить покупки</div>
                    </div>
                    <script>
                        var target = document.querySelector('.popup-basket-fast-buy-success.js-basket-fast-buy-success');
                        var config = {
                            attributes: true,
                        };
                        var callback = function(mutationsList, observer) {
                            for (let mutation of mutationsList) {
                                if (mutation.type === 'attributes') {
                                    window.dataLayer = window.dataLayer || [];
                                    window.dataLayer.push({'event':'sendFastBuyFormSuccess'});
                                }
                            }
                        };
                        var observer = new MutationObserver(callback);
                        observer.observe(target, config);
                    </script>
                </div>
                <div class="popup-basket-fast-buy-error js-basket-fast-buy-error" style="display: none;">
                    <div class="popup__title">Ошибка</div>
                    <p class="js-order-error"></p>
                </div>
            </div>
        </div>
    </div>
<? endif ?>
