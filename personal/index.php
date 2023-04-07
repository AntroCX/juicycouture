<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

Jamilco\Blocks\Block::load(array('b-form'));

?>

<h1>Личный кабинет</h1>

<div class="row">
    <div class="col-sm-2">
        <?$APPLICATION->IncludeComponent("bitrix:menu", "left", Array(
            "ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
            "CHILD_MENU_TYPE" => "left",	// Тип меню для остальных уровней
            "DELAY" => "N",	// Откладывать выполнение шаблона меню
            "MAX_LEVEL" => "1",	// Уровень вложенности меню
            "MENU_CACHE_GET_VARS" => array(	// Значимые переменные запроса
                0 => "",
            ),
            "MENU_CACHE_TIME" => "3600",	// Время кеширования (сек.)
            "MENU_CACHE_TYPE" => "A",	// Тип кеширования
            "MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
            "ROOT_MENU_TYPE" => "left",	// Тип меню для первого уровня
            "USE_EXT" => "N",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
        ),
        false
    );?>
    </div>
    <div class="col-sm-10">
        <?$APPLICATION->IncludeComponent(
            "bitrix:sale.personal.section",
            "personal",
            array(
                "ACCOUNT_PAYMENT_ELIMINATED_PAY_SYSTEMS" => array(
                    0 => "0",
                ),
                "ACCOUNT_PAYMENT_PERSON_TYPE" => "2",
                "ACCOUNT_PAYMENT_SELL_CURRENCY" => "RUB",
                "ACCOUNT_PAYMENT_SELL_SHOW_FIXED_VALUES" => "Y",
                "ACCOUNT_PAYMENT_SELL_TOTAL" => array(
                    0 => "100",
                    1 => "200",
                    2 => "500",
                    3 => "1000",
                    4 => "5000",
                    5 => "",
                ),
                "ACCOUNT_PAYMENT_SELL_USER_INPUT" => "N",
                "ACTIVE_DATE_FORMAT" => "d.m.Y",
                "ALLOW_INNER" => "N",
                "CACHE_GROUPS" => "Y",
                "CACHE_TIME" => "36000",
                "CACHE_TYPE" => "A",
                "CHECK_RIGHTS_PRIVATE" => "N",
                "COMPATIBLE_LOCATION_MODE_PROFILE" => "N",
                "CUSTOM_PAGES" => "[]",
                "CUSTOM_SELECT_PROPS" => array(
                ),
                "MAIN_CHAIN_NAME" => "МОЙ АККАУНТ",
                "NAV_TEMPLATE" => "",
                "ONLY_INNER_FULL" => "N",
                "ORDERS_PER_PAGE" => "20",
                "ORDER_DEFAULT_SORT" => "STATUS",
                "ORDER_DISALLOW_CANCEL" => "N",
                "ORDER_HIDE_USER_INFO" => array(
                    0 => "0",
                ),
                "ORDER_HISTORIC_STATUSES" => array(
                    0 => "F",
                ),
                "ORDER_REFRESH_PRICES" => "N",
                "ORDER_RESTRICT_CHANGE_PAYSYSTEM" => array(
                    0 => "0",
                ),
                "PATH_TO_BASKET" => "/personal/cart",
                "PATH_TO_CATALOG" => "/catalog/",
                "PATH_TO_CONTACT" => "/contacts",
                "PATH_TO_PAYMENT" => "/personal/order/",
                "PER_PAGE" => "20",
                "PROFILES_PER_PAGE" => "20",
                "PROP_1" => array(
                ),
                "PROP_2" => "",
                "SAVE_IN_SESSION" => "N",
                "SEF_FOLDER" => "/personal/",
                "SEF_MODE" => "Y",
                "SEND_INFO_PRIVATE" => "N",
                "SET_TITLE" => "Y",
                "SHOW_ACCOUNT_COMPONENT" => "N",
                "SHOW_ACCOUNT_PAGE" => "N",
                "SHOW_ACCOUNT_PAY_COMPONENT" => "N",
                "SHOW_BASKET_PAGE" => "Y",
                "SHOW_CONTACT_PAGE" => "Y",
                "SHOW_ORDER_PAGE" => "Y",
                "SHOW_PRIVATE_PAGE" => "Y",
                "SHOW_PROFILE_PAGE" => "N",
                "SHOW_SUBSCRIBE_PAGE" => "Y",
                "USER_PROPERTY_PRIVATE" => "",
                "USE_AJAX_LOCATIONS_PROFILE" => "N",
                "COMPONENT_TEMPLATE" => "personal",
                "SEF_URL_TEMPLATES" => array(
                    "index" => "profile/",
                    "orders" => "orders/",
                    "account" => "account/",
                    "subscribe" => "subscribe/",
                    "profile" => "profiles/",
                    "profile_detail" => "profiles/#ID#",
                    "private" => "private/",
                    "order_detail" => "orders/#ID#",
                    "order_cancel" => "cancel/#ID#",
                )
            ),
            false
        );?>

    </div>
</div>

<div data-retailrocket-markup-block="5bd1bd0f97a528207806f25b" data-stock-id="<?= \Jamilco\Main\Retail::getStoreName(true) ?>"></div>

<div class="row">
  <div class="col-xs-12 account-products-viewed">
    <?
    global $viewedFilter;
    \Jamilco\Main\Retail::getItemFilter($viewedFilter, false);

    $APPLICATION->IncludeComponent(
        "jamilco:catalog.products.viewed",
        "",
        Array(
            "CACHE_GROUPS"               => "Y",
            "CACHE_TIME"                 => "3600",
            "CACHE_TYPE"                 => "A",
            "CONVERT_CURRENCY"           => "Y",
            "CURRENCY_ID"                => "RUB",
            "DATA_LAYER_NAME"            => "dataLayer",
            "DEPTH"                      => "",
            "HIDE_NOT_AVAILABLE"         => "N",
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
    );
    ?>
  </div>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
