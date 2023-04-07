<?php

use Bitrix\Main\EventManager;
use Juicycouture\EventHandlers;

$eventManager = EventManager::getInstance();

/** @see EventHandlers\User::onBeforeUserLogin() */
$eventManager->addEventHandler('main', 'OnBeforeUserLogin', [EventHandlers\User::class, 'onBeforeUserLogin']);

/** @see EventHandlers\User::onBeforeUserSendPassword() */
$eventManager->addEventHandler('main', 'OnBeforeUserSendPassword', [EventHandlers\User::class, 'onBeforeUserSendPassword']);

/** @see EventHandlers\Order::onSaleOrderBeforeSavedHandler() */
$eventManager->addEventHandler('sale', 'OnSaleOrderBeforeSaved', [EventHandlers\Order::class, 'onSaleOrderBeforeSaved']);

$eventManager->addEventHandler('main', 'OnAfterUserAuthorize', ['\\Jamilco\\EventHandlers\\Main', 'OnAfterUserAuthorizeHandler']);
$eventManager->addEventHandler('main', 'OnAfterUserAdd', ['\\Jamilco\\EventHandlers\\Main', 'OnAfterUserAddHandler']);
$eventManager->addEventHandler('sale', 'OnSaleOrderSaved', ['\\Jamilco\\EventHandlers\\Sale', 'OnSaleOrderSavedHandler']);
$eventManager->addEventHandler('sale', 'OnSaleOrderCanceled', ['\\Jamilco\\EventHandlers\\Sale', 'OnSaleOrderCanceledHandler']);
$eventManager->addEventHandler('sale', 'OnBasketAdd', ['\\Jamilco\\EventHandlers\\Sale', 'OnBasketAddHandler']);
$eventManager->addEventHandler('sale', 'OnBeforeBasketDelete', ['\\Jamilco\\EventHandlers\\Sale', 'OnBeforeBasketDeleteHandler']);
