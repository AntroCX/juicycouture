<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

//if(check_bitrix_sessid()) {
    CModule::IncludeModule('iblock');
    CModule::IncludeModule("highloadblock");
    $review = new \CIBlockElement();
    $review->Add(array(
        'IBLOCK_ID' => 11,
        'ACTIVE' => 'N',
        'NAME' => $_REQUEST['title'],
        'PREVIEW_TEXT' => $_REQUEST['text'],
        'PROPERTY_VALUES' => array(
            'TOTAL' => $_REQUEST['totalRating'],
            'PRODUCT_RECOMMENDATION' => $_REQUEST['product_recommendation'],
            'NAME' => $_REQUEST['name'],
            'ADDRESS' => $_REQUEST['address'],
            'EMAIL' => $_REQUEST['email'],
            'YEARS' => $_REQUEST['years'],
            'QUALITY' => $_REQUEST['quality'],
            'PRODUCT_EVALUATION' => $_REQUEST['product_evaluation'],
            'JC_RECOMMENDATION' => $_REQUEST['jc_recommendation'],
            'PRODUCT_ID' => $_REQUEST['product_review_id']
        )
    ));

    $hlblock   = Bitrix\Highloadblock\HighloadBlockTable::getById(2)->fetch();
    $entity   = Bitrix\Highloadblock\HighloadBlockTable::compileEntity( $hlblock ); //генерация класса
    $entityClass = $entity->getDataClass();
    $rsData = $entityClass::GetList(array(
        'select' => array('*'),
        'filter' => array('UF_PRODUCT_ID' => $_REQUEST['product_review_id'])
    ));
    if($rsData->getSelectedRowsCount() > 0) {
        $arData = $rsData->Fetch();
        $entityClass::Update($arData['ID'], array(
            'UF_PRODUCT_ID' => $_REQUEST['product_review_id'],
            'UF_TOTAL' => $arData['UF_TOTAL'] + $_REQUEST['totalRating'],
            'UF_COUNT' => $arData['UF_COUNT'] + 1
        ));
    } else {
        $entityClass::Add(array(
            'UF_PRODUCT_ID' => $_REQUEST['product_review_id'],
            'UF_TOTAL' => $_REQUEST['totalRating'],
            'UF_COUNT' => 1,
            'UF_Y' => 0,
            'UF_N' => 0
        ));
    }
//}
