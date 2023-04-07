<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();


CBitrixComponent::includeComponentClass("bitrix:catalog.smart.filter");

class CJamilcoCatalogSmartFilter extends CBitrixCatalogSmartFilter
{
    function ArrayMultiply(&$arResult, $arTuple)
    {
        $arTemp = [];

        foreach ($arTuple as $key => $head) {
            if (is_array($head) && !count($head)) $head = false;
            if (!is_array($head)) $head = [$head];

            $arTemp2 = [];

            foreach ($head as $one) {
                if ($arTemp) {
                    foreach ($arTemp as $arTempOne) {
                        $arTempOne[$key] = $one;
                        $arTemp2[] = $arTempOne;
                    }
                } else {
                    $arTemp2[] = [$key => $one];
                }
            }
            $arTemp = $arTemp2;
        }

        $arResult = array_merge($arResult, $arTemp);
    }
}