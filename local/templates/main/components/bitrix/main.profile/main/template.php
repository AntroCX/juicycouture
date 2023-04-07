<?
/**
 * @global CMain $APPLICATION
 * @param array $arParams
 * @param array $arResult
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
?>

<div class="bx-auth-profile">

<script type="text/javascript">
<!--
var opened_sections = [<?
$arResult["opened"] = $_COOKIE[$arResult["COOKIE_PREFIX"]."_user_profile_open"];
$arResult["opened"] = preg_replace("/[^a-z0-9_,]/i", "", $arResult["opened"]);
if (strlen($arResult["opened"]) > 0)
{
	echo "'".implode("', '", explode(",", $arResult["opened"]))."'";
}
else
{
	$arResult["opened"] = "reg";
	echo "'reg'";
}
?>];
//-->

var cookie_prefix = '<?=$arResult["COOKIE_PREFIX"]?>';
</script>
<form method="post" name="form1" action="<?=$arResult["FORM_TARGET"]?>" enctype="multipart/form-data">
<?=$arResult["BX_SESSION_CHECK"]?>
<input type="hidden" name="lang" value="<?=LANG?>" />
<input type="hidden" name="ID" value=<?=$arResult["ID"]?> />

	<div class="b-form">
		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label>Ваше имя</label>
					<input type="text" name="NAME" maxlength="50" value="<?=$arResult["arUser"]["NAME"]?>" class="form-control" />
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label>Ваша фамилия</label>
					<input type="text" name="LAST_NAME" maxlength="50" value="<?=$arResult["arUser"]["LAST_NAME"]?>" class="form-control" />
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label>Телефон</label>
					<input type="text" name="PERSONAL_PHONE" maxlength="255" value="<?=$arResult["arUser"]["PERSONAL_PHONE"]?>" class="form-control" />
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label>Email</label>
					<input type="text" name="LOGIN" maxlength="50" value="<? echo $arResult["arUser"]["LOGIN"]?>" class="form-control" />
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label>Дата рождения</label>
					<input type="text" name="PERSONAL_BIRTHDAY" class="form-control" value="<?=$arResult["arUser"]["PERSONAL_BIRTHDAY"]?>" />
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label>Пол</label>
					<select name="PERSONAL_GENDER" class="form-control">
						<option value=""><?=GetMessage("USER_DONT_KNOW")?></option>
						<option value="M"<?=$arResult["arUser"]["PERSONAL_GENDER"] == "M" ? " SELECTED=\"SELECTED\"" : ""?>><?=GetMessage("USER_MALE")?></option>
						<option value="F"<?=$arResult["arUser"]["PERSONAL_GENDER"] == "F" ? " SELECTED=\"SELECTED\"" : ""?>><?=GetMessage("USER_FEMALE")?></option>
					</select>
				</div>
			</div>
		</div>
		<?if($arResult["arUser"]["EXTERNAL_AUTH_ID"] == ''):?>
		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label>Новый пароль</label>
					<input type="password" name="NEW_PASSWORD" maxlength="50" value="" autocomplete="off" class="form-control" />
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label>Подтверждение пароля</label>
					<input type="password" name="NEW_PASSWORD_CONFIRM" maxlength="50" value="" autocomplete="off" class="form-control" />
				</div>
			</div>
		</div>
		<?endif?>
		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<input type="submit" name="save" class="btn btn-primary" value="<?=(($arResult["ID"]>0) ? GetMessage("MAIN_SAVE") : GetMessage("MAIN_ADD"))?>">
				</div>
			</div>
			<div class="col-sm-6">
				<div style="padding: 15px">
				<?ShowError($arResult["strProfileError"]);?>
				<?
				if ($arResult['DATA_SAVED'] == 'Y')
					ShowNote(GetMessage('PROFILE_DATA_SAVED'));
				?>
				</div>
			</div>
		</div>
	</div>


</form>
<?
if ($arResult['SOCSERV_ENABLED']) {
    $APPLICATION->IncludeComponent(
        "bitrix:socserv.auth.split",
        ".default",
        array(
          "SHOW_PROFILES" => "Y",
          "ALLOW_DELETE" => "Y"
		    ),
		    false
	  );
}
?>
</div>
