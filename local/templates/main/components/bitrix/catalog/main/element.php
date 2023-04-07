<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Jamilco\Omni\Tablet;

$this->setFrameMode(true);

$checkTablet = (Loader::includeModule('jamilco.omni') && Tablet::ifTablet()) ? 'Y' : 'N';
if ($checkTablet == 'Y') $checkTablet .= Tablet::getCurrentShopID();

if (isset($arParams['USE_COMMON_SETTINGS_BASKET_POPUP']) && $arParams['USE_COMMON_SETTINGS_BASKET_POPUP'] == 'Y') {
    $basketAction = (isset($arParams['COMMON_ADD_TO_BASKET_ACTION']) ? array($arParams['COMMON_ADD_TO_BASKET_ACTION']) : array());
} else {
    $basketAction = (isset($arParams['DETAIL_ADD_TO_BASKET_ACTION']) ? $arParams['DETAIL_ADD_TO_BASKET_ACTION'] : array());
}

$isSidebar = ($arParams["SIDEBAR_DETAIL_SHOW"] == "Y" && isset($arParams["SIDEBAR_PATH"]) && !empty($arParams["SIDEBAR_PATH"]));

?><div class="row">
	<div class="<?=($isSidebar ? "col-md-9 col-sm-8" : "col-xs-12")?>">
<?
if($_REQUEST['ajax'] == 'Y') {
	$GLOBALS['APPLICATION']->RestartBuffer();
}

$ElementID = $APPLICATION->IncludeComponent(
	"bitrix:catalog.element",
    "main",
	array(
        "CITY_STORES_NAME" => \Jamilco\Main\Retail::getStoreName(), // разный кеш для разных выбранных городов
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"PROPERTY_CODE" => $arParams["DETAIL_PROPERTY_CODE"],
		"META_KEYWORDS" => $arParams["DETAIL_META_KEYWORDS"],
		"META_DESCRIPTION" => $arParams["DETAIL_META_DESCRIPTION"],
		"BROWSER_TITLE" => $arParams["DETAIL_BROWSER_TITLE"],
		"SET_CANONICAL_URL" => $arParams["DETAIL_SET_CANONICAL_URL"],
		"BASKET_URL" => $arParams["BASKET_URL"],
		"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
		"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
		"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
		"CHECK_SECTION_ID_VARIABLE" => (isset($arParams["DETAIL_CHECK_SECTION_ID_VARIABLE"]) ? $arParams["DETAIL_CHECK_SECTION_ID_VARIABLE"] : ''),
		"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
		"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => ((isset($_REQUEST['look']) && $_REQUEST['look'] == 'Y') ? 0 : $arParams["CACHE_TIME"]),
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"SET_TITLE" => $arParams["SET_TITLE"],
		"SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
		"MESSAGE_404" => $arParams["MESSAGE_404"],
		"SET_STATUS_404" => $arParams["SET_STATUS_404"],
		"SHOW_404" => $arParams["SHOW_404"],
		"FILE_404" => $arParams["FILE_404"],
		"PRICE_CODE" => $arParams["PRICE_CODE"],
		"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
		"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
		"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
		"PRICE_VAT_SHOW_VALUE" => $arParams["PRICE_VAT_SHOW_VALUE"],
		"USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
		"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
		"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
		"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
		"LINK_IBLOCK_TYPE" => $arParams["LINK_IBLOCK_TYPE"],
		"LINK_IBLOCK_ID" => $arParams["LINK_IBLOCK_ID"],
		"LINK_PROPERTY_SID" => $arParams["LINK_PROPERTY_SID"],
		"LINK_ELEMENTS_URL" => $arParams["LINK_ELEMENTS_URL"],

		"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
		"OFFERS_FIELD_CODE" => $arParams["DETAIL_OFFERS_FIELD_CODE"],
		"OFFERS_PROPERTY_CODE" => $arParams["DETAIL_OFFERS_PROPERTY_CODE"],
		"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
		"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
		"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
		"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],

		"ELEMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
		"ELEMENT_CODE" => $arResult["VARIABLES"]["ELEMENT_CODE"],
		"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
		"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
		"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
		'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
		'CURRENCY_ID' => $arParams['CURRENCY_ID'],
		'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
		'USE_ELEMENT_COUNTER' => $arParams['USE_ELEMENT_COUNTER'],
		'SHOW_DEACTIVATED' => $arParams['SHOW_DEACTIVATED'],
		"USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],

		'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
		'LABEL_PROP' => $arParams['LABEL_PROP'],
		'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
		'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
		'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
		'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
		'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
		'SHOW_MAX_QUANTITY' => $arParams['DETAIL_SHOW_MAX_QUANTITY'],
		'MESS_BTN_BUY' => $arParams['MESS_BTN_BUY'],
		'MESS_BTN_ADD_TO_BASKET' => $arParams['MESS_BTN_ADD_TO_BASKET'],
		'MESS_BTN_SUBSCRIBE' => $arParams['MESS_BTN_SUBSCRIBE'],
		'MESS_BTN_COMPARE' => $arParams['MESS_BTN_COMPARE'],
		'MESS_NOT_AVAILABLE' => $arParams['MESS_NOT_AVAILABLE'],
		'USE_VOTE_RATING' => $arParams['DETAIL_USE_VOTE_RATING'],
		'VOTE_DISPLAY_AS_RATING' => (isset($arParams['DETAIL_VOTE_DISPLAY_AS_RATING']) ? $arParams['DETAIL_VOTE_DISPLAY_AS_RATING'] : ''),
		'USE_COMMENTS' => $arParams['DETAIL_USE_COMMENTS'],
		'BLOG_USE' => (isset($arParams['DETAIL_BLOG_USE']) ? $arParams['DETAIL_BLOG_USE'] : ''),
		'BLOG_URL' => (isset($arParams['DETAIL_BLOG_URL']) ? $arParams['DETAIL_BLOG_URL'] : ''),
		'BLOG_EMAIL_NOTIFY' => (isset($arParams['DETAIL_BLOG_EMAIL_NOTIFY']) ? $arParams['DETAIL_BLOG_EMAIL_NOTIFY'] : ''),
		'VK_USE' => (isset($arParams['DETAIL_VK_USE']) ? $arParams['DETAIL_VK_USE'] : ''),
		'VK_API_ID' => (isset($arParams['DETAIL_VK_API_ID']) ? $arParams['DETAIL_VK_API_ID'] : 'API_ID'),
		'FB_USE' => (isset($arParams['DETAIL_FB_USE']) ? $arParams['DETAIL_FB_USE'] : ''),
		'FB_APP_ID' => (isset($arParams['DETAIL_FB_APP_ID']) ? $arParams['DETAIL_FB_APP_ID'] : ''),
		'BRAND_USE' => (isset($arParams['DETAIL_BRAND_USE']) ? $arParams['DETAIL_BRAND_USE'] : 'N'),
		'BRAND_PROP_CODE' => (isset($arParams['DETAIL_BRAND_PROP_CODE']) ? $arParams['DETAIL_BRAND_PROP_CODE'] : ''),
		'DISPLAY_NAME' => (isset($arParams['DETAIL_DISPLAY_NAME']) ? $arParams['DETAIL_DISPLAY_NAME'] : ''),
		'ADD_DETAIL_TO_SLIDER' => (isset($arParams['DETAIL_ADD_DETAIL_TO_SLIDER']) ? $arParams['DETAIL_ADD_DETAIL_TO_SLIDER'] : ''),
		'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
		"ADD_SECTIONS_CHAIN" => (isset($arParams["ADD_SECTIONS_CHAIN"]) ? $arParams["ADD_SECTIONS_CHAIN"] : ''),
		"ADD_ELEMENT_CHAIN" => (isset($arParams["ADD_ELEMENT_CHAIN"]) ? $arParams["ADD_ELEMENT_CHAIN"] : ''),
		"DISPLAY_PREVIEW_TEXT_MODE" => (isset($arParams['DETAIL_DISPLAY_PREVIEW_TEXT_MODE']) ? $arParams['DETAIL_DISPLAY_PREVIEW_TEXT_MODE'] : ''),
		"DETAIL_PICTURE_MODE" => (isset($arParams['DETAIL_DETAIL_PICTURE_MODE']) ? $arParams['DETAIL_DETAIL_PICTURE_MODE'] : ''),
		'ADD_TO_BASKET_ACTION' => $basketAction,
		'SHOW_CLOSE_POPUP' => isset($arParams['COMMON_SHOW_CLOSE_POPUP']) ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
		'DISPLAY_COMPARE' => (isset($arParams['USE_COMPARE']) ? $arParams['USE_COMPARE'] : ''),
		'COMPARE_PATH' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['compare'],
		'SHOW_BASIS_PRICE' => (isset($arParams['DETAIL_SHOW_BASIS_PRICE']) ? $arParams['DETAIL_SHOW_BASIS_PRICE'] : 'Y'),
		'BACKGROUND_IMAGE' => (isset($arParams['DETAIL_BACKGROUND_IMAGE']) ? $arParams['DETAIL_BACKGROUND_IMAGE'] : ''),
		'DISABLE_INIT_JS_IN_COMPONENT' => (isset($arParams['DISABLE_INIT_JS_IN_COMPONENT']) ? $arParams['DISABLE_INIT_JS_IN_COMPONENT'] : ''),
		'SET_VIEWED_IN_COMPONENT' => (isset($arParams['DETAIL_SET_VIEWED_IN_COMPONENT']) ? $arParams['DETAIL_SET_VIEWED_IN_COMPONENT'] : ''),

		"USE_GIFTS_DETAIL" => $arParams['USE_GIFTS_DETAIL']?: 'Y',
		"USE_GIFTS_MAIN_PR_SECTION_LIST" => $arParams['USE_GIFTS_MAIN_PR_SECTION_LIST']?: 'Y',
		"GIFTS_SHOW_DISCOUNT_PERCENT" => $arParams['GIFTS_SHOW_DISCOUNT_PERCENT'],
		"GIFTS_SHOW_OLD_PRICE" => $arParams['GIFTS_SHOW_OLD_PRICE'],
		"GIFTS_DETAIL_PAGE_ELEMENT_COUNT" => $arParams['GIFTS_DETAIL_PAGE_ELEMENT_COUNT'],
		"GIFTS_DETAIL_HIDE_BLOCK_TITLE" => $arParams['GIFTS_DETAIL_HIDE_BLOCK_TITLE'],
		"GIFTS_DETAIL_TEXT_LABEL_GIFT" => $arParams['GIFTS_DETAIL_TEXT_LABEL_GIFT'],
		"GIFTS_DETAIL_BLOCK_TITLE" => $arParams["GIFTS_DETAIL_BLOCK_TITLE"],
		"GIFTS_SHOW_NAME" => $arParams['GIFTS_SHOW_NAME'],
		"GIFTS_SHOW_IMAGE" => $arParams['GIFTS_SHOW_IMAGE'],
		"GIFTS_MESS_BTN_BUY" => $arParams['GIFTS_MESS_BTN_BUY'],

		"GIFTS_MAIN_PRODUCT_DETAIL_PAGE_ELEMENT_COUNT" => $arParams['GIFTS_MAIN_PRODUCT_DETAIL_PAGE_ELEMENT_COUNT'],
		"GIFTS_MAIN_PRODUCT_DETAIL_BLOCK_TITLE" => $arParams['GIFTS_MAIN_PRODUCT_DETAIL_BLOCK_TITLE'],
    "IS_LOOK_MODE" => ((isset($_REQUEST['look']) && $_REQUEST['look'] == 'Y') ? true : false)
	),
	$component
);

?><?php

if ($_REQUEST['ajax'] == 'Y') {
	?>
	<link rel="stylesheet" type="text/css" href="/local/blocks/b-catalog-detail/b-catalog-detail.css?v=7">
	<script src="/local/blocks/b-catalog-detail/b-catalog-detail.min.js"></script>
    <script src="/local/blocks/b-shops/b-shops.min.js"></script>
    <script>
      $(function(){
        $('#closeQuickViewProduct').on('click', function (e) {
            $('#quickViewProduct').modal('hide');
        });
    })
    </script>
	<style>
		.b-catalog-detail__add2favourite {
			display: none !important;
		}

		.b-catalog-detail__reviews {
			display: none;
		}

	</style>


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

	/*if($ElementID > 0) {
		$APPLICATION->IncludeComponent("bitrix:catalog.store.amount", "main", array(
			"ELEMENT_ID" => $ElementID,
			"STORE_PATH" => $arParams['STORE_PATH'],
			"CACHE_TYPE" => "A",
			"CACHE_TIME" => "36000",
			"MAIN_TITLE" => $arParams['MAIN_TITLE'],
			"USE_MIN_AMOUNT" => $arParams['USE_MIN_AMOUNT'],
			"MIN_AMOUNT" => $arParams['MIN_AMOUNT'],
			"STORES" => $arParams['STORES'],
			"SHOW_EMPTY_STORE" => $arParams['SHOW_EMPTY_STORE'],
			"SHOW_GENERAL_STORE_INFORMATION" => $arParams['SHOW_GENERAL_STORE_INFORMATION'],
			"USER_FIELDS" => $arParams['USER_FIELDS'],
			"FIELDS" => $arParams['FIELDS']
		),
			$component,
			array("HIDE_ICONS" => "Y")
		);
	}*/

	die();
}?>
		<?if($ElementID > 0 && $USER->IsAdmin()):?>
			<?/*$APPLICATION->IncludeComponent(
				"bitrix:catalog.bigdata.products",
				"main",
				array(
					"ACTION_VARIABLE" => "action_cbdp",
					"ADDITIONAL_PICT_PROP_1" => "",
					"ADDITIONAL_PICT_PROP_2" => "MORE_PHOTO",
					"ADD_PROPERTIES_TO_BASKET" => "Y",
					"BASKET_URL" => "/order/",
					"CACHE_GROUPS" => "Y",
					"CACHE_TIME" => "3600",
					"CACHE_TYPE" => "A",
					"CART_PROPERTIES_1" => array(
						0 => "",
						1 => "",
					),
					"CART_PROPERTIES_2" => array(
						0 => "SIZES_SHOES",
						1 => "SIZES_CLOTHES",
						2 => "COLOR",
						3 => "",
					),
					"CONVERT_CURRENCY" => "N",
					"DEPTH" => "",
					"DETAIL_URL" => "",
					"HIDE_NOT_AVAILABLE" => "Y",
					"IBLOCK_ID" => "1",
					"IBLOCK_TYPE" => "catalog",
					"ID" => $ElementID,
					"LABEL_PROP_1" => "-",
					"LINE_ELEMENT_COUNT" => "3",
					"MESS_BTN_BUY" => "Купить",
					"MESS_BTN_DETAIL" => "Подробнее",
					"MESS_BTN_SUBSCRIBE" => "Подписаться",
					"OFFER_TREE_PROPS_2" => array(
						0 => "SIZES_SHOES",
						1 => "SIZES_CLOTHES",
						2 => "COLOR",
					),
					"PAGE_ELEMENT_COUNT" => "30",
					"PARTIAL_PRODUCT_PROPERTIES" => "N",
					"PRICE_CODE" => array(
						0 => "BASE",
					),
					"PRICE_VAT_INCLUDE" => "Y",
					"PRODUCT_ID_VARIABLE" => "id",
					"PRODUCT_PROPS_VARIABLE" => "prop",
					"PRODUCT_QUANTITY_VARIABLE" => "",
					"PRODUCT_SUBSCRIPTION" => "N",
					"PROPERTY_CODE_1" => array(
						0 => "ARTNUMBER",
						1 => "",
					),
					"PROPERTY_CODE_2" => array(
						0 => "SIZES_SHOES",
						1 => "SIZES_CLOTHES",
						2 => "VIDEO",
						3 => "COLOR",
						4 => "",
					),
					"RCM_TYPE" => "personal",
					"SECTION_CODE" => "",
					"SECTION_ELEMENT_CODE" => "",
					"SECTION_ELEMENT_ID" => "",
					"SECTION_ID" => "",
					"SHOW_DISCOUNT_PERCENT" => "Y",
					"SHOW_FROM_SECTION" => "N",
					"SHOW_IMAGE" => "Y",
					"SHOW_NAME" => "Y",
					"SHOW_OLD_PRICE" => "N",
					"SHOW_PRICE_COUNT" => "1",
					"SHOW_PRODUCTS_1" => "Y",
					"TEMPLATE_THEME" => "blue",
					"USE_PRODUCT_QUANTITY" => "N",
					"COMPONENT_TEMPLATE" => ".default"
				),
				false
			);*/?>
		<?endif?>

    <?
    /** Блок купить образ */
    if ($ElementID > 0 && 0) {
        $rsElements = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 15,
                'PROPERTY_MAIN_PRODUCT' => $ElementID,
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            [
                'ID',
                'PROPERTY_PRODUCTS'
            ]
        );
        $arIDs = [];
        while ($arElement = $rsElements->Fetch()) {
            $arIDs = array_merge($arIDs, $arElement['PROPERTY_PRODUCTS_VALUE']);
        }
        if (!empty($arIDs)) {

            global $arrFilterLook;
            $arrFilterLook = [
                '=ID' => $arIDs,
                '!ID' => $ElementID,
                '>CATALOG_QUANTITY' => 0,
            ];

            $APPLICATION->IncludeComponent(
                "jamilco:catalog.section",
                "look",
                Array(
                    "ACTION_VARIABLE" => "action",
                    "ADD_PICT_PROP" => "-",
                    "ADD_PROPERTIES_TO_BASKET" => "Y",
                    "ADD_SECTIONS_CHAIN" => "N",
                    "ADD_TO_BASKET_ACTION" => "ADD",
                    "AJAX_MODE" => "N",
                    "AJAX_OPTION_ADDITIONAL" => "",
                    "AJAX_OPTION_HISTORY" => "N",
                    "AJAX_OPTION_JUMP" => "N",
                    "AJAX_OPTION_STYLE" => "N",
                    "BACKGROUND_IMAGE" => "-",
                    "BASKET_URL" => "/personal/basket.php",
                    "BROWSER_TITLE" => "-",
                    "CACHE_FILTER" => "Y",
                    "CACHE_GROUPS" => "Y",
                    "CACHE_TIME" => "36000000",
                    "CACHE_TYPE" => "A",
                    "CONVERT_CURRENCY" => "N",
                    "DETAIL_URL" => "",
                    "DISABLE_INIT_JS_IN_COMPONENT" => "N",
                    "DISPLAY_BOTTOM_PAGER" => "Y",
                    "DISPLAY_TOP_PAGER" => "N",
                    "ELEMENT_SORT_FIELD" => "id",
                    "ELEMENT_SORT_FIELD2" => "id",
                    "ELEMENT_SORT_ORDER" => "desc",
                    "ELEMENT_SORT_ORDER2" => "desc",
                    "FILTER_NAME" => "arrFilterLook",
                    "HIDE_NOT_AVAILABLE" => "N",
                    "IBLOCK_ID" => "1",
                    "IBLOCK_TYPE" => "catalog",
                    "INCLUDE_SUBSECTIONS" => "Y",
                    "LABEL_PROP" => "-",
                    "LINE_ELEMENT_COUNT" => "5",
                    "MESSAGE_404" => "",
                    "MESS_BTN_ADD_TO_BASKET" => "В корзину",
                    "MESS_BTN_BUY" => "Купить",
                    "MESS_BTN_DETAIL" => "Подробнее",
                    "MESS_BTN_SUBSCRIBE" => "Подписаться",
                    "MESS_NOT_AVAILABLE" => "Нет в наличии",
                    "META_DESCRIPTION" => "-",
                    "META_KEYWORDS" => "-",
                    "OFFERS_CART_PROPERTIES" => "",
                    "OFFERS_FIELD_CODE" => array(0 => "", 1 => "",),
                    "OFFERS_LIMIT" => "8",
                    "OFFERS_PROPERTY_CODE" => array(0 => "SIZES_SHOES", 1 => "SIZES_CLOTHES", 2 => "COLOR", 3 => "",),
                    "OFFERS_SORT_FIELD" => "sort",
                    "OFFERS_SORT_FIELD2" => "id",
                    "OFFERS_SORT_ORDER" => "asc",
                    "OFFERS_SORT_ORDER2" => "desc",
                    "PAGER_BASE_LINK_ENABLE" => "N",
                    "PAGER_DESC_NUMBERING" => "N",
                    "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
                    "PAGER_SHOW_ALL" => "N",
                    "PAGER_SHOW_ALWAYS" => "N",
                    "PAGER_TEMPLATE" => ".default",
                    "PAGER_TITLE" => "Товары",
                    "PAGE_ELEMENT_COUNT" => "9",
                    "PARTIAL_PRODUCT_PROPERTIES" => "N",
                    "PRICE_CODE" => array(0 => "BASE",),
                    "PRICE_VAT_INCLUDE" => "Y",
                    "PRODUCT_DISPLAY_MODE" => "N",
                    "PRODUCT_ID_VARIABLE" => "id",
                    "PRODUCT_PROPERTIES" => "",
                    "PRODUCT_PROPS_VARIABLE" => "prop",
                    "PRODUCT_QUANTITY_VARIABLE" => "",
                    "PRODUCT_SUBSCRIPTION" => "N",
                    "PROPERTY_CODE" => array(0 => "ARTNUMBER", 1 => "NEW", 2 => "",),
                    "SECTION_CODE" => "",
                    "SECTION_ID" => "",
                    "SECTION_ID_VARIABLE" => "SECTION_ID",
                    "SECTION_URL" => "",
                    "SECTION_USER_FIELDS" => array(0 => "", 1 => "",),
                    "SEF_MODE" => "N",
                    "SET_BROWSER_TITLE" => "N",
                    "SET_LAST_MODIFIED" => "N",
                    "SET_META_DESCRIPTION" => "N",
                    "SET_META_KEYWORDS" => "N",
                    "SET_STATUS_404" => "N",
                    "SET_TITLE" => "N",
                    "SHOW_404" => "N",
                    "SHOW_ALL_WO_SECTION" => "Y",
                    "SHOW_CLOSE_POPUP" => "N",
                    "SHOW_DISCOUNT_PERCENT" => "N",
                    "SHOW_OLD_PRICE" => "Y",
                    "SHOW_PRICE_COUNT" => "1",
                    "TEMPLATE_THEME" => "blue",
                    "USE_MAIN_ELEMENT_SECTION" => "N",
                    "USE_PRICE_COUNT" => "N",
                    "USE_PRODUCT_QUANTITY" => "N"
                )
            );
        }
    }

    global $viewedFilter;
    \Jamilco\Main\Retail::getItemFilter($viewedFilter, false);

    // REES46
/*    global $Rees46_CatalogItemId;
    if(IsModuleInstalled("mk.rees46")):
    if($Rees46_CatalogItemId):?>
        <div class="container" style="margin-top: 20px; margin-bottom: 20px;">
            <?$APPLICATION->IncludeComponent('rees46:recommend', '.default', array(
            'recommender' => 'also_bought',
            'params' => array(
            'item_id' => $Rees46_CatalogItemId
            ),
            ));?>
        </div>
        <div class="container" style="margin-top: 20px; margin-bottom: 20px;">
            <?$APPLICATION->IncludeComponent('rees46:recommend', '.default', array(
            'recommender' => 'see_also',
            'params' => array()
            ));?>
        </div>
    <?endif;
     endif;
*/
        /*$APPLICATION->IncludeComponent(
        "jamilco:catalog.products.viewed",
        "",
        Array(
            "BASKET_URL"                 => "/personal/basket.php",
            "CACHE_GROUPS"               => "Y",
            "CACHE_TIME"                 => "3600",
            "CACHE_TYPE"                 => "A",
            "CONVERT_CURRENCY"           => "Y",
            "CURRENCY_ID"                => "RUB",
            "DATA_LAYER_NAME"            => "dataLayer",
            "DEPTH"                      => "",
            "HIDE_NOT_AVAILABLE"         => "N",
            "HIDE_NOT_AVAILABLE_OFFERS"  => "L",
            "IBLOCK_ID"                  => "1",
            "IBLOCK_MODE"                => "multi",
            "IBLOCK_TYPE"                => "catalog",
            "PAGE_ELEMENT_COUNT"         => "10",
            "PARTIAL_PRODUCT_PROPERTIES" => "N",
            "PRICE_CODE"                 => array("BASE", "SALE"),
            "PRICE_VAT_INCLUDE"          => "Y",
            "RELATIVE_QUANTITY_FACTOR"   => "5",
            "SECTION_CODE"               => "",
            "SECTION_ELEMENT_CODE"       => "",
            "SECTION_ELEMENT_ID"         => "",
            "SECTION_ID"                 => "",
            "SHOW_CLOSE_POPUP"           => "N",
            "SHOW_DISCOUNT_PERCENT"      => "Y",
            "SHOW_FROM_SECTION"          => "N",
            "SHOW_MAX_QUANTITY"          => "M",
            "SHOW_OLD_PRICE"             => "Y",
            "SHOW_PRICE_COUNT"           => "1",
            "SHOW_PRODUCTS_1"            => "Y",
            "USE_ENHANCED_ECOMMERCE"     => "N",
            "USE_PRICE_COUNT"            => "N",
            "USE_PRODUCT_QUANTITY"       => "Y",
            "FILTER_NAME"                => "viewedFilter",
            "CITY_STORES_NAME"           => \Jamilco\Main\Retail::getStoreName(), // разный кеш для разных выбранных городов
        )
    );*/

    Jamilco\Blocks\Block::load(array('b-modal-review'));

		global $arrReviewFilter;
		$arrReviewFilter = array('PROPERTY_PRODUCT_ID' => $ElementID);

		?>
		<?$APPLICATION->IncludeComponent(
			"bitrix:news.list",
			"reviews",
			Array(
				"ACTIVE_DATE_FORMAT" => "d.m.Y",
				"ADD_SECTIONS_CHAIN" => "N",
				"AJAX_MODE" => "N",
				"AJAX_OPTION_ADDITIONAL" => "",
				"AJAX_OPTION_HISTORY" => "N",
				"AJAX_OPTION_JUMP" => "N",
				"AJAX_OPTION_STYLE" => "N",
				"CACHE_FILTER" => "N",
				"CACHE_GROUPS" => "Y",
				"CACHE_TIME" => "36000000",
				"CACHE_TYPE" => "N",
				"CHECK_DATES" => "Y",
				"DETAIL_URL" => "",
				"DISPLAY_BOTTOM_PAGER" => "Y",
				"DISPLAY_DATE" => "Y",
				"DISPLAY_NAME" => "Y",
				"DISPLAY_PICTURE" => "Y",
				"DISPLAY_PREVIEW_TEXT" => "Y",
				"DISPLAY_TOP_PAGER" => "N",
				"FIELD_CODE" => array("",""),
				"FILTER_NAME" => "arrReviewFilter",
				"HIDE_LINK_WHEN_NO_DETAIL" => "N",
				"IBLOCK_ID" => "11",
				"IBLOCK_TYPE" => "reviews",
				"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
				"INCLUDE_SUBSECTIONS" => "N",
				"MESSAGE_404" => "",
				"NEWS_COUNT" => "8",
				"PAGER_BASE_LINK_ENABLE" => "N",
				"PAGER_DESC_NUMBERING" => "N",
				"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
				"PAGER_SHOW_ALL" => "N",
				"PAGER_SHOW_ALWAYS" => "N",
				"PAGER_TEMPLATE" => ".default",
				"PAGER_TITLE" => "Новости",
				"PARENT_SECTION" => "",
				"PARENT_SECTION_CODE" => "",
				"PREVIEW_TRUNCATE_LEN" => "",
				"PROPERTY_CODE" => array("TOTAL","PRODUCT_EVALUATION",""),
				"SET_BROWSER_TITLE" => "Y",
				"SET_LAST_MODIFIED" => "N",
				"SET_META_DESCRIPTION" => "Y",
				"SET_META_KEYWORDS" => "Y",
				"SET_STATUS_404" => "N",
				"SET_TITLE" => "N",
				"SHOW_404" => "N",
				"SORT_BY1" => "TIMESTAMP_X",
				"SORT_BY2" => "SORT",
				"SORT_ORDER1" => "DESC",
				"SORT_ORDER2" => "ASC"
			)
		);

    if ($ElementID > 0) {
        if ($arParams["USE_STORE"] == "Y" && ModuleManager::isModuleInstalled("catalog")) {
            ?>
            <?$APPLICATION->IncludeComponent("bitrix:catalog.store.amount", "main", array(
                "TABLET" => $checkTablet,
                "ELEMENT_ID" => $ElementID,
                "STORE_PATH" => $arParams['STORE_PATH'],
                "CACHE_TYPE" => "A",
                "CACHE_TIME" => "36000",
                "MAIN_TITLE" => $arParams['MAIN_TITLE'],
                "USE_MIN_AMOUNT" => $arParams['USE_MIN_AMOUNT'],
                "MIN_AMOUNT" => $arParams['MIN_AMOUNT'],
                "STORES" => $arParams['STORES'],
                "SHOW_EMPTY_STORE" => $arParams['SHOW_EMPTY_STORE'],
                "SHOW_GENERAL_STORE_INFORMATION" => $arParams['SHOW_GENERAL_STORE_INFORMATION'],
                "USER_FIELDS" => $arParams['USER_FIELDS'],
                "FIELDS" => $arParams['FIELDS']
            ),
                $component,
                array("HIDE_ICONS" => "Y")
            ); ?><?
        }
    }
		?>
	</div>

	<div class="modal fade b-modal-review" tabindex="-1" role="dialog" id="writeReview">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
                <form class="b-modal-review-form">
					<div class="b-modal-review__loader"></div>
					<input type="hidden" name="product_review_id" value="<?=$ElementID?>">
					<?=bitrix_sessid_post()?>
					<div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                        <h4 class="modal-title">Написать отзыв</h4>
                    </div>
                    <div class="modal-body b-modal-review__body">
                        <div class="row">
                            <div class="col-sm-3">
                                <label class="inline-label"><span class="pink">*</span> Общий рейтинг</label>
                            </div>
                            <div class="col-sm-9">
								<div class="form-group">
									<div class="btn-group btn-group_stars" data-toggle="buttons">
										<label class="btn btn-star green-dark">
											<input type="radio" name="totalRating" autocomplete="off" value="1" title="Ужасно">
										</label>
										<label class="btn btn-star green-dark">
											<input type="radio" name="totalRating" autocomplete="off" value="2" title="Плохо">
										</label>
										<label class="btn btn-star green-dark">
											<input type="radio" name="totalRating" autocomplete="off" value="3" title="Удовлетворительно">
										</label>
										<label class="btn btn-star green-dark">
											<input type="radio" name="totalRating" autocomplete="off" value="4" title="Хорошо">
										</label>
										<label class="btn btn-star green-dark">
											<input type="radio" name="totalRating" autocomplete="off" value="5" title="Отлично">
										</label>
									</div>
									<span class="btn-star__value"></span>
								</div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>
                                Заголовок отзыва
                            </label>
                            <input type="text" name="title" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Отзыв</label>
                            <textarea name="text" class="form-control"></textarea>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-7">
                                <label class="inline-label-big">БУДЕТЕ ЛИ ВЫ РЕКОММЕНДОВАТЬ ЭТОТ ПРОДУКТ</label>
                            </div>
                            <div class="col-sm-5">
                                <div class="btn-group" data-toggle="buttons">
                                    <label class="btn btn-radio active">
                                        <input name="product_recommendation" value="Да" type="radio" autocomplete="off" checked> Да
                                    </label>
                                    <label class="btn btn-radio">
                                        <input name="product_recommendation" value="Нет" type="radio" autocomplete="off"> Нет
                                    </label>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>
                                        <span class="pink">*</span> Ваше имя
                                    </label>
                                    <input name="name" type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>
                                        Ваш адрес
                                    </label>
                                    <input name="address" type="text" class="form-control">
                                </div>
                            </div>
						</div>
						<div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>
                                        <span class="pink">*</span> E-mail
                                    </label>
                                    <input name="email" type="email" class="form-control">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-6 text-right">
                                    <label class="inline-label-big">Сколько вам лет?</label>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="number" name="years" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-6 text-right">
                                    <label class="inline-label">Как вы оцениваете качество продукта</label>
                                </div>
                                <div class="col-sm-6">
                                    <div class="btn-group btn-group_stars" data-toggle="buttons">
                                        <label class="btn btn-star green">
                                            <input type="radio" name="quality" autocomplete="off" value="1" title="Ужасно">
                                        </label>
                                        <label class="btn btn-star green">
                                            <input type="radio" name="quality" autocomplete="off" value="2" title="Плохо">
                                        </label>
                                        <label class="btn btn-star green">
                                            <input type="radio" name="quality" autocomplete="off" value="3" title="Удовлетворительно">
                                        </label>
                                        <label class="btn btn-star green">
                                            <input type="radio" name="quality" autocomplete="off" value="4" title="Хорошо">
                                        </label>
                                        <label class="btn btn-star green">
                                            <input type="radio" name="quality" autocomplete="off" value="5" title="Отлично">
                                        </label>
                                    </div>
                                    <span class="btn-star__value"></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-6 text-right">
                                    <label class="inline-label">Как вы в целом оцениваете продукт</label>
                                </div>
                                <div class="col-sm-6">
                                    <div class="btn-group btn-group_stars" data-toggle="buttons">
                                        <label class="btn btn-star yellow">
                                            <input type="radio" name="product_evaluation" autocomplete="off" value="1" title="Ужасно">
                                        </label>
                                        <label class="btn btn-star yellow">
                                            <input type="radio" name="product_evaluation" autocomplete="off" value="2" title="Плохо">
                                        </label>
                                        <label class="btn btn-star yellow">
                                            <input type="radio" name="product_evaluation" autocomplete="off" value="3" title="Удовлетворительно">
                                        </label>
                                        <label class="btn btn-star yellow">
                                            <input type="radio" name="product_evaluation" autocomplete="off" value="4" title="Хорошо">
                                        </label>
                                        <label class="btn btn-star yellow">
                                            <input type="radio" name="product_evaluation" autocomplete="off" value="5" title="Отлично">
                                        </label>
                                    </div>
                                    <span class="btn-star__value"></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-6 text-right">
                                    <label class="inline-label">Будите ли вы рекомендовать Juicy Couture друзьям ?</label>
                                </div>
                                <div class="col-sm-6">
                                    <div class="btn-group btn-group__recommendation" data-toggle="buttons">
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="1">1
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="2">2
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="3">3
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="4">4
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="5">5
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="6">6
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="7">7
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="8">8
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="9">9
                                        </label>
                                        <label class="btn btn-radio">
                                            <input type="radio" name="jc_recommendation" autocomplete="off" value="10">10
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer b-modal-review__footer">
                        <input type="submit" class="btn btn-primary" value="Опубликовать отзыв">
                    </div>
                </form>
			</div>
		</div>
	</div>


<?
$GLOBALS["CATALOG_CURRENT_ELEMENT_ID"] = $ElementID;
unset($basketAction);
if ($ElementID > 0)
{
	if($arParams["USE_STORE"] == "Y" && ModuleManager::isModuleInstalled("catalog"))
	{
		?><?
	}

	$arRecomData = array();
	$recomCacheID = array('IBLOCK_ID' => $arParams['IBLOCK_ID']);
	$obCache = new CPHPCache();
	if ($obCache->InitCache(36000, serialize($recomCacheID), "/catalog/recommended"))
	{
		$arRecomData = $obCache->GetVars();
	}
	elseif ($obCache->StartDataCache())
	{
		if (Loader::includeModule("catalog"))
		{
			$arSKU = CCatalogSKU::GetInfoByProductIBlock($arParams['IBLOCK_ID']);
			$arRecomData['OFFER_IBLOCK_ID'] = (!empty($arSKU) ? $arSKU['IBLOCK_ID'] : 0);
			$arRecomData['IBLOCK_LINK'] = '';
			$arRecomData['ALL_LINK'] = '';
			$rsProps = CIBlockProperty::GetList(
				array('SORT' => 'ASC', 'ID' => 'ASC'),
				array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'PROPERTY_TYPE' => 'E', 'ACTIVE' => 'Y')
			);
			$found = false;
			while ($arProp = $rsProps->Fetch())
			{
				if ($found)
				{
					break;
				}
				if ($arProp['CODE'] == '')
				{
					$arProp['CODE'] = $arProp['ID'];
				}
				$arProp['LINK_IBLOCK_ID'] = intval($arProp['LINK_IBLOCK_ID']);
				if ($arProp['LINK_IBLOCK_ID'] != 0 && $arProp['LINK_IBLOCK_ID'] != $arParams['IBLOCK_ID'])
				{
					continue;
				}
				if ($arProp['LINK_IBLOCK_ID'] > 0)
				{
					if ($arRecomData['IBLOCK_LINK'] == '')
					{
						$arRecomData['IBLOCK_LINK'] = $arProp['CODE'];
						$found = true;
					}
				}
				else
				{
					if ($arRecomData['ALL_LINK'] == '')
					{
						$arRecomData['ALL_LINK'] = $arProp['CODE'];
					}
				}
			}
			if ($found)
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->StartTagCache("/catalog/recommended");
					$CACHE_MANAGER->RegisterTag("iblock_id_".$arParams["IBLOCK_ID"]);
					$CACHE_MANAGER->EndTagCache();
				}
			}
		}
		$obCache->EndDataCache($arRecomData);
	}
	if (!empty($arRecomData) && false)
	{
		if (ModuleManager::isModuleInstalled("sale") && (!isset($arParams['USE_BIG_DATA']) || $arParams['USE_BIG_DATA'] != 'N'))
		{
			?><?$APPLICATION->IncludeComponent("bitrix:catalog.bigdata.products", "", array(
				"LINE_ELEMENT_COUNT" => 5,
				"TEMPLATE_THEME" => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
				"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
				"BASKET_URL" => $arParams["BASKET_URL"],
				"ACTION_VARIABLE" => (!empty($arParams["ACTION_VARIABLE"]) ? $arParams["ACTION_VARIABLE"] : "action")."_cbdp",
				"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
				"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
				"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
				"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
				"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
				"SHOW_OLD_PRICE" => $arParams['SHOW_OLD_PRICE'],
				"SHOW_DISCOUNT_PERCENT" => $arParams['SHOW_DISCOUNT_PERCENT'],
				"PRICE_CODE" => $arParams["PRICE_CODE"],
				"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
				"PRODUCT_SUBSCRIPTION" => $arParams['PRODUCT_SUBSCRIPTION'],
				"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
				"USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
				"SHOW_NAME" => "Y",
				"SHOW_IMAGE" => "Y",
				"MESS_BTN_BUY" => $arParams['MESS_BTN_BUY'],
				"MESS_BTN_DETAIL" => $arParams['MESS_BTN_DETAIL'],
				"MESS_BTN_SUBSCRIBE" => $arParams['MESS_BTN_SUBSCRIBE'],
				"MESS_NOT_AVAILABLE" => $arParams['MESS_NOT_AVAILABLE'],
				"PAGE_ELEMENT_COUNT" => 5,
				"SHOW_FROM_SECTION" => "N",
				"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"DEPTH" => "2",
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
				"SHOW_PRODUCTS_".$arParams["IBLOCK_ID"] => "Y",
				"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
				"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
				"CURRENCY_ID" => $arParams["CURRENCY_ID"],
				"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
				"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
				"SECTION_ELEMENT_ID" => $arResult["VARIABLES"]["SECTION_ID"],
				"SECTION_ELEMENT_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
				"ID" => $ElementID,
				"LABEL_PROP_".$arParams["IBLOCK_ID"] => $arParams['LABEL_PROP'],
				"PROPERTY_CODE_".$arParams["IBLOCK_ID"] => $arParams["LIST_PROPERTY_CODE"],
				"PROPERTY_CODE_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["LIST_OFFERS_PROPERTY_CODE"],
				"CART_PROPERTIES_".$arParams["IBLOCK_ID"] => $arParams["PRODUCT_PROPERTIES"],
				"CART_PROPERTIES_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFERS_CART_PROPERTIES"],
				"ADDITIONAL_PICT_PROP_".$arParams["IBLOCK_ID"] => $arParams['ADD_PICT_PROP'],
				"ADDITIONAL_PICT_PROP_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams['OFFER_ADD_PICT_PROP'],
				"OFFER_TREE_PROPS_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFER_TREE_PROPS"],
				"RCM_TYPE" => (isset($arParams['BIG_DATA_RCM_TYPE']) ? $arParams['BIG_DATA_RCM_TYPE'] : '')
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);
		}
		if (($arRecomData['IBLOCK_LINK'] != '' || $arRecomData['ALL_LINK'] != ''))
		{
	?><?
		$APPLICATION->IncludeComponent(
			"bitrix:catalog.recommended.products",
			"",
			array(
				"LINE_ELEMENT_COUNT" => $arParams["ALSO_BUY_ELEMENT_COUNT"],
				"TEMPLATE_THEME" => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
				"ID" => $ElementID,
				"PROPERTY_LINK" => ($arRecomData['IBLOCK_LINK'] != '' ? $arRecomData['IBLOCK_LINK'] : $arRecomData['ALL_LINK']),
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"BASKET_URL" => $arParams["BASKET_URL"],
				"ACTION_VARIABLE" => (!empty($arParams["ACTION_VARIABLE"]) ? $arParams["ACTION_VARIABLE"] : "action")."_crp",
				"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
				"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
				"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
				"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
				"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
				"PAGE_ELEMENT_COUNT" => $arParams["ALSO_BUY_ELEMENT_COUNT"],
				"SHOW_OLD_PRICE" => $arParams['SHOW_OLD_PRICE'],
				"SHOW_DISCOUNT_PERCENT" => $arParams['SHOW_DISCOUNT_PERCENT'],
				"PRICE_CODE" => $arParams["PRICE_CODE"],
				"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
				"PRODUCT_SUBSCRIPTION" => 'N',
				"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
				"USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
				"SHOW_NAME" => "Y",
				"SHOW_IMAGE" => "Y",
				"MESS_BTN_BUY" => $arParams['MESS_BTN_BUY'],
				"MESS_BTN_DETAIL" => $arParams["MESS_BTN_DETAIL"],
				"MESS_NOT_AVAILABLE" => $arParams['MESS_NOT_AVAILABLE'],
				"MESS_BTN_SUBSCRIBE" => $arParams['MESS_BTN_SUBSCRIBE'],
				"SHOW_PRODUCTS_".$arParams["IBLOCK_ID"] => "Y",
				"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
				"OFFER_TREE_PROPS_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFER_TREE_PROPS"],
				"OFFER_TREE_PROPS_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFER_TREE_PROPS"],
				"ADDITIONAL_PICT_PROP_".$arParams['IBLOCK_ID'] => $arParams['ADD_PICT_PROP'],
				"ADDITIONAL_PICT_PROP_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams['OFFER_ADD_PICT_PROP'],
				"PROPERTY_CODE_".$arRecomData['OFFER_IBLOCK_ID'] => array(),
				"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
				"CURRENCY_ID" => $arParams["CURRENCY_ID"]
			),
			$component
		);
	?><?
		}
	}

	if($arParams["USE_ALSO_BUY"] == "Y" && ModuleManager::isModuleInstalled("sale") && !empty($arRecomData))
	{
		?><?$APPLICATION->IncludeComponent("bitrix:sale.recommended.products", ".default", array(
			"ID" => $ElementID,
			"TEMPLATE_THEME" => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
			"MIN_BUYES" => $arParams["ALSO_BUY_MIN_BUYES"],
			"ELEMENT_COUNT" => $arParams["ALSO_BUY_ELEMENT_COUNT"],
			"LINE_ELEMENT_COUNT" => $arParams["ALSO_BUY_ELEMENT_COUNT"],
			"DETAIL_URL" => $arParams["DETAIL_URL"],
			"BASKET_URL" => $arParams["BASKET_URL"],
			"ACTION_VARIABLE" => (!empty($arParams["ACTION_VARIABLE"]) ? $arParams["ACTION_VARIABLE"] : "action")."_srp",
			"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
			"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
			"PAGE_ELEMENT_COUNT" => $arParams["ALSO_BUY_ELEMENT_COUNT"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"PRICE_CODE" => $arParams["PRICE_CODE"],
			"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
			"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
			"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
			'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
			'CURRENCY_ID' => $arParams['CURRENCY_ID'],
			'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
			"SHOW_PRODUCTS_".$arParams["IBLOCK_ID"] => "Y",
			"PROPERTY_CODE_".$arRecomData['OFFER_IBLOCK_ID'] => array(    ),
			"OFFER_TREE_PROPS_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFER_TREE_PROPS"],
			"OFFER_TREE_PROPS_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams["OFFER_TREE_PROPS"],
			"ADDITIONAL_PICT_PROP_".$arParams['IBLOCK_ID'] => $arParams['ADD_PICT_PROP'],
			"ADDITIONAL_PICT_PROP_".$arRecomData['OFFER_IBLOCK_ID'] => $arParams['OFFER_ADD_PICT_PROP']
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);
?><?
	}
}
?>
	</div>
	<?if ($isSidebar):?>
	<div class="col-md-3 col-sm-4">
		<?$APPLICATION->IncludeComponent(
			"bitrix:main.include",
			"",
			Array(
				"AREA_FILE_SHOW" => "file",
				"PATH" => $arParams["SIDEBAR_PATH"],
				"AREA_FILE_RECURSIVE" => "N",
				"EDIT_MODE" => "html",
			),
			false,
			Array('HIDE_ICONS' => 'Y')
		);?>
	</div>
	<?endif?>
</div>