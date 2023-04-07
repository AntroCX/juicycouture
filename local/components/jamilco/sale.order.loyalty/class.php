<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Class CJamilcoLoyality
 * компонент для работы с бонусными картами Джамилько
 */
class CJamilcoLoyality extends CBitrixComponent
{
    /**
     * @param $code - код подтверждения
     * @return array
     */
    public function confirmCode($code)
    {

        Loader::includeModule('jamilco.loyalty');
        Loader::includeModule('iblock');

        $arResult = \Jamilco\Loyalty\Card::confirmCode(0, $code);

        return $arResult;
    }

    /**
     * @param $type - тип запроса
     * @return array
     */
    public function checkCode($type)
    {
        Loader::includeModule('jamilco.loyalty');
        Loader::includeModule('iblock');

        $arResult = \Jamilco\Loyalty\Card::checkClientSend(0, $type);

        return $arResult;
    }

    /**
     * @param $number - номер карты
     * @param $applyBonuses - тип запроса, либо применить карту, либо получить информацию по карте
     * @return array
     */
    public function getData($number, $applyBonuses = 'N')
    {
        Loader::includeModule('jamilco.loyalty');
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        global $skipCheckCardType;
        $skipCheckCardType = true; // пропустить проверку на бренд карты

        $arResult = \Jamilco\Loyalty\Bonus::getData($number, $applyBonuses);

        return $arResult;
    }

    public function executeComponent()
    {
        $this->arResult = $this->getData($this->arParams['CARD_NUMBER'], $this->arParams['APPLY_BONUS']);
        $this->arResult['BONUS_PRODUCT'] = \Jamilco\Loyalty\Bonus::checkBasketForBonusCard();
        $this->includeComponentTemplate();
    }
}