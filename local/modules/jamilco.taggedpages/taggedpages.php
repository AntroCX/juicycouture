<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

Bitrix\Main\Loader::includeModule('jamilco.taggedpages');

$rsTaggedPages = Jamilco\TaggedPages\PagesTable::getList(
    array(
        'filter' => array(
            'URL' => $GLOBALS['APPLICATION']->GetCurPage()
        ),
        'limit'  => 1
    )
);
$arTaggedPage = $rsTaggedPages->Fetch();

if ($arTaggedPage['ACTIVE']) {

    $arTaggedPage['RULE_URL_DIR'] = dirname($arTaggedPage['RULE_URL']).'/';
    $arUrl = explode('?', $arTaggedPage['RULE_URL'], 2);
    parse_str($arTaggedPage['RULE_PARAMS'], $arThisPageFilter);

    $smartFilterPath = '';
    $arSmartFilterSef = '';
    $sectionCodePath = '';
    $sectionCode = '';
    $sectionId = '';
    if(strpos(array_keys($arThisPageFilter)[0], 'arrFilter_') !== false) {
        // параметрический фильтр
        $GLOBALS['arCatalogFilter'] = $arThisPageFilter;
        $arTaggSections = explode(',',$arTaggedPage['SECTIONS']);
        TrimArr($arTaggSections);
        if (!count($arTaggSections)) {
            // если раздел не указан явно, то возьмем его из УРЛа
            $arTaggSections = array(\Jamilco\TaggedPages\SectionFilter::getSectionByURL($arUrl[0]));
        }
        if ($arTaggSections) {
            $GLOBALS['arCatalogFilter']['SECTION_ID'] = $arTaggSections;
            if ($arTaggedPage['RULE_URL_DIR'] == '/catalog/' && count($arTaggSections) == 1) {
                // базовым УРЛом будет УРЛ раздела
                $sectionId = array_shift($arTaggSections);
                $arSect = CIblockSection::GetByID($sectionId)->GetNext();
                $arTaggedPage['RULE_URL_DIR'] = $arSect['SECTION_PAGE_URL'];
            }
        }
    }
    else{
        // чпу-фильтр
        $GLOBALS['APPLICATION']->SetCurPage($arTaggedPage["RULE_URL"]."index.php", '');
        $smartFilterPath = $arTaggedPage["RULE_URL"];
        preg_match('/^\/catalog\/(.+)\/filter\/(.+)\/apply\/.*$/s', $smartFilterPath, $matches);
        if($matches[1]){
            $sectionCodePath = $matches[1];
            $arSectionCodePath = explode("/", $sectionCodePath);
            $sectionCode = end($arSectionCodePath);
            if($sectionCode){
                $db_res = CIBlockSection::GetList(array(), array("CODE" => $sectionCode), false, array("ID"));
                if($arSection = $db_res->Fetch()){
                    $sectionId = $arSection["ID"];
                }
            }
        }
        if($matches[2]){
            $smartFilterSef = $matches[2];
            $arSmartFilterSef = explode("/", $smartFilterSef);
        }
    }

    $GLOBALS['arCatalogFilter']['INCLUDE_SUBSECTIONS'] = 'Y';
    $GLOBALS['arCatalogFilter'][] = [
        'LOGIC' => 'OR',
        ['>PROPERTY_RETAIL_QUANTITY' => 0],
        ['>CATALOG_QUANTITY' => 0]
    ];

    ?>
    <?if($arTaggedPage['TOP_HTML']):?>
    <div class="row medium">
        <div class="tp-top-html">
            <?=$arTaggedPage['TOP_HTML'];?>
        </div>
    </div>
    <?endif;?>
    <?
    $_REQUEST['TAG_PAGE'] = 'Y';
    $_REQUEST['TAG_PAGE_TITLE'] = $arTaggedPage['TITLE'];
    $_REQUEST['TAG_PAGE_BASE_URL'] = $arTaggedPage['RULE_URL_DIR'];
    $_REQUEST['TAG_PAGE_SHOW_FILTER'] = ($arTaggedPage['SHOW_FILTER']) ? 'Y' : 'N';

    global $APPLICATION;
   $APPLICATION->IncludeComponent(
        "bitrix:catalog",
        "main",
        array(
		"ACTION_VARIABLE" => "action",
		"ADD_ELEMENT_CHAIN" => "Y",
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"ADD_SECTIONS_CHAIN" => "Y",
            "AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "Y",
            "AJAX_OPTION_JUMP" => "N",
            "AJAX_OPTION_STYLE" => "Y",
		"BASKET_URL" => "/personal/basket.php",
		"BIG_DATA_RCM_TYPE" => "bestsell",
		"CACHE_FILTER" => "Y",
            "CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "N",
		"COMMON_ADD_TO_BASKET_ACTION" => "BUY",
		"COMMON_SHOW_CLOSE_POPUP" => "N",
		"CONVERT_CURRENCY" => "Y",
		"DETAIL_ADD_DETAIL_TO_SLIDER" => "N",
		"DETAIL_ADD_TO_BASKET_ACTION" => array(
			0 => "BUY",
		),
		"DETAIL_BACKGROUND_IMAGE" => "-",
		"DETAIL_BLOG_USE" => "N",
		"DETAIL_BRAND_USE" => "N",
		"DETAIL_BROWSER_TITLE" => "-",
		"DETAIL_CHECK_SECTION_ID_VARIABLE" => "N",
		"DETAIL_DETAIL_PICTURE_MODE" => "IMG",
		"DETAIL_DISPLAY_NAME" => "Y",
		"DETAIL_DISPLAY_PREVIEW_TEXT_MODE" => "E",
		"DETAIL_FB_USE" => "N",
		"DETAIL_META_DESCRIPTION" => "-",
		"DETAIL_META_KEYWORDS" => "-",
		"DETAIL_PROPERTY_CODE" => array(
			0 => "COLOR",
			1 => "SIZES_CLOTHES",
			2 => "SIZES_SHOES",
		),
		"DETAIL_SET_CANONICAL_URL" => "Y",
		"DETAIL_SET_VIEWED_IN_COMPONENT" => "Y",
		"DETAIL_SHOW_MAX_QUANTITY" => "Y",
		"DETAIL_USE_COMMENTS" => "Y",
		"DETAIL_USE_VOTE_RATING" => "Y",
		"DETAIL_VK_USE" => "N",
		"DISABLE_INIT_JS_IN_COMPONENT" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_TOP_PAGER" => "Y",
		"ELEMENT_SORT_FIELD" => "SORT",
		"ELEMENT_SORT_FIELD2" => "PROPERTY_CAN_BUY",
		"ELEMENT_SORT_ORDER" => "ASC",
		"ELEMENT_SORT_ORDER2" => "DESC",
		"FILTER_FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"FILTER_NAME" => "arCatalogFilter",
		"FILTER_PRICE_CODE" => array(
			0 => "BASE",
		),
		"FILTER_PROPERTY_CODE" => array(
			0 => "",
			1 => "",
		),
            "FILTER_VIEW_MODE" => "VERTICAL",
		"FORUM_ID" => "",
		"GIFTS_DETAIL_BLOCK_TITLE" => "Выберите один из подарков",
		"GIFTS_DETAIL_HIDE_BLOCK_TITLE" => "N",
		"GIFTS_DETAIL_PAGE_ELEMENT_COUNT" => "3",
		"GIFTS_DETAIL_TEXT_LABEL_GIFT" => "Подарок",
		"GIFTS_MAIN_PRODUCT_DETAIL_BLOCK_TITLE" => "Выберите один из товаров, чтобы получить подарок",
		"GIFTS_MAIN_PRODUCT_DETAIL_HIDE_BLOCK_TITLE" => "N",
		"GIFTS_MAIN_PRODUCT_DETAIL_PAGE_ELEMENT_COUNT" => "3",
		"GIFTS_MESS_BTN_BUY" => "Выбрать",
		"GIFTS_SECTION_LIST_BLOCK_TITLE" => "Подарки к товарам этого раздела",
		"GIFTS_SECTION_LIST_HIDE_BLOCK_TITLE" => "N",
		"GIFTS_SECTION_LIST_PAGE_ELEMENT_COUNT" => "3",
		"GIFTS_SECTION_LIST_TEXT_LABEL_GIFT" => "Подарок",
		"GIFTS_SHOW_DISCOUNT_PERCENT" => "Y",
		"GIFTS_SHOW_IMAGE" => "Y",
		"GIFTS_SHOW_NAME" => "Y",
		"GIFTS_SHOW_OLD_PRICE" => "Y",
		"HIDE_NOT_AVAILABLE" => "N",
		"IBLOCK_ID" => "1",
		"IBLOCK_TYPE" => "catalog",
		"INCLUDE_SUBSECTIONS" => "Y",
		"INSTANT_RELOAD" => "Y",
		"LINE_ELEMENT_COUNT" => "3",
		"LINK_ELEMENTS_URL" => "link.php?PARENT_ELEMENT_ID=#ELEMENT_ID#",
		"LINK_IBLOCK_ID" => "",
		"LINK_IBLOCK_TYPE" => "",
		"LINK_PROPERTY_SID" => "",
		"LIST_BROWSER_TITLE" => "-",
		"LIST_META_DESCRIPTION" => "-",
		"LIST_META_KEYWORDS" => "-",
		"LIST_PROPERTY_CODE" => array(
			0 => "COLOR",
			1 => "SIZES_CLOTHES",
			2 => "SIZES_SHOES",
		),
		"MESSAGES_PER_PAGE" => "5",
		"MESSAGE_404" => "",
		"MESS_BTN_ADD_TO_BASKET" => "В корзину",
		"MESS_BTN_BUY" => "Купить",
		"MESS_BTN_COMPARE" => "Сравнение",
		"MESS_BTN_DETAIL" => "Подробнее",
		"MESS_NOT_AVAILABLE" => "Нет в наличии",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "Y",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => "main",
		"PAGER_TITLE" => "Товары",
		"PAGE_ELEMENT_COUNT" => "12", // 12
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
            "PATH_TO_SMILE" => "/bitrix/images/forum/smile/",
		"PRICE_CODE" => array(
			0 => "BASE",
			1 => 'SALE'
		),
            "PRICE_VAT_INCLUDE" => "Y",
            "PRICE_VAT_SHOW_VALUE" => "N",
            "PRODUCT_ID_VARIABLE" => "id",
		"PRODUCT_PROPERTIES" => array(
			0 => "COLOR",
			1 => "SIZES_CLOTHES",
			2 => "SIZES_SHOES",
			3 => "SIZES_RINGS"
		),
            "PRODUCT_PROPS_VARIABLE" => "prop",
		"PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"REVIEW_AJAX_POST" => "Y",
            "SECTIONS_SHOW_PARENT_NAME" => "Y",
		"SECTIONS_VIEW_MODE" => "LIST",
		"SECTION_ADD_TO_BASKET_ACTION" => "ADD",
		"SECTION_BACKGROUND_IMAGE" => "-",
		"SECTION_COUNT_ELEMENTS" => "Y",
		"SECTION_ID_VARIABLE" => "SECTION_ID",
		"SECTION_TOP_DEPTH" => "1",
		"SEF_FOLDER" => "/catalog/",
		"SEF_MODE" => "Y",
		"SET_LAST_MODIFIED" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "Y",
		"SHOW_DEACTIVATED" => "N",
		"SHOW_DISCOUNT_PERCENT" => "N",
		"SHOW_LINK_TO_FORUM" => "Y",
		"SHOW_OLD_PRICE" => "Y",
		"SHOW_PRICE_COUNT" => "1",
		"SHOW_TOP_ELEMENTS" => "N",
		"SIDEBAR_DETAIL_SHOW" => "N",
		"SIDEBAR_PATH" => "",
		"SIDEBAR_SECTION_SHOW" => "N",
		"TEMPLATE_THEME" => "blue",
		"TOP_ADD_TO_BASKET_ACTION" => "ADD",
		"TOP_ELEMENT_COUNT" => "9",
		"TOP_ELEMENT_SORT_FIELD" => "sort",
		"TOP_ELEMENT_SORT_FIELD2" => "id",
		"TOP_ELEMENT_SORT_ORDER" => "asc",
		"TOP_ELEMENT_SORT_ORDER2" => "desc",
		"TOP_LINE_ELEMENT_COUNT" => "3",
		"TOP_PROPERTY_CODE" => array(
			0 => "",
			1 => "",
		),
		"TOP_VIEW_MODE" => "SECTION",
		"URL_TEMPLATES_READ" => "",
		"USE_ALSO_BUY" => "N",
		"USE_BIG_DATA" => "Y",
		"USE_CAPTCHA" => "N",
		"USE_COMMON_SETTINGS_BASKET_POPUP" => "Y",
		"USE_COMPARE" => "N",
		"USE_ELEMENT_COUNTER" => "Y",
		"USE_FILTER" => "Y",
		"USE_GIFTS_DETAIL" => "Y",
		"USE_GIFTS_MAIN_PR_SECTION_LIST" => "Y",
		"USE_GIFTS_SECTION" => "Y",
		"USE_MAIN_ELEMENT_SECTION" => "Y",
		"USE_PRICE_COUNT" => "N",
		"USE_PRODUCT_QUANTITY" => "Y",
		"USE_REVIEW" => "Y",
		"USE_SALE_BESTSELLERS" => "N",
            "USE_STORE" => "Y",
		"COMPONENT_TEMPLATE" => "main",
		"ADD_PICT_PROP" => "-",
		"LABEL_PROP" => "-",
		"PRODUCT_DISPLAY_MODE" => "Y",
		"OFFER_ADD_PICT_PROP" => "-",
		"OFFER_TREE_PROPS" => array(
			0 => "COLOR",
			1 => "SIZES_CLOTHES",
			2 => "SIZES_SHOES",
			3 => "SIZES_RINGS"
		),
		"FILTER_OFFERS_FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"FILTER_OFFERS_PROPERTY_CODE" => array(
			0 => "SIZES_SHOES",
			1 => "SIZES_CLOTHES",
			2 => "COLOR",
			4 => "SIZES_RINGS",
		),
		"OFFERS_CART_PROPERTIES" => array(
			0 => "ARTNUMBER",
			1 => "SIZES_SHOES",
			2 => "SIZES_CLOTHES",
			3 => "COLOR",
			4 => "SIZES_RINGS"
		),
		"TOP_OFFERS_FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"TOP_OFFERS_PROPERTY_CODE" => array(
			0 => "ARTNUMBER",
			1 => "MORE_PHOTO",
			2 => "SIZES_SHOES",
			3 => "SIZES_CLOTHES",
			4 => "COLOR",
			5 => "",
		),
		"TOP_OFFERS_LIMIT" => "5",
		"LIST_OFFERS_FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"LIST_OFFERS_PROPERTY_CODE" => array(
			0 => "ARTNUMBER",
			1 => "MORE_PHOTO",
			2 => "SIZES_SHOES",
			3 => "SIZES_CLOTHES",
			4 => "COLOR",
			5 => "VIDEO",
		),
		"LIST_OFFERS_LIMIT" => "0",
		"DETAIL_OFFERS_FIELD_CODE" => array(
			0 => "NAME",
			1 => "",
		),
		"DETAIL_OFFERS_PROPERTY_CODE" => array(
			0 => "ARTNUMBER",
			1 => "MORE_PHOTO",
			2 => "SIZES_SHOES",
			3 => "SIZES_CLOTHES",
			4 => "COLOR",
			5 => "SIZES_RINGS",
		),
		"STORES" => array(
			0 => "1",
		),
		"USE_MIN_AMOUNT" => "Y",
		"USER_FIELDS" => array(
			0 => "",
			1 => "",
		),
		"FIELDS" => array(
			0 => "TITLE",
			1 => "ADDRESS",
			2 => "DESCRIPTION",
			3 => "PHONE",
			4 => "SCHEDULE",
			5 => "EMAIL",
			6 => "IMAGE_ID",
			7 => "COORDINATES",
			8 => "",
		),
		"MIN_AMOUNT" => "10",
		"SHOW_EMPTY_STORE" => "N",
		"SHOW_GENERAL_STORE_INFORMATION" => "N",
        "STORE_PATH" => "/store/#store_id#",
		"MAIN_TITLE" => "Наличие в магазинах",
		"OFFERS_SORT_FIELD" => "CATALOG_PRICE_1",
        "OFFERS_SORT_ORDER" => "asc",
		"OFFERS_SORT_FIELD2" => "CATALOG_PRICE_1",
		"OFFERS_SORT_ORDER2" => "asc",
        "DETAIL_VOTE_DISPLAY_AS_RATING" => "rating",
		"CURRENCY_ID" => "RUB",
		"DETAIL_SHOW_BASIS_PRICE" => "Y",
        "DETAIL_BLOG_URL" => "catalog_comments",
        "DETAIL_VK_USE" => "Y",
        "DETAIL_VK_API_ID" => "API_ID",
        "DETAIL_FB_USE" => "Y",
        "DETAIL_FB_APP_ID" => "",
        "DETAIL_BRAND_USE" => "Y",
        "DETAIL_BRAND_PROP_CODE" => "BRAND_REF",
        "AJAX_OPTION_ADDITIONAL" => "",
        "SEF_URL_TEMPLATES" => array(
            "filter" => "filter/".filterUrls()."/apply/",
            "sections" => "",
            "section" => "#SECTION_CODE_PATH#/",
            "section_new" => "#SECTION_CODE_PATH#/new/",
            "section_sale" => "#SECTION_CODE_PATH#/sale/",
            "element" => "#SECTION_CODE_PATH#/#ELEMENT_CODE#/",
            "compare" => "compare.php?action=#ACTION_CODE#",
            "smart_filter" => "#SECTION_CODE_PATH#/filter/#SMART_FILTER_PATH#/apply/",
        ),
        "VARIABLE_ALIASES" => array(),
        //TAG QUERY STRING
        'TAG_QUERY_STRING' => $arUrl[1] ? urldecode($arUrl[1]) : '',
        // чпу-фильтр
        'SMART_FILTER_PATH' => $smartFilterPath? $smartFilterPath: '',
        'SEF_FILTER_PATH' => $arSmartFilterSef? $arSmartFilterSef: '',
        'SECTION_CODE_PATH' => $sectionCodePath? $sectionCodePath: '',
        'SECTION_ID' => $sectionId? $sectionId: '',
        'SECTION_CODE' => $sectionCode? $sectionCode: '',
        ),
        false
    );
    ?>
    <?if($arTaggedPage['BOTTOM_HTML']):?>
        <div class="row medium">
            <div class="tp-bottom-html">
                <?=$arTaggedPage['BOTTOM_HTML'];?>
            </div>
        </div>
    <?endif;?>
    <?
    $c = $_REQUEST["c"];
    $collectionID = $_REQUEST["i"];
    if ($collectionID && $c == "collections") {
        if (strstr($APPLICATION->GetCurDir(), '/catalog/men/')) {
            $preffix = "Мужские";
            $preffix_low = "мужские";
        } elseif (strstr($APPLICATION->GetCurDir(), '/catalog/women/')) {
            $preffix = "Женские";
            $preffix_low = "женские";
        }
    }
} else {
    CHTTP::SetStatus("404 Not Found");
    @define("ERROR_404", "Y");
}
?>
<style>
    .tp-top-html, .tp-bottom-html{
        padding: 10px;
        margin: 20px auto;
    }
</style>

<?
// seo metategs
$GLOBALS['APPLICATION']->SetPageProperty("keywords", $arTaggedPage['SEO_KEYWORDS']);
$GLOBALS['APPLICATION']->SetPageProperty("title", $arTaggedPage['SEO_TITLE']);
$GLOBALS['APPLICATION']->SetPageProperty("description", $arTaggedPage['SEO_DESCRIPTION']);
$GLOBALS['APPLICATION']->SetPageProperty("og:title", $arTaggedPage['SEO_TITLE']);

?>
<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");