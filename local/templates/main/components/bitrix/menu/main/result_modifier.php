<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

require_once('functions.php');

// получим список типов отображения
$viewType = CUserTypeEntity::GetList([], ['ENTITY_ID' => 'IBLOCK_'.IBLOCK_CATALOG_ID.'_SECTION', 'FIELD_NAME' => 'UF_VIEW_TYPE'])->Fetch();
$en = CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $viewType]);
while ($arEnum = $en->Fetch()) {
    $arEnum['XML_ID'] = ToUpper($arEnum['XML_ID']);
    $viewType['ENUM'][$arEnum['ID']] = $arEnum;
}

// пересоберем меню
$arLast = [];
$maxLevel = $arParams['MAX_LEVEL'];
foreach ($arResult as $key => $arItem) {
    $dl = $arItem['DEPTH_LEVEL'];
    if ($dl > $maxLevel) continue;

    $arItem['IS_PARENT'] = false;

    if ($arLast[$dl]) {
        collectOne($arLast, $maxLevel, $dl);
    }
    $arLast[$dl] = $arItem;
    if ($arItem['PARAMS']['PICTURE']) {
        $arLast[1]['PICTURES'][] = [
            'SRC'  => CFile::GetPath($arItem['PARAMS']['PICTURE']),
            'LINK' => $arItem['LINK'],
            'TEXT' => $arItem['TEXT'],
        ];
    }
}

collectOne($arLast, $maxLevel, 1);
$arResult = [
    'MENU'      => $arLast[0],
    'VIEW_TYPE' => $viewType['ENUM'],
];

// для правильной подсветки активных под-разделов Brand и Collection
if(strpos($APPLICATION->GetCurPage(false), '/collection/') !== false)
{
    $selectedSection = 'collection';
}
elseif(strpos($APPLICATION->GetCurPage(false), '/brand/') !== false)
{
    $selectedSection = 'brand';
}
if($selectedSection)
{
	foreach($arResult['MENU'] as $key => $arMenuItem){

		if(strpos($arMenuItem['LINK'], '/'.$selectedSection.'/') !== false)
		{
			$arResult['MENU'][$key]['SELECTED'] = 1;
		}
		else
		{
			$arResult['MENU'][$key]['SELECTED'] = 0;
		}

	}
}