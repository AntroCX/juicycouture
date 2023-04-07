<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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

	<a name="stores"></a>
	<div class="b-shops b-section">
		<div class="b-section__title-wrapper">
			<h2 class="b-section__title"><?=$arParams["MAIN_TITLE"]?></h2>
		</div>
		<div class="b-shops__list b-shops__list_load">
			<?/*foreach($arResult["STORES"] as $pid => $arProperty):?>
			<div class="b-shops__list-item">
				<div class="row">
					<div class="col-sm-5">
						<div class="b-shops__list-item-address">г. Балашиха, шоссе Энтузиастов, 1а</div>
						<div class="b-shops__list-item-prop">Тел: +7 (902) 225-56-78</div>
						<div class="b-shops__list-item-prop">пн. — пт.: 8:00 – 20:00</div>
						<div class="b-shops__list-item-prop">сб. — вс.: 9:00 – 18:00</div>
					</div>
					<div class="col-sm-4">
						<div class="b-shops__list-item-count">В наличии > 5 шт.</div>
					</div>
					<div class="col-sm-3 text-right">
						<!--<a class="btn btn-primary">Забронировать</a>-->
					</div>
				</div>
			</div>
			<?endforeach;*/?>
		</div>
	</div>

<!-- reservation any shop-->
<div class="modal fade b-modal-reserved" id="reservationForm" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="b-modal-reserved__loader"></div>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
				<h4 class="modal-title">Бронирование товара</h4>
			</div>
			<div class="modal-body">
				<div class="b-modal-reserved__product">
					<div class="row">
						<div class="col-sm-2">
							<img width="100" id="b-modal-reserved__preview">
						</div>
						<div class="col-sm-6">
							<h4 class="b-modal-reserved__product-artnum"></h4>
							<h6>Размер: <span class="b-modal-reserved__product-size"></span></h6>
							<h6 class="b-modal-reserved__product-price"></h6>
						</div>
						<div class="col-sm-4">
							<h6>В магазине по адресу:</h6>
							<div class="b-modal-reserved__shop-address">

							</div>
						</div>
					</div>
				</div>
				<? if ($arResult['TABLET']) { ?>
                <div class="form-group">
                    <label>Номер сотрудника</label>
                    <div class="select">
                        <select class="form-control" name="RESERVED_TABLET">
                            <? foreach ($arResult['TABLET'] as $arOne) { ?>
                                <option value="<?=$arOne['UF_TABLET_ID']?>" <?=($arOne['SELECTED'] == 'Y')?'selected="selected"':''?>>
                                    <?=$arOne['UF_TABLET_ID']?>. <?=$arOne['LAST_NAME']?> <?=$arOne['NAME']?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
                <? } ?>
				<div class="form-group b-reservation-shop-list">
					<label>Выберите магазин</label>
					<div class="select">
						<select class="b-reservation-shop-select form-control">
							<option>1</option>
							<option>2</option>
							<option>3</option>
						</select>
					</div>
				</div>
				<div class="b-modal-reserved__shop-map" id="YMapsID">

				</div>
				<form class="b-modal-reserved__form">
					<div class="form-group">
						<input type="text" name="RESERVED_NAME" class="form-control" placeholder="Ваше имя*">
					</div>
					<div class="form-group">
						<input type="email" name="RESERVED_EMAIL" class="form-control" placeholder="Ваш e-mail*">
					</div>
					<div class="form-group">
						<input type="text" name="RESERVED_PHONE" class="form-control" placeholder="Номер телефона*">
					</div>
				</form>
				
				<div class="form-group">
					<div class="checkbox">
						<label>
							<input type="checkbox" checked name="i-agree"> я согласен с условиями <a href="/reference/contract-offer/" target="_blank">публичной оферты и обработкой моих персональных данных в порядке, предусмотренном публичной офертой</a>
						</label>
					</div>
				</div>
				
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary btn-send-reservation">Забронировать</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- ! reservation any shop-->

<div class="modal fade" id="b-modal-notify" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
				<h4 class="modal-title">Уведомление</h4>
			</div>
			<div class="modal-body text-center">
				Бронирование успешно произведенно. <br> Номер бронирования №12321312312.
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
