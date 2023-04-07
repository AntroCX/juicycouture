<?php

namespace Jamilco\Blocks;

class Block
{
    private static $blockDir = '/local/blocks/';

    private static function load_from_array($ar_name)
    {
        foreach ($ar_name as $name) {
            self::load_css($name);
            self::load_js($name);
        }
    }

    private static function load_from_name($name)
    {
        self::load_css($name);
        self::load_js($name);
    }


    private static function load_css($name)
    {
        $path = $_SERVER['DOCUMENT_ROOT'].self::$blockDir.$name;
        foreach (glob($path.'/*.css', GLOB_BRACE) as $key => $file) {

            $GLOBALS['APPLICATION']->SetAdditionalCSS(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file));
        }
    }

    private static function load_js($name)
    {
        $path = $_SERVER['DOCUMENT_ROOT'].self::$blockDir.$name;
        $arFiles = glob($path.'/*.js', GLOB_BRACE);
        $arFileNames = [];
        foreach ($arFiles as $file) {
            $arFileNames[] = basename($file);
        }

        foreach ($arFiles as $key => $file) {
            $fileName = basename($file);
            if (!substr_count($fileName, '.min.js')) {
                $fileNameMin = str_replace('.js', '.min.js', $fileName);
                if (in_array($fileNameMin, $arFileNames)) continue; // не подключаем, есть min-версия
            }
            $GLOBALS['APPLICATION']->AddHeadScript(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file));
        }
    }

    public static function load($name)
    {
        if (is_array($name)) {
            self::load_from_array($name);
        } else {
            self::load_from_name($name);
        }
    }

    public function OnBeforeEventSendHandler(&$arFields, $arTemplate)
    {
        define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log_buy.txt");

        \CModule::IncludeModule('sale');

        if ($arTemplate['EVENT_NAME'] == 'SALE_NEW_ORDER') {
            $order_id = $arFields["ORDER_REAL_ID"];

            $arOrder = \CSaleOrder::GetByID($order_id);

            //AddMessage2Log("--------arOrder------");
            //AddMessage2Log($arOrder);

            //Свойства заказа
            $arsOrderProps = \CSaleOrderPropsValue::GetOrderProps($order_id);

            //Телефон и адрес
            while ($arOrderProps = $arsOrderProps->Fetch()) {
                if ($arOrderProps['CODE'] == 'PHONE') {
                    $arFields['PHONE'] = $arOrderProps['VALUE'];
                } elseif ($arOrderProps['CODE'] == 'F_ADDRESS') {
                    $arFields['LOCATION'] .= $arOrderProps['VALUE'];
                } elseif ($arOrderProps["CODE"] == "STORE_ID") {
                    $STORE_ID = $arOrderProps["VALUE"];
                }
            }

            if (isset($STORE_ID) && $STORE_ID != "") {
                return false;
            }

            //Доставка
            $arFields["DELIVERY"] = SaleFormatCurrency($arOrder["PRICE_DELIVERY"], 'RUB');

            //Скидка
            $arFields['DISCOUNT'] = SaleFormatCurrency($arOrder["DISCOUNT_VALUE"], 'RUB');

            //Платежная система
            $arPayment = \CSalePaySystem::GetByID($arOrder['PAY_SYSTEM_ID']);
            $arFields['PAYMENT'] = $arPayment['NAME'];
            $arFields['REAL_ID'] = $arOrder['ID'];

            //Состав заказа
            $link = 'http://'.SITE_SERVER_NAME;
            $rsItems = \CSaleBasket::GetList(array("NAME" => "ASC"), array("ORDER_ID" => $arOrder['ID']));
            $arFields['TOTAL'] = 0;
            $strItems = '';
            while ($arItems = $rsItems->GetNext()) {
                $arPrice = \CPrice::GetList([], ['PRODUCT_ID' => $arItems['PRODUCT_ID'], 'CATALOG_GROUP_ID' => 1])->Fetch();
                if ($arPrice['PRICE'] > $arItems['BASE_PRICE']) $arItems['BASE_PRICE'] = $arPrice['PRICE'];

                $arItems['DISCOUNT_PRICE'] = $arItems['BASE_PRICE'] - $arItems['PRICE'];

                $mxResult = \CCatalogSku::GetProductInfo($arItems['PRODUCT_ID']);
                $ar_res = \CCatalogProduct::GetByID($mxResult['ID']);
                $res = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 1, "ID" => $ar_res['ID']), false, false, array());
                while ($ob = $res->GetNext()) {
                    $tovName = $ob['NAME'];
                }

                $res = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 2, "ID" => $arItems['PRODUCT_ID']), false, false, array());
                while ($ob = $res->GetNext()) {
                    $img = \CFile::getPath($ob['PREVIEW_PICTURE']);
                }
                $arFields['TOTAL'] += ($arItems['PRICE'] * $arItems['QUANTITY']);

                if ($arItems['BASE_PRICE'] > $arItems['PRICE']) {
                    $price = '<span style="text-decoration: line-through;">'.SaleFormatCurrency(
                            $arItems['BASE_PRICE'],
                            'RUB'
                        ).'</span><br />'.SaleFormatCurrency($arItems['PRICE'], 'RUB');
                } else {
                    $price = SaleFormatCurrency($arItems['PRICE'], 'RUB');
                }

                $strItems .= '<tr>';
                $strItems .= '<td border="0" width="120" style="padding: 0px 0 10px 0; border: 1px solid #D0D0D0; text-align: center; font-family: Arial, Helvetica, FreeSans, sans-serif;">
                                    <img src="'.$img.'" alt="" width="110" /><br>'.
                    $tovName
                    .'</td>';
                $strItems .= '<td style="padding: 0 0 10px 0; border: 1px solid #D0D0D0; text-align: center;">';
                $strItems .= '<a href="'.$link.'" target="_blank" style="font-family: Arial, sans-serif; color: #0099cc; text-decoration: underline;">'.$arItems['NAME'].'</a>';
                $strItems .= '</td>';
                $strItems .= '<td style="padding: 0 0 10px 0; border: 1px solid #D0D0D0; text-align: center; font-family: Arial, Helvetica, FreeSans, sans-serif;">'.$price.'</td>';
                $strItems .= '<td style="padding: 0 0 10px 0; border: 1px solid #D0D0D0; text-align: center;">'.intval($arItems['QUANTITY']).'</td>';
                $strItems .= '<td style="padding: 0 0 10px 0; border: 1px solid #D0D0D0; text-align: center; font-family: Arial, Helvetica, FreeSans, sans-serif;">'.SaleFormatCurrency(
                        $arItems['PRICE'] * $arItems['QUANTITY'],
                        'RUB'
                    ).'</td>';
                $strItems .= '</tr>';
            }
            $arFields['TOTAL'] = SaleFormatCurrency($arFields['TOTAL'], 'RUB');
            $arFields['LIST_ITEMS'] = $strItems;
        }
        if ($arTemplate['EVENT_NAME'] == 'SALE_STATUS_CHANGED_I') {
            //AddMessage2Log($arFields);
            $arsOrderProps = \CSaleOrderPropsValue::GetOrderProps($arFields["ORDER_REAL_ID"]);
            while ($arOrderProps = $arsOrderProps->Fetch()) {
                if ($arOrderProps['CODE'] == 'NAME') {
                    $arFields['NAME'] = $arOrderProps['VALUE'];
                }
            }
        }
    }

    public function OnSaleStatusOrderHandler($ID, $val)
    {
        // Если заказ принят - код A
        if ($val == "A") {
            //define( "LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log_reserv.txt");
            // Получаем параметры заказа
            $arOrder = \CSaleOrder::GetByID($ID);
            //AddMessage2Log($arOrder);
            //Получаем свойства заказа
            $db_props = \CSaleOrderPropsValue::GetOrderProps($ID);
            $EMAIL = $NAME = "";
            while ($arProps = $db_props->Fetch()) {
                //AddMessage2Log($arProps);
                if ($arProps["CODE"] == "EMAIL") {
                    $EMAIL = $arProps["VALUE"];
                } elseif ($arProps["CODE"] == "NAME") {
                    $NAME = $arProps["VALUE"];
                } elseif ($arProps["CODE"] == "STORE_ID") {
                    $STORE_ID = $arProps["VALUE"];
                }
            }

            $date = date_create($arOrder['DATE_INSERT_FORMAT']);
            date_add($date, date_interval_create_from_date_string('1 day')); //держим товар в резерве 1 (один) день
            $reserve_date = date_format($date, 'd.m.Y');

            $arStore = \CCatalogStore::GetList(array(), array('XML_ID' => $STORE_ID))->Fetch();
            $STORE_TITLE = $arStore['TITLE'];
            $STORE_ADDRESS = $arStore['ADDRESS'];

            $arEventFields = array(
                "ORDER_ID"      => $ID,
                "RESERVE_DATE"  => $reserve_date,
                "EMAIL"         => $EMAIL,
                "NAME"          => $NAME,
                "STORE_TITLE"   => $STORE_TITLE,
                "STORE_ADDRESS" => $STORE_ADDRESS,
                "CUR_DATE"      => $arOrder['DATE_INSERT_FORMAT'],
            );
            if ($STORE_ID != "") {
                \CEvent::Send("RESERVATION_CONFIRM", 's1', $arEventFields);
            }
        }
    }
}

