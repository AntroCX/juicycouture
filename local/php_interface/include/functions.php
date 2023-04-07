<?
include('nf_pp.php');

function ppr($array = array('array' => 'y'), $die = false, $show = false)
{
    global $USER;

    if ($die) $die = true;
    if ($show) $show = true;
    if (class_exists('nf_pp')) {
        if ($USER->isAdmin() || $show) {
            pp($array);
            if ($die) die();
        }
    } else {
        pr($array, $die, $show);
    }
}

function pr($array = array(), $die = false, $show = false)
{
 return;
}

/** парсинг урла для фильтра Каталога */
function filterUrls() {
    $re = '/^\/.*\/filter\/(.*)\/apply\//';
    $str = $GLOBALS['APPLICATION']->GetCurPage();
    preg_match($re, $str, $matches);
    $arrUrl= explode("/", $matches[1]);
    $urls = '';
    foreach ($arrUrl as $key => $prop) {
        $urls .= ($key == 0)?'#FILTER#':'/#FILTER'.$key.'#';
    }
    return $urls;
}

/** Узнаем e-mail пользователя по заказу. */
function getOwnerEmail($order)
{
    // Пробуем узнать из свойств заказа
    $res = CSaleOrderPropsValue::GetOrderProps($order);
    while ($row = $res->fetch()) {
        if ($row['IS_EMAIL']=='Y' && check_email($row['VALUE'])) {
            return $row['VALUE'];
        }
    }
    // Если не нашли, берем email пользователя
    if ($order = CSaleOrder::getById($order)) {
        if ($user = CUser::GetByID($order['USER_ID'])->fetch()) {
            return $user['EMAIL'];
        }
    }
    return false;
}

// Получить цвета
// необходимые классы
function getColors() {
    \Bitrix\Main\Loader::IncludeModule("highloadblock");

    $colors = [];
    $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(HIBLOCK_COLOR_ID)->fetch();
    $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
    $dataClass = $entity->getDataClass();
    $rsColors = $dataClass::getList(array(
        'order' => [],
        'select' => [
            'UF_XML_ID',
            'UF_NAME',
            'UF_FILE',
            'UF_SORT'
        ]
    ));
    while ($arColor = $rsColors->Fetch()) {
        $colors[$arColor['UF_XML_ID']] = $arColor;
    }

    return $colors;
}