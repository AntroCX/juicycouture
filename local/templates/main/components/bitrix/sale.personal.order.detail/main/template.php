<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Jamilco\Main\OrderTracking;
?>
<div class="b-order-detail">
	<div class="b-order-detail__number">Заказ №<?=$arResult['ACCOUNT_NUMBER']?></div>
	<div class="b-order-detail__date">от <?=$arResult['DATE_INSERT_FORMATED']?></div>
	<div class="b-order-detail__main-info">
		<div class="row">
			<div class="col-sm-5">
				Город получения
			</div>
			<div class="col-sm-7">
				<?=$arResult['PROPS']['TARIF_LOCATION']?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-5">
				Способ получения
			</div>
			<div class="col-sm-7">
				<?=str_replace(':доставка', '', $arResult['DELIVERY']['NAME'])?>, <?=$arResult['PROPS']['F_ADDRESS']?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-5">
				Способ оплаты
			</div>
			<div class="col-sm-7">
				<?=reset($arResult['PAYMENT'])['PAY_SYSTEM_NAME']?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-5">
				Получатель заказа
			</div>
			<div class="col-sm-7">
				<?=$arResult['PROPS']['LAST_NAME']?> <?=$arResult['PROPS']['NAME']?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-5">
				Статус заказа
			</div>
			<div class="col-sm-7">
                <?=($arResult["CANCELED"] == "Y" ? GetMessage("SPOD_ORDER_CANCELED") : $arResult["STATUS"]["NAME"])?>
			</div>
		</div>
        <?if (
            $arResult["CANCELED"] != "Y" &&
            $arResult["STATUS_ID"] == "S" &&
            $arResult['TK_NAME'] &&
            $arResult['TK_NUM'] &&
            OrderTracking::$tkData[$arResult['TK_NAME']]
        ): ?>
        <div class="row">
            <div class="col-sm-5">
                Трек-номер
            </div>
            <div class="col-sm-7">
                <?=$arResult['TK_NUM']?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-5">
                <a target="_blank" href="<?= OrderTracking::$tkData[$arResult['TK_NAME']]['URL'].$arResult['TK_NUM'] ?>" class="btn btn-primary">Отследить трек</a>
            </div>
        </div>
        <? endif; ?>
	</div>
	<div class="b-order-detail__basket">
		<div class="b-order-detail__header">
			<div class="row">
				<div class="col-sm-3">
					Наименование
				</div>
				<div class="col-sm-3">
					Цена
				</div>
				<div class="col-sm-3">
					Количество
				</div>
				<div class="col-sm-3">
					Стоимость
				</div>
			</div>
		</div>
		<div class="b-order-detail__body">
			<?foreach ($arResult['BASKET'] as $arBasket):?>
			<div class="b-order-detail__body-item">
				<div class="row">
					<div class="col-sm-3">
						<a href="<?=$arBasket['URL']?>"><?=$arBasket['PRODUCT_NAME']?></a>
						<div class="b-order-detail__body-item-props">
							<?foreach ($arBasket['PROPS'] as $arProp):?>
								<?=$arProp['NAME']?>: <?=$arProp['VALUE']?><br>
							<?endforeach;?>
						</div>
					</div>
					<div class="col-sm-3">
						<?=$arBasket['PRICE_FORMATED']?>
					</div>
					<div class="col-sm-3">
						<?=$arBasket['QUANTITY']?>
					</div>
					<div class="col-sm-3">
						<?=$arBasket['FORMATED_SUM']?>
					</div>
				</div>
			</div>
			<?endforeach;?>
		</div>
		<div class="b-order-detail__delivery">
			<div class="row">
				<div class="col-xs-6">
					Доставка:
				</div>
				<div class="col-xs-6 text-right">
					<?=$arResult['PRICE_DELIVERY_FORMATED']?>
				</div>
			</div>
		</div>
		<div class="b-order-detail__total">
			<div class="row">
				<div class="col-xs-6">
					Итого:
				</div>
				<div class="col-xs-6 text-right">
					<?=$arResult['PRICE_FORMATED']?>
				</div>
			</div>
		</div>
		<?if($arResult['CAN_CANCEL'] == 'Y'):?>
			<a class="btn btn-primary" href="<?=$arResult['URL_TO_CANCEL']?>">Отменить заказ</a>
		<?endif?>
	</div>
</div>