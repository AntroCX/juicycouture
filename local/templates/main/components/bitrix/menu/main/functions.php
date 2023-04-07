<?

function collectOne(&$arLast = [], $maxLevel = 1, $minLevel = 1)
{
    for ($i = $maxLevel; $i >= $minLevel; $i--) {
        if ($arLast[$i]) {
            $type = ($arLast[$i]['PARAMS']['UF_VIEW_TYPE']) ?: 'main';
            $type = ToUpper($type);
            $set = $i - 1;
            if ($set == 0) {
                $arLast[($i - 1)][] = $arLast[$i];
            } else {
                $arLast[($i - 1)][$type][] = $arLast[$i];
                $arLast[($i - 1)]['IS_PARENT'] = true;
            }
            unset($arLast[$i]);
        }
    }
}