<?php
namespace Jamilco\OCS;

use \Jamilco\Main\Update;

/**
 * Класс обрабатывает запросы к api ocs по урлу /api/ocs/orders/
 * Class Orders
 * @package Jamilco\OCS
 */
class Orders extends Xml
{

    static $arLogDirs = [
        'CHANGE_ORDER_STATUS' => 'change_order_status',
    ];

    /**
     * метод выводит список заказов,
     * с возможностью фильтрации
     * POST-параметры (могут быть пустыми):
     * creation_time_from - фильтрация заказов по дате создания (creation_time_from >= orders.creation_time), формат - timestamp
     * creation_time_to - фильтрация заказов по дате создания (creation_time_to <= orders.creation_time), формат - timestamp
     * modification_time_from - фильтрация заказов по дате модификации (modification_time_from >= orders.modification_time), формат - timestamp
     * modification_time_to - фильтрация заказов по дате модификации (modification_time_to <= orders.modification_time), формат - timestamp
     * current_status_code - фильтрация заказов по коду текущего статуса (order2status.status_id => order_statuses.code)
     */
    static function index()
    {
        /**
         * переменные фильтра для входных параметров
         */
        $creation_time_from = trim($_REQUEST['creation_time_from']);
        $creation_time_to = trim($_REQUEST['creation_time_to']);
        $modification_time_from = trim($_REQUEST['modification_time_from']);
        $modification_time_to = trim($_REQUEST['modification_time_to']);
        $current_status_code = trim($_REQUEST['current_status_code']);

        /**
         * обработка ошибок по формату времени и статусу фильтра
         */
        $arError = array();

        if (!preg_match("/^\d+$/", $creation_time_from) && strlen($creation_time_from) > 0) {
            $arError['creation_time_from'] = 'Неверный формат времени. Требуется формат "timestamp"';
        }

        if (!preg_match("/^\d+$/", $creation_time_to) && strlen($creation_time_to) > 0) {
            $arError['creation_time_to'] = 'Неверный формат времени. Требуется формат "timestamp"';
        }

        if (!preg_match("/^\d+$/", $modification_time_from) && strlen($modification_time_from) > 0) {
            $arError['modification_time_from'] = 'Неверный формат времени. Требуется формат "timestamp"';
        }

        if (!preg_match("/^\d+$/", $modification_time_to) && strlen($modification_time_to) > 0) {
            $arError['modification_time_to'] = 'Неверный формат времени. Требуется формат "timestamp"';
        }


        if ($current_status_code && self::get_orderid_status($current_status_code) === false && strlen($current_status_code) > 0) {
            $arError['current_status_code'] = "Статуса с кодом \"{$current_status_code}\" не существует";
        }

        if (!empty($arError)) {
            $strError = '<errors>';
            foreach ($arError as $key => $value) {
                $strError .= "<error filter='{$key}'>{$value}</error>";
            }
            $strError .= '</errors>';
            self::set_error($strError);

            return false;
        }


        /**
         * сборка фильтра для заказов
         */
        $arFilter = array();

        if (strlen($creation_time_from) > 0) {
            $arFilter['>=DATE_INSERT'] = date('d.m.Y H:i:s', $creation_time_from);
        }

        if (strlen($creation_time_to) > 0) {
            $arFilter['<=DATE_INSERT'] = date('d.m.Y H:i:s', $creation_time_to);
        }

        if (strlen($modification_time_from) > 0) {
            $arFilter['>=DATE_UPDATE'] = date('d.m.Y H:i:s', $modification_time_from);
        }

        if (strlen($modification_time_to) > 0) {
            $arFilter['<=DATE_UPDATE'] = date('d.m.Y H:i:s', $modification_time_to);
        }

        if (strlen($current_status_code) > 0 && self::get_orderid_status($current_status_code) !== false) {
            $arFilter['STATUS_ID'] = self::get_orderid_status($current_status_code);
        }

        $orders = new \CSaleOrder();
        $arOrder = array(
            'ID' => 'DESC'
        );
        $arGroupBy = false;
        $arNavStartParams = false;
        $arSelectFields = array();
        $obOrders = $orders->GetList(
            $arOrder,
            $arFilter,
            $arGroupBy,
            $arNavStartParams,
            $arSelectFields
        );

        $arOrders = [];
        $arOrderId = [];

        while ($arRes = $obOrders->GetNext()) {
            $arOrders[] = $arRes;
            $arOrderId[] = $arRes['ID'];
        }

        // получаем ip-адреса, сохранённые в свойствах заказа
        $dbProps = \CSaleOrderPropsValue::GetList(
            [],
            [
                'ORDER_ID' => $arOrderId,
                'CODE'     => ['atm_marketing', 'atm_remarketing', 'atm_closer', 'USER_IP']
            ]
        );

        $arProps = [];
        while ($arRes = $dbProps->fetch()) {
            $arProps[$arRes['ORDER_ID']][$arRes['CODE']] = $arRes['VALUE'];
        }

        echo '<orders>';
        if (!empty($arOrders)) {
            foreach ($arOrders as $order) {
                $code = $order['ACCOUNT_NUMBER'];
                $status = self::get_order_status_ocs($order['STATUS_ID']);
                $mtime = strtotime($order['DATE_INSERT'].' UTC+3:00');
                $ctime = strtotime($order['DATE_UPDATE'].' UTC+3:00');
                $atm_marketing = $arProps[$order['ID']]['atm_marketing'] ? $arProps[$order['ID']]['atm_marketing'] : '';
                $atm_remarketing = $arProps[$order['ID']]['atm_remarketing'] ? $arProps[$order['ID']]['atm_remarketing'] : '';
                $atm_closer = $arProps[$order['ID']]['atm_closer'] ? $arProps[$order['ID']]['atm_closer'] : '';
                $user_ip = $arProps[$order['ID']]['USER_IP'] ? $arProps[$order['ID']]['USER_IP'] : '';


                /* Обработка чеков из АТОЛ === */
                if (IntVal($code) > 0) {
                    $cheque = '';
                    $response = '';
                    $bCorrectPayment = true;
                    if (!($arOrder = \CSaleOrder::GetByID(IntVal($code)))) {
                        $bCorrectPayment = false;
                    }
                    if ($bCorrectPayment && $arOrder["PAY_SYSTEM_ID"] == ONLINE_PAY_SYSTEM) {
                        $order = \Bitrix\Sale\Order::load($code);
                        $propertyCollection = $order->getPropertyCollection();
                        $propertyValue = $propertyCollection->getItemByOrderPropertyId(31); // CHEQUE

                        if ($propertyValue && !$propertyValue->getField('VALUE')) {
                            \CSalePaySystemAction::InitParamArrays($arOrder, $arOrder["ID"]);

                            if (\CSalePaySystemAction::GetParamValue("TEST_MODE") == 'Y') {
                                $test_mode = true;
                            } else {
                                $test_mode = false;
                            }
                            if (\CSalePaySystemAction::GetParamValue("TWO_STAGE") == 'Y') {
                                $two_stage = true;
                            } else {
                                $two_stage = false;
                            }
                            if (\CSalePaySystemAction::GetParamValue("LOGGING") == 'Y') {
                                $logging = true;
                            } else {
                                $logging = false;
                            }

                            $rbs = new \RBS(
                                \CSalePaySystemAction::GetParamValue("USER_NAME"),
                                \CSalePaySystemAction::GetParamValue("PASSWORD"),
                                $two_stage,
                                $test_mode,
                                $logging
                            );

                            for ($prefix = 0; $prefix <= 10; $prefix++) {
                                $order_prefix = $code.'_'.$prefix;
                                $response = $rbs->getReceiptStatus($order_prefix);

                                if ($response['errorCode'] == 0) {
                                    // Устанавливаем номер чека АТОЛ в св-ва CHEQUE
                                    if ($propertyValue) {
                                        $propertyValue->setField('VALUE', $response["receipt"][0]["fiscal_document_number"]);
                                        // Если чека возврата нет
                                        if (!empty($response["receipt"][1]["fiscal_document_number"])) {
                                            $cheque = '';
                                        } else {
                                            $cheque = $response["receipt"][0]["fiscal_document_number"];
                                        }
                                    }
                                    $order->save();
                                    break;
                                }
                            }
                        }
                    }
                }
                /* === end */

                echo "<order code='{$code}' current_status_code='{$status}' modification_time='{$mtime}' creation_time='{$ctime}' atm_marketing='{$atm_marketing}' atm_remarketing='{$atm_remarketing}' atm_closer='{$atm_closer}' user_ip='{$user_ip}'  checque='{$cheque}'/>";
            }
        }
        echo '</orders>';

    }


    function xmlEscape($string)
    {
        return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
    }

    /**
     * @param $code - код заказа (ID заказа на сайте)
     *
     * @return bool
     */
    static function detail($code)
    {
        $code = trim($_REQUEST['code']);


        if (strlen($code) > 0) {
            \CModule::IncludeModule('iblock');
            \CModule::IncludeModule('sale');
            \CModule::IncludeModule('catalog');
            define('IBLOCK_SKU_ID', 2);
            $code = intval($code);

            $rsSalesOrder = \CSaleOrder::GetList(array(), array('ACCOUNT_NUMBER' => $code));

            if ($arOrder = $rsSalesOrder->Fetch()) {
                // Платежная система
                $arPaySystem = array();
                $PAY_SYSTEM_ID = $arOrder['PAY_SYSTEM_ID'];

                if ($PAY_SYSTEM_ID > 0) {
                    $arPaySystem = \CSalePaySystem::GetByID($PAY_SYSTEM_ID);
                }


                // Свойства заказа
                $arOrderProps = array();
                $dbRes = \CSaleOrderPropsValue::GetOrderProps($arOrder['ID']);
                while ($arRes = $dbRes->GetNext()) {
                    $key = (strlen($arRes['CODE']) > 0) ? $arRes['CODE'] : $arRes['ID'];
                    $arOrderProps[$key] = $arRes;
                }

                $current_status_code = self::get_order_status_ocs($arOrder['STATUS_ID']);


                $modification_time = strtotime($arOrder['DATE_UPDATE'].' UTC+3:00');
                $creation_time = strtotime($arOrder['DATE_INSERT'].' UTC+3:00');
                $package = 'normal_package';
                $paid = ($arOrder['PAYED'] == 'Y') ? 'yes' : 'no';
                $comment_manager = $arOrder['COMMENTS'];
                //if (!empty($arOrderProps) && array_key_exists('PACKAGE', $arOrderProps))
                //	$package = $arOrderProps['PACKAGE']['VALUE'];

                $name = '';
                $user_id = intval($arOrder['USER_ID']);


                if ($user_id > 0) {
                    $dbUser = \CUser::GetByID($user_id);

                    if ($arUser = $dbUser->GetNext()) {
                        // формат +7XXXXXXXXXX
                        $phone = ($arOrderProps['PHONE']['VALUE']) ?: $arUser['PERSONAL_MOBILE'];
                        if (substr($phone, 0, 1) == '7') $phone = '+'.$phone;
                        if (substr($phone, 0, 1) == '8') $phone = '+7'.substr($phone, 1);
                        $phone = str_replace(['+7', '-', '(', ')', ' '], '', $phone);
                        $phone = '+7'.$phone;

                        $name = self::xmlEscape(trim($arOrderProps['LAST_NAME']['VALUE'].' '.$arOrderProps['NAME']['VALUE']));
                        //$phone 			= self::xmlEscape($arOrderProps['PHONE']['VALUE']);
                        $birth_date = $arUser['PERSONAL_BIRTHDAY'];
                        $e_mail = $arOrderProps['EMAIL']['VALUE'];

                        $arSku = array();

                        $dbRes = \CSaleBasket::GetList(array(), array('ORDER_ID' => $arOrder['ID']));
                        while ($arRes = $dbRes->GetNext()) {
                            if ($_REQUEST['test'] == 'Y') {
                                print_r($arRes);
                            }
                            $arSku[$arRes['PRODUCT_ID']] = array(
                                'code'     => $arRes['PRODUCT_ID'],
                                'quantity' => $arRes['QUANTITY'],
                                'price'    => ($arRes['PRICE']) ? $arRes['PRICE'] : $arRes['BASE_PRICE'] - $arRes['DISCOUNT_PRICE'],
                                'found'    => false,
                            );
                        }

                        if (!empty($arSku)) {
                            $res = \CIBlockElement::GetList(array(), array('ID' => array_keys($arSku), 'IBLOCK_ID' => IBLOCK_SKU_ID));
                            while ($ob = $res->GetNextElement()) {
                                $fields = $ob->GetFields();
                                $properties = $ob->GetProperties();

                                //$arSku[$fields['ID']]['en_name'] = $properties['EN_NAME']['VALUE'];
                                $arSku[$fields['ID']]['found'] = true;
                                $arSku[$fields['ID']]['ru_name'] = $fields['NAME'];
                                $arSku[$fields['ID']]['product_number'] = $fields['ID'];
                                $arSku[$fields['ID']]['color'] = $properties['COLOR']['VALUE'];
                                $arSku[$fields['ID']]['ean'] = $properties['EAN']['VALUE'];
                                $arSku[$fields['ID']]['size'] = (!empty($properties['SIZES_SHOES']['VALUE'])) ? $properties['SIZES_SHOES']['VALUE'] : $properties['SIZES_CLOTHES']['VALUE'];
                                //$arSku[$fields['ID']]['code']			= $properties['ARTNUMBER']['VALUE'].'_'.$properties['COLOR_REF']['VALUE'].'_'.$arSku[$fields['ID']]['size'];

                                //dump($properties);
                                //die();
                            }

                            $cancelReason = '';
                            if ($arOrderProps['CANCEL_REASON']['VALUE']) {
                                $variants = new \CSaleOrderPropsVariant();
                                $arCancelReason = $variants->GetByValue(
                                    $arOrderProps['CANCEL_REASON']['ORDER_PROPS_ID'],
                                    $arOrderProps['CANCEL_REASON']['VALUE']
                                );
                                $cancelReason = $arCancelReason['NAME'];
                            }

                            // определяем менеджера, кто принимал заказ через историю изменений
                            $cancelUserName = '';
                            $rsOrderHistory = \CSaleOrderChange::GetList(
                                array(),
                                array(
                                    'ORDER_ID' => $arOrder['ID'],
                                    'TYPE'     => 'ORDER_STATUS_CHANGED',
                                    'ENTITY'   => 'ORDER'
                                ),
                                false,
                                false,
                                array('DATA', 'USER_ID')
                            );

                            while ($arrOrderHistory = $rsOrderHistory->Fetch()) {
                                $arCurChanges = unserialize($arrOrderHistory['DATA']);
                                if ($arCurChanges['STATUS_ID'] == 'A' || $arCurChanges['STATUS_ID'] == 'R') {
                                    if ($arrOrderHistory['USER_ID']) {
                                        $rsUser = \CUser::GetByID($arrOrderHistory['USER_ID']);
                                        $arUser = $rsUser->Fetch();
                                        $cancelUserName = $arUser['EMAIL'];
                                    }
                                }
                            }

                            echo "<order code='{$code}' current_status_code='{$current_status_code}' modification_time='{$modification_time}' paid='{$paid}' creation_time='{$creation_time}' package='{$package}' >";

                            echo '<adspire>';
                            echo "<atm_marketing>{$arOrderProps['atm_marketing']['VALUE']}</atm_marketing>";
                            echo "<atm_remarketing>{$arOrderProps['atm_remarketing']['VALUE']}</atm_remarketing>";
                            echo "<atm_closer>{$arOrderProps['atm_closer']['VALUE']}</atm_closer>";
                            echo "<user_ip>{$arOrderProps['USER_IP']['VALUE']}</user_ip>";
                            echo '</adspire>';

                            echo '<client>';
                            echo "<name>{$name}</name>";
                            echo "<birth_date>{$birth_date}</birth_date>";
                            echo "<phone>{$phone}</phone>";
                            echo "<e_mail>{$e_mail}</e_mail>";
                            echo "<manager>{$cancelUserName}</manager>";
                            echo "<comment_manager>{$comment_manager}</comment_manager>";
                            echo "<cancel_reason>{$cancelReason}</cancel_reason>";
                            echo '<preferred_contacts></preferred_contacts>';
                            echo "<has_subscription>Y</has_subscription>";
                            echo "<bonus_card_number>{$arOrderProps['PROGRAMM_LOYALTY_CARD']['VALUE']}</bonus_card_number>";
                            echo "<bonus>{$arOrderProps['PROGRAMM_LOYALTY_WRITEOFF']['VALUE']}</bonus>";
                            echo "<upsale>{$arOrderProps['UPSALE']['VALUE']}</upsale>";
                            echo '</client>';

                            /* Доставка в магазин
                            <delivery type=""""shop"""" shop_code=""""ED4564"""" />
                            */

                            /*
                             * Доставка
                             */
                            if (!empty($arOrder['DELIVERY_ID'])) {
                                // OMNI_Retail   - передаем OmniRetail   // самовывоз из РМ
                                // OMNI_Pickup   - передаем OmniPickup   // доставка в РМ
                                // OMNI_Delivery - передаем OmniDelivery // доставка из РМ
                                // PickPoint     - Передаем OMNIPvz      // доставка до ПВЗ
                                // Delivery      - courier               // курьер
                                // GoodsRU       - GoodsRU               // Goods

                                if ($arOrder['DELIVERY_ID'] == 11 || $arOrder['DELIVERY_ID'] == PICKUP_DELIVERY) {
                                    $deliveryCode = 'OmniRetail';
                                    $arDeliveryCompany = 'OmniRetail';

                                    // в рамках этой же доставки возможно и "Доставка в РМ" (OMNI_Pickup)
                                    if ($arOrderProps['OMNI_CHANNEL']['VALUE'] == 'OMNI_Pickup') $deliveryCode = 'OmniPickup';

                                    if ($arOrder['DELIVERY_ID'] == PICKUP_DELIVERY) {
                                        $arStore = \CCatalogStore::GetList([], ['ID' => $arOrderProps['STORE_ID']['VALUE']])->Fetch();
                                        $omniDeliveryStore = $arStore['XML_ID'];
                                    } else {
                                        $omniDeliveryStore = $arOrderProps['STORE_ID']['VALUE'];
                                    }

                                    // hot-fix
                                    // иногда передается xml-id
                                    // проверяем, что магазин есть
                                    if(!$omniDeliveryStore){
                                        $arStore = \CCatalogStore::GetList([], ['XML_ID' => $arOrderProps['STORE_ID']['VALUE']])->Fetch();
                                        $omniDeliveryStore = $arStore['XML_ID'];
                                    }

                                } elseif (substr_count($arOrder['DELIVERY_ID'], OZON_DELIVERY)) {
                                    $deliveryCode = 'OMNIPvz';
                                    $arDeliveryCompany = 'ozon';
                                    $omniDeliveryStore = $arOrderProps['STORE_ID']['VALUE'];
                                } elseif (substr_count($arOrder['DELIVERY_ID'], '23')) {
                                    // GOODS
                                    $deliveryCode = 'GoodsRU';
                                }else {
                                    if (!intval($arOrder['DELIVERY_ID'])) {
                                        list($arDeliveryCompany, $arDeliveryProfile) = explode(':', $arOrder['DELIVERY_ID']);
                                    }

                                    $deliveryCode = 'courier';

                                    if ($arOrderProps['OMNI_CHANNEL']['VALUE'] == 'DayDelivery') {
                                        // доставка день-в-день
                                        $deliveryCode = 'DayDelivery';
                                    }

                                    if ($deliveryCode == 'courier' && $arOrderProps['OMNI_CHANNEL']['VALUE'] == 'OMNI_Delivery') {
                                        $deliveryCode = 'OmniDelivery';
                                    }
                                }
                            } else {
                                $deliveryCode = 'courier';
                                $arDeliveryCompany = 'spsr'; // !!!
                            }

                            if ($deliveryCode == 'courier' && $arDeliveryCompany == 'new'.KCE_DELIVERY) $arDeliveryCompany = 'cse';

                            $address = self::xmlEscape($arOrderProps['F_ADDRESS']['VALUE']);
                            $arLocation = \CSaleLocation::GetByID($arOrderProps['TARIF_LOCATION']['VALUE']);

                            echo "<delivery type='{$deliveryCode}' price='{$arOrder['PRICE_DELIVERY']}' company='{$arDeliveryCompany}' shop_code='{$omniDeliveryStore}'>";
                            echo "<city>{$arLocation['CITY_NAME']}</city>";
                            echo "<region>{$arLocation['REGION_NAME']}</region>";
                            echo "<location-street>{$address}</location-street>";
                            echo "<date>{$arOrderProps['DELIVERY_DATE']['VALUE']}</date>";
                            echo "<time>{$arOrderProps['DELIVERY_TIME']['VALUE']}</time>";

                            //echo "<index>{$index}</index>";
                            //echo "<location-building>{$house_number}</location-building>";
                            //echo "<location-corpus>{$block}</location-corpus>";
                            //echo "<location-flat>{$app_number}</location-flat>";

                            if (!empty($pay_vaucher_num)) echo "<payment_doc_num>{$pay_vaucher_num}</payment_doc_num>";
                            echo '</delivery>';


                            echo '<items>';

                            foreach ($arSku as $sku) {
                                if ($_REQUEST['test'] == 'Y') {
                                    print_r($sku);
                                }
                                if ($sku['found']) {
                                    echo "<sku ru_name='{$sku['ru_name']}' product_number='".$sku['product_number']."' size='{$sku['size']}' quantity='{$sku['quantity']}' price='{$sku['price']}' code='{$sku['ru_name']}' ean='{$sku['ean']}' />";
                                } else {
                                    echo "<sku code='{$sku['code']}' quantity='{$sku['quantity']}' price='{$sku['price']}' />";
                                }
                            }

                            echo '</items>';
                            echo '</order>';
                        }
                    }
                }
            } else {
                echo '<errors>';
                echo '<error>Заказа с таким кодом не существует</error>';
                echo '</errors>';
            }
        } else {
            echo '<errors>';
            echo '<error>Код заказа не указан</error>';
            echo '</errors>';
        }
    }

    static function change()
    {

        $command = 'CHANGE_ORDER_STATUS';
        $logPath = $_SERVER['DOCUMENT_ROOT'].LOG_PATH.self::$arLogDirs[$command].'/';
        CheckDirPath($logPath);

        $file = fopen($logPath.date('Y-m-d').'.log', 'a');
        fwrite($file, date('H:i:s')." :
         order - ".$_REQUEST['order_code']." :
         status - ".$_REQUEST['status_code']." :
         err_msg - ".trim(preg_replace("/[\r\n]/", " ", $_REQUEST['err_msg']))." :
         tk_num - ".$_REQUEST['tk_num']." :
         tk_name - ".$_REQUEST['tk_name']."\r\n");
        fclose($file);

        global $ocsIs;
        $ocsIs = true;

        $order_code = intval(trim($_REQUEST['order_code']));
        $status_code = trim($_REQUEST['status_code']);
        // Трансп. компания
        $tk_name = trim($_REQUEST['tk_name']);
        $tk_num = trim($_REQUEST['tk_num']);

        if ($order_code <= 0) {
            self::set_error('Код заказа не указан');

            return false;
        }


        if (self::get_orderid_status($status_code) === false) {
            self::set_error("Статус с таким кодом ({$status_code}) не существует");

            return false;
        }

        $rsSalesOrder = \CSaleOrder::GetList(array(), array('ACCOUNT_NUMBER' => $order_code));
        if ($arOrder = $rsSalesOrder->Fetch()) {
            $order = \Bitrix\Sale\Order::load($order_code);
            // Трансп. компания
            if($status_code == 'AT_DELIVERY_SERVICE' && $tk_name && $tk_num) {
                $propertyCollection = $order->getPropertyCollection();
                $propertyValue = $propertyCollection->getItemByOrderPropertyId(ORDER_PROP_TK_NAME);
                if ($propertyValue) {
                    $propertyValue->setField('VALUE', $tk_name);
                }else {
                    $propertyValue = \Bitrix\Sale\PropertyValue::create($propertyCollection, [
                        'ID' => ORDER_PROP_TK_NAME,
                        'NAME' => 'Название транспортной компании',
                        'TYPE' => 'STRING',
                        'CODE' => 'TK_NAME',
                    ]);
                    $propertyValue->setField('VALUE', $tk_name);
                    $propertyCollection->addItem($propertyValue);
                }
                $propertyValue = $propertyCollection->getItemByOrderPropertyId(ORDER_PROP_TK_NUM);
                if ($propertyValue) {
                    $propertyValue->setField('VALUE', $tk_num);
                }else {
                    $propertyValue = \Bitrix\Sale\PropertyValue::create($propertyCollection, [
                        'ID' => ORDER_PROP_TK_NUM,
                        'NAME' => 'Трек-номер заказа',
                        'TYPE' => 'STRING',
                        'CODE' => 'TK_NUN',
                    ]);
                    $propertyValue->setField('VALUE', $tk_name);
                    $propertyCollection->addItem($propertyValue);
                }
                $order->save();
            }

            $order_code = $arOrder['ID'];
            $result = false;

            if (\CSaleOrder::Update($order_code, array('STATUS_ID' => self::get_orderid_status($status_code)))) {
                $result = true;
            }

            if ($result) {
                echo '<result>OK</result>';
            } else {
                self::set_error('Ошибка изменения статуса заказа');
            }
        } else {
            self::set_error("Заказ с таким кодом ({$order_code}) не существует");
        }
    }


    /**
     * Возвращает статус заказа
     *
     * @param $statusID
     *
     * @return mixed
     */
    static function get_order_status_ocs($statusID)
    {
        $arStatus = array(
            'A' => 'CONFIRMED',                // Принят
            'I' => 'ISSUED',                // Выдан в РМ
            'C' => 'CANCELLED',                // Отменен
            'D' => 'DUPLICATED',            // Дубль
            'F' => 'COMPLETED',                // Выполнен
            'H' => 'DELIVERED_TO_SHOP',        // Доставлено в магазин
            'J' => 'TEST',                    // Тест
            'M' => 'REFUND',                // Частичный возврат
            'N' => 'NEW',                    // Новый
	    'EP'=> 'PAY_ONLINE',            // Оплата по уведомлению
            'P' => 'PREPARING',                // Собирается на складе
            'R' => 'CONFIRMED_CHANGE',        // Принято с изменениями
            'S' => 'AT_DELIVERY_SERVICE',    // Передано в доставку
        );
        $status = $arStatus[$statusID];
        if (!$status) {
            $status = false;
        }

        return $status;
    }


    /**
     * Возвращает статус заказа
     *
     * @param $statusID
     *
     * @return mixed
     */
    static function get_orderid_status($statusID)
    {
        $arStatus = array(
            'CONFIRMED'           => 'A',                // Принят
            'CANCELLED'           => 'C',                // Отменен
            'DUPLICATED'          => 'D',            // Дубль
            'COMPLETED'           => 'F',                // Выполнен
            'DELIVERED_TO_SHOP'   => 'H',        // Доставлено в магазин
            'TEST'                => 'J',                    // Тест
            'REFUND'              => 'M',                // Частичный возврат
            'NEW'                 => 'N',                    // Новый
	    'PAY_ONLINE'	  => 'EP',       // Оплата по уведомлению
            'PREPARING'           => 'P',                // Собирается на складе
            'CONFIRMED_CHANGE'    => 'R',        // Принято с изменениями
            'AT_DELIVERY_SERVICE' => 'S',    // Передано в доставку
            'ISSUED'              => 'I',                // Выдан в РМ
        );
        $status = $arStatus[$statusID];
        if (!$status) {
            $status = false;
        }

        return $status;
    }


    /**
     * Проверка формата даты
     *
     * @param $timestamp
     *
     * @return bool
     */
    static function check_timestamp($timestamp)
    {
        if (!preg_match("/^\d+$/", $timestamp) &&
            strlen($timestamp) > 0
        ) {
            return false;
        }

        return true;
    }

        /**
     *
     * @return bool
     */
    static function checkPayment()
    {
        $res = Update::checkOrderPayment($_REQUEST);
        echo $res;
    }
}