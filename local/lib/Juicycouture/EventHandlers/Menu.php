<?php

namespace Juicycouture\EventHandlers;


class Menu
{
    public static function OnBuildGlobalMenuHandler(&$adminMenu, &$moduleMenu)
    {

        $moduleMenu[] = array(
            "parent_menu" => "global_menu_marketing", // поместим в раздел "Маркетинг"
            "section"     => "",
            "sort"        => 1,                    // сортировка пункта меню
            "url"         => "/bitrix/admin/jamilco_catalog_export_by_article.php",  // ссылка на пункте меню
            "text"        => 'Выгрузка товаров',       // текст пункта меню
            "title"       => 'Выгрузка товаров', // текст всплывающей подсказки
            "icon"        => "trade_catalog_menu_icon", // малая иконка
            "page_icon"   => "trade_catalog_menu_icon", // большая иконка
            "items_id"    => "",  // идентификатор ветви
        );


        $arPage = array(
            "text"     => "Настройка SALE в каталоге",
            "url"      => "/bitrix/admin/sale_section_settings.php",
            "more_url" => array(),
            "title"    => "Настройка SALE в каталоге"
        );

        $arDir = array(
            "parent_menu" => "global_menu_store",       // поместим в раздел "Магазин"
            "section"     => "jamilco",
            "sort"        => 3,                         // сортировка пункта меню
            "url"         => "",                        // ссылка на пункте меню
            "text"        => 'Jamilco',                 // текст пункта меню
            "title"       => 'Настройка модулей Jamilco', // текст всплывающей подсказки
            "icon"        => "jamilco_menu_icon",       // малая иконка
            "page_icon"   => "jamilco_page_icon",       // большая иконка
            "items_id"    => "jamilco",                 // идентификатор ветви
            "items"       => array($arPage)
        );

        $found = false;
        foreach ($moduleMenu as $key => $arOne) {
            if ($arOne['section'] == $arDir['section']) {
                $found = true;
                $moduleMenu[$key]['items'][] = $arPage;
            }
        }

        if (!$found) $moduleMenu[] = $arDir;
    }
}
