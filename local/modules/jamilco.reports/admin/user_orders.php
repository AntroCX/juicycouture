<?php
/**
 * Created by PhpStorm.
 * User: ermolenko
 * Date: 18.12.18
 * Time: 10:03
 */
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог


IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("jamilco.reports");
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$APPLICATION->SetTitle('Отчет по заказам клиента');




$tableID = "jco_userOrders";
$oSort = new \CAdminSorting($tableID, "ID", "desc");
$lAdmin = new \CAdminList($tableID, $oSort);

$pathFile = '/upload/user_orders.csv';

    // крепим кнопку для скачивания
    $lAdmin->AddAdminContextMenu(
        array(
            array(
                'TEXT' => 'Выгрузить данные в .csv файл',
                'TITLE' => 'Выгрузить данные в файл',
                'LINK_PARAM' => 'class="adm-btn adm-btn-save adm-btn-add"',
                'LINK' => '/bitrix/admin/jamilco_user_orders.php?download=Y',
            )
        ),
        false
    );
    
    // вывод шапки таблицы
    $lAdmin->AddHeaders(array(
        array(
            'id' => 'SITE',
            'content' => 'Сайт',
            'sort' => 'num',
            'align' => 'left',
            'default' => true
        ),
        array(
            'id' => 'ID',
            'content' => 'ID',
            'sort' => 'id',
            'align' => 'right',
            'default' => true
        ),
        array(
            'id' => 'NAME',
            'content' => 'Клиент',
            'sort' => 'name',
            'default' => true
        ),
        array(
            'id' => 'LAST_NAME',
            'content' => 'Фамилия',
            'sort' => 'last',
            'default' => true
        ),
        array(
            'id' => 'EMAIL',
            'content' => 'E-mail',
            'sort' => 'mail',
            'default' => true
        ),
        array(
            'id' => 'PHONE',
            'content' => 'Телефон',
            'sort' => 'number',
            'default' => true
        ),
        array(
            'id' => 'LOCATION',
            'content' => 'Город',
            'default' => true
        )
    ));

    

\CModule::IncludeModule('sale');


$arFilter = Array(
   "STATUS_ID" => array("F", "H", "S", "I"),
   );

$db_sales = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), $arFilter, array("ID"));

$rsOrders = new \CAdminResult($db_sales, $tableID);

if($_REQUEST['download'] == 'Y') {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/csv_data.php");
    $csvFile = new CCSVData();
    $fields_type = 'R';
    $delimiter = ";";
    $csvFile->SetFieldsType($fields_type);
    $csvFile->SetDelimiter($delimiter);

    file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/upload/user_orders.csv', "");
 

        while ($arRes = $rsOrders->Fetch()) {
            $arrRow = [
                    $_SERVER["SERVER_NAME"],
                    $arRes['ID']
            ];

             $dbOrderProps = CSaleOrderPropsValue::GetList(
                array("SORT" => "ASC"),
                array("ORDER_ID" => $arRes['ID'], "CODE"=>array("EMAIL", "LAST_NAME", "NAME", "PHONE", "TARIF_LOCATION"))
            );
            while ($arOrderProps = $dbOrderProps->GetNext()):
                if($arOrderProps["CODE"] == "TARIF_LOCATION") {
                    $arLocs = CSaleLocation::GetByID($arOrderProps["VALUE"], LANGUAGE_ID);
                    $arrRow[6] = iconv('UTF-8', 'windows-1251', $arLocs["CITY_NAME"]);
                }
                elseif($arOrderProps["CODE"] == "PHONE")
                        $arrRow[5] = iconv('UTF-8', 'windows-1251', $arOrderProps["VALUE"]);
                elseif($arOrderProps["CODE"] == "EMAIL")
                        $arrRow[4] = iconv('UTF-8', 'windows-1251', $arOrderProps["VALUE"]);
                elseif($arOrderProps["CODE"] == "LAST_NAME")
                        $arrRow[3] = iconv('UTF-8', 'windows-1251', $arOrderProps["VALUE"]);
                elseif($arOrderProps["CODE"] == "NAME")
                        $arrRow[2] = iconv('UTF-8', 'windows-1251', $arOrderProps["VALUE"]);
            endwhile;
            
            
            $csvFile->SaveFile($_SERVER["DOCUMENT_ROOT"] . '/upload/user_orders.csv', $arrRow);
        }
        
    $file = ($_SERVER["DOCUMENT_ROOT"] . '/upload/user_orders.csv');
    header ("Content-Type: application/octet-stream; charset=windows-1251");
    header ("Accept-Ranges: bytes");
    header ("Content-Length: ".filesize($file));
    header ("Content-Disposition: attachment; filename=".$file);
    readfile($file);
    die();
}



        $rsOrders->NavStart();
        $lAdmin->NavText($rsOrders->GetNavPrint('Orders'));
        
        // даем уникальный номер строке в соответсвии с номером страницы и количеством на листе
        $i_num = 1;
        $i_end = ($rsOrders->NavPageNomer > 1) ? ($rsOrders->NavPageNomer * $rsOrders->NavPageSize - $rsOrders->NavPageSize + 1) : 1;

  

        while ($arRes = $rsOrders->NavNext(true, "f_")) {
            $i_num = $i_end++;
            
                        $row =& $lAdmin->AddRow($i_num, $arRes); // формируем строки таблицы
            $row->AddViewField('SITE', $_SERVER["SERVER_NAME"]);
            $row->AddViewField('ID', $arRes['ID']);
            
            $dbOrderProps = CSaleOrderPropsValue::GetList(
                array("SORT" => "ASC"),
                array("ORDER_ID" => $arRes['ID'], "CODE"=>array("EMAIL", "LAST_NAME", "NAME", "PHONE", "TARIF_LOCATION"))
            );
            while ($arOrderProps = $dbOrderProps->GetNext()):
                if($arOrderProps["CODE"] == "TARIF_LOCATION") {
                    $arLocs = CSaleLocation::GetByID($arOrderProps["VALUE"], LANGUAGE_ID);
                    $row->AddViewField('LOCATION', $arLocs["CITY_NAME"]);
                }
                else {
                    $row->AddViewField($arOrderProps["CODE"], $arOrderProps["VALUE"]);
                }
            endwhile;
        }

    $lAdmin->AddFooter(
        array(
            array("title" => "Всего записей", "value" => $rsOrders->NavRecordCount), // кол-во элементов
            array("counter" => true, "title" => "Выбрано", "value" => "0"), // счетчик выбранных элементов
        )
    );
    $lAdmin->CheckListMode();
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог

    echo '<div class="adm-info-message">Всего записей: ' . $rsOrders->NavRecordCount.'<br>
    <br>
        *Выгружает весь список заказов в статусах: <br>
        <i style="font-size: 12px; line-height: 12px;">Выполнен / Доставлено в магазин / Передано в доставку / Выдан в PM</i><br>
        т.к. список очень большой, то на одной странице вывести можно максимум 500.
        <p>Выводим поля из заказа: сайт / Клиент / e-mail / Телефон / Город</p>
        <p>Выгрузка в файл занимает минут 5 ... надо ждать</p>
        
        </div>';
    
    $lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");

