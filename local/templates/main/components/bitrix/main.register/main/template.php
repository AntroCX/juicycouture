<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponentTemplate $this
 */

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
?>

<?if($USER->IsAuthorized()):?>

<p><?echo GetMessage("MAIN_REGISTER_AUTH")?></p>

<?else:?>
<?
if (count($arResult["ERRORS"]) > 0):
	foreach ($arResult["ERRORS"] as $key => $error)
		if (intval($key) == 0 && $key !== 0) 
			$arResult["ERRORS"][$key] = str_replace("#FIELD_NAME#", "&quot;".GetMessage("REGISTER_FIELD_".$key)."&quot;", $error);

	ShowError(implode("<br />", $arResult["ERRORS"]));

elseif($arResult["USE_EMAIL_CONFIRMATION"] === "Y"):
?>
<p><?echo GetMessage("REGISTER_EMAIL_WILL_BE_SENT")?></p>
<?endif?>

<form method="post" action="<?=POST_FORM_ACTION_URI?>" class="b-auth__reg" name="regform" enctype="multipart/form-data">
<?
if($arResult["BACKURL"] <> ''):
?>
	<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
<?
endif;
?>

	<div class="form-group">
		<input type="text" class="form-control" name="REGISTER[NAME]" size="30" value="<?=$arResult["VALUES"]['NAME']?>" placeholder="Имя">
	</div>
	<div class="form-group">
		<input type="text" class="form-control" name="REGISTER[LAST_NAME]" size="30" value="<?=$arResult["VALUES"]['LAST_NAME']?>" placeholder="Фамилия">
	</div>
	<div class="form-group">
		<input id="emailFieldID" type="email" class="form-control" size="30" value="<?=$arResult["VALUES"]['LOGIN']?>" name="REGISTER[LOGIN]" placeholder="E-mail">
	</div>
	<div class="form-group">
		<input type="password" id="password" name="REGISTER[PASSWORD]" size="30" value="<?=$arResult["VALUES"][$FIELD]?>" autocomplete="off" class="form-control required" placeholder="Пароль">
	</div>
	<div class="form-group">
		<input type="password" name="REGISTER[CONFIRM_PASSWORD]" value="<?=$arResult["VALUES"][$FIELD]?>" size="30" class="form-control required" placeholder="Пароль еще раз">
	</div>
    <div class="form-group">
        <div class="checkbox">
            <label>
                <input name="register_subscribe" type="checkbox" value="Y">Подписаться на новости и рассылки
            </label>
        </div>
    </div>
	<div class="form-group">
		<div class="checkbox">
			<label>
				<input name="i-agree" type="checkbox"> Я согласен с условиями <a href="/reference/contract-offer/" target="_blank">публичной оферты и обработкой моих персональных данных в порядке, предусмотренном публичной офертой</a>
			</label>
		</div>
	</div>
	<div class="form-group">
		<input type="submit" name="register_submit_button" checked class="btn btn-primary-black" value="Зарегистрироваться"
           onclick="
               try {rrApi.setEmail($('#emailFieldID').val(),{'stockId': '<?=\Jamilco\Main\Retail::getStoreName(true)?>'});}catch(e){}
           "
        >
        <input type="hidden" class="g-recaptcha" name="TOKEN_CAPTCHA" data-action="reg"/>
	</div>

</form>
<?endif?>
