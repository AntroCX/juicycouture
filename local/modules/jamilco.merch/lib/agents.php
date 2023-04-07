<?php
namespace Jamilco\Merch;

use \Jamilco\Merch\Common;
use \Bitrix\Main\Loader;

/**
 * Class Agents
 * @package Jamilco\Merch
 */
class Agents
{
    static public function checkNewTimes()
    {
        $arCatalog = Common::getCatalogIblock();
        $arProps = Common::getProps();

        // проверим флаг в настройках модуля
        if ($arProps['new.up']['VALUE']) {

            $timeClear = MakeTimeStamp($arProps['new.time']['VALUE']);
            if (time() >= $timeClear) {
                \COption::SetOptionInt('jamilco.merch', 'new.up', 0);
                \COption::SetOptionString('jamilco.merch', 'new.time', '');
            }

        } elseif ($arProps['new.time']['VALUE'] != '') {
            \COption::SetOptionString('jamilco.merch', 'new.time', '');
        }

        // проверим флаги в товарах
        $newCode = $arProps['prop.new']['VALUE'];
        $newTimeCode = $arProps['prop.new_clear']['VALUE'];

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arCatalog['IBLOCK_ID'],
                [
                    'LOGIC'                   => 'OR',
                    '!PROPERTY_'.$newCode     => false,
                    '!PROPERTY_'.$newTimeCode => false,
                ]
            ],
            false,
            false,
            ['IBLOCK_ID', 'ID', 'PROPERTY_'.$newCode, 'PROPERTY_'.$newTimeCode]
        );
        while ($arItem = $el->Fetch()) {
            if ($arItem['PROPERTY_'.$newCode.'_ENUM_ID'] > 0) {
                $timeClear = MakeTimeStamp($arItem['PROPERTY_'.$newTimeCode.'_VALUE']);
                if (time() > $timeClear) {
                    \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$newCode => false, $newTimeCode => '']);
                }
            } elseif ($arItem['PROPERTY_'.$newTimeCode.'_VALUE'] != '') {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], $arItem['IBLOCK_ID'], [$newTimeCode => '']);
            }
        }

        return "\Jamilco\Merch\Agents::checkNewTimes();";
    }

    static public function updateSortIndex()
    {
        Loader::includeModule('iblock');
        Loader::IncludeModule('jamilco.merch');

        $sort = new Common();
        $defSort = $sort->getSortCustom();

        $arSelect = Array("ID", "IBLOCK_ID", "PROPERTY_CAPSULE");
        $arFilter = Array("IBLOCK_ID" => IBLOCK_CATALOG_ID);
        $res = \CIBlockElement::GetList($defSort, $arFilter, false, false, $arSelect);

        $index = array();
        $item = array();
        $i = 1;

        while ($ob = $res->Fetch()) {
            $item['ID'] = $ob['ID'];
            $capsul = $ob['PROPERTY_CAPSULE_VALUE'];
            if (!empty($capsul)) {
                if (in_array($capsul, $index)) {
                    $item['SORT_VALUE'] = array_search($capsul, $index);
                } else {
                    $index[$i] = $capsul;
                    $item['SORT_VALUE'] = $i;
                    $i++;
                }
            } else {
                $item['SORT_VALUE'] = $i;
                $i++;
            }
            $items[] = $item;
        }

        $prop_add = 'CAPSULE_SORT';

        foreach ($items as $value) {

            \CIBlockElement::SetPropertyValuesEx((int)$value['ID'], IBLOCK_CATALOG_ID, array($prop_add => $value['SORT_VALUE']));

        }
        return "\Jamilco\Merch\Agents::updateSortIndex();";
    }
}