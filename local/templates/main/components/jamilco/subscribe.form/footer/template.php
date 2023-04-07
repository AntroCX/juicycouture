<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

CJSCore::Init(array("popup"));

$buttonId = $this->randString();
?>
<div class="bx-subscribe" id="sender-subscribe">
    <div id="sender-subscribe-response-cont" style="display: none;">
        <div class="bx_subscribe_response_container">
            <table>
                <tr>
                    <td class="bx_subscribe_response_container__header"><img src="" alt="">
                    </td>
                    <td>
                        <div class="bx_subscribe_response_container__title"></div>
                        <div class="bx_subscribe_response_container__msg"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <form id="footerSubscribe" role="form" class="b-subscription__form" method="post" action="">
        <?=bitrix_sessid_post()?>
        <input type="hidden" name="path" value="<?=$this->GetFolder();?>">
        <input type="hidden" name="ACTION" value="subscribe">
        <input type="hidden" class="g-recaptcha" name="TOKEN_CAPTCHA" data-action="subscribe"/>
        <div class="b-subscription__form_btn_container">
            <input class="b-subscription__form-email" type="email" name="EMAIL" value="<?=$arResult["EMAIL"]?>" title="<?=GetMessage("subscr_form_email_title")?>" placeholder="<?=htmlspecialcharsbx(GetMessage('subscr_form_email_title'))?>">
            <button class="b-subscription__form-submit" id="bx_subscribe_btn_<?=$buttonId?>" type="submit"
                    onclick="
                        try {rrApi.setEmail($('.b-subscription__form-email').val(),{'stockId': '<?=\Jamilco\Main\Retail::getStoreName(true)?>'});}catch(e){}
                        "
            >
            </button>
        </div>
        <div class="b-subscription__form_agree_container">
            <div class="b-subscription__form_agree">
                <input type="checkbox" name="SUBSCRIBE_AGREE"> я соглашаюсь с условиями <a href="/reference/contract-offer/" target="_blank">публичной оферты и обработкой моих персональных данных в порядке, предусмотренном публичной офертой</a>
            </div>
        </div>
    </form>
</div>