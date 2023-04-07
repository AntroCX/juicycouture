<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 18.07.16
 * Time: 10:37
 */
$aMenu[] = array(
    "parent_menu" => "global_menu_marketing", // поместим в раздел "Сервис"
    "section" => "jamilco.taggedpages",
    "sort"        => 1000,                    // сортировка пункта меню
    "url"         => "/jamilco_reports_goods.php",  // ссылка на пункте меню
    "text"        => 'Тегированные страницы',       // текст пункта меню
    "title"       => 'Тегированные страницы', // текст всплывающей подсказки
    "icon"        => "jamilco_taggedpages_menu_icon", // малая иконка
    "page_icon"   => "jamilco_taggedpages_page_icon", // большая иконка
    "items_id"    => "menu_jamilco_taggedpages",  // идентификатор ветви

);