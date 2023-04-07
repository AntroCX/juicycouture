<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 18.07.16
 * Time: 10:37
 */
$aMenu[] = array(
    "parent_menu" => "global_menu_services", // поместим в раздел "Сервис"
    "section" => "jamilco.reports",
    "sort"        => 1000,                    // сортировка пункта меню
    "url"         => "",  // ссылка на пункте меню
    "text"        => 'Отчеты Jamilco',       // текст пункта меню
    "title"       => 'Отчеты Jamilco', // текст всплывающей подсказки
    "icon"        => "jamilco_reports_menu_icon", // малая иконка
    "page_icon"   => "jamilco_reports_page_icon", // большая иконка
    "items_id"    => "menu_jamilco_reports",  // идентификатор ветви
    "items"       => array(
       /* array(
            "text" => "Список товаров",
            "url" => "/jamilco_reports_goods.php",
            "more_url" => array(),
            "title" => "Список товаров"
        ),*/
        array(
            "text" => "Список E-mail",
            "url" => "/jamilco_all_emails.php",
            "more_url" => array(),
            "title" => "Список E-mail"
        )
            // остальные уровни меню сформируем ниже.
    )       
);