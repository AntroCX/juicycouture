<?php
/**
 * роутинг который я написал не подходит под текущий OCS и запарно менять все, поэтому будет адаптер под старый роут
 */
$arRoutes = array(
    'GET_ORDERS' => array( // название команды, команды приходят через параметр $_REQUEST['command']
        'orders' => 'index', // название класса контроллера и метода, вске контроллеры размещены в controllers/
    ),
    'GET_ORDER_DETAILS' => array(
        'orders' => 'detail'
    ),
    'CHANGE_ORDER_STATUS' => array(
        'orders' => 'change'
    ),
    'BLOCK' => array(
        'system' => 'block'
    ),
    'RELEASE' => array(
        'system' => 'release'
    ),
    'CHANGE_SKU_QUANTITIES' => array(
        'sku' => 'change_quantity'
    ),
    'CHANGE_RETAIL_SKU_QUANTITIES' => array(
        'sku' => 'change_retail_quantity'
    ),
    'CHANGE_RETAIL_SKU_QTY_SHOP' => array(
        'sku' => 'change_retail_quantity_all'
    ),
    'CHANGE_SKU_PRICES' => array(
        'sku' => 'change_prices'
    ),
    'STORES' => array(
        'stores' => 'index'
    ),
    'ADSPIRE' => array(
        'adspire' => 'index'
    ),
    'CHECK_PAYMENT' => array(
        'orders' => 'checkPayment'
    ),
    'PRODUCT' => array(
        'product' => 'index'
    )
);