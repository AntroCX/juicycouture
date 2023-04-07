<?

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock as HL;

function getGiftData($arGifts = [])
{
    if (!$arGifts) return false;

    Loader::includeModule('highloadblock');

    $hlblock = HL\HighloadBlockTable::getById(1)->fetch();
    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $colors = $entity->getDataClass();

    $arOut = [];
    foreach ($arGifts as $ruleId => $arGiftItems) {
        $of = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID'                 => IBLOCK_SKU_ID,
                'ACTIVE'                    => 'Y',
                'PROPERTY_CML2_LINK.ACTIVE' => 'Y',
                'ID'                        => array_keys($arGiftItems),
                '!PROPERTY_DELIVERY_CAN'    => false,
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'PREVIEW_PICTURE',
                'PROPERTY_CML2_LINK',
                'PROPERTY_CML2_LINK.NAME',
                'PROPERTY_CML2_LINK.PROPERTY_HIDE',
                'PROPERTY_COLOR',
                'PROPERTY_SIZES_CLOTHES',
                'PROPERTY_SIZES_SHOES',
                'PROPERTY_SIZES_RINGS',
                'CATALOG_GROUP_1',
                'CATALOG_GROUP_2',
            ]
        );
        while ($arOffer = $of->Fetch()) {
            if (array_key_exists($arOffer['ID'], $arOut)) continue;

            $size = $arOffer['PROPERTY_SIZES_CLOTHES_VALUE'];
            if (!$size) $size = $arOffer['PROPERTY_SIZES_SHOES_VALUE'];
            if (!$size) $size = $arOffer['PROPERTY_SIZES_RINGS_VALUE'];

            $arColor = $colors::getList(['filter' => ['UF_XML_ID' => $arOffer['PROPERTY_COLOR_VALUE']]])->Fetch();

            $arOut[$arOffer['ID']] = [
                'ID'       => $arOffer['ID'],
                'RULE_ID'  => $ruleId,
                'QUANTITY' => $arGiftItems[$arOffer['ID']],
                'ARTICLE'  => $arOffer['NAME'],
                'NAME'     => $arOffer['PROPERTY_CML2_LINK_NAME'],
                'PRICE'    => CurrencyFormat(($arOffer['CATALOG_PRICE_2']) ?: $arOffer['CATALOG_PRICE_1'], 'RUB'),
                'COLOR'    => $arColor['UF_NAME'],
                'SIZE'     => $size,
                'PHOTO'    => \CFile::GetPath($arOffer['PREVIEW_PICTURE']),
            ];
        }
    }

    return $arOut;
}

function isCurier($deliveryId = 0)
{
    return ($deliveryId == CURIER_DELIVERY || $deliveryId == KCE_DELIVERY) ? true : false;
}

function getStreets($locationId = 0)
{
    $cacheManager = \Bitrix\Main\Data\Cache::createInstance();
    $cacheTime = 86400 * 30; // 30 дней
    $cacheId = 'streets-'.$locationId;
    $cachePath = '/s1/location/streets/'.$locationId;
    $cacheParams = [];

    if ($cacheManager->startDataCache($cacheTime, $cacheId, $cachePath)) {
        global $CACHE_MANAGER;
        $CACHE_MANAGER->startTagCache($cachePath);
        $CACHE_MANAGER->RegisterTag('streets');
        $CACHE_MANAGER->RegisterTag('streets_'.$locationId);

        $res = \Bitrix\Sale\Location\LocationTable::getList(
            array(
                'runtime' => [
                    'SUB' => [
                        'data_type' => '\Bitrix\Sale\Location\Location',
                        'reference' => [
                            '>=ref.LEFT_MARGIN'  => 'this.LEFT_MARGIN',
                            '<=ref.RIGHT_MARGIN' => 'this.RIGHT_MARGIN'
                        ],
                        'join_type' => "inner"
                    ]
                ],
                'filter'  => [
                    '=ID'                   => $locationId,
                    '=SUB.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    '=SUB.TYPE.CODE'        => 'STREET'
                ],
                'select'  => [
                    //'S_ID'        => 'SUB.ID',
                    //'S_CODE'      => 'SUB.CODE',
                    'S_NAME_RU'   => 'SUB.NAME.NAME',
                    'S_TYPE_CODE' => 'SUB.TYPE.CODE'
                ],
                'order'   => [
                    'S_NAME_RU' => 'ASC'
                ],
            )
        );

        $arStreets = [];
        while ($item = $res->fetch()) {
            $arStreets[] = [
                'value' => $item['S_NAME_RU'],
                'data'  => $item['S_NAME_RU']
            ];
        }

        $CACHE_MANAGER->EndTagCache();
        $cacheManager->endDataCache($arStreets);
    } else {
        $arStreets = $cacheManager->GetVars();
    }

    return $arStreets;
}