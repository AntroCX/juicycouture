<?php

use Bitrix\Main\Loader;
use Juicycouture\Helpers\IblockHelper;

class HeaderInfoPanelComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        if ($this->startResultCache()) {

            Loader::requireModule('iblock');

            $this->arResult = $this->getInfoPanel();

            $this->includeComponentTemplate();
        }
    }

    private function getInfoPanel(): array
    {
        $dbRes = CIBlockElement::GetList(
            ['SORT' => 'asc'],
            [
                // 'IBLOCK_ID' => IblockHelper::getIblockIdByCode('header_info_panel'),
                'IBLOCK_ID' => 27,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'PROPERTY_LINK',
            ]
        );

        $items = [];
        while ($fields = $dbRes->Fetch()) {
            $items[] = [
                'id' => $fields['ID'],
                'name' => $fields['NAME'],
                'link' => $fields['PROPERTY_LINK_VALUE'] ?: '',
            ];
        }

        return $items;
    }
}
