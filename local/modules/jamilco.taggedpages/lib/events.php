<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 21.02.17
 * Time: 16:33
 */

namespace Jamilco\TaggedPages;


class Events {
    public function addMenuItem(&$adminMenu, &$moduleMenu) {
        $moduleMenu[] = array(
            "parent_menu" => "global_menu_marketing", // поместим в раздел "Сервис"
            "section" => "jamilco.taggedpages",
            "sort"        => 1000,                    // сортировка пункта меню
            "url"         => "/bitrix/admin/jamilco_taggedpages_list.php",  // ссылка на пункте меню
            "text"        => 'Тегированные страницы',       // текст пункта меню
            "title"       => 'Тегированные страницы', // текст всплывающей подсказки
            "icon"        => "statistic_icon_searchers", // малая иконка
            "page_icon"   => "statistic_icon_searchers", // большая иконка
            "items_id"    => "menu_jamilco_taggedpages",  // идентификатор ветви
        );
    }
}