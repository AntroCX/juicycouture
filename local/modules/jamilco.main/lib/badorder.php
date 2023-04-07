<?

namespace Jamilco\Main;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Web\Json;
use \Bitrix\Sale;

class BadOrder
{
    const IBLOCK_BLACK_ID = 25; // инфоблок черный список
    const ORDER_PROP_NAME = 'NAME';
    const ORDER_PROP_LAST_NAME = 'LAST_NAME';
    const ORDER_PROP_PHONE = 'PHONE';
    const ORDER_PROP_EMAIL = 'EMAIL';

    public static function checkAllOrders()
    {
        $arLog = [];
        $arData = self::getData();

        $or = Sale\Internals\OrderTable::getList(
            [
                'order'  => ['ID' => 'DESC'],
                'select' => ['ID'],
                'limit'  => 1000,
            ]
        );
        while ($arOrder = $or->Fetch()) {
            $bad = self::checkOrder($arOrder['ID'], $arData);
            $arLog[$bad]++;
        }

        return $arLog;
    }

    /**
     * проверяет заказ
     *
     * @param int $orderId
     */
    public static function checkOrder($orderId = 0, $arData = [])
    {
        if (!$arData) $arData = self::getData();

        $bad = 'NO';
        $pr = \CSaleOrderPropsValue::GetList(
            [],
            [
                'ORDER_ID' => $orderId,
                'CODE'     => [
                    self::ORDER_PROP_NAME,
                    self::ORDER_PROP_LAST_NAME,
                    self::ORDER_PROP_PHONE,
                    self::ORDER_PROP_EMAIL,
                ]
            ]
        );
        while ($arProp = $pr->Fetch()) {
            if ($arProp['CODE'] == self::ORDER_PROP_EMAIL) {
                if (in_array($arProp['VALUE'], $arData['EMAIL'])) $bad = 'YES';
            } elseif ($arProp['CODE'] == self::ORDER_PROP_PHONE) {
                $phone = str_replace(['(', ')', '-', '+', ' '], '', $arProp['VALUE']);
                if (in_array($phone, $arData['PHONE'])) $bad = 'YES';
            } elseif ($arProp['CODE'] == self::ORDER_PROP_NAME || $arProp['CODE'] == self::ORDER_PROP_LAST_NAME) {
                if (in_array($arProp['VALUE'], $arData['NAME'])) $bad = 'MAYBE';
            }
        }

        if ($bad != 'NO') self::saveInOrder($orderId, $bad, $arData);

        return $bad;
    }

    /**
     * сохранить значение в заказ
     *
     * @param        $orderId
     * @param string $bad
     * @param array  $arData
     */
    public static function saveInOrder($orderId, $bad = 'NO', $arData = [])
    {
        if (!$arData) $arData = self::getData();

        $pr = \CSaleOrderPropsValue::GetList(
            [],
            [
                'ORDER_ID' => $orderId,
                'CODE'     => 'BAD'
            ]
        );
        if ($arProp = $pr->Fetch()) {
            \CSaleOrderPropsValue::Update($arProp['ID'], ['VALUE' => $bad]);
        } else {
            $rsProps = \CSaleOrderProps::GetList([], ['CODE' => 'BAD']);
            if ($arProp = $rsProps->Fetch()) {
                \CSaleOrderPropsValue::Add(
                    [
                        "ORDER_ID"       => $orderId,
                        "ORDER_PROPS_ID" => $arProp['ID'],
                        "NAME"           => $arProp['NAME'],
                        "CODE"           => $arProp['CODE'],
                        "VALUE"          => $bad
                    ]
                );
            }
        }
    }

    /**
     * получает все данные из черного списка
     *
     * @return array
     */
    public static function getData()
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
            ['ID', 'NAME', 'PROPERTY_PHONE', 'PROPERTY_EMAIL']
        );
        while ($arItem = $el->Fetch()) {
            $arData['NAME'][] = $arItem['NAME'];
            $arData['PHONE'][] = str_replace(['(', ')', '-', ' '], '', $arItem['PROPERTY_PHONE_VALUE']);
            $arData['EMAIL'][] = $arItem['PROPERTY_EMAIL_VALUE'];
        }

        $arData['NAME'] = array_unique($arData['NAME']);
        $arData['PHONE'] = array_unique($arData['PHONE']);
        $arData['EMAIL'] = array_unique($arData['EMAIL']);

        /*
        // не требуется, свойство в заказ сохраняется по коду
        $rsProps = \CSaleOrderProps::GetList([], ['CODE' => 'BAD']);
        if ($arProp = $rsProps->Fetch()) {
            $en = \CSaleOrderPropsVariant::GetList([], ['ORDER_PROPS_ID' => $arProp['ID']]);
            while ($arEnum = $en->Fetch()) {
                $arData['ENUM'][$arEnum['VALUE']] = $arEnum['ID'];
            }
        }
        */

        return $arData;
    }
}