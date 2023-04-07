<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?
Jamilco\Blocks\Block::load(array('b-orders-list'));
?>

<?php
$orderCancelId = $_SESSION['SEND_DATA_LAYER_ORDERS']['ORDER_CANCEL'];
if ($orderCancelId):?>
    <script>
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({
            'ecommerce': {
                'refund': {
                    'actionField': {'id': '<?= $orderCancelId ?>'}
                }
            },
            'event': 'gtm-ee-event',
            'gtm-ee-event-category': 'Enhanced Ecommerce',
            'gtm-ee-event-action': 'Full Refund',
            'gtm-ee-event-non-interaction': 'False',
        });
    </script>
<?php endif; ?>
<?php
unset($_SESSION['SEND_DATA_LAYER_ORDERS']['ORDER_CANCEL']);
?>

<?if(count($arResult['ORDERS']) > 0):?>

<div class="b-orders-list">
	<div class="b-orders-list__header hidden-xs hidden-sm">
		<div class="row">
			<div class="col-sm-2">
				Номер заказа
			</div>
			<div class="col-sm-2">
				Дата заказа
			</div>
			<div class="col-sm-3">
				Статус заказа
			</div>
			<div class="col-sm-3">
				Статус оплаты
			</div>
			<div class="col-sm-2">
				Сумма
			</div>
		</div>
	</div>
		<div class="b-orders-list__body">
			<?foreach ($arResult['ORDERS'] as $arOrder):?>
			<a href="<?=$arOrder['ORDER']['URL_TO_DETAIL']?>" class="b-orders-list__body-item">
				<div class="row">
					<div class="col-sm-2">
						<span class="order-num"><?=$arOrder['ORDER']['ACCOUNT_NUMBER']?></span>
					</div>
					<div class="col-sm-2">
						<?=$arOrder['ORDER']['DATE_INSERT_FORMATED']?>
					</div>
					<div class="col-sm-3">
						<?=$arResult['INFO']['STATUS'][$arOrder['ORDER']['STATUS_ID']]['NAME']?>
					</div>
					<div class="col-sm-3">
						<?if($arOrder['ORDER']['PAYED'] == 'N'):?>
							Ожидает оплаты
						<?else:?>
							Оплачен
						<?endif?>
					</div>
					<div class="col-sm-2">
						<?=$arOrder['ORDER']['FORMATED_PRICE']?>
					</div>
				</div>
			</a>
			<?endforeach;?>
		</div>
</div>
<?else:?>
	<div class="text-center">
		Вы еще не сделали ни одного заказа
	</div>
<?endif?>
