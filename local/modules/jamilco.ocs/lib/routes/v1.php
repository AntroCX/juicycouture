<?php
/**
 * $arRoutes - список маршрутов для api, будет роутить пути вида /api/ocs/*
 * текущая
 */
$arRoutes = array(
    'orders' => array( // название класса
        'index', // название метода - значит корень, т.е. /api/ocs/orders/<параметр>.xml, но <параметр> необязателен
        'detail', // означает /api/ocs/orders/detail/<параметр>.xml
        'changes',
        'filter'
    ),
    'pricie' => array (
        ''
    )
);