<?php

$module_id = "jamilco.loyalty";
$RIGHT = $APPLICATION->GetGroupRight($module_id);

CModule::IncludeModule($module_id);

if ($RIGHT != "D") {

    IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
    IncludeModuleLangFile(__FILE__);

    $aTabs = array(
        array(
            "DIV"   => "edit1",
            "TAB"   => "Доступ",
            "ICON"  => "perfmon_settings",
            "TITLE" => "Доступ",
        ),
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);

    if ($REQUEST_METHOD == "POST" && $RIGHT == "W" && check_bitrix_sessid()) {

        ob_start();
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
        ob_end_clean();

        if (strlen($_REQUEST["back_url_settings"]) > 0) {
            LocalRedirect($_REQUEST["back_url_settings"]);
        } else {
            LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
        }
    }

    ?>
    <form method="post" action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
        <?
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
        $tabControl->Buttons();
        ?>
        <input <? if ($RIGHT < "W") echo "disabled" ?> type="submit" name="Update" value="<?= GetMessage("MAIN_SAVE") ?>" title="<?= GetMessage(
            "MAIN_OPT_SAVE_TITLE"
        ) ?>" class="adm-btn-save">
        <? if (strlen($_REQUEST["back_url_settings"]) > 0): ?>
            <input <? if ($RIGHT < "W") echo "disabled" ?> type="button" name="Cancel" value="<?= GetMessage("MAIN_OPT_CANCEL") ?>" title="<?= GetMessage(
                "MAIN_OPT_CANCEL_TITLE"
            ) ?>" onclick="window.location='<?= htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"])) ?>'">
            <input type="hidden" name="back_url_settings" value="<?= htmlspecialcharsbx($_REQUEST["back_url_settings"]) ?>">
        <? endif ?>
        <?= bitrix_sessid_post(); ?>
        <? $tabControl->End(); ?>
    </form>
<? } ?>