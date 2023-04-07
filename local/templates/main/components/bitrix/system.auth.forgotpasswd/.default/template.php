<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

Jamilco\Blocks\Block::load(array('b-auth'));
?>
<div class="b-auth">
	<h3>Восстановление пароля</h3>
<form name="bform" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>">
<?
if (strlen($arResult["BACKURL"]) > 0)
{
?>
	<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
<?
}
?>
	<input type="hidden" name="AUTH_FORM" value="Y">
	<input type="hidden" name="TYPE" value="SEND_PWD">
    <input type="hidden" class="g-recaptcha" name="TOKEN_CAPTCHA" data-action="restorePwd"/>

	<div class="form-group">
		<input type="text" name="USER_LOGIN" class="form-control" placeholder="email@domain.ru" value="<?=$arResult["LAST_LOGIN"]?>" />
	</div>
	<?ShowMessage($arParams["~AUTH_RESULT"]); ?>
	<div class="form-group">
		<input type="submit" class="btn btn-primary" name="send_account_info" value="Восстановить" />
		<a href="<?=$arResult["AUTH_AUTH_URL"]?>" rel="nofollow" class="b-auth__forgot pull-right">Авторизация</a>
	</div>
</form>
</div>
<script type="text/javascript">
document.bform.USER_LOGIN.focus();
</script>
