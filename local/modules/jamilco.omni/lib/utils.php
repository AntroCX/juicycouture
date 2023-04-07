<?php

namespace Jamilco\Omni;

use \Bitrix\Main\Loader,
    \Bitrix\Sale,
    \Bitrix\Iblock\SectionTable;

class Utils
{

    public static function getDefaultLocations()
    {
        $arLocs = [
            \Jamilco\Delivery\Location::getLocationByName('Московская область', true),
            \Jamilco\Delivery\Location::getLocationByName('Ленинградская область', true),
            \Jamilco\Delivery\Location::getLocationByName('Москва', true),
            \Jamilco\Delivery\Location::getLocationByName('Санкт-Петербург', true),
        ];

        return $arLocs;
    }

    /**
     * отображает выбор разделов
     *
     * @param string $sections - активные разделы через запятую
     *
     * @return string
     */
    public static function showSectionFilter($sections = '')
    {
        $result = '<div class="section-select">';
        $result .= '<input type="hidden" name="sections" value="'.$sections.'">';

        $arActive = explode(',', $sections);
        $arSections = self::getCatalogSections();
        foreach ($arSections['MAIN'] as $arMainSect) {
            self::printOnePount($arMainSect, $arSections['CHILD'], $arActive, $result);
        }

        $result .= '</div>';

        return $result;
    }

    public static function printOnePount($arEnum = [], $arChild = [], $arActive = [], &$out = '')
    {

        $isParent = (count($arChild[$arEnum['ID']])) ? true : false;

        $bSelected = in_array($arEnum['ID'], $arActive);

        if ($isParent) {
            $parentBlock = '<span class="parent-slide parent-yes" id="sections-'.$arEnum['ID'].'" data-id="'.$arEnum['ID'].'"></span>';
        } else {
            $parentBlock = '<span class="parent-slide parent-no">&nbsp;</span>';
        }

        $out .= $parentBlock.'<label><input class="radio-in-tree" type="checkbox" value="'.$arEnum["ID"].'" name="sections_list[]"'.($bSelected ? ' checked' : '').'> '.$arEnum["NAME"].'</label><br />';

        if ($isParent) {
            $out .= '<div class="child-slide" id="sections-for-'.$arEnum["ID"].'">';
            foreach ($arChild[$arEnum['ID']] as $subEnum) {
                $out = self::printOnePount($subEnum, $arChild, $arActive, $out);
            }
            $out .= '</div>';
        }

        return $out;
    }

    /**
     * возвращает разделы каталога товаров
     * @return array
     */
    public static function getCatalogSections()
    {
        $arResult = [
            'MAIN'  => [], // корневые разделы
            'CHILD' => [], // дочерние разделы всех уровней
        ];

        $iblockId = self::getCatalogIblock();
        $se = SectionTable::GetList(
            [
                'order'  => ['LEFT_MARGIN' => 'ASC'],
                'filter' => ['IBLOCK_ID' => $iblockId],
                'select' => ['ID', 'NAME', 'IBLOCK_SECTION_ID'],
            ]
        );
        while ($arSect = $se->Fetch()) {
            if (!$arSect['IBLOCK_SECTION_ID']) {
                $arResult['MAIN'][] = $arSect;
            } else {
                $arResult['CHILD'][$arSect['IBLOCK_SECTION_ID']][] = $arSect;
            }
        }

        return $arResult;
    }

    public static function getCatalogIblock()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        $cat = \CCatalog::GetList(['IBLOCK_ID' => 'ASC'], ['!OFFERS_IBLOCK_ID' => false], false, ['nTopCount' => 1]);
        if ($arCatalog = $cat->Fetch()) {
            return $arCatalog['IBLOCK_ID'];
        }
    }
}