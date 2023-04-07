<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader;

Loader::IncludeModule('iblock');

global $APPLICATION, $arItemFilter;

// каталог товаров (ключ в массиве)
$catalog_insert_key = 0;

$arNewMenuLinks = [];
foreach ($aMenuLinks as $key => $menuLink) {
    $arNewMenuLinks[] = $menuLink;
	$arItemFilter = false;
    if ($menuLink[3]['ADD_CATALOG_FILTER'] == 'NEW') {
        $arItemFilter = [
            '!PROPERTY_NEW' => false,
            [
                'LOGIC'                     => 'OR',
                '>PROPERTY_RETAIL_QUANTITY' => 0,
                '>CATALOG_QUANTITY'         => 0,
            ],
        ];
    } elseif ($menuLink[3]['ADD_CATALOG_FILTER'] == 'SALE') {
        $arItemFilter = [];
        $canSaleItemsBeReserved = \COption::GetOptionInt('jamilco.omni', 'sale.retail', 0);
        if ($canSaleItemsBeReserved) {
            // Sale-товары можно бронировать
            $arItemFilter[] = [
                'LOGIC'                     => 'OR',
                '>PROPERTY_RETAIL_QUANTITY' => 0,
                '>CATALOG_QUANTITY'         => 0,
            ];
        } else {
            $arItemFilter[] = [
                '>CATALOG_QUANTITY' => 0,
            ];
        }
        $arItemFilter['=ID'] = \CIBlockElement::SubQuery('PROPERTY_CML2_LINK', ['ACTIVE' => 'Y', '!CATALOG_PRICE_2' => 0]);
    }

    if ($arItemFilter) {
        $aMenuLinksExt = $APPLICATION->IncludeComponent(
            "custom:menu.sections",
            "",
            Array(
                "IS_FILTER"        => $menuLink[3]['ADD_CATALOG_FILTER'],
                "IBLOCK_TYPE"      => "catalog",
                "IBLOCK_ID"        => "1",
                "FILTER_NAME"      => "arItemFilter",
                "DEPTH_LEVEL"      => "3",
                "CACHE_TYPE"       => "A",
                "CACHE_TIME"       => "36000",
                "IS_SEF"           => "Y",
                "SEF_BASE_URL"     => $menuLink[1],
                "SECTION_PAGE_URL" => '#SECTION_CODE_PATH#/',
                "ADD_DEPTH_LEVEL"  => 1,
                "ALL_TYPES"        => "OWN",
                "HIDE_PICTURES"    => "Y",
                "GET_COLLECTION"   => "Y",
                "GET_BRAND"        => "Y"
                //"SHOW_HIDDEN"      => "Y",
            )
        );
        $arNewMenuLinks = array_merge($arNewMenuLinks, $aMenuLinksExt);
    }
	
	if(strpos($menuLink[1], '/new/') !== false && !$catalog_insert_key){
        // после пункта "Новинки" добавим каталог товаров 
        $catalog_insert_key = $key+1;
    }/*
	if(strpos($menuLink[1], '/brand/') !== false){
		// после пункта "Бренды" добавим каталог товаров
        $catalog_insert_key = $key+2;

        // список брендов
        $arBrands = [];
        $property_enums = CIBlockPropertyEnum::GetList(Array("SORT"=>"ASC"), Array("IBLOCK_ID"=>$arParams["IBLOCK_ID"], "CODE"=>"BRAND"));
        while($enum_fields = $property_enums->GetNext())
        {
           $arBrands[] = array(
               "ID" => $enum_fields["ID"],
               "XML_ID" => $enum_fields["XML_ID"],
               "VALUE" => $enum_fields["VALUE"],
            );
        }
		foreach($arBrands as $arBrand){
			$arBrandMenuItems[] =
				Array(
					0 => $arBrand["VALUE"],
					1 => '/brand/'.$arBrand["XML_ID"].'/',
					2 => '/brand/'.$arBrand["XML_ID"].'/',
					3 => Array
						(
							"FROM_IBLOCK" => 1,
							"IS_PARENT" => 0,
							"DEPTH_LEVEL" => 2,
							"UF_VIEW_TYPE" => "brand"
						)
				
				);
		}

		$arNewMenuLinks = array_merge($arNewMenuLinks, $arBrandMenuItems);
	
    }*/
}

// каталаог товаров
$arItemFilter = [
    [
        'LOGIC'                     => 'OR',
        '>PROPERTY_RETAIL_QUANTITY' => 0,
        '>CATALOG_QUANTITY'         => 0,
    ]
];
$aMenuLinksExt = $APPLICATION->IncludeComponent(
    "custom:menu.sections",
    "",
    Array(
        "IBLOCK_TYPE" => "catalog",
        "IBLOCK_ID"   => "1",
        "FILTER_NAME" => "arItemFilter",
        "DEPTH_LEVEL" => "3",
        "CACHE_TYPE"  => "A",
        "CACHE_TIME"  => "36000",
        "GET_SALE"    => "Y",
        "GET_NEW"     => "Y",
        "GET_COLLECTION"     => "Y",
        "GET_BRAND"     => "Y",
    )
);
$i = 0;
foreach ($aMenuLinksExt as $item) {
    array_splice($arNewMenuLinks, ($catalog_insert_key+$i), 0, [$item]);
    $i++;
}

$aMenuLinks = $arNewMenuLinks;