<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php"); ?>

<h1>Заказы</h1>

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
	"bitrix:sale.personal.order", 
	"main", 
	array(
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "3600",
		"CACHE_TYPE" => "A",
		"CUSTOM_SELECT_PROPS" => array(
		),
		"HISTORIC_STATUSES" => array(
		),
		"NAV_TEMPLATE" => "",
		"ORDERS_PER_PAGE" => "999",
		"PATH_TO_BASKET" => "/order/",
		"PATH_TO_PAYMENT" => "/order/payment/",
		"PROP_1" => array(
		),
		"SAVE_IN_SESSION" => "N",
		"SEF_FOLDER" => "/personal/orders/",
		"SEF_MODE" => "N",
		"SET_TITLE" => "Y",
		"STATUS_COLOR_F" => "gray",
		"STATUS_COLOR_N" => "green",
		"STATUS_COLOR_PSEUDO_CANCELLED" => "red",
		"COMPONENT_TEMPLATE" => "main",
		"DETAIL_HIDE_USER_INFO" => array(
			0 => "0",
		),
		"PATH_TO_CATALOG" => "/catalog/",
		"DISALLOW_CANCEL" => "N",
		"RESTRICT_CHANGE_PAYSYSTEM" => array(
			0 => "0",
		),
		"REFRESH_PRICES" => "N",
		"ORDER_DEFAULT_SORT" => "STATUS",
		"ALLOW_INNER" => "N",
		"ONLY_INNER_FULL" => "N",
		"STATUS_COLOR_A" => "gray",
		"STATUS_COLOR_AN" => "gray",
		"STATUS_COLOR_C" => "gray",
		"STATUS_COLOR_CA" => "gray",
		"STATUS_COLOR_D" => "gray",
		"STATUS_COLOR_EP" => "gray",
		"STATUS_COLOR_H" => "gray",
		"STATUS_COLOR_I" => "gray",
		"STATUS_COLOR_J" => "gray",
		"STATUS_COLOR_K" => "gray",
		"STATUS_COLOR_M" => "gray",
		"STATUS_COLOR_P" => "yellow",
		"STATUS_COLOR_R" => "gray",
		"STATUS_COLOR_S" => "gray"
	),
	false
);?>

        <div data-retailrocket-markup-block="5bd1bd0f97a528207806f25b" data-stock-id="<?= \Jamilco\Main\Retail::getStoreName(true) ?>"></div>
    </div>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
