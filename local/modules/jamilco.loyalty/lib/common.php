<?php
namespace Jamilco\Loyalty;

use Bitrix\Main\EventManager;

class Common
{

    public static function init()
    {
        if (\COption::GetOptionString("jamilco.loyalty", "autoload_module", 1)) self::autoLoad();

        EventManager::getInstance()->addEventHandler('sale', 'OnSalePropertyValueSetField', ['Jamilco\\Loyalty\\Events', 'OnSalePropertyValueSetFieldHandler']);
        EventManager::getInstance()->addEventHandler('sale', '\Bitrix\Sale\Internals\OrderPropsValue::OnUpdate', ['Jamilco\\Loyalty\\Events', 'OrderPropsValueOnUpdateHandler']);
    }

    public static function autoLoad()
    {
        return \Bitrix\Main\Loader::includeModule('jamilco.loyalty');
    }

    /**
     * скидки перенесены в Манзану
     *
     * @return bool
     */
    public static function discountsAreMoved()
    {
        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0); // отправим данные по заказу в Манзану
        $manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0); // отправляем заказы в Манзану
        $manzanaDiscounts = \COption::GetOptionInt("jamilco.loyalty", "manzanadiscounts", 0); // скидки перенесены в Манзану

        if ($manzanaUse && $manzanaOrders && $manzanaDiscounts) {
            return true;
        }

        return false;
    }

    /**
     * @return int Направление сортировки товаров
     */
    public static function productsSort()
    {
        return \COption::GetOptionInt("jamilco.loyalty", "manzanasort", self::NO_SORT);
    }

    /**
     * Callback-функция сортировки по возрастанию цены
     * @param array $item1
     * @param array $item2
     * @return float
     */
    public static function cmpPriceAsc($item1, $item2)
    {
        return $item1['PRICE'] - $item2['PRICE'];
    }

    /**
     * Callback-функция сортировки по убыванию цены
     * @param array $item1
     * @param array $item2
     * @return float
     */
    public static function cmpPriceDesc($item1, $item2)
    {
        return $item2['PRICE'] - $item1['PRICE'];
    }

    const NO_SORT = 0;
    const SORT_PRICE_ASC = 1;
    const SORT_PRICE_DESC = 2;
}