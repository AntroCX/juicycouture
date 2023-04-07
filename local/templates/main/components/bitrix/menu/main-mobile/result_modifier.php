<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

foreach ($arResult as $key => $arMenu) {
    if ($arMenu['DEPTH_LEVEL'] > $arParams['MAX_LEVEL']) unset($arResult[$key]);
}
$arResult = array_values($arResult);

$prevLevel = 1;
foreach ($arResult as $key => $arMenu) {
    if ($arMenu['DEPTH_LEVEL'] > $prevLevel) {
        $arResult[($key - 1)]['IS_PARENT'] = true;
    }
    $prevLevel = $arMenu['DEPTH_LEVEL'];
}