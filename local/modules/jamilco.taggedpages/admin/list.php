<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 18.07.16
 * Time: 10:58
 */

use \Bitrix\Main\Loader;
use \Jamilco\TaggedPages\SectionFilter;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("jamilco.taggedpages");
/*if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}*/

Loader::includeModule('iblock');
Loader::includeModule('catalog');
Loader::includeModule('jamilco.taggedpages');

$APPLICATION->AddHeadScript('/local/modules/jamilco.taggedpages/admin/list.js');
$APPLICATION->SetAdditionalCSS('/local/modules/jamilco.taggedpages/admin/list.css');

CJSCore::Init(array('jquery'));

function siteURL()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol.$domainName;
}

$arParams = array();

if($_REQUEST['action']) {
   if($_REQUEST['action'] == 'ADD') {

       $arUpdateElement = array();

       if($_REQUEST['action_add'] == 'EDIT' && $_REQUEST['id']) {
           $APPLICATION->SetTitle('Редактирование тегированной страницы');
           $rsPage = Jamilco\TaggedPages\PagesTable::getList(array(
               'filter' => array('ID' => $_REQUEST['id'])
           ));

           $arUpdateElement = $rsPage->Fetch();

           parse_str($arUpdateElement['RULE_PARAMS'], $arParams);


       } else {
           $APPLICATION->SetTitle('Добавление тегированной страницы');
       }

       Bitrix\Main\Loader::includeModule('catalog');
       $rsCatalog = Bitrix\Catalog\CatalogIblockTable::getList(array(
           'select' => array('IBLOCK_ID')
       ));

       $arCatalogIDs = array();

       while($arrCatalog = $rsCatalog->Fetch()) {
           $arCatalogIDs = $arrCatalog['IBLOCK_ID'];
       }



       require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог

       if($_REQUEST['action_add'] == 'ADD_PAGE') {


           $arParamsForURL = array();
           if($arRules = $_REQUEST['RULE_ADD_FIELD']) {
               foreach ($arRules as $key => $rule) {
                   $arParamsForURL[$rule] = $_REQUEST['RULE_ADD_VALUE'][$key];
               }
           }

           $addParams = http_build_query($arParamsForURL);

           $rsResult = Jamilco\TaggedPages\PagesTable::Add(array(
               'TITLE' => $_REQUEST['TITLE'],
               'URL' => $_REQUEST['URL'],
               'RULE_URL' => $_REQUEST['RULE_URL'],
               'RULE_PARAMS' => $addParams,
               'NUM_PAGES' => $_REQUEST['NUM_PAGES'],
               'ACTIVE' => ($_REQUEST['ACTIVE']) ? true : false,
               'SHOW_FILTER' => ($_REQUEST['SHOW_FILTER']) ? true : false,
               'TOP_HTML' => $_REQUEST['TOP_HTML'],
               'BOTTOM_HTML' => $_REQUEST['BOTTOM_HTML'],
               'SEO_DESCRIPTION' => $_REQUEST['SEO_DESCRIPTION'],
               'SEO_KEYWORDS' => $_REQUEST['SEO_KEYWORDS'],
               'SEO_TITLE' => $_REQUEST['SEO_TITLE'],
           ));

           $link = siteURL().$_REQUEST['URL'];

           if($rsResult->isSuccess()) {
               LocalRedirect('/bitrix/admin/jamilco_taggedpages_list.php?status=ADDED&link='.$link);
           }

       }

       if($_REQUEST['action_add'] == 'UPDATE_PAGE' && $_REQUEST['UPDATE_PAGE_ID']) {


           print_r($_REQUEST);

           $arParamsForURL = array();
           if($arRules = $_REQUEST['RULE_ADD_FIELD']) {
               foreach ($arRules as $key => $rule) {
                   $arParamsForURL[$rule] = $_REQUEST['RULE_ADD_VALUE'][$key];
               }
           }

           $addParams = http_build_query($arParamsForURL);

           $rsResult = Jamilco\TaggedPages\PagesTable::update($_REQUEST['UPDATE_PAGE_ID'], array(
               'TITLE' => $_REQUEST['TITLE'],
               'URL' => $_REQUEST['URL'],
               'RULE_URL' => $_REQUEST['RULE_URL'],
               'RULE_PARAMS' => $addParams,
               'NUM_PAGES' => $_REQUEST['NUM_PAGES'],
               'ACTIVE' => ($_REQUEST['ACTIVE']) ? true : false,
               'SHOW_FILTER' => ($_REQUEST['SHOW_FILTER']) ? true : false,
               'TOP_HTML' => $_REQUEST['TOP_HTML'],
               'BOTTOM_HTML' => $_REQUEST['BOTTOM_HTML'],
               'SEO_DESCRIPTION' => $_REQUEST['SEO_DESCRIPTION'],
               'SEO_KEYWORDS' => $_REQUEST['SEO_KEYWORDS'],
               'SEO_TITLE' => $_REQUEST['SEO_TITLE'],
               'SECTIONS' => $_REQUEST['SECTIONS'],
           ));

           $link = siteURL().$_REQUEST['URL'];

           if($rsResult->isSuccess()) {
               LocalRedirect('/bitrix/admin/jamilco_taggedpages_list.php?status=UPDATED&link='.$link);
           }

       }


       ?>
       <?
       $msgText = 'добавляемой';
       if($_REQUEST['action_add'] == 'EDIT') {
           $msgText = 'редактируемой';
       }

       $aTabs = array(
           array("DIV" => "add", "TAB" => 'Параметры страницы', "ICON"=>"", "TITLE"=> 'Параметры '.$msgText.' тегированной страницы')
       );
       $tabControl = new CAdminTabControl("tabControl", $aTabs);
       ?>


       <form method="POST" Action="/bitrix/admin/jamilco_taggedpages_list.php" ENCTYPE="multipart/form-data" name="post_form">
        <?
            $tabControl->Begin();
            $tabControl->BeginNextTab();
        ?>
           <input type="hidden" name="action" value="ADD">
           <?if($_REQUEST['action_add'] == 'EDIT' && $_REQUEST['id']):?>
               <input type="hidden" name="action_add" value="UPDATE_PAGE">
               <input type="hidden" name="UPDATE_PAGE_ID" value="<?=$_REQUEST['id']?>">
           <?else:?>
               <input type="hidden" name="action_add" value="ADD_PAGE">
           <?endif?>
       <tr class="heading">
           <td colspan="2">Основные параметры</td>
       </tr>
       <tr>
           <td width="40%">Активность</td>
           <td width="60%"><input type="checkbox" <?if($arUpdateElement['ACTIVE']):?>checked<?endif?> name="ACTIVE" value="Y"></td>
       </tr>
       <tr>
           <td width="40%"><span class="required">*</span>Заголовок страницы</td>
           <td width="60%"><input type="text" name="TITLE" value="<?=$arUpdateElement['TITLE']?>"></td>
       </tr>
       <tr>
           <td width="40%"><span class="required">*</span>URL страницы</td>
           <td width="60%"><input type="text" name="URL" value="<?=$arUpdateElement['URL']?>"></td>
       </tr>
       <tr class="heading">
           <td colspan="2">Условия/фильтр/правила</td>
       </tr>
       <tr>
           <td width="40%">Страница каталога с GET-параметрами фильтра</td>
           <td width="60%"><input type="text" name="RULE_URL" value="<?=$arUpdateElement['RULE_URL']?>"></td>
       </tr>
           <!--
       <tr>
           <td width="40%">Доп. условия (название свойства = значение)</td>
           <td width="60%">
               <?if(!empty($arParams)):?>
                   <?foreach ($arParams as $name => $value):?>
                       <div>
                           <input type="text" placeholder="Поле/Свойство" name="RULE_ADD_FIELD[]" value="<?=$name?>"><input type="text" placeholder="Значение" name="RULE_ADD_VALUE[]" value="<?=$value?>">
                       </div>
                   <?endforeach;?>
               <?endif?>
               <div>
                   <input type="text" placeholder="Поле/Свойство" name="RULE_ADD_FIELD[]"><input type="text" placeholder="Значение" name="RULE_ADD_VALUE[]">
               </div>
               <div>
                   <input type="text" placeholder="Поле/Свойство" name="RULE_ADD_FIELD[]"><input type="text" placeholder="Значение" name="RULE_ADD_VALUE[]">
               </div>
               <div>
                   <input type="text" placeholder="Поле/Свойство" name="RULE_ADD_FIELD[]"><input type="text" placeholder="Значение" name="RULE_ADD_VALUE[]">
               </div>
               <div>
                   <input type="text" placeholder="Поле/Свойство" name="RULE_ADD_FIELD[]"><input type="text" placeholder="Значение" name="RULE_ADD_VALUE[]">
               </div>
               <div>
                   <input type="text" placeholder="Поле/Свойство" name="RULE_ADD_FIELD[]"><input type="text" placeholder="Значение" name="RULE_ADD_VALUE[]">
               </div>
           </td>
       </tr>
           -->
       <tr>
           <td width="40%" valign="top">Фильтр по разделам</td>
           <td width="60%">
               <?= SectionFilter::showSectionFilter($arUpdateElement['SECTIONS']) ?>
           </td>
       </tr>
       <tr class="heading">
           <td colspan="2">Дополнительные настройки страницы</td>
       </tr>
       <tr>
           <td width="40%">Отображать фильтр</td>
           <td width="60%"><input type="checkbox" <?if($arUpdateElement['SHOW_FILTER']):?>checked<?endif;?> name="SHOW_FILTER" value="Y"></td>
       </tr>
       <tr>
           <td width="40%">Количество товаров на странице</td>
           <td width="60%"><input type="text" name="NUM_PAGES" value="<?=$arUpdateElement['NUM_PAGES']?>"></td>
       </tr>
       <tr>
           <td width="40%">SEO Title</td>
           <td width="60%"><textarea cols="100" rows="10" name="SEO_TITLE"><?=$arUpdateElement['SEO_TITLE']?></textarea></td>
       </tr>
       <tr>
           <td width="40%">SEO Description</td>
           <td width="60%"><textarea cols="100" rows="10" name="SEO_DESCRIPTION"><?=$arUpdateElement['SEO_DESCRIPTION']?></textarea></td>
       </tr>
       <tr>
           <td width="40%">SEO Keywords</td>
           <td width="60%"><textarea cols="100" rows="10" name="SEO_KEYWORDS"><?=$arUpdateElement['SEO_KEYWORDS']?></textarea></td>
       </tr>
       <tr>
           <td width="40%">HTML над списком товаров</td>
           <td width="60%"><textarea cols="100" rows="10" name="TOP_HTML"><?=$arUpdateElement['TOP_HTML']?></textarea></td>
       </tr>
       <tr>
           <td width="40%">HTML под списком товаров</td>
           <td width="60%"><textarea cols="100" rows="10" name="BOTTOM_HTML"><?=$arUpdateElement['BOTTOM_HTML']?></textarea></td>
       </tr>
       <?$tabControl->Buttons(
           array(
               "btnApply" => false,
               "submit"=>"Добавить"
           )
       );?>
        <?$tabControl->End();?>
       </form>
       <?
   }
} else {

    $APPLICATION->SetTitle('Список тегированных страниц');
    $tableID = "jco_taggedpages";
    $oSort = new \CAdminSorting($tableID, "ID", "desc");
    $lAdmin = new \CAdminList($tableID, $oSort);

    function CheckFilter()
    {
        global $FilterArr, $lAdmin;
        foreach ($FilterArr as $f) global $$f;

        return count($lAdmin->arFilterErrors) == 0; // если ошибки есть, вернем false;
    }

// опишем элементы фильтра
    $FilterArr = Array(
        "find_title",
        "find_url",
        "find_active",
        "find_id"
    );

// инициализируем фильтр
    $lAdmin->InitFilter($FilterArr);

    if (CheckFilter()) {
        $arCatalogFilter = [];
        if (!empty($find_title)) {
            $arCatalogFilter["%TITLE"] = $find_title;
        }
        if (!empty($find_url)) {
            $arCatalogFilter["%URL"] = $find_url;
        }
        if (!empty($find_active)) {
            $arCatalogFilter["ACTIVE"] = $find_active == 'Y' ? true : false;
        }
        if (!empty($find_id)) {
            $arCatalogFilter["ID"] = (int)$find_id;
        }
    }

    $lAdmin->AddHeaders(array(
        array(
            'id' => 'ID',
            'content' => 'ID',
            'sort' => 'id',
            'default' => true
        ),
        array(
            'id' => 'TITLE',
            'content' => 'Заголовок страницы',
            'sort' => 'title',
            'default' => true
        ),
        array(
            'id' => 'URL',
            'content' => 'URL',
            'sort' => 'url',
            'default' => true
        ),
        array(
            'id' => 'ACTIVE',
            'content' => 'Активность',
            'sort' => 'active',
            'default' => true
        )
    ));

    if($_REQUEST['action_add'] == 'DELETE') {
        $rsResult = Jamilco\TaggedPages\PagesTable::Delete($_REQUEST['id']);
        if($rsResult->isSuccess()) {
            \CAdminMessage::ShowMessage(array(
                "MESSAGE" => "Страница успешно удалена",
                "TYPE" => "OK"
            ));
        } else {
            \CAdminMessage::ShowMessage(array(
                "MESSAGE" => "Ошибка при удалении страницы",
                "TYPE" => "ERROR"
            ));
        }
    }

    if($_REQUEST['status'] == 'ADDED') {
        \CAdminMessage::ShowMessage(array(
            "MESSAGE" => "Страница успешно создана, она доступна по ссылке ".$_REQUEST['link'],
            "TYPE" => "OK"
        ));
    }
    if($_REQUEST['status'] == 'UPDATED') {
        \CAdminMessage::ShowMessage(array(
            "MESSAGE" => "Страница успешно обновлена, она доступна по ссылке ".$_REQUEST['link'],
            "TYPE" => "OK"
        ));
    }

    $rsPages = Jamilco\TaggedPages\PagesTable::GetList(array(
        'select' => array(
            'TITLE',
            'URL',
            'ACTIVE',
            'ID'
        ),
        'filter' => $arCatalogFilter
    ));

    $rsPages = new \CAdminResult($rsPages, $tableID);
    $rsPages->NavStart();

    $lAdmin->NavText($rsPages->GetNavPrint('Страницы'));

    while ($arRes = $rsPages->NavNext(true, "f_")) {
        $row =& $lAdmin->AddRow($f_ID, $arRes);
        $row->AddViewField('TITLE', $arRes['TITLE']);
        $row->AddViewField('URL', $arRes['URL']);
        $row->AddViewField('ACTIVE', ($arRes['ACTIVE']) ? 'Y' : 'N');

        $arActions = Array();

        $arActions[] = array(
            "ICON"=>"edit",
            "DEFAULT"=>true,
            "TEXT"=> "Изменить",
            "ACTION"=>$lAdmin->ActionRedirect("?action=ADD&action_add=EDIT&id=".$arRes['ID'])
        );

        $arActions[] = array(
            "ICON"=>"delete",
            "TEXT"=> "Удалить",
            "ACTION"=>$lAdmin->ActionRedirect("?action_add=DELETE&id=".$arRes['ID'])
        );

        $row->AddActions($arActions);

    }
    $lAdmin->AddFooter(
        array(
            array("title" => "Всего страниц", "value" => $rsPages->SelectedRowsCount()), // кол-во элементов
            array("counter" => true, "title" => "Выбраны страницы", "value" => "0"), // счетчик выбранных элементов
        )
    );

    $lAdmin->AddAdminContextMenu(
        array(
            array(
                'TEXT' => 'Добавить страницу',
                'TITLE' => 'Добавить страницу',
                'LINK' => '/bitrix/admin/jamilco_taggedpages_list.php?action=ADD',
            )
        ),
        false
    );
    $lAdmin->CheckListMode();
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог

    $oFilter = new CAdminFilter(
        $sTableID . "_filter",
        array()
    );
    ?>
    <form name="jamilco_taggedpages_filter" method="get" action="<? echo $APPLICATION->GetCurPage(); ?>">
        <? $oFilter->Begin(); ?>
        <tr>
            <td><b>Заголовок страницы</b></td>
            <td>
                <input type="text" size="25" name="find_title" value="<? echo htmlspecialchars($find_title) ?>"
                       title="<?= GetMessage("rub_f_find_title") ?>">
            </td>
        </tr>
        <tr>
            <td>URL</td>
            <td><input type="text" name="find_url" size="47" value="<? echo htmlspecialchars($find_url) ?>"></td>
        </tr>
        <?
        $oFilter->Buttons(array("table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "jamilco_taggedpages_filter"));
        $oFilter->End();
        ?>
    </form>
    <?
    $lAdmin->DisplayList();
}


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");

