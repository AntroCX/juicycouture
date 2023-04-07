<?php
namespace Jamilco\Merch;

use Jamilco\Merch\Common;

/**
 * Class Events
 * @package Jamilco\Merch
 */
class Events
{
    const MODULE_ID = 'jamilco.merch';

    public function addMenuItem(&$adminMenu, &$moduleMenu)
    {
        global $APPLICATION;
        $RIGHT = $APPLICATION->GetGroupRight(self::MODULE_ID);
        if ($RIGHT == 'D') return false;

        // пункт меню "Merch"
        $arPage = array(
            "text"     => "Merch",
            "url"      => "/bitrix/admin/jamilco_merch.php",
            "more_url" => array(),
            "title"    => "Панель управления Merch",
        );

        $arDir = array(
            "parent_menu" => "global_menu_store",       // поместим в раздел "Магазин"
            "section"     => "jamilco",
            "sort"        => 1,                         // сортировка пункта меню
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

    /**
     * после добавления \ изменения товара
     *
     * @param array $arItem
     *
     * @return bool
     */
    public function checkItem($arItem = [])
    {
        if (!$arItem['ID']) return false;

        if ($arItem['IBLOCK_ID'] == Common::getCapsuleIblockId()) {
            Common::checkCapsulesInItems();

            return true;
        }

        $arCatalog = Common::getCatalogIblock();
        if ($arItem['IBLOCK_ID'] > 0 && $arItem['IBLOCK_ID'] != $arCatalog['IBLOCK_ID']) return true;

        Common::checkSeasonInItem($arItem['ID'], $arItem['IBLOCK_ID']);
        Common::checkNewTimeInItem($arItem['ID'], $arItem['IBLOCK_ID']);
        Common::checkCapsuleInItem($arItem['ID'], $arItem['IBLOCK_ID']);

        return true;
    }

    /**
     * вызывается после удаления элемента
     *
     * @param array $arItem
     */
    public function deleteItem($arItem = [])
    {
        if ($arItem['IBLOCK_ID'] == Common::getCapsuleIblockId()) {
            Common::checkCapsulesInItems();
        }
    }
}