<?php

use \Bitrix\Catalog\Product\Price;
use \Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$bDefaultColumns = $arResult["GRID"]["DEFAULT_COLUMNS"];
$colspan = $bDefaultColumns ? count($arResult["GRID"]["HEADERS"]) : count($arResult["GRID"]["HEADERS"]) - 1;
$bPropsColumn = false;
$bUseDiscount = false;
$bPriceType = false;
$bShowNameWithPicture = $bDefaultColumns ? true : false; // flat to show name and picture column in one column
$sumWithoutDiscount = 0;
$sumWithDiscount = 0;
$sumDiscount = 0;
$sumLoyalty = 0;
$sumWithoutDisabledItems = 0;
$discountWithoutDisabledItems = 0;

$deliveryText = '';
foreach ($arResult['DELIVERY'] as $arDelivery) {
    if ($arDelivery['CHECKED'] == 'Y') {
        $deliveryText = $arDelivery['OWN_NAME'].', ';
        $activeDeliveryId = $arDelivery['ID'];
    }
}

foreach ($arResult["PAY_SYSTEM"] as $pay_id => $arPaySystem) {
    if ($arPaySystem['CHECKED'] == 'Y') {
        $payTypeName = $arPaySystem['PSA_NAME'];
        $activePaySystemId = $arPaySystem['ID'];
    }
}

$deliveryText .= ($arResult["DELIVERY_PRICE"] > 0) ? '+'.$arResult["DELIVERY_PRICE_FORMATED"] : 'бесплатно';
$deliveryDescription = '';
if ($activeDeliveryId === COURIER_DELIVERY || $activeDeliveryId === OZON_DELIVERY) {
    $deliveryDescription = formatTransitText(
        $arResult['DELIVERY'][$activeDeliveryId]['PERIOD_TEXT'],
        $arResult['LOCATION']['SALE_LOCATION_LOCATION_NAME_NAME_UPPER']
    );
}

$getDownPriceByBonuses = 'Y'; // снижать цену за счет бонусов
$showBonusDeniedText = 'N'; // отображать текст о том, что бонусы будут списаны в другое время
if ($activeDeliveryId && $activePaySystemId) {
    $getDownPriceByBonuses = ($activeDeliveryId == PICKUP_DELIVERY /*&& $activePaySystemId == CASH_PAYSYSTEM*/) ? 'N' : 'Y';
}
?>
<?php
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
?>
<div class="b-order__block">
	<div class="b-order__block-title">
		<h4>Корзина</h4>
	</div>
	<div class="b-order__block-body">
<div class="bx_ordercart b-order__basket">
	<div class="bx_ordercart_order_table_container">
		<?=bitrix_sessid_post()?>
		<?
        $total = 0;
        foreach($arResult['BASKET_ITEMS'] as $arItem) {

        $disabledItem = false;
        if ($activeDeliveryId == COURIER_DELIVERY && $arItem['OMNI']['DELIVERY_T_DEV'] != 'Y') {
          $disabledItem = true;
        } else if ($activeDeliveryId == PICKUP_DELIVERY && $arItem['OMNI']['PM_N_PICK'] != 'Y') {
            $disabledItem = true;
        } else if ($activeDeliveryId == OZON_DELIVERY && $arItem['OMNI']['PICKUP_POINT_T_DEV'] != 'Y') {
            $disabledItem = true;
        }

			$currentLoyalty = 0;
            $isGift = false;
			foreach ($arItem['PROPS'] as $arProp) {
                if (strpos($arProp['CODE'], 'SIZE') !== false) {
                    $itemSize = ($arProp['VALUE'] == 'One size') ? 'O\S' : $arProp['VALUE'];
                } elseif ($arProp['CODE'] === 'LOYALTY_BONUS' && $arProp['VALUE'] > 0) {
                    if ($getDownPriceByBonuses == 'Y') {
                        $currentLoyalty = $arProp['VALUE'] / $arItem['QUANTITY'];
                        $arItem['PRICE'] -= $currentLoyalty;
                        $sumLoyalty += $currentLoyalty * $arItem['QUANTITY'];
                    } else {
                        $showBonusDeniedText = 'Y';
                    }
                } elseif ($arProp['CODE'] == 'MANZANA_GIFT' && $arProp['VALUE'] > '') {
                    $isGift = true;
                }
			}
			?>
			<div class="b-order__basket-item"
                 data-id="<?= (int)$arItem['ID'] ?>"
                 data-price="<?= (float)$arItem['PRICE'] ?>"
                 data-baseprice="<?= (float)$arItem['BASE_PRICE'] ?>"
                 data-loyalty="<?= (float)$currentLoyalty ?>"
                 data-quantity="<?= (int)$arItem['QUANTITY'] ?>"
            >
				<div class="row">
					<div class="col-xs-4 text-center">
						<img width="100%" src="<?=$arItem['PREVIEW_PICTURE_SRC_ORIGINAL']?>">
					</div>
					<div class="col-xs-8">
						<div class="b-order__basket-item-name">
							<?=$arItem['NAME']?>
						</div>
						<div class="b-order__basket-item-price">
							<? if ($arItem['BASE_PRICE'] != $arItem['PRICE']): ?>
                <? if (!$disabledItem) {
                      $discountWithoutDisabledItems += ($arItem['BASE_PRICE'] - $arItem['PRICE'] - $currentLoyalty) * $arItem['QUANTITY'];
                  }
                  $sumDiscount += ($arItem['BASE_PRICE'] - $arItem['PRICE'] - $currentLoyalty) * $arItem['QUANTITY'];
                ?>
								<span class="price-sale"><?=CurrencyFormat($arItem['PRICE'], 'RUB')?></span>
							<? endif ?>
                <? if (!$disabledItem) {
                    $sumWithoutDisabledItems += $arItem['BASE_PRICE'] * $arItem['QUANTITY'];
                  }
                  $sumWithoutDiscount += $arItem['BASE_PRICE'] * $arItem['QUANTITY'];
                ?>
							<span class="price-base"><?=CurrencyFormat($arItem['BASE_PRICE'], 'RUB')?></span>
						</div>
						<div class="b-order__basket-item-stock">
							<?if($arItem['CAN_BUY'] == 'Y'):?>
							На складе
							<?else:?>
							Нет в наличии
							<?endif?>
						</div>
						<div class="b-order__basket-item-menu">
							<? if ($isGift) { ?>
                            <span class="gift-text">Подарок!</span>
                            <? } else { ?>
							<a class="b-order__basket-item-delete black" data-id="<?=$arItem['ID']?>" data-product-id="<?=$arItem['PRODUCT_ID']?>">Удалить</a>
                            <? } ?>
						</div>
						<div class="b-order__basket-item-params">
							<div class="b-order__basket-item-params-item text-center">
								<div class="">Размер</div>
								<a class="black">
                                    <?= ((int)$itemSize > 0 && $arResult['SIZES_TABLE'][$itemSize]) ? $arResult['SIZES_TABLE'][$itemSize] : $itemSize ?>
								</a>
							</div>

							<div class="b-order__basket-item-params-item text-center">
								<div class="">Кол-во</div>
								<a class="black"><?=$arItem['QUANTITY']?></a>
							</div>
						</div>
                        <?/*?>
						<div class="b-order__basket-item-total">
							<?$curTotal = $arItem['PRICE']*$arItem['QUANTITY']?>
							<?$total += $curTotal?>
							Всего: <?=CurrencyFormat($curTotal, 'RUB')?>
						</div>
                        <?*/?>
					</div>
				</div>
			</div>
		<? } ?>
	</div>
	</div>
</div>
<div class="b-order-basket__side-delivery-notes">
    <div class="b-order-basket__side-delivery-notes-title">
        Вы выбрали:
    </div>
    <div class="b-order-basket__side-delivery-notes-text">
        <span class="delivery-price" data-price="<?= $arResult['DELIVERY_PRICE'] ?>">Доставка: <?= $deliveryText ?></span>
        <span class="gray js-cart-address" style="display: block;"><?= (trim($arResult['USER_VALS']['ORDER_PROP'][21])) ?: '' ?></span>
        <span class="gray" style="display: block;"><?= $deliveryDescription ?></span>
        <span>Оплата:</span> <span class="gray js-cart-delivery"><?= $payTypeName ?></span>
    </div>
    <div class="b-order-basket__side-delivery-notes-no_delivery hidden">
        Не все товары могут быть доставлены выбранным способом!
    </div>
</div>
</div>
	<div class="bx_ordercart_order_pay b-order__block">
		<div class="b-order__block-title">
			<h4>Ваш заказ</h4>
		</div>
		<div class="bx_ordercart_order_pay_right b-order__block-body b-order__info-wrapper">
			<div class="bx_ordercart_order_sum b-order__info">
				<div class="row">
					<div class="col-xs-6 b-order__info-bold">
						Сумма заказа
					</div>
					<div class="col-xs-6 text-right b-order__info-bold">
						<?=CurrencyFormat($sumWithoutDiscount, 'RUB')?>
					</div>
					<?
					if ($sumDiscount > 0)
					{
						?>
						<div class="col-xs-6">
							Скидка
						</div>
						<div class="col-xs-6 text-right b-order__info-pink">
							-<?=CurrencyFormat($sumDiscount, 'RUB')?>
						</div>
						<?
					}?>

					<?if($sumLoyalty > 0):?>
						<div class="col-xs-6">
							Скидка по бонусной программе
						</div>
						<div class="col-xs-6 text-right b-order__info-pink" id="totalLoyalty">
							-<?=CurrencyFormat($sumLoyalty, 'RUB')?>
						</div>
					<?endif?>


					<div class="col-xs-6">
						Доставка
					</div>
					<div class="col-xs-6 text-right b-order__info-pink" id="totalDelivery">
						<?if((float)$arResult["DELIVERY_PRICE"] > 0):?>
							+<?=$arResult["DELIVERY_PRICE_FORMATED"]?>
						<?else:?>
							Бесплатно
						<?endif?>
					</div>

				</div>
			</div>
			<div class="b-order__info-total">
        <div class="row checkout__total_discount-info">
          <div class="col-xs-6">
            <input class="checkbox" id="onlineDiscountCheckbox" type="checkbox" <?= ($arResult['ORDER_DATA']['PAY_SYSTEM_ID'] == ONLINE_PAY_SYSTEM) ? 'checked' : '' ?> />
            <label for="onlineDiscountCheckbox">Оплатить онлайн</label> <span href="#" id="payOnlineHelp">?</span>
              <div class="hidden" id="payOnlineHelpText">
                  <h3>Скидка 5% при оплате онлайн!</h3>
                  <ol>
                      <li>Действует при выбранном способе "Оплата онлайн"</li>
                      <li>Рассчитывается автоматически</li>
                      <li>Действует для способов доставки:
                          <ul>
                              <li><span>Доставка курьером</span></li>
                              <li><span>Забрать в пункте выдачи</span></li>
                          </ul>
                      </li>
                  </ol>
              </div>
          </div>
          <div class="col-xs-6 checkout__total_discount-info-value">Выгода:
            <span class="checkout__total_discount-info-value-price" id="discount-info-value">
              <?php
              if ($arResult['ORDER_DATA']['PAY_SYSTEM_ID'] == ONLINE_PAY_SYSTEM) {
                  $discount = ($sumWithoutDisabledItems - $discountWithoutDisabledItems) / (1 - ONLINE_PAY_DISCOUNT) * ONLINE_PAY_DISCOUNT;
              } else {
                  $discount = ($sumWithoutDisabledItems - $discountWithoutDisabledItems) * ONLINE_PAY_DISCOUNT;
              }
              ?>
              <?= \CCurrencyLang::CurrencyFormat(Price::roundPrice(1, $discount, 'RUB'), 'RUB'); ?>
            </span>
          </div>
        </div>
				<div class="row">
					<div class="col-xs-6">
						Всего
					</div>
					<div class="col-xs-6 text-right" id="totalPrice">
						<?=CurrencyFormat($total, 'RUB')?>
					</div>
				</div>
			</div>
            <div class="b-order__promo-code <?= ($_REQUEST['COUPON']) ? '' : 'mobile-hidden-form' ?>">
				<div class="b-order__promo-code-title hidden-block-title">У вас есть промо-код?</div>
				<div class="row">
					<div class="col-xs-6 col-lg-8">
						<input class="b-order__promo-code-input" name="COUPON" value="<?=$_REQUEST['COUPON']?>" type="text" placeholder="Промо-код">
					</div>
					<div class="col-xs-6 col-lg-4">
						<button class="btn btn-default btn-block btn-set-coupon">Применить</button>
					</div>
				</div>
			</div>
            <? if ($getDownPriceByBonuses == 'N') { ?>
                <div class="b-order-basket__side-delivery-notes-no_bonus">
                    Вы можете воспользоваться бонусными баллами при оплате заказа в розничном магазине. Просто предъявите карту лояльности продавцу.
                </div>
            <? } ?>
            <? $APPLICATION->IncludeComponent('jamilco:sale.order.loyalty', '', ['HIDE' => ($getDownPriceByBonuses == 'N') ? 'Y' : 'N']); ?>
		</div>
	</div>
    <!-- fb tracking -->
    <script>
        if (typeof fbq === 'function') {
            fbq('track', 'AddToCart');
        }
    </script>
    <!-- !fb tracking -->
