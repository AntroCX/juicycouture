<?php

use Bitrix\Main\Context;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$request = Context::getCurrent()->getRequest();
$queryString = filter_var($request->getQuery('q'), FILTER_SANITIZE_STRING);

/** DigitalDataLayer start */
$digitalData = \DigitalDataLayer\Manager::getInstance()->getData();
$digitalData->listing = ['query' => $queryString];

// Если компоненты включенные в страницу не были вызваны и не заполнили объект listing, то нужно заполнить его дефолтными значениями
if (!$digitalData->listing['items']) {
    $digitalData->listing = [
        'listId' => 'main',
        'listName' => 'search',
        'items' => [],
        'resultCount' => 0
    ];
}
/** DigitalDataLayer end */
