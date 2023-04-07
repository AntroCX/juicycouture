<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Context;

/**@var array $arResult */

$request = Context::getCurrent()->getRequest();
?>

<script type="text/javascript">
    <?php
    $window = 'window';
    if ($request->get('is_ajax_post') === 'Y') {
        $window = 'top.window';
    }
    ?>
    var wnd = <?= $window ?>;
    wnd.templatePath = '<?= $templateFolder ?>';
    wnd.pvz = <?=Json::encode($arResult['PVZ'])?>;
    wnd.shops = <?=Json::encode($arResult['SHOPS'])?>;
    wnd.omni = <?=Json::encode($arResult['OMNI'])?>;
    wnd.deliveryId = {
        courier : <?=(array_key_exists(KCE_DELIVERY, $arResult['DELIVERY']))?KCE_DELIVERY:COURIER_DELIVERY?>,
        ozon    : <?=OZON_DELIVERY?>,
        pickup  : <?=PICKUP_DELIVERY?>,
        day     : <?=DAY_DELIVERY?>
    };
    wnd.paySystemId = {
      online : <?= ONLINE_PAY_SYSTEM ?>,
      cash   : <?= CASH_PAY_SYSTEM ?>
    };
    wnd.orderProps = <?=Json::encode($arResult['PROPS_ID'])?>;
    //wnd.preStoreId = '<?= $request->get('ORDER_PROP_' . $arResult['PROPS_ID']['STORE_ID']) ?>';

    window.locationStreets = <?= Json::encode($arResult['LOCATION']['STREETS']) ?>;
    window.locationType = '<?= $arResult['LOCATION']['TYPE_CODE'] ?>';
    window.needReload = '<?= $arResult['need_reload'] ?>';
    window.gifts = <?=Json::encode($arResult["GIFTS"])?>;
</script>
<div class="b-order__props-delivery">
	<div class="b-order__props-delivery-title"><?=GetMessage("SOA_TEMPL_DELIVERY")?></div>
<input type="hidden" name="BUYER_STORE" id="BUYER_STORE" value="<?=$arResult["BUYER_STORE"]?>" />
<div class="bx_section">
	<div class="btn-group" data-toggle="buttons">
	<?
	if(empty($arResult["DELIVERY"])) {
        echo '<div class="delivery-error">Нет вариантов доставки для Вашего города</div>';

    } else {
		$width = ($arParams["SHOW_STORES_IMAGES"] == "Y") ? 850 : 700;

        $dayDelivery = $hasCurierDelivery = false;
        foreach ($arResult["DELIVERY"] as $delivery_id => $arDelivery) {
            if ($delivery_id == DAY_DELIVERY) $dayDelivery = $arDelivery;
            if (isCurier($delivery_id)) $hasCurierDelivery = true;
        }

		foreach ($arResult["DELIVERY"] as $delivery_id => $arDelivery) {
            if ($arDelivery['CHECKED'] === 'Y') $arResult['DELIVERY_CHECK_ID'] = $arDelivery['ID'];
            if ($delivery_id == DAY_DELIVERY && $hasCurierDelivery) continue; // пропуск доставки день-в-день
            if (isCurier($arDelivery['ID']) && $dayDelivery['CHECKED'] == 'Y') $arDelivery['CHECKED'] = 'Y';

            if ($delivery_id == DAY_DELIVERY) {
                $arDeliveryName = explode('Экспресс-доставка', $arDelivery['NAME']);
                $arDelivery['NAME'] = 'Экспресс-доставка';
                $arDelivery['DESCRIPTION'] = trim(str_replace('!', '', $arDeliveryName[1])).', ';
                $arDelivery['DESCRIPTION'] = ToUpper(substr($arDelivery['DESCRIPTION'], 0, 1)).substr($arDelivery['DESCRIPTION'], 1);
            }
			?>

				<label class="bx_element btn btn-radio delivery-button <?=($arDelivery["CHECKED"]=="Y")?'active':''?>" for="ID_DELIVERY_ID_<?=$arDelivery["ID"]?>">

					<input type="radio"
						id="ID_DELIVERY_ID_<?= $arDelivery["ID"] ?>"
						name="<?=htmlspecialcharsbx($arDelivery["FIELD_NAME"])?>"
						value="<?= $arDelivery["ID"] ?>"<?if ($arDelivery["CHECKED"]=="Y") echo " checked";?>
						/>

						<div data-delivery="<?=$arResult["JS_DATA"]["DELIVERY"][$arDelivery["ID"]]["PRICE"]?>"><?= htmlspecialcharsbx(str_replace('('.$arDelivery['OWN_NAME'].')', '', $arDelivery["NAME"]))?></div>

					<?if ($arDelivery['CHECKED'] == 'Y'):?>
						<table class="delivery_extra_services">
							<?foreach ($arDelivery['EXTRA_SERVICES'] as $extraServiceId => $extraService):?>
								<?if(!$extraService->canUserEditValue()) continue;?>
								<tr>
									<td class="name">
										<?=$extraService->getName()?>
									</td>
									<td class="control">
										<?=$extraService->getEditControl('DELIVERY_EXTRA_SERVICES['.$arDelivery['ID'].']['.$extraServiceId.']')	?>
									</td>
									<td rowspan="2" class="price">
										<?

										if ($price = $extraService->getPrice())
										{
											echo GetMessage('SOA_TEMPL_SUM_PRICE').': ';
											echo '<strong>'.SaleFormatCurrency($price, $arResult['BASE_LANG_CURRENCY']).'</strong>';
										}

										?>
									</td>
								</tr>
								<tr>
									<td colspan="2" class="description">
										<?=$extraService->getDescription()?>
									</td>
								</tr>
							<?endforeach?>
						</table>
					<?endif?>

					<div class="clear"></div>
				</label>
			<?
		}

		?>
        <div class="clearfix"></div>

    <? if ($USER->IsAdmin()):?>
    <?$propId = null; //Иначе подтянет значение int 21 из www/local/templates/dkny_main/components/bitrix/sale.order.ajax/page/template.php
    //Баг PHP 7.2?
    // Доставка GoodsRU для админов
    $r = \CSaleOrderProps::GetList([], ['CODE' => 'DELIVERY_GOODSRU']);
    if ($orderProp = $r->fetch()) {
        $propId = $orderProp['ID'];
    }
    ?>
    <? if ($propId): ?>
        <br>
        <div style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px;">
            <label for="ORDER_PROP_<?= $propId ?>">Доставка GoodsRU</label>
            <input type="checkbox"
                   name="ORDER_PROP_<?= $propId ?>"
                   id="ORDER_PROP_<?= $propId ?>"
                   value="<?= $arResult['USER_VALS']['ORDER_PROP'][$propId]?>"
                <?= ($arResult['USER_VALS']['ORDER_PROP'][$propId] === 'Y')? 'checked': '' ?>
            >
        </div>
        <script>
          $(function(){
            $('#ORDER_PROP_<?= $propId ?>').on('change', function(){
              if($(this).prop('checked')){
                $(this).val('Y');
              }
              else{
                $(this).val('N');
              }
            });
          })
        </script>
    <?endif; ?>
    <?endif; ?>
        <div class="delivery-blocks">
            <? PrintPropsForm($arResult['ORDER_PROP']['ADDRESS_PROPS'], $arParams['TEMPLATE_LOCATION']); ?>
            <? foreach ($arResult["DELIVERY"] as $delivery_id => $arDelivery) { ?>
                <?
                if ($delivery_id == DAY_DELIVERY && $hasCurierDelivery) continue; // пропуск доставки день-в-день
                if (isCurier($arDelivery['ID']) && $dayDelivery['CHECKED'] == 'Y') $arDelivery['CHECKED'] = 'Y';
                ?>
            <div class="delivery-block<?= $arDelivery['CHECKED'] === 'Y' ? ' active' : '' ?>"><?
                switch ($arDelivery['ID']) {
                    case COURIER_DELIVERY:
                    case KCE_DELIVERY:
                    case DAY_DELIVERY:

                        $streetRequired = ($arResult['LOCATION']['TYPE_CODE'] == 'CITY');
                        ?>
                        <div class="delivery-courier">
                        <? if ($dayDelivery) { ?>
                            <? $classHidden = ($hasCurierDelivery) ? '' : 'hidden'; ?>
                            <div class="delivery-courier-fast">
                                <input type="checkbox" class="red <?= $classHidden ?>" name="fastDelivery" id="fast_delivery"
                                       data-delivery="<?= $dayDelivery['ID'] ?>"
                                       data-price="<?= ($dayDelivery['DELIVERY_DISCOUNT_PRICE']) ?: $dayDelivery['PRICE'] ?>"
                                    <?= ($dayDelivery['CHECKED'] == 'Y') ? 'checked="checked"' : '' ?>
                                    <?= (!$hasCurierDelivery) ? 'disabled' : '' ?>
                                    >
                                <label class="checkbox-label <?= $classHidden ?>"><?= $dayDelivery['NAME'] ?></label>
                                <em class="question <?= $classHidden ?>"></em>
                                <div class="clear"></div>
                                <div class="delivery-courier-fast-description <?= ($hasCurierDelivery) ? 'hidden' : '' ?>"><?= $dayDelivery['DESCRIPTION'] ?></div>
                            </div>
                        <? } ?>
                            <div class="row delivery-period" style="margin-bottom: 15px;">
                                <div class="col-sm-4 col-md-4">
                                    <div class="form__row_label">Срок доставки</div>
                                </div>
                                <div class="col-sm-4 col-md-4">
                                    <div class="form__row_label dayFormat"><?= $arDelivery['DAY_FORMAT'] ?></div>
                                </div>
                            </div>
                            <div class="delivery-courier-title">Укажите адрес доставки</div>
                            <div class="delivery-courier-line clearfix">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control delivery-courier-form-street js-delivery-address" placeholder="Улица<?= ($streetRequired) ? '*' : '' ?>" data-text="ул. " name="courierStreet" id="courierStreet" value="<?= $_POST['courierStreet'] ?>" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <input type="text" class="form-control delivery-courier-form-house js-delivery-address" placeholder="№ дома*" data-text="д. " name="courierHouse" id="courierHouse" value="<?= $_POST['courierHouse'] ?>" />
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <input type="text" class="form-control delivery-courier-form-apps js-delivery-address" placeholder="Квартира" data-text="кв. " name="courierApps" id="courierApps" value="<?= $_POST['courierApps'] ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($arResult['JS_OBJ']['deliveryTime'])): ?>
                            <div class="delivery-courier-date">
                                <p class="delivery-courier-title">Дата и время доставки</p>
                                <div class="delivery-courier-date__select">
                                    <div>
                                        <input
                                                type="hidden"
                                                name="ORDER_PROP_7"
                                                id="ORDER_PROP_7"
                                                value=""
                                                data-delivery-day>
                                        <select id="dateList"></select>
                                    </div>
                                    <div>
                                        <input
                                                type="hidden"
                                                name="ORDER_PROP_8"
                                                id="ORDER_PROP_8"
                                                value=""
                                                data-delivery-time>
                                        <select id="timeList"></select>
                                    </div>
                                    <script data-skip-moving>

                                    class Input {
                                        constructor(selector, value) {
                                            this.input = document.querySelector(selector);
                                            this.input.value = value;
                                        }
                                    }
                                    class TimeInput extends Input {}
                                    class DateInput extends Input {}
                                    class TimeSelect {
                                        constructor(selector, timeItems) {
                                            this.timeSelect = document.querySelector(selector);
                                            this._renderForOneDate(timeItems);
                                        }
                                        _renderForOneDate(timeItems) {
                                            this.timeSelect.innerHTML = "";
                                            for (let timeItem of timeItems) {
                                                this.timeSelect.insertAdjacentHTML(
                                                    'beforeend', `<option value="${timeItem.value}">${timeItem.text}</option>`);
                                            }
                                        }
                                    }
                                    class DateList {
                                        constructor(dateSelect = '#dateList',
                                                    timeSelect = '#timeList',
                                                    inputForDate  = '#ORDER_PROP_7',
                                                    inputForTime = '#ORDER_PROP_8',
                                                    deliveryTime = []) {
                                            this.inputForDate = inputForDate;
                                            this.inputForTime = inputForTime;
                                            this.dateSelect = dateSelect;
                                            this.timeSelect = timeSelect;
                                            this.dateIntervals = deliveryTime;
                                            this._init();
                                            this._render();
                                        }
                                        _init() {
                                            new DateInput(this.inputForDate, this.dateIntervals[0].date.value);
                                            new TimeInput(this.inputForTime, this.dateIntervals[0].time[0].value);
                                            new TimeSelect(this.timeSelect, this.dateIntervals[0].time);

                                            document.querySelector(this.dateSelect).addEventListener('change', (e) => {
                                                this.showIntervals(e.target.value);
                                            });

                                            document.querySelector(this.timeSelect).addEventListener('change', (e) => {
                                                new TimeInput(this.inputForTime, e.target.value);
                                            });

                                        }
                                        _render() {
                                            const dateSelect = document.querySelector(this.dateSelect);

                                            for (let item of this.dateIntervals) {
                                                const dateObject = new DateItem(item);
                                                dateSelect.insertAdjacentHTML('beforeend', dateObject.render());
                                            }
                                        }
                                        showIntervals(value) {
                                            new DateInput(this.inputForDate, value);

                                            for (let item of this.dateIntervals) {
                                                const dateObject = new DateItem(item);

                                                if (dateObject.dateValue === value) {
                                                    new TimeInput(this.inputForTime, dateObject.timeIntervals[0].value);
                                                    new TimeSelect(this.timeSelect,dateObject.timeIntervals);
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    class DateItem {
                                        constructor(item) {
                                            this.dateText = item.date.text;
                                            this.dateValue = item.date.value;
                                            this.timeIntervals = item.time;
                                        }

                                        render() {
                                            return `<option value="${this.dateValue}">${this.dateText}</option>`;
                                        }
                                    }
                                    new DateList(
                                        '#dateList',
                                        '#timeList',
                                        '#ORDER_PROP_7',
                                        '#ORDER_PROP_8',
                                        <?= Json::encode($arResult['JS_OBJ']['deliveryTime']) ?>
                                    );
                                    </script>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div><?php
                        break;

                    case OZON_DELIVERY:
                        ?><div class="delivery-pvz" id="pvzDeliveryBlock">
                             <div class="row delivery-period" style="margin-bottom: 15px; margin-top: 15px;">
                                 <div class="col-sm-4 col-md-4">
                                     <div class="form__row_label">Срок доставки</div>
                                 </div>
                             <div class="col-sm-4 col-md-4">
                                 <div class="form__row_label dayFormat"><?= $arDelivery['DAY_FORMAT'] ?></div>
                             </div>
                            </div>
                            <div class="delivery-pvz-buttons">
                                <a href="javascript:void(0);" class="delivery-pvz-buttons-button js-pvz-on-map active"><span></span>На карте</a>
                                <a href="javascript:void(0);" class="delivery-pvz-buttons-button js-pvz-in-list"><span></span>Списком</a>
                            </div>

                            <div class="delivery-pvz-map" id="pvzmap"></div>

                            <div class="delivery-pvz-side" id="pvzside">
                                <input type="hidden" class="delivery-pvz-side-address" value="" />
                                <div class="b-page__collapse-top__close"></div>
                                <div class="delivery-pvz-side-title"></div>
                                <div class="delivery-pvz-side-phone"></div>
                                <div class="delivery-pvz-side-metro"></div>
                                <div class="delivery-pvz-side-date">
                                    Дата самовывоза:
                                    <select name="datetime_pvz" class="delivery-courier-form-date">
                                        <?php
                                        $now = time();
                                        $period = (int)$arResult["DELIVERY"][OZON_DELIVERY]['PERIOD_TEXT'];
                                        if (!$period) {
                                            $period = 1;
                                        }
                                        $now += $period * 86400;

                                        for ($i = 0; $i < 5; $i++) {
                                            $str = ToLower(FormatDate('j F', $now));
                                            echo '<option value="'.$str.'" '.(($i == 2) ? 'selected="selected"' : '').'>'.$str.'</option>';
                                            $now += 86400;
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="delivery-pay-warning hidden">Обратите внимание! В данном пункте выдачи нельзя оплатить наличными средствами.</div>

                                <a href="#" id="setPVZ" class="btn btn-primary btn-block b-order-basket__main-contacts-text-form-btn">
                                    Выбрать пункт выдачи
                                </a>

                                <div class="delivery-pvz-side-time">
                                    <div class="delivery-pvz-side-time-title">Режим работы:</div>
                                    <div class="delivery-pvz-side-time-text"></div>
                                </div>
                                <div class="delivery-pvz-side-how">
                                    <div class="delivery-pvz-side-how-title">Как нас найти</div>
                                    <div class="delivery-pvz-side-how-text"></div>
                                </div>
                            </div>

                            <div class="delivery-pvz-list hidden clearfix">
                                <div class="delivery-pvz-list-item-header">
                                    <div class="delivery-pvz-list-item-wrap">
                                        <div class="delivery-pvz-list-item-name">Адрес</div>
                                        <div class="delivery-pvz-list-item-metro">Метро</div>
                                        <div class="delivery-pvz-list-item-time">Срок доставки</div>
                                    </div>
                                </div>
                                <? foreach ($arResult['PVZ'] as $pvzId => $arPvz) { ?>
                                    <div class="delivery-pvz-list-item" data-pvz="<?= $pvzId ?>">
                                        <div class="delivery-pvz-list-item-wrap">
                                            <div class="delivery-pvz-list-item-name"><?= $arPvz['ADDRESS'] ?></div>
                                            <div class="delivery-pvz-list-item-metro"><?= $arPvz['PROPERTIES']['METRO'] ?></div>
                                            <div class="delivery-pvz-list-item-time"><?= $arResult['DELIVERY'][OZON_DELIVERY]['PERIOD_TEXT'] ?></div>
                                        </div>
                                    </div>
                                <? } ?>
                            </div>
                        </div><?
                        break;

                    case PICKUP_DELIVERY:
                        ?><div class="delivery-pvz" id="shopDeliveryBlock">
                            <div class="row delivery-period" style="margin-bottom: 15px; margin-top: 15px;">
                                <div class="col-sm-4 col-md-4">
                                    <div class="form__row_label">Срок доставки</div>
                                </div>
                                <div class="col-sm-4 col-md-4">
                                    <div class="form__row_label dayFormat"><?= $arDelivery['DAY_FORMAT'] ?></div>
                                </div>
                            </div>
                            <div class="delivery-pvz-buttons">
                                <a href="#" class="delivery-pvz-buttons-button js-pvz-on-map active"><span></span>На карте</a>
                                <a href="#" class="delivery-pvz-buttons-button js-pvz-in-list"><span></span>Списком</a>
                            </div>

                            <div class="delivery-pvz-map" id="shopmap"></div>
                            <div class="delivery-pvz-side" id="shopside">
                                <input type="hidden" class="delivery-pvz-side-address" value="">
                                <div class="b-page__collapse-top__close"></div>
                                <div class="delivery-pvz-side-title"></div>
                                <div class="delivery-pvz-side-phone"></div>
                                <div class="delivery-pvz-side-metro"></div>
                                <div class="delivery-pvz-side-date">
                                    Дата самовывоза:
                                    <select name="datetime_shop" class="delivery-courier-form-date">
                                        <?php
                                        $now = time();
                                        $period = -1;
                                        $now += $period * 86400;

                                        for ($i = 0; $i < 5; $i++) {
                                            $now += 86400;
                                            $str = ToLower(FormatDate('j F', $now));
                                            echo '<option value="'.$str.'" '.($i == 0 ? 'selected="selected"' : '').'>'.$str.'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <a href="#" id="setSHOP" class="btn btn-primary btn-block b-order-basket__main-contacts-text-form-btn">
                                    Выбрать магазин
                                </a>

                                <div class="delivery-pvz-side-time">
                                    <div class="delivery-pvz-side-time-title">Режим работы:</div>
                                    <div class="delivery-pvz-side-time-text"></div>
                                </div>
                                <div class="delivery-pvz-side-how">
                                    <div class="delivery-pvz-side-how-title">Как нас найти</div>
                                    <div class="delivery-pvz-side-how-text"></div>
                                </div>
                            </div>

                            <div class="delivery-pvz-list hidden clearfix" id="shoplist">
                                <div class="delivery-pvz-list-item-header">
                                    <div class="delivery-pvz-list-item-wrap">
                                        <div class="delivery-pvz-list-item-name">Магазин</div>
                                        <div class="delivery-pvz-list-item-metro">Время работы</div>
                                        <div class="delivery-pvz-list-item-time">Адрес</div>
                                    </div>
                                </div>
                                <?php foreach ($arResult['SHOPS'] as $shopId => $arShop) { ?>
                                    <div class="delivery-pvz-list-item" data-shop="<?= $shopId ?>">
                                        <div class="delivery-pvz-list-item-wrap">
                                            <div class="delivery-pvz-list-item-name"><?= $arShop['TITLE'] ?></div>
                                            <div class="delivery-pvz-list-item-metro">
                                                <?= $arShop['PHONE'] ? $arShop['PHONE'].'<br />' : '' ?>
                                                <?= $arShop['SCHEDULE'] ?>
                                            </div>
                                            <div class="delivery-pvz-list-item-time">
                                                <?= $arShop['ADDRESS'] ?>
                                            </div>
                                        </div>
                                    </div>
                                <? } ?>
                            </div>
                        </div><?php
                        break;

                    default:
                        break;
                }
            ?></div><?php
        }
        ?></div>
        <div class="delivery-pvz delivery-place-select">
            <div class="delivery-pay-warning hidden">Обратите внимание! В данном пункте выдачи нельзя оплатить наличными средствами. Пожалуйста, выберите другой пункт выдачи или способ оплаты.</div>
            <div class="b-order__props-delivery-title">Выбран способ доставки:</div>
            <div id="deliveryPlaceSelect"></div>
            <input type="button" value="Изменить" id="deliveryPlaceChange" class="btn btn-radio" />
        </div>
        <?php /** переключение на мобильном разрешении на список ПВЗ вместо карты */ ?>
        <script>
            if ((Math.max(document.documentElement.clientWidth, top.window.innerWidth || 0)) < 768) {
                [].map.call(document.querySelectorAll('.js-pvz-on-map'), function (el) {
                    el.classList.remove('active');
                });
                [].map.call(document.querySelectorAll('.delivery-pvz-map'), function (el) {
                    el.classList.add('hidden');
                });
                [].map.call(document.querySelectorAll('.js-pvz-in-list'), function (el) {
                    el.classList.add('active');
                });
                [].map.call(document.querySelectorAll('.delivery-pvz-list'), function (el) {
                    el.classList.remove('hidden');
                });
            }
        </script>
<?php
	}
?>
	</div>
</div>
</div>