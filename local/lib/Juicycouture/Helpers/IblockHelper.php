<?php

namespace Juicycouture\Helpers;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Juicycouture\Exceptions\IblockNotFoundException;

class IblockHelper
{
    public static function getIblockIdByCode(string $iblockCode): int
    {
        static $iblockCodeIdMap;

        if ($iblockCodeIdMap === null) {
            Loader::requireModule('iblock');

            $iblocks = IblockTable::getList([
                'select' => ['ID', 'CODE'],
            ])->fetchAll();

            $iblockCodeIdMap = collect($iblocks)
                ->mapWithKeys(function ($iblock) {
                    return [$iblock['CODE'] => $iblock['ID']];
                })
                ->all();
        }

        if (!($id = $iblockCodeIdMap[$iblockCode])) {
            throw new IblockNotFoundException("Iblock with code ${iblockCode} not found");
        }

        return $id;
    }
}
