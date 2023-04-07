<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог

use \Bitrix\Main\Web\Json;
use \Jamilco\Merch\Common;

IncludeModuleLangFile(__FILE__);

global $APPLICATION;

require_once('functions.php');

$POST_RIGHT = $APPLICATION->GetGroupRight("jamilco.merch");
if ($POST_RIGHT == "D") {
    echo 'Доступ запрещен';
    return;
}

// свойства модуля
$arModuleProperties = Common::getProps();

// полученные данные
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if ($request->isAjaxRequest() && $request->isPost()) {
    $APPLICATION->RestartBuffer();

    $action = $request->get('action');
    $data = $request->get('data');

    if ($action == 'saveProperty') {
        foreach ($data as $key => $val) {
            $arProp = $arModuleProperties[$key];
            if ($arProp['TYPE'] == 'int') {
                \COption::SetOptionInt("jamilco.merch", $key, $val);
            } else {
                \COption::SetOptionString("jamilco.merch", $key, $val);
            }
        }
        Common::EventSaveOptions();
    } elseif ($action == 'saveData') {
        Common::saveSeasons($data['seasons']);
    }

    die();
}


CJSCore::Init(array('jquery'));

$APPLICATION->SetTitle('Панель управления Merch');

$APPLICATION->AddHeadScript('/local/modules/jamilco.merch/admin/merch.js');
$APPLICATION->SetAdditionalCSS('/local/modules/jamilco.merch/admin/merch.css');

$APPLICATION->AddHeadScript('/local/modules/jamilco.merch/admin/js/jquery-ui.min.js');
$APPLICATION->SetAdditionalCSS('/local/modules/jamilco.merch/admin/js/jquery-ui.min.css');
$APPLICATION->SetAdditionalCSS('/local/modules/jamilco.merch/admin/js/jquery-ui.structure.min.css');
$APPLICATION->SetAdditionalCSS('/local/modules/jamilco.merch/admin/js/jquery-ui.theme.min.css');

$aTabs = array(
    array("DIV" => "catalog", "TAB" => "Настройки Merch", "TITLE" => "Настройки товаров"),
    array("DIV" => "settings", "TAB" => "Настройки модуля", "TITLE" => "Настройки модуля"),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$message = null; // сообщение об ошибке
?>
    <script type="text/javascript">
        window.nextDate = '<?=Common::getNextDate()?>';
    </script>
    <form method="POST" Action="<? echo $APPLICATION->GetCurPage() ?>" ENCTYPE="multipart/form-data" name="post_form" class="merch">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="update" value="Y">

        <?
        $tabControl->Begin();
        $tabControl->BeginNextTab();

        $arSeasons = Common::getSeasons();
        ?>

        <tr class="catalog-head">
            <th></th>
            <th></th>
        </tr>

        <?
        $arModulePropertiesSel = [];
        foreach ($arModuleProperties as $prodCode => $arProp){
            if($prodCode == 'prop.default.sort'){
                $arModulePropertiesSel[$prodCode] = $arProp;
                unset($arModuleProperties[$prodCode]);
            }
        }
        ?>

        <? printProps($arModulePropertiesSel, 'MAIN'); ?>

        <tr>
            <td style="width:20%;">
                <div class="adm-submenu-item-name">
                    <span class="adm-submenu-item-name-link-text" style="white-space: nowrap; padding-right: 10px;">Сортировка сезонов</span>
                </div>
            </td>
            <td>
                <ul class="season-sort" id="sortable">
                    <? if ($arModuleProperties['prop.season_group']['VALUE'] == 'TWO') { ?>
                        <? $n = 0; ?>
                        <li class="ui-state-default two-block">
                            <? foreach ($arSeasons as $seasonCode => $arSeason) { ?>
                                <? $n++; ?>
                                <div class="season-one" data-code="<?= $seasonCode ?>"><?= $arSeason['VALUE'] ?></div>
                                <?= ($n % 2 === 0) ? '</li>' : '' ?>
                                <?= ($n % 2 === 0 && count($arSeasons) > $n) ? '<li class="ui-state-default two-block">' : '' ?>
                            <? } ?>
                        </li>
                    <? } else { ?>
                        <? foreach ($arSeasons as $seasonCode => $arSeason) { ?>
                            <li class="season-one ui-state-default" data-code="<?= $seasonCode ?>"><?= $arSeason['VALUE'] ?></li>
                        <? } ?>
                    <? } ?>
                </ul>
            </td>
        </tr>

        <? printProps($arModuleProperties, 'MAIN'); ?>

        <? $tabControl->BeginNextTab(); ?>
        <? printProps($arModuleProperties, 'SETTINGS'); ?>

        <?
        $tabControl->Buttons(array("back_url" => "/bitrix/admin/jamilco_merch.php?lang=".LANG,));
        $tabControl->End();
        $tabControl->ShowWarnings("post_form", $message);
        ?>
    </form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");