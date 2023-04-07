<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

\CUtil::InitJSCore(['popup']);

$arAuthServices = $arPost = [];
if (is_array($arParams['~AUTH_SERVICES'])) {
    $arAuthServices = $arParams['~AUTH_SERVICES'];
}
if (is_array($arParams['~POST'])) {
    $arPost = $arParams['~POST'];
}

$hiddenInputs = '';
foreach ($arPost as $key => $value) {
    if (!preg_match('|OPENID_IDENTITY|', $key)) {
        $hiddenInputs .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />'."\n";
    }
}

if (count($arAuthServices)):
?>
    <script type="text/javascript">
        function BxSocServPopup(id) {
            var content = BX("bx_socserv_form_" + id);
            if (content) {
                var popup = BX.PopupWindowManager.create("socServPopup" + id, BX("bx_socserv_icon_" + id), {
                    autoHide   : true,
                    closeByEsc : true,
                    angle      : {offset : 24},
                    content    : content,
                    offsetTop  : 3
                });

                popup.show();

                var input = BX.findChild(content, {
                    'tag'       : 'input',
                    'attribute' : {'type' : 'text'}
                }, true);
                if (input) {
                    input.focus();
                }

                var button = BX.findChild(content, {
                    'tag'       : 'input',
                    'attribute' : {'type' : 'submit'}
                }, true);
                if (button) {
                    button.className = 'btn btn-primary';
                }
            }
        }
    </script>

    <div class="bx-authform-social <? if (isset($arParams['ACCOUNT_MODE'])): ?>bx-authform-social-account<? endif ?>">
        <? if (!isset($arParams['ACCOUNT_MODE'])): ?>
            <div class="bx-authform-title"><?= Loc::getMessage('socserv_as_user') ?></div>
        <? endif ?>
        <? foreach ($arAuthServices as $service): ?>
            <? $onclick = ($service['ONCLICK'] <> '' ? $service['ONCLICK'] : "BxSocServPopup('".$service['ID']."')"); ?>
                <a id="bx_socserv_icon_<?= $service['ID'] ?>" class="bx-ss-icon <?= htmlspecialcharsbx($service['ICON']) ?>" href="javascript:void(0)" onclick="<?= \Bitrix\Main\Text\HtmlFilter::encode(
                    $onclick
                ) ?>"></a>
                <? if ($service['ONCLICK'] == '' && $service['FORM_HTML'] <> ''): ?>
                    <div id="bx_socserv_form_<?= $service['ID'] ?>" class="bx-authform-social-popup">
                        <form action="<?= $arParams['AUTH_URL'] ?>" method="post">
                            <?= $service['FORM_HTML'] ?>
                            <?= $hiddenInputs ?>
                            <input type="hidden" name="auth_service_id" value="<?= $service['ID'] ?>">
                        </form>
                    </div>
                <? endif ?>
        <? endforeach; ?>
    </div>
<?
endif;
