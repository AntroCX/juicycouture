<?php
/**
 * Created by PhpStorm.
 * User: Eaa
 * Date: 06.10.17
 * Time: 10:58
 */
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("jamilco.reports");
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
$APPLICATION->SetTitle('Список всех E-mail');

$tableID = "jco_emails";
$oSort = new \CAdminSorting($tableID, "ID", "desc");
$lAdmin = new \CAdminList($tableID, $oSort);

$pathFile = '/upload/all_listEmail.csv';

// крепим кнопку для скачивания
    $lAdmin->AddAdminContextMenu(
        array(
              array(
                  'TEXT' => 'Скачать отписавшихся',
                  'TITLE' => 'Скачать отписавшихся',
                  'LINK_PARAM' => 'class="adm-btn adm-btn-save adm-btn-add"',
                  'LINK' => '/bitrix/admin/jamilco_all_emails.php?send=Y&download=noSub',
              ),
            array(
                'TEXT' => 'Скачать все в csv файл',
                'TITLE' => 'Скачать csv файл',
                'LINK_PARAM' => 'class="adm-btn adm-btn-save adm-btn-add"',
                'LINK' => '/bitrix/admin/jamilco_all_emails.php?send=Y&download=all',
            ),
            array(
                'TEXT' => 'Все E-mail без отписанных',
                'TITLE' => 'Все E-mail без отписанных',
                'LINK_PARAM' => 'class="adm-btn adm-btn-save adm-btn-add"',
                'LINK' => '/bitrix/admin/jamilco_all_emails.php?send=Y&download=allSub',
            )
        ),
        false
    );
    
    // вывод шапки таблицы
    $lAdmin->AddHeaders(array(
        array(
            'id' => 'ID',
            'content' => 'ID',
            'sort' => 'id',
            'align' => 'left',
            'default' => true
        ),
        array(
            'id' => 'NAME',
            'content' => 'E-mail',
            'sort' => 'name',
            'default' => true
        )
    ));
    
    global $DB;

    // запрашиваем все e-mail отписавшихся
        CModule::IncludeModule("subscribe");
    
        $arrNoSubscriber = array();
    
        //не активные адреса, подписанные на рубрики
        $subscr = CSubscription::GetList(
            array("ID"=>"ASC"),
            array("ACTIVE"=>"N")
        );
        while(($subscr_arr = $subscr->Fetch()))
            $arrNoSubscriber[$subscr_arr["EMAIL"]] = $subscr_arr["EMAIL"];

    // запрашиваем все e-mail из 5 присланных таблиц (включая отписавшихся)
        $allEmails = array();
        $sql = "SELECT b_subscription.EMAIL  as mail FROM b_subscription
        UNION
        SELECT b_catalog_subscribe.USER_CONTACT as mail FROM b_catalog_subscribe
        UNION
        SELECT DESCRIPTION as mail FROM b_sale_discount_coupon WHERE DESCRIPTION!=''
        UNION
        SELECT b_sender_contact.CODE as mail FROM b_sender_contact
        UNION
        SELECT b_sale_user_props_value.VALUE as mail FROM b_sale_user_props_value WHERE b_sale_user_props_value.NAME='Эл.почта'
        UNION
        SELECT b_user.EMAIL as mail FROM b_user";
        
        $dbRes = $DB->Query($sql);
        $rsMails = new \CAdminResult($dbRes, $tableID);
        $rsMails->NavStart();
        $lAdmin->NavText($rsMails->GetNavPrint('E-mail'));
        
        // даем уникальный номер строке в соответсвии с номером страницы и количеством на листе
        $i_num = 1;
        $i_end = ($rsMails->NavPageNomer > 1) ? ($rsMails->NavPageNomer * $rsMails->NavPageSize - $rsMails->NavPageSize + 1) : 1;

        while ($arRes = $rsMails->NavNext(true, "f_")) {
            $i_num = $i_end++;
            $row =& $lAdmin->AddRow($i_num, $arRes); // формируем строки таблицы
            $row->AddViewField('NAME', $arRes['mail']);
            $row->AddViewField('ID', $i_num);
        }
        
        $dbResAll = $DB->Query($sql);
        while ($mail = $dbResAll->fetch()) {
            $allEmails[] = $mail['mail'];
        }


    // Формируем файл выгрузки
        if ($_REQUEST['send'] == 'Y') {
            require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/csv_data.php");
            $csvFile = new CCSVData();
            $fields_type = 'R';
            $delimiter = ";";
            $csvFile->SetFieldsType($fields_type);
            $csvFile->SetDelimiter($delimiter);
        
            file_put_contents($_SERVER["DOCUMENT_ROOT"] . $pathFile, "");
             $num = 1;
             
        // список всех E-mail
            if ($_REQUEST['download'] == 'all') {
                foreach($allEmails as $arrMail) {
                    $csvFile->SaveFile($_SERVER["DOCUMENT_ROOT"] . $pathFile, array($num, $arrMail));
                    $num++;
                }
            }
            
        // список отписавшихся
            if ($_REQUEST['download'] == 'noSub') {
                foreach($arrNoSubscriber as $arrMail) {
                    $csvFile->SaveFile($_SERVER["DOCUMENT_ROOT"] . $pathFile, array($num, $arrMail));
                    $num++;
                }
            }
            
        // Все E-mail минус отписанных
            if ($_REQUEST['download'] == 'allSub') {
                foreach($allEmails as $arrMail) {
                    if (!in_array($arrMail, $arrNoSubscriber)) {
                        $csvFile->SaveFile($_SERVER["DOCUMENT_ROOT"] . $pathFile, array($num, $arrMail));
                        $num++;
                    }
                }
            }
            
            $file = ($_SERVER["DOCUMENT_ROOT"] . $pathFile);
            header("Content-Type: application/octet-stream; charset=windows-1251");
            header("Accept-Ranges: bytes");
            header("Content-Length: " . filesize($file));
            header("Content-Disposition: attachment; filename=" . $file);
            readfile($file);
            die();
        }


    $lAdmin->AddFooter(
        array(
            array("title" => "Всего e-mail", "value" => $rsMails->NavRecordCount), // кол-во элементов
            array("counter" => true, "title" => "Выбраны e-mail", "value" => "0"), // счетчик выбранных элементов
        )
    );
    $lAdmin->CheckListMode();
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог

    echo '<div class="adm-info-message">Всего уникальных E-mail: ' . $rsMails->NavRecordCount.'
    <br><i style="font-size: 11px; line-height: 12px;">
        *выгружает весь список<br>&nbsp; т.к. список очень большой, то на одной странице вывести можно максимум 500.</i>
    </div>';
    
    $lAdmin->DisplayList();
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");