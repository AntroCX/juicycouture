<?
define("NO_KEEP_STATISTIC", true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

global $APPLICATION;
use Bitrix\Main;

$request = Main\Context::getCurrent()->getRequest()->toArray();
$prodId = intval($request['prod_id']);

$ElementID = $APPLICATION->IncludeComponent(
	"bitrix:catalog.element",
    "fastview",
	array(
        "CITY_STORES_NAME" => \Jamilco\Main\Retail::getStoreName(), // разный кеш для разных выбранных городов
		"IBLOCK_TYPE" => "catalog",
		"IBLOCK_ID" => 1,
		"PROPERTY_CODE" => array(
            0 => "COLOR",
            1 => "SIZES_CLOTHES",
            2 => "SIZES_SHOES",
        ),
		"SET_CANONICAL_URL" => "Y",
		"BASKET_URL" => "/personal/basket.php",
		"ACTION_VARIABLE" => "action",
		"PRODUCT_ID_VARIABLE" => "id",
		"SECTION_ID_VARIABLE" => "SECTION_ID",
		"CHECK_SECTION_ID_VARIABLE" => "N",
		"PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"PRODUCT_PROPS_VARIABLE" => "prop",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
		"CACHE_GROUPS" => "Y",
		"SET_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"MESSAGE_404" => "",
		"SET_STATUS_404" => "N",
		"SHOW_404" => "N",
		"PRICE_CODE" => array(
            0 => "BASE",
            1 => 'SALE'
        ),
		"USE_PRICE_COUNT" => "N",
		"SHOW_PRICE_COUNT" => "1",
		"PRICE_VAT_INCLUDE" => "Y",
		"PRICE_VAT_SHOW_VALUE" => "N",
		"USE_PRODUCT_QUANTITY" => "Y",
		"PRODUCT_PROPERTIES" => array(
            0 => "COLOR",
            1 => "SIZES_CLOTHES",
            2 => "SIZES_SHOES",
            3 => "SIZES_RINGS"
        ),
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
		"LINK_IBLOCK_TYPE" => "",
		"LINK_IBLOCK_ID" => "",
		"LINK_PROPERTY_SID" => "",
		"LINK_ELEMENTS_URL" => "link.php?PARENT_ELEMENT_ID=#ELEMENT_ID#",

		"OFFERS_CART_PROPERTIES" => array(
            0 => "ARTNUMBER",
            1 => "SIZES_SHOES",
            2 => "SIZES_CLOTHES",
            3 => "COLOR",
            4 => "SIZES_RINGS"
        ),
		"OFFERS_FIELD_CODE" => array(
            0 => "NAME",
            1 => "",
        ),
		"OFFERS_PROPERTY_CODE" => array(
            0 => "ARTNUMBER",
            1 => "MORE_PHOTO",
            2 => "SIZES_SHOES",
            3 => "SIZES_CLOTHES",
            4 => "COLOR",
            5 => "SIZES_RINGS",
        ),
		"OFFERS_SORT_FIELD" => "CATALOG_PRICE_1",
		"OFFERS_SORT_ORDER" => "asc",
		"OFFERS_SORT_FIELD2" => "CATALOG_PRICE_1",
		"OFFERS_SORT_ORDER2" => "asc",

		"ELEMENT_ID" => $prodId,
		//"ELEMENT_CODE" => $arResult["VARIABLES"]["ELEMENT_CODE"],
		//"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
		//"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
		"SECTION_URL" => '/catalog/#SECTION_CODE_PATH#/',
		"DETAIL_URL" => '/catalog/#SECTION_CODE_PATH#/#ELEMENT_CODE#/',

		'CONVERT_CURRENCY' => "Y",
		'CURRENCY_ID' => "RUB",
		'HIDE_NOT_AVAILABLE' => "N",
		'USE_ELEMENT_COUNTER' => "Y",
		'SHOW_DEACTIVATED' => "N",
		"USE_MAIN_ELEMENT_SECTION" => "Y",

		'OFFER_TREE_PROPS' => array(
            0 => "COLOR",
            1 => "SIZES_CLOTHES",
            2 => "SIZES_SHOES",
            3 => "SIZES_RINGS"
        ),
		'SHOW_DISCOUNT_PERCENT' => "N",
		'SHOW_OLD_PRICE' => "Y",
		'SHOW_MAX_QUANTITY' => "Y",
		'MESS_BTN_BUY' => "Купить",
		'MESS_BTN_ADD_TO_BASKET' => "В корзину",
		'MESS_NOT_AVAILABLE' => "Нет в наличии",
		'USE_VOTE_RATING' => "Y",
		'VOTE_DISPLAY_AS_RATING' => "rating",
		'USE_COMMENTS' => "Y",
		'BLOG_USE' => "N",
		'VK_USE' => "N",
		'FB_USE' => "N",
		'BRAND_USE' => "N",
		'DISPLAY_NAME' => "Y",
		'ADD_DETAIL_TO_SLIDER' => "N",
		"ADD_SECTIONS_CHAIN" => "Y",
		"ADD_ELEMENT_CHAIN" => "Y",
		"DISPLAY_PREVIEW_TEXT_MODE" => "E",
		"DETAIL_PICTURE_MODE" => "IMG",
		'ADD_TO_BASKET_ACTION' => "BUY",
		'SHOW_CLOSE_POPUP' => "N",
		'DISPLAY_COMPARE' => "N",
		'SHOW_BASIS_PRICE' => "Y",
		'DISABLE_INIT_JS_IN_COMPONENT' => "N",
		'SET_VIEWED_IN_COMPONENT' => "Y",

	)
);

?>
	<!-- reservation any shop-->
	<div class="modal fade b-modal-reserved" id="reservedInStore" tabindex="-1" role="dialog">
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
							<input type="tel" name="RESERVED_PHONE" class="form-control" placeholder="Номер телефона*">
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
					Бронирование успешно произведенно. <br> Номер бронирования №.
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->

	<?
	die();
?>
