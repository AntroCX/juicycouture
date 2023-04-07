<?
/** @global CMain$APPLICATION */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$module_id = 'jamilco.googlecatalog';
if ($APPLICATION->GetGroupRight($module_id) == 'D')
    return false;

return array(
    "parent_menu" => "global_menu_marketing", // поместим в раздел "Маркетинг"
    "section" => "",
    "sort"        => 1,                    // сортировка пункта меню
    "url"         => "jamilco_googlecatalog.php",  // ссылка на пункте меню
    "text"        => 'Google product category',       // текст пункта меню
    "title"       => 'Google product category', // текст всплывающей подсказки
    "icon"        => "jamilco_googlecatalog_menu_icon", // малая иконка
    "page_icon"   => "jamilco_googlecatalog_menu_icon", // большая иконка
    "items_id"    => "",  // идентификатор ветви
);