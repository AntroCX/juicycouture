<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;

global $USER, $APPLICATION;

Loader::includeModule('iblock');
Loader::includeModule('catalog');
Loader::includeModule('sale');


// ID типа плательщика
$personTypeId = 1;

$propPhoneId = 0;

$propertyIterator = \CSaleOrderProps::GetList(
    [],
    [
        'CODE' => 'PHONE',
        'PERSON_TYPE_ID' => $personTypeId,
    ]
);

if ($propertyPhone = $propertyIterator->Fetch()) {
    $propPhoneId = $propertyPhone['ID'];
}

$phonesOrderPropValues = [];

if ($propPhoneId > 0) {

    $connection = Application::getConnection();

    $phonesOrderPropValuesIterator = $connection->query(
        sprintf(
            "SELECT
                    *
                FROM
                    (
                        SELECT
                            v.ID,
                            v.ORDER_ID,
                            v.`NAME`,
                            v.`VALUE`,
                            o.USER_ID
                        FROM
                            b_sale_order_props_value v
                        INNER JOIN b_sale_order o ON v.ORDER_ID = o.ID
                        WHERE
                            v.ORDER_PROPS_ID = %s
                        ORDER BY
                            v.ID DESC
                    ) x
                GROUP BY
                    USER_ID",
            $connection->getSqlHelper()->forSql($propPhoneId)
        )
    );

    while ($phonesOrderPropValue = $phonesOrderPropValuesIterator->fetch()) {
        $phonesOrderPropValues[$phonesOrderPropValue['USER_ID']] = $phonesOrderPropValue['VALUE'];
    }

}

//pr($phonesOrderPropValues);

$arUsers = [];

global $USER;
$filter = Array();
$rsUsers = CUser::GetList(($by = "NAME"), ($order = "desc"), $filter);
while ($arUser = $rsUsers->Fetch()) {
    $arUsers[] = $arUser;
}

pr($arUsers);


$fp = fopen('file.csv', 'w');

fputcsv($fp, array('firstname', 'lastname',  'middlename',  'emailaddress1',  'telephone1',  'gendercode',  'birthdate',  'mobilephone',  'pl_registration_date',  'pl_shopsname',  'pl_codeword',  'pl_externalid',  'pl_address1_additionalfield',  'address1_country',  'address1_postalcode',  'adress1_district',  'address1_stateorprovince',  'adress1_city',  'adress1_street_typeid',  'address1_line1',  'address1_line2',  'address1_line3',  'adress1_additionalfield',  'adress1_flat',  'pl_source',  'familystatuscode',  'loyaltysystem',  'preferredcontactmethodcode',  'pl_sendsms',  'donotemail',  'donotphone',  'donotpostalmail',  'donotsendmm',  'description' ));

foreach ($arUsers as $arUser) {
    fputcsv($fp, array(
        $arUser['NAME'],
        $arUser['LAST_NAME'],
        '',
        $arUser['EMAIL'],
        '',
        '',
        '',
        $phonesOrderPropValues[$arUser['ID']],
        $arUser['DATE_REGISTER'],
        'juicycouture.ru',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        3,
        '',
        '',
        '',
        $arUser['ACTIVE'] ? 1 : 0,
        $arUser['ACTIVE'] ? 0 : 1,
        $arUser['ACTIVE'] ? 0 : 1,
        '',
        '',
        ''
    ));
}

fclose($fp);

?>