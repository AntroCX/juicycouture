<?php

namespace Jamilco;

class YmarketHandler
{
    public static $propertyStoreId = ORDER_PROP_STORE_ID;

    public static $propertyOmniChannelId = ORDER_PROP_OMNI_CHANNEL_ID;

    public static $propertyAddresslId = ORDER_PROP_ADDRESS_ID;

    public static $iblockStoreId = IBLOCK_SHOPS_ID;

    /**
     * @param \Bitrix\Main\Event $event
     */
    function onYmarketOrderAcceptEvent($event)
    {
        $arParameters = $event->getParameters();
        $orderId = intval( str_replace('"', '', $arParameters['RESULT']['order']['id']) );

        //AddMessage2Log(['$orderId', $orderId]);

        if ($orderId <= 0) {
           return;
        }

        //AddMessage2Log(['postParameters', $arParameters['POST_DATA']]);

        switch ($arParameters['POST_DATA']['order']['delivery']['type']) {
            case 'PICKUP':

                \CSaleOrderPropsValue::Add(
                    array(
                        'ORDER_ID'       => $orderId,
                        'ORDER_PROPS_ID' => self::$propertyOmniChannelId,
                        'NAME'           => 'Omni Channel',
                        'VALUE'          => 'OMNI_Retail',
                        'CODE'           => 'OMNI_CHANNEL'
                    )
                );

                $outlet = intval($arParameters['POST_DATA']['order']['delivery']['outlet']['id']);

                if ($outlet > 0) {
                    $dbStore = \CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID' => self::$iblockStoreId,
                            'PROPERTY_YMARKET_OUTLET' => $outlet
                        ],
                     false,
                     false,
                     ['ID', 'NAME']
                    );

                    if ($arStore = $dbStore->Fetch()) {
                        \CSaleOrderPropsValue::Add(
                            array(
                                'ORDER_ID'       => $orderId,
                                'ORDER_PROPS_ID' => self::$propertyStoreId,
                                'NAME'           => 'ID магазина самовывоза',
                                'VALUE'          => $arStore['ID'],
                                'CODE'           => 'STORE_ID'
                            )
                        );

                        $db_vals = \CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $orderId, 'ORDER_PROPS_ID' => self::$propertyAddresslId));

                        if ($arVals = $db_vals->Fetch()) {
                            \CSaleOrderPropsValue::Update(
                                $arVals ['ID'],
                                [
                                    'ORDER_ID'       => $orderId,
                                    'ORDER_PROPS_ID' => self::$propertyAddresslId,
                                    'NAME'           => 'Адрес доставки',
                                    'VALUE'          => $arStore['NAME'],
                                    'CODE'           => 'F_ADDRESS',
                                ]
                            );
                        } else {
                            \CSaleOrderPropsValue::Add(
                                [
                                    'ORDER_ID'       => $orderId,
                                    'ORDER_PROPS_ID' => self::$propertyAddresslId,
                                    'NAME'           => 'Адрес доставки',
                                    'VALUE'          => $arStore['NAME'],
                                    'CODE'           => 'F_ADDRESS',
                                ]
                            );
                        }

                        //AddMessage2Log($arStore);
                    }
                }

                break;

            case 'DELIVERY':

                \CSaleOrderPropsValue::Add(
                    array(
                        'ORDER_ID'       => $orderId,
                        'ORDER_PROPS_ID' => self::$propertyOmniChannelId,
                        'NAME'           => 'Omni Channel',
                        'VALUE'          => 'Delivery',
                        'CODE'           => 'OMNI_CHANNEL'
                    )
                );

                break;

            default :
                break;
        }
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    function onYmarketOrderStatusEvent($event)
    {
        $arParameters = $event->getParameters();

        $dbOrder = \Bitrix\Sale\Internals\OrderTable::getList(array(
            'filter' => array("XML_ID" => \CSaleYMHandler::XML_ID_PREFIX.$arParameters['POST_DATA']["order"]["id"]),
            'select' => array('ID', 'LID', 'XML_ID')
        ));

        if($arOrder = $dbOrder->fetch()) {

            if ($arParameters['POST_DATA']['order']['delivery']['type'] == 'PICKUP') {
                $orderId = $arOrder['ID'];
                $outlet = intval($arParameters['POST_DATA']['order']['delivery']['outlet']['id']);

                if ($outlet > 0) {
                    $dbStore = \CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID' => self::$iblockStoreId,
                            'PROPERTY_YMARKET_OUTLET' => $outlet
                        ],
                        false,
                        false,
                        ['ID', 'NAME']
                    );

                    if ($arStore = $dbStore->Fetch()) {
                        $db_vals = \CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $orderId, 'ORDER_PROPS_ID' => self::$propertyAddresslId));

                        if ($arVals = $db_vals->Fetch()) {
                            \CSaleOrderPropsValue::Update(
                                $arVals ['ID'],
                                [
                                    'ORDER_ID'       => $orderId,
                                    'ORDER_PROPS_ID' => self::$propertyAddresslId,
                                    'NAME'           => 'Адрес доставки',
                                    'VALUE'          => $arStore['NAME'],
                                    'CODE'           => 'F_ADDRESS',
                                ]
                            );
                        } else {
                            \CSaleOrderPropsValue::Add(
                                [
                                    'ORDER_ID'       => $orderId,
                                    'ORDER_PROPS_ID' => self::$propertyAddresslId,
                                    'NAME'           => 'Адрес доставки',
                                    'VALUE'          => $arStore['NAME'],
                                    'CODE'           => 'F_ADDRESS',
                                ]
                            );
                        }
                    }
                }
            }
        }
    }
}

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler(
    'sale',
    'OnSaleYandexMarketRequest_orderaccept',
    ['\Jamilco\YmarketHandler', 'onYmarketOrderAcceptEvent']
);

$eventManager->addEventHandler(
    'sale',
    'OnSaleYandexMarketRequest_orderstatus',
    ['\Jamilco\YmarketHandler', 'onYmarketOrderStatusEvent']
);
