<?
/**
 * Запрет создания заказов для пользователей из ЧС
 */
namespace Jamilco\Main;

use Bitrix\Highloadblock\HighloadBlockTable;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Sale\Location\LocationTable;

class BlackListOrder
{
    const IBLOCK_BLACK_ID = 28; // Запрет создания заказов (пользователи чс)
    const HL_BLOCK_TABLE_NAME = 'tryorderbl'; // Лог
    const ORDER_PROP_NAME = 'NAME';
    const ORDER_PROP_LAST_NAME = 'LAST_NAME';
    const ORDER_PROP_PHONE = 'PHONE';
    const ORDER_PROP_EMAIL = 'EMAIL';
    const ORDER_PROP_LOCATION = 'TARIF_LOCATION';
    const ORDER_PROP_STREET = 'STREET';
    const ORDER_PROP_BUILDING = 'BUILDING';
    const ORDER_PROP_FLAT = 'FLAT';
    const DO_NOT_CREATE_ORDER = 'Y';

    private $order = null;

    public function __construct (\Bitrix\Sale\Order $order){
        $this->order = $order;
    }

    /**
     * Проверяет заказ
     *
     * @return bool
     */
    public function checkOrder()
    {
        Loader::IncludeModule('sale');
        $arData = self::getData();

        $bad = false;

        $arPropsData = $this->order->getPropertyCollection()->getArray();

        foreach ($arPropsData['properties'] as $arProp) {
            if ($arProp['CODE'] == self::ORDER_PROP_EMAIL) {
                $email = ToLower(trim($arProp['VALUE'][0]));
                if ($email && in_array($email, $arData['EMAIL'])) {
                    $bad = true;
                    break;
                }
            }
            if ($arProp['CODE'] == self::ORDER_PROP_PHONE) {
                $phone = preg_replace('/[^0-9]/is', '', $arProp['VALUE'][0]);
                if ($phone && in_array($phone, $arData['PHONE'])) {
                    $bad = true;
                    break;
                }
            }
        }

        // проверка адреса
        if (!$bad) {
            $tryAddress = [];
            $addressProps = [
                self::ORDER_PROP_LOCATION,
                self::ORDER_PROP_STREET,
                self::ORDER_PROP_BUILDING,
                self::ORDER_PROP_FLAT
            ];
            foreach ($arPropsData['properties'] as $arProp) {
                $v = ToLower(trim($arProp['VALUE'][0]));
                if (in_array($arProp['CODE'], $addressProps)) {
                    $tryAddress[$arProp['CODE']] = $v;
                }
            }

            foreach ($arData['ADDRESS'] as $userId => $arAddress) {
                if (!$tryAddress[self::ORDER_PROP_LOCATION] || !$arAddress['LOCATION']) {
                    continue;
                }
                if (!$tryAddress[self::ORDER_PROP_STREET] || !$arAddress['STREET']) {
                    continue;
                }
                if (!$tryAddress[self::ORDER_PROP_BUILDING] || !$arAddress['BUILDING']) {
                    continue;
                }

                // получаем код местоположения для адреса из чс
                $loc = LocationTable::getList(
                    [
                        'filter' => [
                            'ID' => $arAddress['LOCATION']
                        ],
                        'select' => [
                            'CODE'
                        ],
                        'limit'  => 1,
                    ]
                );
                if ($arLoc = $loc->Fetch()) {
                    $arAddress['LOCATION'] = $arLoc['CODE'];
                } else {
                    continue;
                }

                if (
                    $tryAddress[self::ORDER_PROP_LOCATION] == $arAddress['LOCATION'] &&
                    $tryAddress[self::ORDER_PROP_STREET] == $arAddress['STREET'] &&
                    $tryAddress[self::ORDER_PROP_BUILDING] == $arAddress['BUILDING']
                ) {
                    if (!$arAddress['FLAT']) {
                        $bad = true;
                        break;
                    }
                    if ($tryAddress[self::ORDER_PROP_FLAT] &&
                        $tryAddress[self::ORDER_PROP_FLAT] == $arAddress['FLAT']) {
                        $bad = true;
                        break;
                    }
                }
            }
        }

        if ($bad) {
            self::deleteUser();
            self::makeRecord();
        }
        if ($bad && self::DO_NOT_CREATE_ORDER == 'Y') {
            return false;
        }

        return true;
    }

    /**
     * Получает все данные из черного списка
     *
     * @return array
     */
    private function getData()
    {
        Loader::includeModule('iblock');

        $arData = [];
        $el = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => self::IBLOCK_BLACK_ID,
                'ACTIVE'    => 'Y',
            ],
            false,
            false,
            ['ID', 'PROPERTY_PHONE', 'PROPERTY_EMAIL', 'PROPERTY_LOCATION', 'PROPERTY_STREET', 'PROPERTY_BUILDING', 'PROPERTY_FLAT']
        );
        while ($arItem = $el->Fetch()) {
            if (is_array($arItem['PROPERTY_PHONE_VALUE'])) {
                foreach ($arItem['PROPERTY_PHONE_VALUE'] as $value) {
                    $arData['PHONE'][] = preg_replace('/[^0-9]/is', '', $value);
                }
            } else {
                $arData['PHONE'][] = preg_replace('/[^0-9]/is', '', $arItem['PROPERTY_PHONE_VALUE']);
            }
            if (is_array($arItem['PROPERTY_EMAIL_VALUE'])) {
                foreach ($arItem['PROPERTY_EMAIL_VALUE'] as $value) {
                    $arData['EMAIL'][] = ToLower(trim($value));
                }
            } else {
                $arData['EMAIL'][] = ToLower(trim($arItem['PROPERTY_EMAIL_VALUE']));
            }
            $arData['ADDRESS'][$arItem['ID']]['LOCATION'] = ToLower(trim($arItem['PROPERTY_LOCATION_VALUE']));
            $arData['ADDRESS'][$arItem['ID']]['STREET'] = ToLower(trim($arItem['PROPERTY_STREET_VALUE']));
            $arData['ADDRESS'][$arItem['ID']]['BUILDING'] = ToLower(trim($arItem['PROPERTY_BUILDING_VALUE']));
            $arData['ADDRESS'][$arItem['ID']]['FLAT'] = ToLower(trim($arItem['PROPERTY_FLAT_VALUE']));
        }

        $arData['PHONE'] = array_unique($arData['PHONE']);
        $arData['EMAIL'] = array_unique($arData['EMAIL']);

        return $arData;
    }

    /**
     * Записывает лог
     *
     * @return void
     */
    private function makeRecord()
    {
        Loader::includeModule("sale");
        Loader::includeModule("highloadblock");

        $name = '';
        $last_name = '';
        $fio = '';
        $phone = '';
        $email = '';
        $order_sum = '';
        $basket_output = [];
        $date = new DateTime();
        $location = '';
        $street = '';
        $building = '';
        $flat = '';
        $address = '';

        $arPropsData = $this->order->getPropertyCollection()->getArray();
        foreach ($arPropsData['properties'] as $arProp) {
            if ($arProp['CODE'] == self::ORDER_PROP_NAME) {
                $name = $arProp['VALUE'][0];
            }
            if ($arProp['CODE'] == self::ORDER_PROP_LAST_NAME) {
                $last_name = $arProp['VALUE'][0];
            }
            if ($arProp['CODE'] == self::ORDER_PROP_PHONE) {
                $phone = $arProp['VALUE'][0];
            }
            if ($arProp['CODE'] == self::ORDER_PROP_EMAIL) {
                $email = $arProp['VALUE'][0];
            }
            if ($arProp['CODE'] == self::ORDER_PROP_LOCATION) {
                $location = $arProp['VALUE'][0];
            }
            if ($arProp['CODE'] == self::ORDER_PROP_STREET) {
                $street = $arProp['VALUE'][0];
            }
            if ($arProp['CODE'] == self::ORDER_PROP_BUILDING) {
                $building = $arProp['VALUE'][0];
            }
            if ($arProp['CODE'] == self::ORDER_PROP_FLAT) {
                $flat = $arProp['VALUE'][0];
            }
        }

        $fio = $name;
        if($last_name) $fio .= ' '.$last_name;

        $order_sum = $this->order->getPrice();

        $basket = $this->order->getBasket();
        foreach ($basket as $basketItem) {
            $name = $basketItem->getField('NAME');
            $price = $basketItem->getPrice();
            $quantity = $basketItem->getQuantity();
            $basket_output[] = $name."\t".$quantity."\t".$price;
        }

        $arLocation = \Jamilco\Delivery\Location::getLocationData(0, $location);
        $address = implode(', ', array_reverse($arLocation['PATH']));
        if($street) $address .= ', '.$street;
        if($building) $address .= ', д '.$building;
        if($flat) $address .= ', кв '.$flat;

        $hlblock = HighloadBlockTable::getList(array('filter' => array('TABLE_NAME' => self::HL_BLOCK_TABLE_NAME)))->Fetch();
        if (!empty($hlblock)) {
            $entity = HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $result = $entity_data_class::add([
                                                  'UF_TIME'      => $date->toString(),
                                                  'UF_PHONE'     => $phone,
                                                  'UF_EMAIL'     => $email,
                                                  'UF_ORDER_SUM' => $order_sum,
                                                  'UF_BASKET'    => $basket_output,
                                                  'UF_ADDRESS'   => $address,
                                                  'UF_FIO'       => $fio,
                                              ]);
        }
    }

    /**
     * Удаляет пользователя
     *
     * @return void
     */
    private function deleteUser(){
        $userId = $this->order->getUserId();
        if($userId) \CUser::Delete($userId);
    }
}