<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Jamilco\Blocks\Block::load(array('b-shops'));

if ($_REQUEST['ajax'] == 'Y') $APPLICATION->RestartBuffer(); // ajax запрос
?>
</div>
<div class="b-catalog">
	<div class="b-catalog__content">
		<div class="row">
			<div class="col-md-12">
				<div class="b-catalog__views hidden-xs">
					<span class="b-catalog__views-title">Отображение:</span>
					<?if(isset($_REQUEST['view'])) {
						$GLOBALS['APPLICATION']->set_cookie('view', $_REQUEST['view']);
						$view = $_REQUEST['view'];
					} else {
						$cookieView = $GLOBALS['APPLICATION']->get_cookie('view');
						$view = ($cookieView) ? $cookieView : 'three';
					}?>
					<a class="b-catalog__views-three <?if($view == 'three'):?>active<?endif?>"
					   href="<?=$GLOBALS['APPLICATION']->GetCurPageParam('view=three', array('view', 'ajax'))?>"></a>
					<a class="b-catalog__views-two <?if($view == 'two'):?>active<?endif?>"
						href="<?=$GLOBALS['APPLICATION']->GetCurPageParam('view=two', array('view', 'ajax'))?>"></a>
				</div>
                <?
                if(empty($arResult["VARIABLES"]["SMART_FILTER_PATH"]) || isset($arParams['TAG_URL'])){
                    $re = '/^\/.*\/filter\/(.*)\/apply\//';
                    $str = Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPage();
                    if(isset($arParams['TAG_URL']))
                        $str = $arParams['TAG_URL'];
                    preg_match($re, $str, $matches);
                    $arResult["VARIABLES"]["SMART_FILTER_PATH"] = $matches[1];
                    $arResult["VARIABLES"]["~SMART_FILTER_PATH"] = $matches[1];
                }

                /** выводим фильтр в карточке товара */
                ob_start();
                ?>
				<?$APPLICATION->IncludeComponent(
					"jamilco:catalog.smart.filter",
					"main",
					array(
						"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
						"IBLOCK_ID" => $arParams["IBLOCK_ID"],
						"SECTION_ID" => $arResult['VARIABLES']['SECTION_ID'],
						"FILTER_NAME" => $arParams["FILTER_NAME"],
						"PRICE_CODE" => $arParams["PRICE_CODE"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
						"DISPLAY_ELEMENT_COUNT" => "N",
						"SAVE_IN_SESSION" => "N",
						"FILTER_VIEW_MODE" => $arParams["FILTER_VIEW_MODE"],
						"XML_EXPORT" => "Y",
						"SECTION_TITLE" => "NAME",
						"SECTION_DESCRIPTION" => "DESCRIPTION",
						'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
						"TEMPLATE_THEME" => $arParams["TEMPLATE_THEME"],
						'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
						'CURRENCY_ID' => $arParams['CURRENCY_ID'],
						"SEF_MODE" => $arParams["SEF_MODE"],
						"SEF_RULE" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["smart_filter"],
						"SMART_FILTER_PATH" => $arResult["VARIABLES"]["SMART_FILTER_PATH"],
						"PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
						"INSTANT_RELOAD" => $arParams["INSTANT_RELOAD"],
					),
					$component,
					array('HIDE_ICONS' => 'Y')
				);?>
                <?
                $GLOBALS["FILTER_BLOCK_HTML"] = ob_get_contents();
                ob_end_clean();
                ?>
            </div>
			<div class="col-md-12 b-page__loader-wrapper">
				<div class="b-page__loader"></div>

				<?if($_REQUEST['sort']) {
					$arSort = array(
						'name-asc' => array(
							'field' => 'NAME',
							'sort' => 'ASC'
						),
						'name-desc' => array(
							'field' => 'NAME',
							'sort' => 'DESC'
						),
						'new' => array(
							'field' => 'PROPERTY_NEW',
							'sort' => 'ASC'
						),
						'price-asc' => array(
							'field' => 'PROPERTY_MINIMUM_PRICE',
							'sort' => 'ASC'
						),
						'price-desc' => array(
							'field' => 'PROPERTY_MINIMUM_PRICE',
							'sort' => 'DESC'
						)
					);
					$arParams["ELEMENT_SORT_FIELD"] = $arSort[$_REQUEST['sort']]['field'];
					$arParams["ELEMENT_SORT_ORDER"] = $arSort[$_REQUEST['sort']]['sort'];
                } else {
                    $defaultSort = \COption::GetOptionString('jamilco.merch', 'prop.default.sort');
                    if (in_array($defaultSort, ['ASC', 'DESC'])) {
                        $arParams['ELEMENT_SORT_FIELD'] = 'SORT';
                        $arParams['ELEMENT_SORT_ORDER'] = $defaultSort;
                    } else {
                        $arParams['ELEMENT_SORT_FIELD'] = 'PROPERTY_CAPSULE_SORT';
                        $arParams['ELEMENT_SORT_ORDER'] = 'ASC';
                    }
                }

                $cityStoreName = \Jamilco\Main\Retail::getItemFilter($GLOBALS[$arParams['FILTER_NAME']], false);

                // распродажные товары выводим только в /sale/
                $saleShow = \COption::GetOptionInt('jamilco.merch', 'sale.all', 0);
                $outletShow = \COption::GetOptionInt('jamilco.merch', 'outlet.all', 0);
                if (strpos($APPLICATION->GetCurDir(), '/sale/') == false && !$outletShow) {
                    $GLOBALS[$arParams['FILTER_NAME']]['OFFERS']['PROPERTY_OUTLET'] = false;
                }

                if (strpos($APPLICATION->GetCurDir(), '/sale/') == false && !$saleShow) {
                    $GLOBALS[$arParams['FILTER_NAME']][] = [
                        '=ID' => CIblockElement::SubQuery(
                            'PROPERTY_CML2_LINK',
                            [
                                'ACTIVE'          => 'Y',
                                'CATALOG_PRICE_2' => false,
                            ]
                        )
                    ];
                }
                //ppr($GLOBALS[$arParams['FILTER_NAME']]);

				$intSectionID = $APPLICATION->IncludeComponent(
					"jamilco:catalog.section",
					"main-".$view,
					array(
                        "BLOCK" => 'NEW',
                        "NAME" => 'Новые поступления',
						"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
						"IBLOCK_ID" => $arParams["IBLOCK_ID"],
						"ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
						"ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"],
						"ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
						"ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
						"PROPERTY_CODE" => $arParams["LIST_PROPERTY_CODE"],
						"META_KEYWORDS" => $arParams["LIST_META_KEYWORDS"],
						"META_DESCRIPTION" => $arParams["LIST_META_DESCRIPTION"],
						"BROWSER_TITLE" => $arParams["LIST_BROWSER_TITLE"],
						"SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
						"INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"],
						"BASKET_URL" => $arParams["BASKET_URL"],
						"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
						"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
						"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
						"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
						"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
						"FILTER_NAME" => $arParams["FILTER_NAME"],
                        //"FILTER_NAME" => 'arCatalogFilter',
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"CACHE_FILTER" => $arParams["CACHE_FILTER"],
						"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
						"SET_TITLE" => $arParams["SET_TITLE"],
						"MESSAGE_404" => $arParams["MESSAGE_404"],
						"SET_STATUS_404" => $arParams["SET_STATUS_404"],
						"SHOW_404" => $arParams["SHOW_404"],
						"FILE_404" => $arParams["FILE_404"],
						"DISPLAY_COMPARE" => $arParams["USE_COMPARE"],
						"PAGE_ELEMENT_COUNT" => $arParams["PAGE_ELEMENT_COUNT"],
						"LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
						"PRICE_CODE" => $arParams["PRICE_CODE"],
						"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
						"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],

						"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
						"USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
						"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
						"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
						"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],

						"DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
						"DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
						"PAGER_TITLE" => $arParams["PAGER_TITLE"],
						"PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
						"PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
						"PAGER_DESC_NUMBERING" => $arParams["PAGER_DESC_NUMBERING"],
						"PAGER_DESC_NUMBERING_CACHE_TIME" => $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
						"PAGER_SHOW_ALL" => $arParams["PAGER_SHOW_ALL"],
						"PAGER_BASE_LINK_ENABLE" => $arParams["PAGER_BASE_LINK_ENABLE"],
						"PAGER_BASE_LINK" => $arParams["PAGER_BASE_LINK"],
						"PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],

						"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
						"OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
						"OFFERS_PROPERTY_CODE" => $arParams["LIST_OFFERS_PROPERTY_CODE"],
						"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
						"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
						"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
						"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
						"OFFERS_LIMIT" => $arParams["LIST_OFFERS_LIMIT"],

						"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
						"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
						"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
						"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
						"USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],
						'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
						'CURRENCY_ID' => $arParams['CURRENCY_ID'],
						'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],

						'LABEL_PROP' => $arParams['LABEL_PROP'],
						'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
						'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],

						'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
						'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
						'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
						'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
						'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
						'MESS_BTN_BUY' => $arParams['MESS_BTN_BUY'],
						'MESS_BTN_ADD_TO_BASKET' => $arParams['MESS_BTN_ADD_TO_BASKET'],
						'MESS_BTN_SUBSCRIBE' => $arParams['MESS_BTN_SUBSCRIBE'],
						'MESS_BTN_DETAIL' => $arParams['MESS_BTN_DETAIL'],
						"SHOW_ALL_WO_SECTION" => "Y",
						'MESS_NOT_AVAILABLE' => $arParams['MESS_NOT_AVAILABLE'],

						'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
						"ADD_SECTIONS_CHAIN" => $arParams['ADD_SECTIONS_CHAIN'],
						'ADD_TO_BASKET_ACTION' => $basketAction,
						'SHOW_CLOSE_POPUP' => isset($arParams['COMMON_SHOW_CLOSE_POPUP']) ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
						'COMPARE_PATH' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['compare'],
						'BACKGROUND_IMAGE' => (isset($arParams['SECTION_BACKGROUND_IMAGE']) ? $arParams['SECTION_BACKGROUND_IMAGE'] : ''),
						'DISABLE_INIT_JS_IN_COMPONENT' => (isset($arParams['DISABLE_INIT_JS_IN_COMPONENT']) ? $arParams['DISABLE_INIT_JS_IN_COMPONENT'] : ''),
						//'IS_SALE' => 'Y'
					),
					$component
				);?>
			</div>
		</div>
	</div>
</div>

<?
$cur = $GLOBALS['APPLICATION']->GetCurPage();
$GLOBALS['APPLICATION']->AddHeadString('<link rel="canonical" href="http://juicycouture.ru'.$cur.'">');

$APPLICATION->IncludeComponent(
	"bitrix:main.include",
	"",
	Array(
		"AREA_FILE_SHOW" => "file",
		"AREA_FILE_SUFFIX" => "inc",
		"EDIT_TEMPLATE" => "",
		"PATH" => "/local/includes/quickModal.php"
	)
);

if ($_REQUEST['ajax'] == 'Y') die(); // ajax запрос
?>
<link rel="stylesheet" type="text/css" href="/local/blocks/b-catalog-detail/b-catalog-detail.css?<?=md5_file($_SERVER["DOCUMENT_ROOT"]."/local/blocks/b-catalog-detail/b-catalog-detail.css")?>">
<script src="/local/blocks/b-fastview/b-fastview.js?<?=md5_file($_SERVER["DOCUMENT_ROOT"]."/local/blocks/b-fastview/b-fastview.js")?>"></script>
<script src="/local/blocks/b-shops/b-shops.min.js"></script>


