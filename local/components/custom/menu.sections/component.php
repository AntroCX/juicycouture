<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */


if (!isset($arParams["CACHE_TIME"])) {
    $arParams["CACHE_TIME"] = 36000000;
}

$arParams["ID"] = intval($arParams["ID"]);
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);

$arParams["DEPTH_LEVEL"] = intval($arParams["DEPTH_LEVEL"]);
if ($arParams["DEPTH_LEVEL"] <= 0) {
    $arParams["DEPTH_LEVEL"] = 1;
}

$arResult["SECTIONS"] = array();
$arResult["ELEMENT_LINKS"] = array();

if ($this->StartResultCache()) {
    if (!CModule::IncludeModule("iblock")) {
        $this->AbortResultCache();
    } else {
        // получим список типов отображения
        $viewType = CUserTypeEntity::GetList([], ['ENTITY_ID' => 'IBLOCK_'.$arParams["IBLOCK_ID"].'_SECTION', 'FIELD_NAME' => 'UF_VIEW_TYPE'])->Fetch();
        $en = CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $viewType['ID']]);
        while ($arEnum = $en->Fetch()) {
            $viewType['ENUM'][$arEnum['ID']] = $arEnum;
        }

        $hideFilter = CUserTypeEntity::GetList([], ['ENTITY_ID' => 'IBLOCK_'.$arParams["IBLOCK_ID"].'_SECTION', 'FIELD_NAME' => 'UF_HIDE_FILTER'])->Fetch();
        $en = CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $hideFilter['ID']]);
        while ($arEnum = $en->Fetch()) {
            $hideFilter['ENUM'][$arEnum['ID']] = $arEnum;
        }

        // список брендов
        /*
        $arBrands = [];
        $property_enums = CIBlockPropertyEnum::GetList(Array("SORT"=>"ASC"), Array("IBLOCK_ID"=>$arParams["IBLOCK_ID"], "CODE"=>"BRAND"));
        while($enum_fields = $property_enums->GetNext())
        {
           $arBrands[] = array(
               "ID" => $enum_fields["ID"],
               "XML_ID" => $enum_fields["XML_ID"],
               "VALUE" => $enum_fields["VALUE"],
            );
        }*/
        $arMainType = [];
        $arHiddenSect = [];

        // список разделов и подразделов
        $rsSections = CIBlockSection::GetList(
            array(
                "left_margin" => "asc",
                "sort"        => "asc",
            ),
            array(
                "IBLOCK_ID"        => $arParams["IBLOCK_ID"],
                "GLOBAL_ACTIVE"    => "Y",
                "IBLOCK_ACTIVE"    => "Y",
                "<="."DEPTH_LEVEL" => $arParams["DEPTH_LEVEL"]
            ),
            false,
            array(
                "ID",
                "IBLOCK_ID",
                "IBLOCK_SECTION_ID",
                "DEPTH_LEVEL",
                "NAME",
                "SECTION_PAGE_URL",
                "PICTURE",
                "UF_HIDDEN_MENU",
                "UF_VIEW_TYPE",
                "UF_HIDE_FILTER",
                "UF_ACTUAL"
            )
        );
        if ($arParams["IS_SEF"] !== "Y") {
            $rsSections->SetUrlTemplates("", $arParams["SECTION_URL"]);
        } else {
            $rsSections->SetUrlTemplates("", $arParams["SEF_BASE_URL"].$arParams["SECTION_PAGE_URL"]);
        }

        $arFilter = $GLOBALS[$arParams['FILTER_NAME']];
        $arFilter['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
        $arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
        $arFilter['ACTIVE'] = 'Y';

        while ($arSection = $rsSections->GetNext()) {

            $arFilter['SECTION_ID'] = $arSection['ID'];

            $sectGet = [
                'NEW'  => $arParams['GET_NEW'],
                'SALE' => $arParams['GET_SALE'],
                'COLLECTION' => $arParams['GET_COLLECTION'],
                'BRAND' => $arParams['GET_BRAND']
            ];
            if ($arSection['UF_HIDE_FILTER']) {
                foreach ($arSection['UF_HIDE_FILTER'] as $hideId) {
                    $arHide = $hideFilter['ENUM'][$hideId];
                    $arHide['XML_ID'] = ToUpper($arHide['XML_ID']);
                    $sectGet[$arHide['XML_ID']] = false;
                }
            }
            if ($arParams['SHOW_HIDDEN'] == 'Y') $arSection['UF_HIDDEN_MENU'] = 0;
            if (in_array($arSection['IBLOCK_SECTION_ID'], $arHiddenSect)) $arSection['UF_HIDDEN_MENU'] = 1;
            if ($arSection['UF_HIDDEN_MENU']) {
                $arHiddenSect[] = $arSection['ID'];
                continue;
            }

            $mainFilter = $arFilter;

            $arSubQuery = [];

            // распродажные товары выводим только в /sale/
            $saleShow = \COption::GetOptionInt('jamilco.merch', 'sale.all', 0);
            if (strpos($APPLICATION->GetCurDir(), '/sale/') === false && !$saleShow) {
                $mainFilter['=ID'] = \CIBlockElement::SubQuery('PROPERTY_CML2_LINK', ['ACTIVE' => 'Y', 'CATALOG_PRICE_2' => false]);
            }

            if($arParams["IS_FILTER"] == "SALE"){
                // из SALE исключаем коллекции
                if(strpos($arSection["SECTION_PAGE_URL"], '/collection/')!== false){
                    continue;
                }
                $mainFilter['=ID'] = \CIBlockElement::SubQuery('PROPERTY_CML2_LINK', ['ACTIVE' => 'Y', '!CATALOG_PRICE_2' => false, '!PROPERTY_SALE_NO_SHOW' => true]);
            }

            $elCount = \CIblockElement::GetList([], $mainFilter, true);
            if (!$elCount) continue;

            if ($arParams['ALL_TYPES']) {
                $sectType = $arParams['ALL_TYPES'];
            } else {
                $sectType = $viewType['ENUM'][$arSection["UF_VIEW_TYPE"]]['XML_ID'];
                if ($arMainType[$arSection['IBLOCK_SECTION_ID']]) $sectType = $arMainType[$arSection['IBLOCK_SECTION_ID']];
                if ($sectType) $arMainType[$arSection['ID']] = $sectType;
            }

            if ($arParams['HIDE_PICTURES'] == 'Y') unset($arSection['PICTURE']);

            $arResult["SECTIONS"][] = [
                "ID"                => $arSection["ID"],
                "DEPTH_LEVEL"       => $arSection["DEPTH_LEVEL"],
                "~NAME"             => $arSection["~NAME"],
                "~SECTION_PAGE_URL" => $arSection["~SECTION_PAGE_URL"],
                "PICTURE"           => $arSection['PICTURE'],
                "UF_VIEW_TYPE"      => $sectType,
            ];
            $arResult["ELEMENT_LINKS"][$arSection["ID"]] = [];

            if ($arSection["DEPTH_LEVEL"] == 1 && empty($arSection['UF_ACTUAL'])) {
                if ($sectGet['NEW']) {
                    $newFilter = $arFilter;
                    $newFilter['!PROPERTY_NEW'] = false;

                    $elCount = \CIblockElement::GetList([], $newFilter, true);
                    if ($elCount) {
                        $arResult["SECTIONS"][] = [
                            "ID"                => $arSection["ID"],
                            "DEPTH_LEVEL"       => ($arSection["DEPTH_LEVEL"] + 1),
                            "~NAME"             => 'Новинки',
                            "~SECTION_PAGE_URL" => $arSection["~SECTION_PAGE_URL"].'new/',
                            "UF_VIEW_TYPE"      => 'actually',
                        ];
                    }
                }

                if ($sectGet['SALE']) {
                    $saleFilter = $arFilter;
                    $saleFilter['=ID'] = \CIBlockElement::SubQuery('PROPERTY_CML2_LINK', ['ACTIVE' => 'Y', '!CATALOG_PRICE_2' => false]);

                    $elCount = \CIblockElement::GetList([], $saleFilter, true);
                    if ($elCount) {
                        $arResult["SECTIONS"][] = [
                            "ID"                => $arSection["ID"],
                            "DEPTH_LEVEL"       => ($arSection["DEPTH_LEVEL"] + 1),
                            "~NAME"             => 'Sale',
                            "~SECTION_PAGE_URL" => $arSection["~SECTION_PAGE_URL"].'sale/',
                            "UF_VIEW_TYPE"      => 'actually',
                        ];
                    }
                }
            }

            /** добавляем в Бренд *//*
            if ($sectGet['BRAND']) {
                foreach ($arBrands as $arBrand) {
                    $brandFilter = $arFilter;
                    $brandFilter['PROPERTY_BRAND'] = $arBrand['ID'];
                    $elCount = \CIblockElement::GetList([], $brandFilter, true);
                    if ($elCount) {
                        $arResult["SECTIONS"][] = [
                            "ID"                => $arSection["ID"],
                            "DEPTH_LEVEL"       => ($arSection["DEPTH_LEVEL"] + 1),
                            "~NAME"             => $arBrand['VALUE'],
                            "~SECTION_PAGE_URL" => '/brand/'.$arBrand['XML_ID'].'/',
                            "UF_VIEW_TYPE"      => 'brand',
                        ];
                    }
                }
            }*/
            /** добавляем в Актуальное из коллекций для данного раздела */
            if ($arSection["CODE"] != 'collection' && empty($arSection['UF_ACTUAL'])) {
                $arIds = [];
                $res = \CIblockElement::GetList([], $arFilter, false, false, ["ID"]);
                while ($arItem = $res->Fetch()){
                    $arIds[] = $arItem["ID"];
                }
                if(!empty($arIds)) {
                    $arCollectionSections = [];
                    $res = CIBlockElement::GetElementGroups($arIds, true, ["ID", "CODE", "SECTION_PAGE_URL", "NAME"]);
                    while($arSection_ = $res->GetNext()){
                        if(strpos($arSection_["SECTION_PAGE_URL"], 'collection') !== false) {
                            $arCollectionSections[$arSection_["ID"]] = array(
                                "ID" => $arSection_["ID"],
                                "NAME" => $arSection_["NAME"],
                                "CODE" => $arSection_["CODE"],
                                "~SECTION_PAGE_URL" => $arSection_["~SECTION_PAGE_URL"]
                            );
                        }
                    }
                }
                foreach($arCollectionSections as $collectionSection){
                    /** выводим только с явно заданным типом актуальное */
                    $arSectionFields  = CIBlockSection::GetList( array(), array(
                        'IBLOCK_ID'         => $arParams["IBLOCK_ID"],
                        'ID'          => $collectionSection['ID'],
                    ), false, array( 'UF_VIEW_TYPE' ) )->Fetch();
                    if($arSectionFields['UF_VIEW_TYPE'] == 8)
                        $arResult["SECTIONS"][] = [
                            "ID"                => $arSection["ID"],
                            "DEPTH_LEVEL"       => ($arSection["DEPTH_LEVEL"] + 1),
                            "~NAME"             => $collectionSection["NAME"],
                            "~SECTION_PAGE_URL" => $collectionSection["~SECTION_PAGE_URL"],
                            "UF_VIEW_TYPE"      => 'actually',
                        ];
                }
            }

        }
        /** добавляем в Бренд в Sale и New *//*
        if ($arParams['IS_FILTER'] == "SALE" || $arParams['IS_FILTER'] == "NEW") {
            $barndFilter = $arFilter;
            if($arParams['IS_FILTER'] == "SALE") {
                $barndFilter['=ID'] = \CIBlockElement::SubQuery('PROPERTY_CML2_LINK', ['ACTIVE' => 'Y', '!CATALOG_PRICE_2' => false]);
            }
            elseif($arParams['IS_FILTER'] == "NEW"){
                $barndFilter['!PROPERTY_NEW'] = false;
            }
            foreach ($arBrands as $arBrand) {
                $barndFilter['PROPERTY_BRAND'] = $arBrand['ID'];
                $elCount = \CIblockElement::GetList([], $barndFilter, true);
                if ($elCount) {
                    $arResult["SECTIONS"][] = [
                        "ID"                => $arSection["ID"],
                        "DEPTH_LEVEL"       => ($arSection["DEPTH_LEVEL"] + 1),
                        "~NAME"             => $arBrand['VALUE'],
                        "~SECTION_PAGE_URL" => '/sale/brand/'.$arBrand['XML_ID'].'/',
                        "UF_VIEW_TYPE"      => 'brand',
                    ];
                }
            }
        }*/
        $this->EndResultCache();
    }
}

$aMenuLinksNew = array();
$menuIndex = 0;
foreach ($arResult["SECTIONS"] as $key => $arSection) {

    $aMenuLinksNew[$menuIndex++] = array(
        htmlspecialcharsbx($arSection["~NAME"]),
        $arSection["~SECTION_PAGE_URL"],
        urldecode($arSection["~SECTION_PAGE_URL"]),
        array(
            "FROM_IBLOCK"  => true,
            "IS_PARENT"    => false,
            "DEPTH_LEVEL"  => $arSection["DEPTH_LEVEL"] + $arParams['ADD_DEPTH_LEVEL'],
            "PICTURE"      => $arSection['PICTURE'],
            "UF_VIEW_TYPE" => $arSection["UF_VIEW_TYPE"],
        ),
    );
}
return $aMenuLinksNew;