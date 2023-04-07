<?php
namespace Jamilco\TaggedPages;

use \Bitrix\Main\Loader,
    \Bitrix\Iblock\SectionTable;

class SectionFilter
{

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

        $arActive = explode(',', $sections);
        $arSections = self::getCatalogSections();
        foreach ($arSections['MAIN'] as $arMainSect) {
            self::printOnePount($arMainSect, $arSections['CHILD'], $arActive, $result);
        }

        $result .= '<input type="hidden" name="SECTIONS" value="'.$sections.'">';
        $result .= '</div>';

        return $result;
    }

    static function printOnePount($arEnum = array(), $arChild = array(), $arActive = array(), &$out = '')
    {

        $isParent = (count($arChild[$arEnum['ID']])) ? true : false;

        $bSelected = in_array($arEnum['ID'], $arActive);

        if ($isParent) {
            $parentBlock = '<span class="parent-slide parent-yes" id="sections-'.$arEnum['ID'].'" data-id="'.$arEnum['ID'].'"></span>';
        } else {
            $parentBlock = '<span class="parent-slide parent-no">&nbsp;</span>';
        }

        $out .= $parentBlock.'<label><input class="radio-in-tree" type="checkbox" value="'.$arEnum["ID"].'" name="SECTIONS_LIST[]"'.($bSelected ? ' checked' : '').'> '.$arEnum["NAME"].'</label><br />';

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
        $arResult = array(
            'MAIN'  => array(), // корневые разделы
            'CHILD' => array(), // дочерние разделы всех уровней
        );

        $iblockId = self::getCatalogIblock();
        $se = SectionTable::GetList(
            array(
                'order'  => array('LEFT_MARGIN' => 'ASC'),
                'filter' => array('IBLOCK_ID' => $iblockId),
                'select' => array('ID', 'NAME', 'IBLOCK_SECTION_ID')
            )
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

        $cat = \CCatalog::GetList(array('IBLOCK_ID' => 'ASC'), array('!OFFERS_IBLOCK_ID' => false), false, array('nTopCount' => 1));
        if ($arCatalog = $cat->Fetch()) {
            return $arCatalog['IBLOCK_ID'];
        }
    }

    /**
     * возвращает ID раздела по УРЛу
     *
     * @param string $url
     *
     * @return int
     */
    public static function getSectionByURL($url = '')
    {
        $arUrl = explode('/', $url);
        TrimArr($arUrl);

        $return = false;
        foreach ($arUrl as $code) {
            $arFilter = array('IBLOCK_ID' => CATALOG_IBLOCK_ID, 'CODE' => $code);
            if ($return) $arFilter['IBLOCK_SECTION_ID'] = $return;
            $se = SectionTable::GetList(
                array(
                    'filter' => $arFilter,
                    'select' => array('ID'),
                    'limit'  => 1,
                )
            );
            if ($arSect = $se->Fetch()) {
                $return = $arSect['ID'];
            }
        }

        return $return;
    }
}