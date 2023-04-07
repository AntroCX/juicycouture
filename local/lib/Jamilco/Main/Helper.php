<?php

namespace Jamilco\Main;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

class Helper
{
    public static function getGaClientId(): string
    {
        $gaClientId = '';
        $gaClientIdCookie = Application::getInstance()->getContext()->getRequest()->getCookieRaw('_ga');
        if (!empty($gaClientIdCookie)) {
            $tmp = explode('.', $gaClientIdCookie);
            $gaClientId = "{$tmp[2]}.{$tmp[3]}";
        }
        return $gaClientId;
    }

    public static function getProductCategory($id): string
    {
        Loader::includeModule('iblock');
        $productRes = \CIBlockElement::GetByID((int)$id);
        $product = $productRes->GetNext();
        // Массив разделов
        $arSectionsByCurrent = [];
        $res = \CIBlockSection::GetNavChain(false, $product['IBLOCK_SECTION_ID']);
        while ($arSectionPath = $res->GetNext()) {
            $arSectionsByCurrent[] = $arSectionPath['NAME'];
        }
        return implode('/', $arSectionsByCurrent);
    }

    public static function getProductBrand($id)
    {
        Loader::includeModule('iblock');
        $brandProps = \CIBlockElement::GetProperty(IBLOCK_CATALOG_ID, $id, [], ['CODE' => 'BRAND']);
        return $brandProps->Fetch()['VALUE_ENUM'];
    }

    public static function getProductName($id): string
    {
        Loader::includeModule('iblock');
        $productRes = \CIBlockElement::GetByID((int)$id);
        $product = $productRes->GetNext();
        return $product['NAME'];
    }
}
