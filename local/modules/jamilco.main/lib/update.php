<?php

namespace Jamilco\Main;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\IO\Directory;

use \Jamilco\Main\Ocs;
use \Jamilco\Delivery\Location;
use \Jamilco\Merch\Common as MerchCommon;


class Update
{

    static $arStatus = array(
        'A'  => 'CONFIRMED',                // Принят
        'C'  => 'CANCELLED',                // Отменен
        'D'  => 'DUPLICATED',            // Дубль
        'F'  => 'COMPLETED',                // Выполнен
        'H'  => 'DELIVERED_TO_SHOP',        // Доставлено в магазин
        'J'  => 'TEST',                    // Тест
        'M'  => 'REFUND',                // Частичный возврат
        'N'  => 'NEW',                    // Новый
        'EP' => 'PAY_ONLINE',            // Оплата по уведомлению
        'P'  => 'PREPARING',                // Собирается на складе
        'R'  => 'CONFIRMED_CHANGE',        // Принято с изменениями
        'S'  => 'AT_DELIVERY_SERVICE',    // Передано в доставку
        'I'  => 'ISSUED',
        'AN' => 'ANNUL'                   // заказ аннулирован из OCS
    );

    /**
     * проверка доступа
     * @return bool
     */
    static public function checkAuth()
    {
        global $USER;

        // пользователь api
        if ($USER->IsAuthorized() && ($USER->isAdmin() || $USER->GetLogin() == 'api')) {
            return true;
        }

        return false;
    }

    /**
     * обработка запроса CHANGE_ORDER_STATUS
     *
     * @param array $arRequest
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentNullException
     */
    static public function changeOrderStatus($arRequest = [])
    {
        $res = '';

        $arStatusCode = array_flip(self::$arStatus);

        $order_code = intval(trim($_REQUEST['order_code']));
        $status_code = trim($_REQUEST['status_code']);

        if ($order_code <= 0) {
            $res .= '<errors>';
            $res .= '<error>Код заказа не указан</error>';
            $res .= '</errors>';

            return $res;
        }

        if (!in_array($status_code, self::$arStatus)) {
            $res .= '<errors>';
            $res .= "<error>Статус с таким кодом ({$status_code}) не существует</error>";
            $res .= '</errors>';

            return $res;
        }

        $rsSalesOrder = \CSaleOrder::GetList(array(), array('ACCOUNT_NUMBER' => $order_code));
        if ($arOrder = $rsSalesOrder->Fetch()) {
            $order_code = $arOrder['ID'];
            $result = false;

            if ($arOrder["STATUS_ID"] == $arStatusCode[$status_code]) {
                $result = true;
            } else {
                $order = \Bitrix\Sale\Order::load($order_code);
                $order->setField("STATUS_ID", $arStatusCode[$status_code]);
                $order->save();
                $order->getField("STATUS_ID");

                if ($order->getField("STATUS_ID") == $arStatusCode[$status_code]) {
                    $result = true;
                }
            }

            if ($result) {
                $res .= '<result>OK</result>';
            } else {
                $res .= '<errors>';
                $res .= "<error>Ошибка изменения статуса заказа</error>";
                $res .= '</errors>';
            }
        } else {
            $res .= '<errors>';
            $res .= "<error>Заказ с таким кодом ({$order_code}) не существует</error>";
            $res .= '</errors>';
        }

        return $res;
    }

    /**
     * @param string $data
     * @param array  $arLog
     *
     * @return string
     * @throws \Bitrix\Main\LoaderException
     */
    static public function changeQuantity($data = '', &$arLog = [])
    {
        $res = '';

        $start = microtime(true);

        global $skipProductUpdateHandler; // флаг, блокирующий обновление возможностей к доставке по отдельным товарам (обновление будет произведено в конце всем сразу)
        $skipProductUpdateHandler = true;

        try {
            //////////////////////////////////////////////// обнуление остатков 
            $ocs_quantities_option = 'Y';
            $path_to_logs = \COption::GetOptionString("additionaloptions", "PATH_TO_LOG", "");
            $code = \COption::GetOptionString("additionaloptions", "OCS_CODE_TO_IDENTIFY_OFFERS", "");

            //// logs
            $file_to_clear = $_SERVER['DOCUMENT_ROOT'].$path_to_logs.date('dmY')."-to_clear.txt";
            $file_request = $_SERVER['DOCUMENT_ROOT'].$path_to_logs.date('dmY')."-request.txt";
            $file_dump = $_SERVER['DOCUMENT_ROOT'].$path_to_logs.date('dmY')."-dump.txt";

            file_put_contents($file_to_clear, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_dump, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_request, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_request, var_export($data, true)."\r\n", FILE_APPEND | LOCK_EX);

            $code = ($code === '') ? 'NAME' : 'PROPERTY_'.$code;

            if ($ocs_quantities_option == "Y") {
                $el = new \CIBlockElement();

                // найти ненулевые остатки
                $dbr = $el->GetList(
                    array(),
                    array("IBLOCK_ID" => IBLOCK_SKU_ID, ">CATALOG_QUANTITY" => 0),
                    false,
                    false,
                    array("ID", $code, "CATALOG_QUANTITY")
                );

                $value_code = $code == "NAME" ? "NAME" : $code."_VALUE";

                $sku_to_clear = array();
                while ($dbr_arr = $dbr->GetNext()) {
                    $sku_to_clear[] = array(
                        "ID"               => $dbr_arr["ID"],
                        "NAME"             => $dbr_arr[$value_code],
                        "CATALOG_QUANTITY" => $dbr_arr["CATALOG_QUANTITY"]
                    );
                }

                foreach ($sku_to_clear as $sku_item) {
                    $arFields = array("ID" => $sku_item["ID"], 'QUANTITY' => 0);
                    $log_str = $sku_item["NAME"]." = ".$sku_item["CATALOG_QUANTITY"]."\r\n";

                    //// logs
                    file_put_contents($file_to_clear, $log_str, FILE_APPEND | LOCK_EX);

                    // обнулить остатки по SKU
                    \CCatalogProduct::Add($arFields);
                }
            }
            /////////////////////////////////////////////// обнуление остатков

            $xml = new \SimpleXMLElement($data);
            $bReset = false;
            $reset = $xml->attributes()->reset;

            if (!is_null($reset) && $reset->__toString() == 'reset') {
                $bReset = true;
            }

            $arSku = array();
            $arSkuIds = array();

            foreach ($xml->sku as $sku) {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();

                $arSku[] = $sku_code;
            }

            // получаем список SKU из запроса с текущим количеством
            $arSelect = Array("ID", $code, "CATALOG_QUANTITY", "PROPERTY_CML2_LINK");
            $arFilter = Array("IBLOCK_ID" => IBLOCK_SKU_ID, $code => $arSku);


            $rs = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

            while ($ob = $rs->GetNextElement()) {
                $arFields = $ob->GetFields();
                $value_field = $code == "NAME" ? "NAME" : $code."_VALUE";
                $arSkuIds[$arFields[$value_field]] = array(
                    "id"         => $arFields["ID"],
                    "code"       => $arFields[$value_field],
                    "quantity"   => $arFields["CATALOG_QUANTITY"],
                    "product_id" => $arFields['PROPERTY_CML2_LINK_VALUE']
                );
            }

            $arSku = array();

            foreach ($xml->sku as $sku) {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();
                $quantity = $attributes->quantity->__toString();

                $result = array(
                    'code'     => $sku_code,
                    'quantity' => $quantity,
                    'result'   => 'OK',
                );

                if (!empty($arSkuIds[$sku_code])) {
                    $arFields = array(
                        'QUANTITY' => intval($quantity),
                    );

                    if ($bReset === false) {
                        $arFields['QUANTITY'] += $arSkuIds[$sku_code]['quantity'];
                    }

                    // обновляем количество в соответствии с запросом
                    if (!\CCatalogProduct::Update($arSkuIds[$sku_code]['id'], $arFields)) {
                        $result['result'] = 'Error';
                        $result['message'] = 'Ошибка обновления';

                        //// logs
                        file_put_contents($file_dump, date("d.m.Y G:i:s")." error on update. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);
                    } else {
                        //// logs
                        file_put_contents($file_dump, date("d.m.Y G:i:s")." done. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);
                    }
                } else {
                    //// logs
                    file_put_contents($file_dump, date("d.m.Y G:i:s")." error. sku not found. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);

                    $result['result'] = 'Error';
                    $result['message'] = 'Не найдена SKU';
                }

                $arSku[] = $result;
            }

            $arProducts = array();

            foreach ($xml->sku as $sku) {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();
                $quantity = $attributes->quantity->__toString();

                if (!empty($arSkuIds[$sku_code])) {
                    $arProducts[$arSkuIds[$sku_code]['product_id']] += $quantity;
                }

            }

            foreach ($arProducts as $productId => $quantity) {
                \CCatalogProduct::Update($productId, array('QUANTITY' => intval($quantity)));
            }

            $product = new \CCatalogProduct();
            foreach ($arProducts as $productId => $quantity) {
                $product->Update($productId, array('QUANTITY' => intval($quantity)));
                \CIBlockElement::SetPropertyValuesEx($productId, 1, array('CAN_BUY' => 'Y'));
            }

            // обнулить количество всем Товарам, не вошедшим в импорт
            $el = \CIblockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => 1,
                    '!ID'       => array_keys($arProducts),
                    [
                        'LOGIC'             => 'OR',
                        '>CATALOG_QUANTITY' => 0,
                        'PROPERTY_CAN_BUY'  => 'Y',
                    ]
                ],
                false,
                false,
                ['ID']
            );
            while ($arItem = $el->Fetch()) {
                $product->Update($arItem['ID'], array('QUANTITY' => 0));
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], 1, array('CAN_BUY' => 'N'));
            }

            // пересохранение сортировки по количеству доступных размеров
            if (Loader::IncludeModule('jamilco.merch')) MerchCommon::resortItemsByAvailability();

            // Заменяем флаги "Скоро в Продаже" на "Новинки"
            $productIds = array_column($arSkuIds, 'product_id');
            if (Loader::IncludeModule('jamilco.merch')) MerchCommon::checkSoonFlag($productIds);

            \Jamilco\Omni\Channel::reSaveCityAvailables(true); // пересохранить наличие по городам
            \Jamilco\Main\Utils::clearCatalogCache(); // сброс кеша каталога

            if (!empty($arSku)) {
                if ($bReset) {
                    $res .= '<items reset="OK">';
                } else {
                    $res .= '<items>';
                }

                foreach ($arSku as $key => $value) {
                    $res .= "<sku code='{$value['code']}' quantity='{$value['quantity']}' result='{$value['result']}'".((array_key_exists(
                            'message',
                            $value
                        )) ? " message='{$value['message']}'" : "")."/>";
                }

                $res .= '</items>';
            }
        } catch (\Exception $e) {
            $res .= '<errors>';
            $res .= "<error>{$e->getMessage()}</error>";
            $res .= '</errors>';
        }

        return $res;
    }

    /**
     * обработка запроса CHANGE_RETAIL_SKU_QTY_SHOP
     *
     * @param string $data
     *
     * @return string
     */
    static public function changeRetailSku($data = '')
    {
        $res = '';

        try {
            $ocs_quantities_option = 'Y';
            $path_to_logs = \COption::GetOptionString("additionaloptions", "PATH_TO_LOG", "");
            $code = \COption::GetOptionString("additionaloptions", "OCS_CODE_TO_IDENTIFY_OFFERS", "");

            //// logs
            $file_to_clear = $_SERVER['DOCUMENT_ROOT'].$path_to_logs.date('dmY')."-to_clear.txt";
            $file_request = $_SERVER['DOCUMENT_ROOT'].$path_to_logs.date('dmY')."-request.txt";
            $file_dump = $_SERVER['DOCUMENT_ROOT'].$path_to_logs.date('dmY')."-dump.txt";

            file_put_contents($file_to_clear, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_dump, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_request, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_request, var_export($data, true)."\r\n", FILE_APPEND | LOCK_EX);

            $code = ($code === '') ? 'NAME' : 'PROPERTY_'.$code;

            // получим список складов
            $arStores = [];
            $st = \CCatalogStore::GetList(
                ['ID' => 'ASC'],
                [
                    '!ID'     => RETAIL_STORE_ID,
                    '!XML_ID' => false
                ]
            );
            while ($arStore = $st->Fetch()) {
                $arStores[$arStore['XML_ID']] = $arStore['ID'];
            }

            $xml = new \SimpleXMLElement($data);
            $bReset = false;
            $reset = $xml->attributes()->reset;

            if (!is_null($reset) && $reset->__toString() == 'reset') $bReset = true;

            $arSku = array();
            $arSkuIds = array();

            foreach ($xml->sku as $sku) {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();

                $arSku[] = $sku_code;
            }
            // в файле нет ску
            $isEmptySku = empty($arSku);

            // получаем список SKU из запроса с текущим количеством
            $rs = \CIBlockElement::GetList([], ["IBLOCK_ID" => IBLOCK_SKU_ID, $code => $arSku], false, false, ["ID", $code, "PROPERTY_CML2_LINK"]);
            while ($arFields = $rs->Fetch()) {
                $value_field = $code == "NAME" ? "NAME" : $code."_VALUE";
                $arSkuIds[$arFields[$value_field]] = [
                    "id"         => $arFields["ID"],
                    "code"       => $arFields[$value_field],
                    "product_id" => $arFields['PROPERTY_CML2_LINK_VALUE']
                ];
            }

            $arSku = [];
            foreach ($xml->sku as $sku) {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();
                $quantity = $attributes->quantity->__toString();

                $result = array(
                    'code'     => $sku_code,
                    'quantity' => $quantity,
                    'result'   => 'OK',
                );

                if (!empty($arSkuIds[$sku_code])) {
                    $arFields = array(
                        'PRODUCT_ID' => $arSkuIds[$sku_code]['id'],
                        'STORE_ID'   => RETAIL_STORE_ID,
                        'AMOUNT'     => intval($quantity)
                    );

                    // обновляем количество в соответствии с запросом
                    $rsCatalog = \CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' => $arFields['PRODUCT_ID'], 'STORE_ID' => $arFields['STORE_ID']));
                    $arCatalog = $rsCatalog->Fetch();
                    if ($arCatalog['ID']) {
                        $resultCatalog = \CCatalogStoreProduct::Update($arCatalog['ID'], $arFields);
                    } else {
                        $resultCatalog = \CCatalogStoreProduct::Add($arFields);
                    }

                    // проверим наличие полных данных о наличии на розничных складах
                    $arRetailStores = [];
                    if ($sku->div) {
                        foreach ($sku->div as $div) {
                            $storeAttr = $div->attributes();
                            $storeXmlId = $storeAttr->id->__toString();
                            $storeQuantity = $storeAttr->quantity->__toString();
                            if ($arStores[$storeXmlId]) {
                                $arRetailStores[$arStores[$storeXmlId]] = $storeQuantity;
                            }
                        }
                    }
                    if (count($arRetailStores)) {
                        // сохраним данные по складам
                        foreach ($arStores as $storeId) {
                            $storeQuantity = (int)$arRetailStores[$storeId];
                            $stAm = \CCatalogStoreProduct::GetList([], ['PRODUCT_ID' => $arFields['PRODUCT_ID'], 'STORE_ID' => $storeId]);
                            if ($arStoreAmount = $stAm->Fetch()) {
                                \CCatalogStoreProduct::Update($arStoreAmount['ID'], ['AMOUNT' => $storeQuantity]);
                            } elseif ($storeQuantity > 0) {
                                \CCatalogStoreProduct::Add(['STORE_ID' => $storeId, 'PRODUCT_ID' => $arFields['PRODUCT_ID'], 'AMOUNT' => $storeQuantity]);
                            }
                        }
                        \CIBlockElement::SetPropertyValuesEx($arFields['PRODUCT_ID'], IBLOCK_SKU_ID, ['RETAIL_TIMESTAMP' => time()]);
                    }

                    // запись в лог
                    if (!$resultCatalog) {
                        $result['result'] = 'Error';
                        $result['message'] = 'Ошибка обновления';

                        //// logs
                        file_put_contents($file_dump, date("d.m.Y G:i:s")." error on update. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);
                    } else {
                        //// logs
                        file_put_contents($file_dump, date("d.m.Y G:i:s")." done. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);
                    }
                } else {
                    //// logs
                    file_put_contents($file_dump, date("d.m.Y G:i:s")." error. sku not found. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);

                    $result['result'] = 'Error';
                    $result['message'] = 'Не найдена SKU';
                }

                $arSku[] = $result;
            }

            $arProducts = array();
            foreach ($xml->sku as $sku) {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();
                $quantity = $attributes->quantity->__toString();

                $productId = $arSkuIds[$sku_code]['product_id'];

                // сумму считаем только по активным РМ
                //if (!empty($arSkuIds[$sku_code])) $arProducts[$productId]['quantity'] += $quantity;

                if ($sku->div) {
                    foreach ($sku->div as $div) {
                        $storeAttr = $div->attributes();
                        $storeXmlId = $storeAttr->id->__toString();
                        $storeQuantity = $storeAttr->quantity->__toString();
                        if ($arStores[$storeXmlId]) {
                            $arProducts[$productId]['quantity'] += $storeQuantity;
                        }
                    }
                }
            }

            foreach ($arProducts as $productId => $arProduct) {
                \CIBlockElement::SetPropertyValuesEx($productId, IBLOCK_CATALOG_ID, ['RETAIL_QUANTITY' => $arProduct['quantity']]);
            }

            // обнулим "остаток в РМ" для всех ТП, которых не было в выгрузке
            $rsCatalog = \CCatalogStoreProduct::GetList(
                [],
                [
                    '!PRODUCT_ID' => array_column($arSkuIds, 'id'),
                    //'STORE_ID'    => RETAIL_STORE_ID, // обнуляем не только "Розничный склад", но и все склады РМ
                    '>AMOUNT'     => 0
                ]
            );
            while ($arCatalog = $rsCatalog->Fetch()) {
                \CCatalogStoreProduct::Update($arCatalog['ID'], ['AMOUNT' => 0]);
            }

            // обнулим "остаток в РМ" для всех товаров, которых не было в этой выгрузке
            $el = \CIBlockElement::getList(
                [],
                [
                    'IBLOCK_ID' => IBLOCK_CATALOG_ID,
                    '!ID'       => array_keys($arProducts),
                    [
                        'LOGIC'                     => 'OR',
                        '>PROPERTY_RETAIL_QUANTITY' => 0,
                    ]
                ],
                false,
                false,
                ['ID']
            );
            while ($arItem = $el->Fetch()) {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], IBLOCK_CATALOG_ID, ['RETAIL_QUANTITY' => 0]);
            }


            // пересохранение сортировки по количеству доступных размеров
            if (Loader::IncludeModule('jamilco.merch')) MerchCommon::resortItemsByAvailability();

            // Заменяем флаги "Скоро в Продаже" на "Новинки"
            if(!$isEmptySku) {
                $productIds = array_column($arSkuIds, 'product_id');
                if (Loader::IncludeModule('jamilco.merch')) {
                    MerchCommon::checkSoonFlag($productIds);
                }
            }
            \Jamilco\Omni\Channel::reSaveCityAvailables(true);
            \Jamilco\Main\Utils::clearCatalogCache(); // сброс кеша каталога

            if (!empty($arSku)) {
                if ($bReset) {
                    $res .= '<items reset="OK">';
                } else {
                    $res .= '<items>';
                }

                foreach ($arSku as $key => $value) {
                    $res .= "<sku code='{$value['code']}' quantity='{$value['quantity']}' result='{$value['result']}'".((array_key_exists(
                            'message',
                            $value
                        )) ? " message='{$value['message']}'" : "")."/>";
                }

                $res .= '</items>';
                //BXClearCache(true, "/catalog/");
            }
        } catch (\Exception $e) {
            $res .= '<errors>';
            $res .= "<error>{$e->getMessage()}</error>";
            $res .= '</errors>';
        }

        return $res;
    }

    static public function changePrices($data = '')
    {
        $res = '';

        if (!$data) {
            $res .= '<error>Нет данных по ценам</error>';

            return $res;
        }

        $obXml = new \SimpleXMLElement($data);
        $arNewPrices = array();
        $arDiscountPrices = array();
        // собираем в нормальный вид новые цены и скидки
        foreach ($obXml->sku as $obSKU) {
            $arSKU = $obSKU->attributes();
            $skuCode = $arSKU['code']->__toString();
            $arNewPrices[$skuCode] = $arSKU['price']->__toString();
            if ($arSKU['price_disc']) {
                $arDiscountPrices[$skuCode] = $arSKU['price_disc']->__toString();
                // если скидочная цена пришла равной обычной цене, то не сохраняем её
                if ($arDiscountPrices[$skuCode] == $arNewPrices[$skuCode]) $arDiscountPrices[$skuCode] = 0;
            } else {
                $arDiscountPrices[$skuCode] = 0;
            }
            $arArtnums[] = $skuCode;
        }
        \CModule::IncludeModule('iblock');
        \CModule::IncludeModule('catalog');

        // обновление цен на товары, тут нужно учесть, что свойство артикул иммет код ARTNUMBER,
        $arProductsPricesIDs = $arProductsDiscountsIDs = $arFinalPrices = $arFinalDiscount = array();

        \AddMessage2Log($arArtnums);

        if (count($arArtnums) > 0) {
            $products = new \CCatalogProduct();
            $obProducts = $products->GetList(
                array(),
                array('%ELEMENT_NAME' => $arArtnums), //'PROPERTY_ARTNUMBER' => $arArtnums
                false,
                false,
                array('*')
            );
            while ($resProducts = $obProducts->Fetch()) {
                foreach ($arArtnums as $art) {
                    if (strpos($resProducts['ELEMENT_NAME'], $art) !== false) {
                        $resProducts['ELEMENT_NAME_OCS'] = $art;
                    }
                }
                if (!$resProducts['ELEMENT_NAME_OCS']) {
                    $resProducts['ELEMENT_NAME_OCS'] = $resProducts['ELEMENT_NAME'];
                }
                if ($arNewPrices[$resProducts['ELEMENT_NAME_OCS']]) {
                    $arFinalPrices[$resProducts['ID']] = $arNewPrices[$resProducts['ELEMENT_NAME_OCS']];
                    $arProductsPricesIDs[] = $resProducts['ID'];
                }
                $arFinalDiscount[$resProducts['ID']] = array(
                    'OCS_ARTNUMBER' => $resProducts['ELEMENT_NAME_OCS'],
                    'ARTNUMBER'     => $resProducts['ELEMENT_NAME'],
                    'PRICE'         => $arDiscountPrices[$resProducts['ELEMENT_NAME_OCS']]
                );
                $arProductsDiscountsIDs[] = $resProducts['ID'];
            }
        }

        $addPrices = 0;
        $updatePrices = 0;
        $discountPricesAdd = 0;
        $discountPricesRemove = 0;

        if (count($arProductsPricesIDs) > 0) {
            // проверяем нашлись ли товары по принятым артикулам
            $price = new \CPrice();

            $productPriceUpdated = [];

            // получаем цены товаров
            $obPrice = $price->GetList([], ["CATALOG_GROUP_ID" => BASE_PRICE_ID, "PRODUCT_ID" => $arProductsPricesIDs]);
            while ($arPriceRes = $obPrice->Fetch()) {
                // обновляем цены товаров
                $price->Update($arPriceRes["ID"], ["PRICE" => $arFinalPrices[$arPriceRes["PRODUCT_ID"]]]);
                $updatePrices++;

                $productPriceUpdated[] = $arPriceRes["PRODUCT_ID"];
            }

            // добавим цены тем ТП, в которых не было цены
            foreach ($arProductsPricesIDs as $offerId) {
                if (in_array($offerId, $productPriceUpdated)) continue;

                $price->Add(
                    [
                        "CATALOG_GROUP_ID" => BASE_PRICE_ID,
                        "PRODUCT_ID"       => $offerId,
                        "PRICE"            => $arFinalPrices[$offerId],
                        "CURRENCY"         => "RUB"
                    ]
                );
                $addPrices++;
            }
        }

        //\AddMessage2Log($arProductsDiscountsIDs);

        // скидки на товары
        if (count($arProductsDiscountsIDs) > 0) {
            $price = new \CPrice();

            // получаем цены товаров
            foreach ($arProductsDiscountsIDs as $id) {
                $obPrice = $price->GetList(
                    array(),
                    array(
                        "CATALOG_GROUP_ID" => 2,
                        "PRODUCT_ID"       => $id
                    )
                );


                if ($arPriceRes = $obPrice->Fetch()) {
                    // обновляем цены товаров
                    if ($arFinalDiscount[$id]['PRICE'] > 0) {
                        $price->Update($arPriceRes["ID"], Array("CATALOG_GROUP_ID" => SALE_PRICE_ID, "PRICE" => $arFinalDiscount[$id]['PRICE']));
                        $discountPricesAdd++;
                    } else {
                        $price->Delete($arPriceRes["ID"]);
                        $discountPricesRemove++;
                    }
                } else {
                    if ($arFinalDiscount[$id]['PRICE'] > 0) {
                        $price->Add(
                            Array("CATALOG_GROUP_ID" => SALE_PRICE_ID, "PRODUCT_ID" => $id, "PRICE" => $arFinalDiscount[$id]['PRICE'], "CURRENCY" => "RUB")
                        );
                        $discountPricesAdd++;
                    }
                }
            }
        }

        // свойство товара минимальная цена
        $productsArr = [];
        $productsIds = \CCatalogSKU::getProductList($arProductsPricesIDs, IBLOCK_SKU_ID);
        if (count($productsIds) > 0) {
            foreach ($productsIds as $offerId => $productsId) {
                $productsArr[$productsId['ID']][] = $arFinalPrices[$offerId];
            }

            foreach ($productsArr as $id => $prices) {
                \CIBlockElement::SetPropertyValuesEx($id, IBLOCK_CATALOG_ID, array("MINIMUM_PRICE" => min($prices)));
            }
        }

        \AddMessage2Log("обновлено: ".$updatePrices);
        \AddMessage2Log("скидки: ".$discountPricesAdd);
        \AddMessage2Log("удалены скидки: ".$discountPricesRemove);

        \Jamilco\Omni\Channel::reSaveCityAvailables(true); // пересохранить наличие по городам
        \Jamilco\Main\Utils::clearCatalogCache(); // сброс кеша каталога

        $res .= '<result>';
        $res .= '<update_prices>'.$updatePrices.'</update_prices>';
        $res .= '<new_discount>'.$discountPricesAdd.'</new_discount>';
        $res .= '<remove_discount>'.$discountPricesRemove.'</remove_discount>';
        $res .= '</result>';
        \AddMessage2Log("/--------------------------------------------------");

        \Jamilco\Main\Utils::checkItemPrices(); // обновляет свойство "Минимальная цена" в товарах (для сортировки)

        return $res;
    }

    /**
     * @param     $arrId
     * @param int $vol
     *
     * @return bool
     * @author sheykin
     */
    static public function specOfferToggle($arrId = [], $vol = 0)
    {
        if (count($arrId) == 0) {
            return false;
        }
        $arrDisc = array();
        $of = \CIBlockElement::GetList(
            array(),
            array("ID" => $arrId),
            false,
            false,
            array("ID", "IBLOCK_ID", "PROPERTY_CML2_LINK")
        );
        while ($data = $of->Fetch()) {
            $arrDisc[] = $data["PROPERTY_CML2_LINK_VALUE"];
        }
        $arrDisc = array_unique($arrDisc);

        foreach ($arrDisc as $item) {
            \CIBlockElement::SetPropertyValuesEx(
                $item,
                false,
                array(
                    "SPECIALOFFER" => $vol
                )
            );
        }
    }

    static public function changeStores($data = '')
    {
        $res = '';

        if (!$data) return $res;

        file_put_contents(
            $_SERVER["DOCUMENT_ROOT"]."/../logs/".date('Y.m.d')."_ocs_stock_log.txt",
            date('Y.m.d H:i:s')."\r\n-----------------------\r\n".$data."\r\n\r\n",
            FILE_APPEND
        );

        $obXml = new \SimpleXMLElement($data);

        $arStores = array();    // массив из xml_id пришедших магазинов из OCS
        $arStoresIDs = array(); // массив для idшников магазинов/складов на стороне OCS
        $arExistenceIDs = array(); // массив с xml_id существующими магазинами/складами
        $arExistenceRealIDs = array(); // массив с id сущ магазинами

        $obStores = $obXml->divs_info;

        foreach ($obStores->div_info as $obStore) {

            $addressStr = $obStore->loc_address->__toString();
            $arCoords[0] = $arCoords[1] = 0;
            $resCoords = \Jamilco\Ocs\Stores::getGeoCoords($addressStr);
            if (!empty($resCoords)) {
                $arCoords = $resCoords;
            }

            $arStores[] = array(
                'TITLE'          => $obStore->div_name->__toString(),
                'ACTIVE'         => 'Y',
                'ADDRESS'        => $addressStr,
                'DESCRIPTION'    => '',
                'GPS_N'          => $arCoords[1],
                'GPS_S'          => $arCoords[0],
                'PHONE'          => $obStore->phone->__toString(),
                'SCHEDULE'       => $obStore->work_time->__toString(),
                'XML_ID'         => $obStore->div_id->__toString(),
                'ISSUING_CENTER' => 'Y'
            );
            $arStoresIDs[] = $obStore->div_id->__toString();
        }

        $rsExistenceStores = \CCatalogStore::GetList(array(), array());
        while ($arrExistenceStores = $rsExistenceStores->Fetch()) {
            $arExistenceIDs[] = $arrExistenceStores['XML_ID'];
            $arExistenceRealIDs[$arrExistenceStores['XML_ID']] = $arrExistenceStores['ID'];
        }


        // тут находим магазины/склады которые не пришли из ocs, значит они закрыты, поэтому их нужно деактивировать
        $arDiff = array_diff($arExistenceIDs, $arStoresIDs);


        $arStatus = array(
            'ADD'      => 0,
            'UPDATE'   => 0,
            'DEACTIVE' => 0
        );


        // добавляем или апдейтим магазины/склады
        foreach ($arStores as $arStore) {
            $arFields = array(
                'TITLE'          => $arStore['TITLE'],
                'ACTIVE'         => $arStore['ACTIVE'],
                'ADDRESS'        => trim($arStore['ADDRESS']),
                'DESCRIPTION'    => $arStore['DESCRIPTION'],
                //'GPS_N' => $arStore['GPS_N'],
                //'GPS_S' => $arStore['GPS_S'],
                'PHONE'          => $arStore['PHONE'],
                'SCHEDULE'       => $arStore['SCHEDULE'],
                'XML_ID'         => $arStore['XML_ID'],
                'ISSUING_CENTER' => $arStore['ISSUING_CENTER']
            );

            if (in_array($arStore['XML_ID'], $arExistenceIDs)) {
                if (!$arFields['ADDRESS']) unset($arFields['ADDRESS']);
                \CCatalogStore::Update($arExistenceRealIDs[$arStore['XML_ID']], $arFields);
                $arStatus['UPDATE']++;
            } else {
                $bStatus = \CCatalogStore::Add($arFields);
                $arStatus['ADD']++;
            }
        }


        // деактивируем магазины/склады, которые не пришли из ocs, но есть на сайте
        if (count($arDiff) > 0) {
            foreach ($arDiff as $xmlId) {
                \CCatalogStore::Update($arExistenceRealIDs[$xmlId], array('ACTIVE' => 'N'));
                $arStatus['DEACTIVE']++;
            }
        }

        $res .= '<result>';
        $res .= '<add>'.$arStatus['ADD'].'</add>';
        $res .= '<update>'.$arStatus['UPDATE'].'</update>';
        $res .= '<deactivate>'.$arStatus['DEACTIVE'].'</deactivate>';
        $res .= '</result>';

        return $res;
    }

        /**
     * обработка запроса CHECK_PAYMENT
     *
     * @param array $arRequest
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentNullException
     */
    static public function checkOrderPayment($arRequest = [])
    {
        //// logs
        $file_to_logs = $_SERVER['DOCUMENT_ROOT'] . "/api/log/check_order_payment.txt";
        file_put_contents($file_to_logs, "Start check: " . date('d-m-Y [h:i:s]') . "\r\n", FILE_APPEND);

        $res = '';

        global $ocsIs;
        $ocsIs = true;
        $arrStatus = ["A", "N", "R", "P"]; // Принят. Новый. Принят с изменениями. Собирается на складе

        $order_code = intval(trim($arRequest['code']));

        file_put_contents($file_to_logs, print_r($order_code, 1) . "\r\n", FILE_APPEND);

        if ($order_code <= 0) {
            $res .= '<errors>';
            $res .= '<error>Код заказа не указан</error>';
            $res .= '</errors>';
            file_put_contents($file_to_logs, print_r($order_code . 'Код заказа не указан', 1) . "\r\n", FILE_APPEND);

            return $res;

        } else {

            $order = \Bitrix\Sale\Order::load($order_code);

            if($order->getId() > 0) {
                $paymentCollection = $order->getPaymentCollection();
                $orderPayment = $paymentCollection->isPaid();
                $returnSumm = $paymentCollection->getPaidSum();
                $orderStatus = $order->getField("STATUS_ID");

                if (in_array($orderStatus, $arrStatus)) {
                    if ($orderPayment)
                        $result = 'Y';
                    elseif(!$orderPayment) {
                        $result = 'N';
                    }

                        $res .= '<result>';
                        $res .= '<payment>'.$result.'</payment>';
                        $res .= '<summPayment>' . $returnSumm . '</summPayment>';
                        $res .= '</result>';

                } else {
                    $res .= '<errors>';
                    $res .= "<error>Заказ с таким кодом не обрабатывается</error>";
                    $res .= '</errors>';
                }

                file_put_contents($file_to_logs, print_r($res, 1) . "\r\n", FILE_APPEND);

                return $res;
            }
        }
    }
}