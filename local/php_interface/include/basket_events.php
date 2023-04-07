<?php
use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\ResultError;
use Bitrix\Sale\Internals\BasketTable;

EventManager::getInstance()->addEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnBeforeAdd', ['BasketHandlers', 'OnBeforeAddHandler']);
EventManager::getInstance()->addEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnBeforeUpdate', ['BasketHandlers', 'OnBeforeUpdateHandler']);

class BasketHandlers
{

    const LOG_DIR = '/local/log/baskethandlers/';

    /**
     * обработчик перед добавлением товара в корзину
     *
     * @param $event
     *
     * @return EventResult
     */
    function OnBeforeAddHandler($event)
    {
        $result = new EventResult;
        $arFields = $event->getParameter("fields");

        /*
         * не работаем:
         *  - с отложенными товарами
         *  - при изменении записи корзины из административного раздела
         *  - если корзина уже прикреплена к заказу
         */
        if (ADMIN_SECTION || $arFields['ORDER_ID'] > 0 || $arFields['DELAY'] == 'Y') return $result;

        // запрещаем создавать новый элемент корзины, если товар уже в корзине
        $bs = BasketTable::GetList(
            [
                'filter' => [
                    "FUSER_ID"   => Fuser::getId(),
                    "LID"        => SITE_ID,
                    "ORDER_ID"   => false,
                    "PRODUCT_ID" => $arFields['PRODUCT_ID'],
                    "DELAY"      => "N",
                ],
                'limit'  => 1,
                'select' => ['ID', 'QUANTITY']
            ]
        );
        if ($arBasket = $bs->Fetch()) {

            $newQuantity = $arBasket['QUANTITY'] + $arFields['QUANTITY'];
            BasketTable::Update($arBasket['ID'], ['PRODUCT_ID' => $arFields['PRODUCT_ID'], 'QUANTITY' => $newQuantity]);

            $result->addError(new ResultError('Item allready added', 'SALE_BASKET_ITEM_ALLREADY_ADDED'));
        } else {
            $availableQuantity = self::getAvailableQuantity($arFields['PRODUCT_ID']);
            if ($availableQuantity == 0) {
                $result->addError(
                    new ResultError('Add item '.$arFields['PRODUCT_ID'].'. Wrong available quantity', 'SALE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY')
                );

                $logDir = $_SERVER['DOCUMENT_ROOT'].self::LOG_DIR;
                CheckDirPath($logDir);
                $file = $logDir.date('Y.m.d-H.i.s').'-add.txt';
                $arData = [$arFields, $_SERVER];
                file_put_contents($file, serialize($arData));

            } elseif ($arFields['QUANTITY'] > $availableQuantity) {
                $result->modifyFields(array('QUANTITY' => $availableQuantity));
            }
        }

        return $result;
    }

    /**
     * обработчик перед обновлением товара в корзине
     *
     * @param $event
     *
     * @return EventResult
     */
    function OnBeforeUpdateHandler($event)
    {
        $result = new EventResult;

        $ID = $event->getParameter("id");
        $ID = $ID['ID'];
        $arFields = $event->getParameter("fields");

        $ba = BasketTable::getList(['filter' => ['ID' => $ID]]);
        $arBasketData = $ba->Fetch();
        /*
         * не работаем:
         *  - с отложенными товарами
         *  - при изменении записи корзины из административного раздела
         *  - если корзина уже прикреплена к заказу
         */
        if (defined('ADMIN_SECTION') || $arFields['ORDER_ID'] > 0 || $arBasketData['ORDER_ID'] > 0 || $arFields['DELAY'] == 'Y') return $result;

        if (!$arFields['PRODUCT_ID']) {
            $arBasket = CSaleBasket::GetList([], ['ID' => $ID], false, ['nTopCount' => 1], ['PRODUCT_ID'])->Fetch();
            $arFields['PRODUCT_ID'] = $arBasket['PRODUCT_ID'];
        }

        $availableQuantity = self::getAvailableQuantity($arFields['PRODUCT_ID']);

        if ($availableQuantity == 0) {
            BasketTable::Delete($ID);

            $result->addError(
                new ResultError(
                    'Update item '.$arFields['PRODUCT_ID'].'. Wrong available quantity. Basket has deleted',
                    'SALE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY'
                )
            );

            $logDir = $_SERVER['DOCUMENT_ROOT'].self::LOG_DIR;
            CheckDirPath($logDir);
            $file = $logDir.date('Y.m.d-H.i.s').'-update.txt';
            $arData = [$ID, $arFields, $_SERVER];
            file_put_contents($file, serialize($arData));

        } elseif ($arFields['QUANTITY'] > $availableQuantity) {
            $result->modifyFields(array('QUANTITY' => $availableQuantity));
        }

        return $result;
    }

    /**
     * возвращает доступное количество для покупки товара
     *
     * @param int $itemId
     *
     * @return int
     */
    static function getAvailableQuantity($itemId = 0)
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        $availableQuantity = 0;

        $of = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => IBLOCK_SKU_ID,
                'ID'        => $itemId,
            ],
            false,
            ['nTopCount' => 1],
            [
                'ID',
                'PROPERTY_RETAIL_CITIES',
                'PROPERTY_DELIVERY_CAN',
                'CATALOG_QUANTITY'
            ]
        );
        $arOffer = $of->Fetch();
        if ($arOffer['CATALOG_QUANTITY'] < 0) $arOffer['CATALOG_QUANTITY'] = 0;
        if ($arOffer['PROPERTY_DELIVERY_CAN_VALUE']) $availableQuantity = ($arOffer['CATALOG_QUANTITY']) ?: 1; // доставка может быть и из РМ
        if ($availableQuantity <= 0) {
            if ($arOffer['PROPERTY_RETAIL_CITIES_VALUE'] || count($arOffer['PROPERTY_RETAIL_CITIES_VALUE'])) {
                $availableQuantity = 1;
            }
        }

        /*
        $arProduct = \CCatalogProduct::GetByID($itemId);
        if ($arProduct['QUANTITY'] > 0) {
            $availableQuantity = $arProduct['QUANTITY'];
        } else {
            $st = \CCatalogStoreProduct::GetList(
                [],
                [
                    'STORE_ID'   => RETAIL_STORE_ID,
                    'PRODUCT_ID' => $itemId,
                ]
            );
            $arStore = $st->Fetch();
            if ($arStore['AMOUNT'] > 0) $availableQuantity = 1;
        }
        */

        return $availableQuantity;
    }

    /**
     * актуализирует корзину
     */
    public static function checkBasketItems()
    {
        Loader::includeModule('catalog');
        Loader::includeModule('sale');

        $bs = BasketTable::GetList(
            [
                'filter' => [
                    "FUSER_ID" => Fuser::getId(),
                    "LID"      => SITE_ID,
                    "ORDER_ID" => false,
                ],
                'limit'  => 100,
                'select' => ['ID', 'PRODUCT_ID', 'QUANTITY']
            ]
        );
        while ($arBasket = $bs->Fetch()) {
            $availableQuantity = self::getAvailableQuantity($arBasket['PRODUCT_ID']);
            if ($availableQuantity == 0) {
                BasketTable::Delete($arBasket['ID']);
            } elseif ($arBasket['QUANTITY'] > $availableQuantity) {
                BasketTable::Update($arBasket['ID'], ['PRODUCT_ID' => $arBasket['PRODUCT_ID'], 'QUANTITY' => $availableQuantity]);
            }
        }
    }
}