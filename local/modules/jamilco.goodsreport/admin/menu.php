<?
/** @global CMain$APPLICATION */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$module_id = 'jamilco.goodsreport';
if ($APPLICATION->GetGroupRight($module_id) == 'D')
    return false;

return array(
    "parent_menu" => "global_menu_marketing", // поместим в раздел "Маркетинг"
    "section" => "",
    "sort"        => 1,                    // сортировка пункта меню
    "url"         => "jamilco_goodsreport.php",  // ссылка на пункте меню
    "text"        => 'Список товаров с сайта',       // текст пункта меню
    "title"       => 'Выгрузить все товары в файл', // текст всплывающей подсказки
    "icon"        => "jamilco_goodsreport_menu_icon", // малая иконка
    "page_icon"   => "jamilco_goodsreport_menu_icon", // большая иконка
    "items_id"    => "",  // идентификатор ветви
);