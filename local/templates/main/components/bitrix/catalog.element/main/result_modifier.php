<?

use Bitrix\Main\Type\Collection;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Jamilco\Omni\Channel;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

require_once 'functions.php';

$arStoresID = [0]; // массив складов для проверки розничного наличия
if ($arParams['CITY_STORES_NAME']) {
    // проверяем только те склады, которые есть в текущем городе
    $storeCities = \Jamilco\Main\Retail::getCityStores();
    $arStoresID = $storeCities[$arParams['CITY_STORES_NAME']];
}

if (Loader::includeModule("jamilco.omni")) {
    $sectionId = $arResult['IBLOCK_SECTION_ID']; // ID раздела заменится на массив цепочки разделов
    $arResult['OMNI']['CHANNEL']['ITEM'] = Channel::getFlagForItem($arResult['ID'], $sectionId, $arResult['IBLOCK_ID']);
    $arResult['OMNI']['HIDE_RETAIL'] = in_array('hideretail', $arResult['OMNI']['CHANNEL']['ITEM']) ? 'Y' : 'N';

    /** ALL-45 показывать HideRetail админам и сотрудникам РМ */
    $arGroupAvalaible = array(1, 6); // группы админ(1) и сотрудники РМ(6)
    $arGroups = CUser::GetUserGroup($USER->GetID()); // группы пользователя
    $result_intersect = array_intersect($arGroupAvalaible, $arGroups);
    if (count($result_intersect) > 0) $arResult['OMNI']['HIDE_RETAIL'] = 'N';

    foreach ($arResult['OFFERS'] as $key => $arOffer) {
        $arResult['OMNI']['CHANNEL']['OFFER'][$arOffer['ID']] = $offerOmni = Channel::getDeliveryData(
            array(
                'ID'         => $arOffer['ID'],
                'IBLOCK_ID'  => $arOffer['IBLOCK_ID'],
                'ARTICLE'    => $arOffer['NAME'],
                'QUANTITY'   => $arOffer['CATALOG_QUANTITY'],
                'DENIED_OCS' => 'Y', // блокируем запрос данных по остаткам в OCS, они запросятся после выбранного размера адресно
                //'DENIED_SHOPS' => 'Y', // на выходе получим только флаги, без списка РМ
            ),
            array(
                'ID'                => $arResult['ID'],
                'IBLOCK_SECTION_ID' => $arResult['IBLOCK_SECTION_ID'],
                'IBLOCK_ID'         => $arResult['IBLOCK_ID']
            ),
            true
        );
        if ($offerOmni['PM_N_PICK'] == 'Y' || $offerOmni['PM_N_DEV_TO'] == 'Y' || $offerOmni['SHOP2SHOP_T_DEV'] == 'Y') {
            $arResult['OFFERS'][$key]['DENIED_RESERVATION'] = 'N';
        } else {
            $arResult['OFFERS'][$key]['DENIED_RESERVATION'] = 'Y';
        }

        if (!$arParams['CITY_STORES_NAME']) $arResult['OFFERS'][$key]['DENIED_RESERVATION'] = 'Y';

        if ($arParams['CITY_STORES_NAME'] && $offerOmni['SHOP']['DAY_DELIVERY']) {
            foreach ($offerOmni['SHOP']['DAY_DELIVERY'] as $shopKey => $arShop) {
                if (!substr_count($arShop['TITLE'], $arParams['CITY_STORES_NAME']) && !substr_count($arShop['ADDRESS'], $arParams['CITY_STORES_NAME'])) {
                    unset($offerOmni['SHOP']['DAY_DELIVERY'][$shopKey]);
                }
            }

            if (!count($offerOmni['SHOP']['DAY_DELIVERY'])) unset($offerOmni['SHOP']['DAY_DELIVERY']);
            if (!\Jamilco\Main\Utils::checkTimeForExpressDelivery()) unset($offerOmni['SHOP']['DAY_DELIVERY']); // проверка на время дня
        } else {
            unset($offerOmni['SHOP']['DAY_DELIVERY']);
        }

        if (
            $offerOmni['DELIVERY_T_DEV'] == 'Y' ||
            $offerOmni['PICKUP_POINT_T_DEV'] == 'Y' ||
            count($offerOmni['SHOP']['DELIVERY']) || count($offerOmni['SHOP']['DAY_DELIVERY'])
        ) {
            $arResult['OFFERS'][$key]['DENIED_DELIVERY'] = 'N';
            if (!$arResult['OFFERS'][$key]['CATALOG_QUANTITY']) $arResult['OFFERS'][$key]['CATALOG_QUANTITY'] = 1;
        } else {
            $arResult['OFFERS'][$key]['DENIED_DELIVERY'] = 'Y';
        }
    }
}

$displayPreviewTextMode = array(
    'H' => true,
    'E' => true,
    'S' => true
);
$detailPictMode = array(
    'IMG'       => true,
    'POPUP'     => true,
    'MAGNIFIER' => true,
    'GALLERY'   => true
);
// таблица соответствия размеров US->RU
$arResult['SIZES_TABLE'] = [
    '5'    => 35,
    '5.5'  => 35.5,
    '6'    => 36,
    '6.5'  => 36.5,
    '7'    => 37,
    '7.5'  => 37.5,
    '8'    => 38,
    '8.5'  => 39,
    '9'    => 40,
    '9.5'  => 40.5,
    '10'   => 41,
    '11'   => 42,
    // на всякий случай для русских размеров
    '35'   => 35,
    '35.5' => 35.5,
    '36'   => 36,
    '36.5' => 36.5,
    '37'   => 37,
    '37.5' => 37.5,
    '38'   => 38,
    '39'   => 39,
    '40'   => 40,
    '40.5' => 40.5,
    '41'   => 41,
    '42'   => 42,
];
$arDefaultParams = array(
    'TEMPLATE_THEME'            => 'blue',
    'ADD_PICT_PROP'             => '-',
    'LABEL_PROP'                => '-',
    'OFFER_ADD_PICT_PROP'       => '-',
    'OFFER_TREE_PROPS'          => array('-'),
    'DISPLAY_NAME'              => 'Y',
    'DETAIL_PICTURE_MODE'       => 'IMG',
    'ADD_DETAIL_TO_SLIDER'      => 'N',
    'DISPLAY_PREVIEW_TEXT_MODE' => 'E',
    'PRODUCT_SUBSCRIPTION'      => 'N',
    'SHOW_DISCOUNT_PERCENT'     => 'N',
    'SHOW_OLD_PRICE'            => 'N',
    'SHOW_MAX_QUANTITY'         => 'N',
    'SHOW_BASIS_PRICE'          => 'N',
    'ADD_TO_BASKET_ACTION'      => array('BUY'),
    'SHOW_CLOSE_POPUP'          => 'N',
    'MESS_BTN_BUY'              => '',
    'MESS_BTN_ADD_TO_BASKET'    => '',
    'MESS_BTN_SUBSCRIBE'        => '',
    'MESS_BTN_COMPARE'          => '',
    'MESS_NOT_AVAILABLE'        => '',
    'USE_VOTE_RATING'           => 'N',
    'VOTE_DISPLAY_AS_RATING'    => 'rating',
    'USE_COMMENTS'              => 'N',
    'BLOG_USE'                  => 'N',
    'BLOG_URL'                  => 'catalog_comments',
    'BLOG_EMAIL_NOTIFY'         => 'N',
    'VK_USE'                    => 'N',
    'VK_API_ID'                 => '',
    'FB_USE'                    => 'N',
    'FB_APP_ID'                 => '',
    'BRAND_USE'                 => 'N',
    'BRAND_PROP_CODE'           => ''
);
$arParams = array_merge($arDefaultParams, $arParams);

$arParams['TEMPLATE_THEME'] = (string)($arParams['TEMPLATE_THEME']);
if ('' != $arParams['TEMPLATE_THEME']) {
    $arParams['TEMPLATE_THEME'] = preg_replace('/[^a-zA-Z0-9_\-\(\)\!]/', '', $arParams['TEMPLATE_THEME']);
    if ('site' == $arParams['TEMPLATE_THEME']) {
        $templateId = COption::GetOptionString("main", "wizard_template_id", "eshop_bootstrap", SITE_ID);
        $templateId = (preg_match("/^eshop_adapt/", $templateId)) ? "eshop_adapt" : $templateId;
        $arParams['TEMPLATE_THEME'] = COption::GetOptionString('main', 'wizard_'.$templateId.'_theme_id', 'blue', SITE_ID);
    }
    if ('' != $arParams['TEMPLATE_THEME']) {
        if (!is_file($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/themes/'.$arParams['TEMPLATE_THEME'].'/style.css')) {
            $arParams['TEMPLATE_THEME'] = '';
        }
    }
}
if ('' == $arParams['TEMPLATE_THEME']) {
    $arParams['TEMPLATE_THEME'] = 'blue';
}

$arParams['ADD_PICT_PROP'] = trim($arParams['ADD_PICT_PROP']);
if ('-' == $arParams['ADD_PICT_PROP']) {
    $arParams['ADD_PICT_PROP'] = '';
}
$arParams['LABEL_PROP'] = trim($arParams['LABEL_PROP']);
if ('-' == $arParams['LABEL_PROP']) {
    $arParams['LABEL_PROP'] = '';
}
$arParams['OFFER_ADD_PICT_PROP'] = trim($arParams['OFFER_ADD_PICT_PROP']);
if ('-' == $arParams['OFFER_ADD_PICT_PROP']) {
    $arParams['OFFER_ADD_PICT_PROP'] = '';
}
if (!is_array($arParams['OFFER_TREE_PROPS'])) {
    $arParams['OFFER_TREE_PROPS'] = array($arParams['OFFER_TREE_PROPS']);
}
foreach ($arParams['OFFER_TREE_PROPS'] as $key => $value) {
    $value = (string)$value;
    if ('' == $value || '-' == $value) {
        unset($arParams['OFFER_TREE_PROPS'][$key]);
    }
}
if (empty($arParams['OFFER_TREE_PROPS']) && isset($arParams['OFFERS_CART_PROPERTIES']) && is_array($arParams['OFFERS_CART_PROPERTIES'])) {
    $arParams['OFFER_TREE_PROPS'] = $arParams['OFFERS_CART_PROPERTIES'];
    foreach ($arParams['OFFER_TREE_PROPS'] as $key => $value) {
        $value = (string)$value;
        if ('' == $value || '-' == $value) {
            unset($arParams['OFFER_TREE_PROPS'][$key]);
        }
    }
}
if ('N' != $arParams['DISPLAY_NAME']) {
    $arParams['DISPLAY_NAME'] = 'Y';
}
if (!isset($detailPictMode[$arParams['DETAIL_PICTURE_MODE']])) {
    $arParams['DETAIL_PICTURE_MODE'] = 'IMG';
}
if ('Y' != $arParams['ADD_DETAIL_TO_SLIDER']) {
    $arParams['ADD_DETAIL_TO_SLIDER'] = 'N';
}
if (!isset($displayPreviewTextMode[$arParams['DISPLAY_PREVIEW_TEXT_MODE']])) {
    $arParams['DISPLAY_PREVIEW_TEXT_MODE'] = 'E';
}
if ('Y' != $arParams['PRODUCT_SUBSCRIPTION']) {
    $arParams['PRODUCT_SUBSCRIPTION'] = 'N';
}
if ('Y' != $arParams['SHOW_DISCOUNT_PERCENT']) {
    $arParams['SHOW_DISCOUNT_PERCENT'] = 'N';
}
if ('Y' != $arParams['SHOW_OLD_PRICE']) {
    $arParams['SHOW_OLD_PRICE'] = 'N';
}
if ('Y' != $arParams['SHOW_MAX_QUANTITY']) {
    $arParams['SHOW_MAX_QUANTITY'] = 'N';
}
if ($arParams['SHOW_BASIS_PRICE'] != 'Y') {
    $arParams['SHOW_BASIS_PRICE'] = 'N';
}
if (!is_array($arParams['ADD_TO_BASKET_ACTION'])) {
    $arParams['ADD_TO_BASKET_ACTION'] = array($arParams['ADD_TO_BASKET_ACTION']);
}
$arParams['ADD_TO_BASKET_ACTION'] = array_filter($arParams['ADD_TO_BASKET_ACTION'], 'CIBlockParameters::checkParamValues');
if (empty($arParams['ADD_TO_BASKET_ACTION']) || (!in_array('ADD', $arParams['ADD_TO_BASKET_ACTION']) && !in_array('BUY', $arParams['ADD_TO_BASKET_ACTION']))) {
    $arParams['ADD_TO_BASKET_ACTION'] = array('BUY');
}
if ($arParams['SHOW_CLOSE_POPUP'] != 'Y') {
    $arParams['SHOW_CLOSE_POPUP'] = 'N';
}

$arParams['MESS_BTN_BUY'] = trim($arParams['MESS_BTN_BUY']);
$arParams['MESS_BTN_ADD_TO_BASKET'] = trim($arParams['MESS_BTN_ADD_TO_BASKET']);
$arParams['MESS_BTN_SUBSCRIBE'] = trim($arParams['MESS_BTN_SUBSCRIBE']);
$arParams['MESS_BTN_COMPARE'] = trim($arParams['MESS_BTN_COMPARE']);
$arParams['MESS_NOT_AVAILABLE'] = trim($arParams['MESS_NOT_AVAILABLE']);
if ('Y' != $arParams['USE_VOTE_RATING']) {
    $arParams['USE_VOTE_RATING'] = 'N';
}
if ('vote_avg' != $arParams['VOTE_DISPLAY_AS_RATING']) {
    $arParams['VOTE_DISPLAY_AS_RATING'] = 'rating';
}
if ('Y' != $arParams['USE_COMMENTS']) {
    $arParams['USE_COMMENTS'] = 'N';
}
if ('Y' != $arParams['BLOG_USE']) {
    $arParams['BLOG_USE'] = 'N';
}
if ('Y' != $arParams['VK_USE']) {
    $arParams['VK_USE'] = 'N';
}
if ('Y' != $arParams['FB_USE']) {
    $arParams['FB_USE'] = 'N';
}
if ('Y' == $arParams['USE_COMMENTS']) {
    if ('N' == $arParams['BLOG_USE'] && 'N' == $arParams['VK_USE'] && 'N' == $arParams['FB_USE']) {
        $arParams['USE_COMMENTS'] = 'N';
    }
}
if ('Y' != $arParams['BRAND_USE']) {
    $arParams['BRAND_USE'] = 'N';
}
if ($arParams['BRAND_PROP_CODE'] == '') {
    $arParams['BRAND_PROP_CODE'] = array();
}
if (!is_array($arParams['BRAND_PROP_CODE'])) {
    $arParams['BRAND_PROP_CODE'] = array($arParams['BRAND_PROP_CODE']);
}

$arEmptyPreview = false;
$strEmptyPreview = $this->GetFolder().'/images/no_photo.png';
if (file_exists($_SERVER['DOCUMENT_ROOT'].$strEmptyPreview)) {
    $arSizes = getimagesize($_SERVER['DOCUMENT_ROOT'].$strEmptyPreview);
    if (!empty($arSizes)) {
        $arEmptyPreview = array(
            'SRC'    => $strEmptyPreview,
            'WIDTH'  => (int)$arSizes[0],
            'HEIGHT' => (int)$arSizes[1]
        );
    }
    unset($arSizes);
}
unset($strEmptyPreview);

$arSKUPropList = array();
$arSKUPropIDs = array();
$arSKUPropKeys = array();
$boolSKU = false;
$strBaseCurrency = '';
$boolConvert = isset($arResult['CONVERT_CURRENCY']['CURRENCY_ID']);

if ($arResult['MODULES']['catalog']) {
    if (!$boolConvert) {
        $strBaseCurrency = CCurrency::GetBaseCurrency();
    }

    $arSKU = CCatalogSKU::GetInfoByProductIBlock($arParams['IBLOCK_ID']);
    $boolSKU = !empty($arSKU) && is_array($arSKU);

    if ($boolSKU && !empty($arParams['OFFER_TREE_PROPS'])) {
        $arSKUPropList = CIBlockPriceTools::getTreeProperties(
            $arSKU,
            $arParams['OFFER_TREE_PROPS'],
            array(
                'PICT' => $arEmptyPreview,
                'NAME' => '-'
            )
        );
        $arSKUPropIDs = array_keys($arSKUPropList);
    }
}

$arResult['CHECK_QUANTITY'] = false;
if (!isset($arResult['CATALOG_MEASURE_RATIO'])) {
    $arResult['CATALOG_MEASURE_RATIO'] = 1;
}
if (!isset($arResult['CATALOG_QUANTITY'])) {
    $arResult['CATALOG_QUANTITY'] = 0;
}
$arResult['CATALOG_QUANTITY'] = (
0 < $arResult['CATALOG_QUANTITY'] && is_float($arResult['CATALOG_MEASURE_RATIO'])
    ? (float)$arResult['CATALOG_QUANTITY']
    : (int)$arResult['CATALOG_QUANTITY']
);
$arResult['CATALOG'] = false;
if (!isset($arResult['CATALOG_SUBSCRIPTION']) || 'Y' != $arResult['CATALOG_SUBSCRIPTION']) {
    $arResult['CATALOG_SUBSCRIPTION'] = 'N';
}

CIBlockPriceTools::getLabel($arResult, $arParams['LABEL_PROP']);

$productSlider = CIBlockPriceTools::getSliderForItem($arResult, $arParams['ADD_PICT_PROP'], 'Y' == $arParams['ADD_DETAIL_TO_SLIDER']);
if (empty($productSlider)) {
    $productSlider = array(
        0 => $arEmptyPreview
    );
}
$productSliderCount = count($productSlider);
$arResult['SHOW_SLIDER'] = true;
$arResult['MORE_PHOTO'] = $productSlider;
$arResult['MORE_PHOTO_COUNT'] = count($productSlider);

if ($arResult['MODULES']['catalog']) {
    $arResult['CATALOG'] = true;
    if (!isset($arResult['CATALOG_TYPE'])) {
        $arResult['CATALOG_TYPE'] = CCatalogProduct::TYPE_PRODUCT;
    }
    if (
        (CCatalogProduct::TYPE_PRODUCT == $arResult['CATALOG_TYPE'] || CCatalogProduct::TYPE_SKU == $arResult['CATALOG_TYPE'])
        && !empty($arResult['OFFERS'])
    ) {
        $arResult['CATALOG_TYPE'] = CCatalogProduct::TYPE_SKU;
    }
    switch ($arResult['CATALOG_TYPE']) {
        case CCatalogProduct::TYPE_SET:
            $arResult['OFFERS'] = array();
            $arResult['CHECK_QUANTITY'] = ('Y' == $arResult['CATALOG_QUANTITY_TRACE'] && 'N' == $arResult['CATALOG_CAN_BUY_ZERO']);
            break;
        case CCatalogProduct::TYPE_SKU:
            break;
        case CCatalogProduct::TYPE_PRODUCT:
        default:
            $arResult['CHECK_QUANTITY'] = ('Y' == $arResult['CATALOG_QUANTITY_TRACE'] && 'N' == $arResult['CATALOG_CAN_BUY_ZERO']);
            break;
    }
} else {
    $arResult['CATALOG_TYPE'] = 0;
    $arResult['OFFERS'] = array();
}

if ($arResult['CATALOG'] && isset($arResult['OFFERS']) && !empty($arResult['OFFERS'])) {
    $boolSKUDisplayProps = false;

    $arResultSKUPropIDs = array();
    $arFilterProp = array();
    $arNeedValues = array();
    foreach ($arResult['OFFERS'] as &$arOffer) {
        foreach ($arSKUPropIDs as &$strOneCode) {
            if (isset($arOffer['DISPLAY_PROPERTIES'][$strOneCode])) {
                $arResultSKUPropIDs[$strOneCode] = true;
                if (!isset($arNeedValues[$arSKUPropList[$strOneCode]['ID']])) {
                    $arNeedValues[$arSKUPropList[$strOneCode]['ID']] = array();
                }
                $valueId = (
                $arSKUPropList[$strOneCode]['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_LIST
                    ? $arOffer['DISPLAY_PROPERTIES'][$strOneCode]['VALUE_ENUM_ID']
                    : $arOffer['DISPLAY_PROPERTIES'][$strOneCode]['VALUE']
                );
                $arNeedValues[$arSKUPropList[$strOneCode]['ID']][$valueId] = $valueId;
                unset($valueId);
                if (!isset($arFilterProp[$strOneCode])) {
                    $arFilterProp[$strOneCode] = $arSKUPropList[$strOneCode];
                }
            }
        }
        unset($strOneCode);
    }
    unset($arOffer);

    CIBlockPriceTools::getTreePropertyValues($arSKUPropList, $arNeedValues);
    unset($arSKUPropList['COLOR']['VALUES'][0]); // зачистка пустого значения цвета

    $arSKUPropIDs = array_keys($arSKUPropList);
    $arSKUPropKeys = array_fill_keys($arSKUPropIDs, false);


    $arMatrixFields = $arSKUPropKeys;
    $arMatrix = array();

    $arNewOffers = array();

    $arIDS = array($arResult['ID']);
    $arOfferSet = array();
    $arResult['OFFER_GROUP'] = false;
    $arResult['OFFERS_PROP'] = false;

    $arDouble = array();
    foreach ($arResult['OFFERS'] as $keyOffer => $arOffer) {
        $arOffer['ID'] = (int)$arOffer['ID'];
        if (isset($arDouble[$arOffer['ID']])) {
            continue;
        }
        $arIDS[] = $arOffer['ID'];
        $boolSKUDisplayProperties = false;
        $arOffer['OFFER_GROUP'] = false;
        $arRow = array();
        foreach ($arSKUPropIDs as $propkey => $strOneCode) {
            $arCell = array(
                'VALUE' => 0,
                'SORT'  => PHP_INT_MAX,
                'NA'    => true
            );
            if (isset($arOffer['DISPLAY_PROPERTIES'][$strOneCode])) {
                $arMatrixFields[$strOneCode] = true;
                $arCell['NA'] = false;
                if ('directory' == $arSKUPropList[$strOneCode]['USER_TYPE']) {
                    $intValue = $arSKUPropList[$strOneCode]['XML_MAP'][$arOffer['DISPLAY_PROPERTIES'][$strOneCode]['VALUE']];
                    $arCell['VALUE'] = $intValue;
                } elseif ('L' == $arSKUPropList[$strOneCode]['PROPERTY_TYPE']) {
                    $arCell['VALUE'] = (int)$arOffer['DISPLAY_PROPERTIES'][$strOneCode]['VALUE_ENUM_ID'];
                } elseif ('E' == $arSKUPropList[$strOneCode]['PROPERTY_TYPE']) {
                    $arCell['VALUE'] = (int)$arOffer['DISPLAY_PROPERTIES'][$strOneCode]['VALUE'];
                }
                $arCell['SORT'] = $arSKUPropList[$strOneCode]['VALUES'][$arCell['VALUE']]['SORT'];
            }
            $arRow[$strOneCode] = $arCell;
        }
        $arMatrix[$keyOffer] = $arRow;

        CIBlockPriceTools::setRatioMinPrice($arOffer, false);

        $arOffer['MORE_PHOTO'] = array();
        $arOffer['MORE_PHOTO_COUNT'] = 0;
        $offerSlider = CIBlockPriceTools::getSliderForItem($arOffer, $arParams['OFFER_ADD_PICT_PROP'], $arParams['ADD_DETAIL_TO_SLIDER'] == 'Y');
        if (empty($offerSlider)) {
            $offerSlider = $productSlider;
        }
        $arOffer['MORE_PHOTO'] = $offerSlider;
        $arOffer['MORE_PHOTO_COUNT'] = count($offerSlider);

        if (CIBlockPriceTools::clearProperties($arOffer['DISPLAY_PROPERTIES'], $arParams['OFFER_TREE_PROPS'])) {
            $boolSKUDisplayProps = true;
        }

        $arDouble[$arOffer['ID']] = true;
        $arNewOffers[$keyOffer] = $arOffer;
    }
    $arResult['OFFERS'] = $arNewOffers;
    $arResult['SHOW_OFFERS_PROPS'] = $boolSKUDisplayProps;

    $arUsedFields = array();
    $arSortFields = array();

    foreach ($arSKUPropIDs as $propkey => $strOneCode) {
        $boolExist = $arMatrixFields[$strOneCode];
        foreach ($arMatrix as $keyOffer => $arRow) {
            if ($boolExist) {
                if (!isset($arResult['OFFERS'][$keyOffer]['TREE'])) {
                    $arResult['OFFERS'][$keyOffer]['TREE'] = array();
                }
                $arResult['OFFERS'][$keyOffer]['TREE']['PROP_'.$arSKUPropList[$strOneCode]['ID']] = $arMatrix[$keyOffer][$strOneCode]['VALUE'];
                $arResult['OFFERS'][$keyOffer]['SKU_SORT_'.$strOneCode] = $arMatrix[$keyOffer][$strOneCode]['SORT'];
                $arUsedFields[$strOneCode] = true;
                $arSortFields['SKU_SORT_'.$strOneCode] = SORT_NUMERIC;
            } else {
                unset($arMatrix[$keyOffer][$strOneCode]);
            }
        }
    }
    $arResult['OFFERS_PROP'] = $arUsedFields;
    $arResult['OFFERS_PROP_CODES'] = (!empty($arUsedFields) ? base64_encode(serialize(array_keys($arUsedFields))) : '');

    Collection::sortByColumn($arResult['OFFERS'], $arSortFields);

    $offerSet = array();
    if (!empty($arIDS) && CBXFeatures::IsFeatureEnabled('CatCompleteSet')) {
        $offerSet = array_fill_keys($arIDS, false);
        $rsSets = CCatalogProductSet::getList(
            array(),
            array(
                '@OWNER_ID' => $arIDS,
                '=SET_ID'   => 0,
                '=TYPE'     => CCatalogProductSet::TYPE_GROUP
            ),
            false,
            false,
            array('ID', 'OWNER_ID')
        );
        while ($arSet = $rsSets->Fetch()) {
            $arSet['OWNER_ID'] = (int)$arSet['OWNER_ID'];
            $offerSet[$arSet['OWNER_ID']] = true;
            $arResult['OFFER_GROUP'] = true;
        }
        if ($offerSet[$arResult['ID']]) {
            foreach ($offerSet as &$setOfferValue) {
                if ($setOfferValue === false) {
                    $setOfferValue = true;
                }
            }
            unset($setOfferValue);
            unset($offerSet[$arResult['ID']]);
        }
        if ($arResult['OFFER_GROUP']) {
            $offerSet = array_filter($offerSet);
            $arResult['OFFER_GROUP_VALUES'] = array_keys($offerSet);
        }
    }

    $arMatrix = array();
    $intSelected = -1;
    $arResult['MIN_PRICE'] = false;
    $arResult['MIN_BASIS_PRICE'] = false;
    foreach ($arResult['OFFERS'] as $keyOffer => $arOffer) {
        if (empty($arResult['MIN_PRICE'])) {
            if ($arResult['OFFER_ID_SELECTED'] > 0) {
                $foundOffer = ($arResult['OFFER_ID_SELECTED'] == $arOffer['ID']);
            } else {
                $foundOffer = $arOffer['CAN_BUY'];
            }
            if ($foundOffer) {
                $intSelected = $keyOffer;
                $arResult['MIN_PRICE'] = (isset($arOffer['RATIO_PRICE']) ? $arOffer['RATIO_PRICE'] : $arOffer['MIN_PRICE']);
                $arResult['MIN_BASIS_PRICE'] = $arOffer['MIN_PRICE'];
            }
            unset($foundOffer);
        }

        $arSKUProps = false;
        if (!empty($arOffer['DISPLAY_PROPERTIES'])) {
            $boolSKUDisplayProps = true;
            $arSKUProps = array();
            foreach ($arOffer['DISPLAY_PROPERTIES'] as &$arOneProp) {
                if ('F' == $arOneProp['PROPERTY_TYPE']) {
                    continue;
                }
                $arSKUProps[] = array(
                    'NAME'  => $arOneProp['NAME'],
                    'VALUE' => $arOneProp['DISPLAY_VALUE']
                );
            }
            unset($arOneProp);
        }
        if (isset($arOfferSet[$arOffer['ID']])) {
            $arOffer['OFFER_GROUP'] = true;
            $arResult['OFFERS'][$keyOffer]['OFFER_GROUP'] = true;
        }
        reset($arOffer['MORE_PHOTO']);
        $firstPhoto = current($arOffer['MORE_PHOTO']);
        $arOneRow = array(
            'ID'                 => $arOffer['ID'],
            'NAME'               => $arOffer['~NAME'],
            'TREE'               => $arOffer['TREE'],
            'PRICE'              => (isset($arOffer['RATIO_PRICE']) ? $arOffer['RATIO_PRICE'] : $arOffer['MIN_PRICE']),
            'BASIS_PRICE'        => $arOffer['MIN_PRICE'],
            'DISPLAY_PROPERTIES' => $arSKUProps,
            'PREVIEW_PICTURE'    => $firstPhoto,
            'DETAIL_PICTURE'     => $firstPhoto,
            'CHECK_QUANTITY'     => $arOffer['CHECK_QUANTITY'],
            'MAX_QUANTITY'       => $arOffer['CATALOG_QUANTITY'],
            'STEP_QUANTITY'      => $arOffer['CATALOG_MEASURE_RATIO'],
            'QUANTITY_FLOAT'     => is_double($arOffer['CATALOG_MEASURE_RATIO']),
            'MEASURE'            => $arOffer['~CATALOG_MEASURE_NAME'],
            'OFFER_GROUP'        => (isset($offerSet[$arOffer['ID']]) && $offerSet[$arOffer['ID']]),
            'CAN_BUY'            => $arOffer['CAN_BUY'],
            'SLIDER'             => $arOffer['MORE_PHOTO'],
            'SLIDER_COUNT'       => $arOffer['MORE_PHOTO_COUNT'],
        );
        $arMatrix[$keyOffer] = $arOneRow;
    }
    if (-1 == $intSelected) {
        $intSelected = 0;
        $arResult['MIN_PRICE'] = (isset($arResult['OFFERS'][0]['RATIO_PRICE']) ? $arResult['OFFERS'][0]['RATIO_PRICE'] : $arResult['OFFERS'][0]['MIN_PRICE']);
        $arResult['MIN_BASIS_PRICE'] = $arResult['OFFERS'][0]['MIN_PRICE'];
    }
    $arResult['JS_OFFERS'] = $arMatrix;
    $arResult['OFFERS_SELECTED'] = $intSelected;
    if ($arMatrix[$intSelected]['SLIDER_COUNT'] > 0) {
        $arResult['MORE_PHOTO'] = $arMatrix[$intSelected]['SLIDER'];
        $arResult['MORE_PHOTO_COUNT'] = $arMatrix[$intSelected]['SLIDER_COUNT'];
    }

    $arResult['OFFERS_IBLOCK'] = $arSKU['IBLOCK_ID'];
}

if ($arResult['MODULES']['catalog'] && $arResult['CATALOG']) {
    if ($arResult['CATALOG_TYPE'] == CCatalogProduct::TYPE_PRODUCT || $arResult['CATALOG_TYPE'] == CCatalogProduct::TYPE_SET) {
        CIBlockPriceTools::setRatioMinPrice($arResult, false);
        $arResult['MIN_BASIS_PRICE'] = $arResult['MIN_PRICE'];
    }
    if (
        CBXFeatures::IsFeatureEnabled('CatCompleteSet')
        && (
            $arResult['CATALOG_TYPE'] == CCatalogProduct::TYPE_PRODUCT
            || $arResult['CATALOG_TYPE'] == CCatalogProduct::TYPE_SET
        )
    ) {
        $rsSets = CCatalogProductSet::getList(
            array(),
            array(
                '@OWNER_ID' => $arResult['ID'],
                '=SET_ID'   => 0,
                '=TYPE'     => CCatalogProductSet::TYPE_GROUP
            ),
            false,
            false,
            array('ID', 'OWNER_ID')
        );
        if ($arSet = $rsSets->Fetch()) {
            $arResult['OFFER_GROUP'] = true;
        }
    }
}

if (!empty($arResult['DISPLAY_PROPERTIES'])) {
    foreach ($arResult['DISPLAY_PROPERTIES'] as $propKey => $arDispProp) {
        if ('F' == $arDispProp['PROPERTY_TYPE']) {
            unset($arResult['DISPLAY_PROPERTIES'][$propKey]);
        }
    }
}

$arResult['SKU_PROPS'] = $arSKUPropList;
$arResult['DEFAULT_PICTURE'] = $arEmptyPreview;

$arResult['CURRENCIES'] = array();
if ($arResult['MODULES']['currency']) {
    if ($boolConvert) {
        $currencyFormat = CCurrencyLang::GetFormatDescription($arResult['CONVERT_CURRENCY']['CURRENCY_ID']);
        $arResult['CURRENCIES'] = array(
            array(
                'CURRENCY' => $arResult['CONVERT_CURRENCY']['CURRENCY_ID'],
                'FORMAT'   => array(
                    'FORMAT_STRING'     => $currencyFormat['FORMAT_STRING'],
                    'DEC_POINT'         => $currencyFormat['DEC_POINT'],
                    'THOUSANDS_SEP'     => $currencyFormat['THOUSANDS_SEP'],
                    'DECIMALS'          => $currencyFormat['DECIMALS'],
                    'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
                    'HIDE_ZERO'         => $currencyFormat['HIDE_ZERO']
                )
            )
        );
        unset($currencyFormat);
    } else {
        $currencyIterator = CurrencyTable::getList(
            array(
                'select' => array('CURRENCY')
            )
        );
        while ($currency = $currencyIterator->fetch()) {
            $currencyFormat = CCurrencyLang::GetFormatDescription($currency['CURRENCY']);
            $arResult['CURRENCIES'][] = array(
                'CURRENCY' => $currency['CURRENCY'],
                'FORMAT'   => array(
                    'FORMAT_STRING'     => $currencyFormat['FORMAT_STRING'],
                    'DEC_POINT'         => $currencyFormat['DEC_POINT'],
                    'THOUSANDS_SEP'     => $currencyFormat['THOUSANDS_SEP'],
                    'DECIMALS'          => $currencyFormat['DECIMALS'],
                    'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
                    'HIDE_ZERO'         => $currencyFormat['HIDE_ZERO']
                )
            );
        }
        unset($currencyFormat, $currency, $currencyIterator);
    }

    $arResult['PRODUCT_PROPS'] = array_keys($arResult['OFFERS_PROP']);


    $arColorsIDs = $arResult['SKU_PROPS']['COLOR']['XML_MAP'];
    $arColorsName = array();
    $arShoesSizesIDs = array();
    $arClothesSizesIDs = array();
    $arRingsSizesIDs = array();

    foreach ($arResult['SKU_PROPS']['SIZES_SHOES']['VALUES'] as $arValue) {
        $arShoesSizesIDs[$arValue['NAME']] = $arValue['ID'];
    }

    foreach ($arResult['SKU_PROPS']['SIZES_CLOTHES']['VALUES'] as $arValue) {
        $arClothesSizesIDs[$arValue['NAME']] = $arValue['ID'];
    }

    foreach ($arResult['SKU_PROPS']['SIZES_RINGS']['VALUES'] as $arValue) {
        $arRingsSizesIDs[$arValue['NAME']] = $arValue['ID'];
    }

    foreach ($arResult['SKU_PROPS']['COLOR']['VALUES'] as $arColor) {
        $arColorsName[$arColor['XML_ID']] = $arColor['NAME'];
    }

    $arResult['COLOR_NAMES'] = $arColorsName;

    $arSizesAvailable = array(); // массив для определения наличия цвета у размера

    $arOfferTree = array(); // собираем удобный массив
    $arColorPhotos = array();
    $arOffersIDs = [0];

    foreach ($arResult['OFFERS'] as $arOffer) {
        if ($arOffer['DENIED_RESERVATION'] == 'Y') continue;
        $arOffersIDs[] = $arOffer['ID'];
    }

    /* наличие в рознице */
    $arStore = [];
    if ($arParams['CITY_STORES_NAME']) {
        // проверяем только те склады, которые есть в текущем городе
        if ($arStoresID) {
            $rsStore = \CCatalogStoreProduct::GetList(
                [],
                [
                    'PRODUCT_ID' => $arOffersIDs,
                    'STORE_ID'   => $arStoresID,
                    '>AMOUNT'    => 0,
                ]
            );
            while ($arrStore = $rsStore->Fetch()) {
                $arStore[] = $arrStore['PRODUCT_ID'];
            }
            $arStore = array_unique($arStore);
        }
    }
    $arResult['IN_STORES'] = $arStore;
    /* ! наличие в рознице */

    //проверяем возможность покупки и бронирования на основании флагов из omni.channel
    $arResult['DENIED_DELIVERY'] = 'Y';
    $arResult['DENIED_RESERVATION'] = 'Y';
    foreach ($arResult['OFFERS'] as $offerId => $arOffer) {
        if (!$arOffer['CATALOG_QUANTITY']) $arResult['OFFERS'][$offerId]['DENIED_DELIVERY'] = 'Y';
        if (!in_array($arOffer['ID'], $arResult['IN_STORES'])) $arResult['OFFERS'][$offerId]['DENIED_RESERVATION'] = 'Y';

        if ($arResult['OFFERS'][$offerId]['DENIED_RESERVATION'] == 'N') $arResult['DENIED_RESERVATION'] = 'N';
        if ($arResult['OFFERS'][$offerId]['DENIED_DELIVERY'] == 'N') $arResult['DENIED_DELIVERY'] = 'N';
    }

    $firstOfferKey = -1; // ключ первого торгового предложения
    $firstTempKey = -1; // первый idшник

    foreach ($arResult['OFFERS'] as $offerId => $arOffer) {
        if ($firstTempKey == -1) $firstTempKey = $offerId;
        if ($arOffer['DENIED_DELIVERY'] == 'Y' && $arOffer['DENIED_RESERVATION'] == 'Y') {
            continue;
        } else {
            if ($firstOfferKey == -1) $firstOfferKey = $offerId;
        }
    }


    foreach ($arResult['OFFERS'] as $offerId => $arOffer) {
        if ($firstOfferKey != -1) {
            if ($firstTempKey == -1) $firstTempKey = $offerId;
            if ($arOffer['DENIED_DELIVERY'] == 'Y' && $arOffer['DENIED_RESERVATION'] == 'Y') {
                continue;
            } else {
                if ($firstOfferKey == -1) $firstOfferKey = $offerId;
            }
        }

        $propSize = $arClothesSizesIDs[$arOffer['PROPERTIES']['SIZES_CLOTHES']['VALUE']];
        $propSizeName = $arOffer['PROPERTIES']['SIZES_CLOTHES']['VALUE'];
        if ($arOffer['PROPERTIES']['SIZES_SHOES']['VALUE']) {
            $propSize = $arShoesSizesIDs[$arOffer['PROPERTIES']['SIZES_SHOES']['VALUE']];
            $propSizeName = $arResult['SIZES_TABLE'][$arOffer['PROPERTIES']['SIZES_SHOES']['VALUE']];
        }

        if ($arOffer['PROPERTIES']['SIZES_RINGS']['VALUE']) {
            $propSize = $arRingsSizesIDs[$arOffer['PROPERTIES']['SIZES_RINGS']['VALUE']];
            $propSizeName = $arOffer['PROPERTIES']['SIZES_RINGS']['VALUE'];
        }


        $arSizesAvailable[$propSize][] = $arColorsIDs[$arOffer['PROPERTIES']['COLOR']['VALUE']];

        $arOfferTree[$arColorsIDs[$arOffer['PROPERTIES']['COLOR']['VALUE']]][$propSize] = array(
            'ID'                 => $arOffer['ID'],
            'ARTNUMBER'          => $arOffer['NAME'],
            'PRICE'              => $arOffer['PRICES']['BASE']['PRINT_VALUE'],
            'DISCOUNT_PRICE'     => $arOffer['PRICES']['SALE']['PRINT_VALUE'],
            'QUANTITY'           => $arOffer['CATALOG_QUANTITY'],
            'COLOR'              => $arColorsName[$arOffer['PROPERTIES']['COLOR']['VALUE']],
            'COLOR_CODE'         => $arOffer['PROPERTIES']['COLOR']['VALUE'],
            'SIZE'               => $propSizeName,
            'DDL'                => [ // DigitalDataLayer (для события "Added Product"),
                'categoryId'    => (string)$arResult['SECTION']['ID'],
                'category'      => array_column($arResult['SECTION']['PATH'], 'NAME'),
                'article'       => $arOffer['NAME'],
                'unitPrice'     => (float)$arOffer['MIN_PRICE']['VALUE'],
                'unitSalePrice' => (float)$arResult['MIN_PRICE']['DISCOUNT_VALUE']
            ],
            'HIDE_RETAIL'        => $arOffer['HIDE_RETAIL'],
            'DENIED_DELIVERY'    => $arOffer['DENIED_DELIVERY'],
            'DENIED_RESERVATION' => $arOffer['DENIED_RESERVATION'],
        );

        if (empty($arColorPhotos[$arOffer['PROPERTIES']['COLOR']['VALUE']])) {
            $arColorPhotos[$arOffer['PROPERTIES']['COLOR']['VALUE']][$arOffer['PREVIEW_PICTURE']['ID']] = $arOffer['PREVIEW_PICTURE'];
            $arColorPhotos[$arOffer['PROPERTIES']['COLOR']['VALUE']][$arOffer['DETAIL_PICTURE']['ID']] = $arOffer['DETAIL_PICTURE'];

            if ($arOffer['DISPLAY_PROPERTIES']['MORE_PHOTO']['FILE_VALUE'][0]) {
                foreach ($arOffer['DISPLAY_PROPERTIES']['MORE_PHOTO']['FILE_VALUE'] as $arFile) {
                    if ($arFile['SRC']) {
                        $arColorPhotos[$arOffer['PROPERTIES']['COLOR']['VALUE']][$arFile['ID']] = $arFile;
                    }
                }
            } else {
                $arFile = $arOffer['DISPLAY_PROPERTIES']['MORE_PHOTO']['FILE_VALUE'];
                $arColorPhotos[$arOffer['PROPERTIES']['COLOR']['VALUE']][$arFile['ID']] = $arFile;
            }
        }
    }

    if ($firstOfferKey == -1) $firstOfferKey = $firstTempKey;

    $arResult['FIRST_OFFER_ID'] = $firstOfferKey;
    $arResult['COLOR_IMAGES'] = $arColorPhotos;
    $arResult['OFFERS_TREE'] = $arOfferTree;
    $arResult['COLORS_AVAILABLE'] = $arSizesAvailable;
}


$hlblock = Bitrix\Highloadblock\HighloadBlockTable::getById(2)->fetch();
$entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock); //генерация класса
$entityClass = $entity->getDataClass();
$rsData = $entityClass::GetList(
    array(
        'select' => array('*'),
        'filter' => array('UF_PRODUCT_ID' => $arResult['ID'])
    )
);
if ($arData = $rsData->Fetch()) {
    $arResult['REVIEWS_COUNT'] = $arData['UF_COUNT'];
    $arResult['REVIEWS_TOTAL'] = $arData['UF_TOTAL'];
    if ($arData['UF_COUNT'] > 0) {
        $arResult['REVIEWS_EVALUATION'] = round($arData['UF_TOTAL'] / $arData['UF_COUNT']);
    }
}

$sizesTableTab = 'clothes';
if (strpos($APPLICATION->GetCurDir(), '/shoes/')) {
    $sizesTableTab = 'shoes';
}
$arResult['SIZES_TABLE_TAB'] = $sizesTableTab;


/* Массив Образов */
$rsElements = \CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID'             => 15,
        'PROPERTY_MAIN_PRODUCT' => $arResult["ID"],
        'ACTIVE'                => 'Y'
    ],
    false,
    false,
    [
        'ID',
        'PROPERTY_PRODUCTS'
    ]
);
$arIDs = [];
while ($arElement = $rsElements->Fetch()) {
    $arIDs = array_merge($arIDs, $arElement['PROPERTY_PRODUCTS_VALUE']);
}
$arResult['LOOKS'] = $arIDs;

// комплекты
$arResult['COMPLECT'] = getComplects([$arResult["ID"]], $arParams['CITY_STORES_NAME']);

/** Adspire start */
$adspire = \Adspire\Manager::getInstance();

$parentCategory = [];
foreach ($arResult['SECTION']['PATH'] as $element) {
    $parentCategory[] = [
        'id'   => $element['ID'],
        'name' => $element['NAME']
    ];
}
$currentCategory = array_pop($parentCategory);

$variantId = [];
foreach ($arResult['OFFERS'] as $offer) {
    $variantId[] = $offer['ID'];
}

$firstOfferId = 0;
$product = [
    'pid'        => $arResult['ID'],
    'pname'      => $arResult['NAME'],
    'url'        => $adspire->formatURL($arResult['DETAIL_PAGE_URL']),
    'picture'    => $adspire->formatURL($arResult['OFFERS'][$firstOfferId]['PREVIEW_PICTURE']['SRC']),
    'price'      => (float)$arResult['OFFERS'][$firstOfferId]['PRICES']['SALE']['DISCOUNT_VALUE'] ?: (float)$arResult['MIN_PRICE']['DISCOUNT_VALUE'],
    'currency'   => 'RUB',
    'variant_id' => $variantId
];

$adspireProperties = [
    'TypeOfPage' => 'product',
    'Category'   => [
        'cid'   => $currentCategory['id'],
        'cname' => $currentCategory['name']
    ],
    'Product'    => $product
];
/** Adspire end */

/** DigitalDataLayer start */
$firstOfferId = $arResult['FIRST_OFFER_ID'];

$cp = $this->getComponent();
if (is_object($cp)) {

    $arVariations = [];
    foreach ($arResult['OFFERS'] as $arOffer) {
        $size = $arOffer['PROPERTIES']['SIZES_SHOES']['VALUE'];
        if (!$size) $size = $arOffer['PROPERTIES']['SIZES_CLOTHES']['VALUE'];
        if (!$size) $size = $arOffer['PROPERTIES']['SIZES_RINGS']['VALUE'];

        $arVariations[] = [
            'id'            => (string)$arResult['ID'],
            'skuCode'       => (string)$arOffer['ID'],
            'size'          => $size,
            'unitPrice'     => (float)$arOffer['PRICES']['BASE']['DISCOUNT_VALUE'],
            'unitSalePrice' => (float)$arOffer['PRICES']['SALE']['DISCOUNT_VALUE'] ?: (float)$arResult['MIN_PRICE']['DISCOUNT_VALUE'],
        ];
    }

    $colorCode = $arItem['OFFERS'][$firstOfferId]['PROPERTIES']['COLOR']['VALUE'];
    $cp->arResult['DDL_PRODUCT_PROPERTIES'] = [
        'id'                   => (string)$arResult['ID'],
        // ID товара
        'name'                 => $arResult['NAME'],
        'currency'             => 'RUB',
        'skuCode'              => (string)$arResult['OFFERS'][$firstOfferId]['ID'],
        'variations'           => $arVariations,
        'article'              => $arResult['PROPERTIES']['ARTNUMBER']['VALUE'] ?: $arResult['OFFERS'][$firstOfferId]['NAME'],
        // Артикул товара или торгового предложения (по умолчанию)
        'unitPrice'            => (float)$arResult['OFFERS'][$firstOfferId]['PRICES']['BASE']['DISCOUNT_VALUE'],
        // Стоимость единицы товара первого ТП без скидки
        'unitSalePrice'        => (float)$arResult['OFFERS'][$firstOfferId]['PRICES']['SALE']['DISCOUNT_VALUE'] ?: (float)$arResult['MIN_PRICE']['DISCOUNT_VALUE'],
        // Это цена, которую в действительности заплатит пользователь за этот товар. Для уцененных товаров, будет указана цена со скидкой.
        'category'             => array_column($arResult['SECTION']['PATH'], 'NAME'),
        // Иерархия категорий, к которым относится товар, исключая главную страницу. Массив из строк.
        'availableForPickup'   => ($arResult['OFFERS'][$firstOfferId]['HIDE_RETAIL'] == 'N' ? true : false),
        'availableForDelivery' => ($arResult['OFFERS'][$firstOfferId]['CATALOG_QUANTITY'] > 0 ? true : false),
        //'color'              => $colorCode, // цвет товара, в ТЗ пока нет
        //'size'               => '', //заполняется только после выбора размера
        //'rating'             => 0, // заполняется в детальной странице компонента catalog
        'categoryId'           => (string)$arResult['SECTION']['ID'],
        // ID конечной категории (родителя) товара
        'url'                  => $arResult['DETAIL_PAGE_URL'],
        // URL страницы с описанием товара
        'imageUrl'             => $arResult['OFFERS'][$firstOfferId]['PREVIEW_PICTURE']['SRC'],
        //URL большой картинки товара
        'thumbnailUrl'         => $arResult['OFFERS'][$firstOfferId]['PREVIEW_PICTURE']['SRC'],
        /**
         * Возможные значения stock
         * - общее кол-во товара $arResult['CATALOG_QUANTITY']
         * - общее кол-во товара первого торгового предложения $arResult['OFFERS'][$firstOfferId]['QUANTITY']
         * - кол-во товара в рознице $arResult['OFFERS'][$firstOfferId]['CATALOG_QUANTITY']
         */
        'stock'                => (int)$arResult['CATALOG_QUANTITY']
        // доступное количество
    ];
    /** DigitalDataLayer end */

    // Adspire
    $cp->arResult['ADSPIRE_PROPERTIES'] = $adspireProperties;

    $cp->SetResultCacheKeys(array('FIRST_OFFER', 'DDL_PRODUCT_PROPERTIES', 'SIZES_TABLE_TAB', 'ADSPIRE_PROPERTIES'));
}

/** Подготавливаем массив для window.dataLayer */
// Массив разделов
$arSectionsByCurrent = [];
$res = CIBlockSection::GetNavChain(false, $arResult['IBLOCK_SECTION_ID']);

while ($arSectionPath = $res->GetNext()) {
    $arSectionsByCurrent[] = $arSectionPath["NAME"];
}
$category = implode('/', $arSectionsByCurrent);

$arResult['JS_OBJ']['WDL_ELEMENT_ITEM'][$arResult['ID']] = [
    'currencyCode'  => $arResult['ORIGINAL_PARAMETERS']['CURRENCY_ID'],
    'list'          => $arSectionsByCurrent[count($arSectionsByCurrent) - 1],
    'name'          => $arResult['NAME'],
    'id'            => (string)$arResult['OFFERS'][$firstOfferId]['ID'],
    'price'         => number_format(round($arResult['PROPERTIES']['MINIMUM_PRICE']['VALUE']), 2, '.', ''),
    'brand'         => $arResult['PROPERTIES']['BRAND_NAME']['VALUE'],
    'category'      => $category,
    'variant'       => $arResult['PROPERTIES']['ARTNUMBER']['VALUE'],
];