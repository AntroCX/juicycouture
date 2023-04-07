<?
$arWorkHosts = array(
    'juicycouture.ru',
    'www.juicycouture.ru',
);

define('IS_DEV', !in_array($_SERVER['SERVER_NAME'], $arWorkHosts));

define('HIBLOCK_COLOR_ID', 1);

define('BASE_PRICE_ID', 1);
define('SALE_PRICE_ID', 2);

define('IBLOCK_CATALOG_ID', 1);
define('IBLOCK_SKU_ID', 2);
define('IBLOCK_SHOPS_ID', 8);
define('IBLOCK_TARIFS_KCE', 22);

define('LOCATIONS_CITIES_MO_30', [8809,7200,2969,3437,3508,4558,4916,5880,6406,6405,6975,6943,7195,12394,14425]);

define('MOSCOW_LOCATION_ID', 19);
define('SPB_LOCATION_ID', 30);

define('IBLOCK_CHANGE_ORDER_ID', 23); // Изменение цен в заказах
define('IBLOCK_COMPLECTS_ID', 24); // Комплекты
define('IBLOCK_MANZANA_COUPONS', 26);

define('RETAIL_STORE_ID', 10); // склад розницы

define('ORDER_PROP_ADDRESS_ID', 6);
define('ORDER_PROP_STORE_ID', 9);
define('ORDER_PROP_OMNI_CHANNEL_ID', 13);

define('DELIVERY_COURIER_ID', 18);
define('DELIVERY_OZON_ID', 19);
define('DELIVERY_PICKUP_ID', 20);

define('DEFAULT_CITY_ID', (IS_DEV) ? 84 : 19);

// online pay discount percent
define('ONLINE_PAY_DISCOUNT', 0.05);

// deliveries
define('COURIER_DELIVERY', 18);
define('CURIER_DELIVERY', 18);
define('OZON_DELIVERY', 19);
define('PICKUP_DELIVERY', 20);
define('DAY_DELIVERY', 21);
define('KCE_DELIVERY', 22);
define('GOODS_RU_DELIVERY', 23);

// pay systems
define('CASH_PAY_SYSTEM', 3);
define('CASH_PAYSYSTEM', 3);
define('ONLINE_PAY_SYSTEM', 7);
define('ONLINE_PAYSYSTEM', 7);

define('ORDER_PROP_TK_NAME', 32); // назв. трансп. компании
define('ORDER_PROP_TK_NUM', 33); // трек-номер заказа

define('SUBSCRIBE_IBLOCK', 21);

require_once 'include/functions.php';
require_once 'include/basket_events.php';
require_once 'include/ocs_events.php';
require_once 'include/events.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/local/properties/ufColorPicker/ufColorPicker.php';
require_once 'include/ymarket_handler.php';

define('BLACK_FRIDAY', 'Y');

// Автозагрузка классов
require_once __DIR__ . '/include/autoload.php';

// Автозагрузка классов composer
require_once __DIR__ . '/../../../vendor/autoload.php';

require_once __DIR__ . '/include/env.php';

require_once __DIR__ . '/include/events.php';
