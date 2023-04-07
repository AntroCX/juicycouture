<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->addExternalJS($this->__folder."/authcard.js");
$this->addExternalCss("/local/components/jamilco/personal.bonus/templates/.default/style.css");
?>

<div id="authCard" class="modal" tabindex="-1" role="dialog">
<div class="modal-dialog" role="document">
<div class="modal-content form-card">
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
    <h2 class="modal-title">Войти с помощью бонусной карты</h2>
</div>
<div class="modal-body">
<div class="auth-blocks first-block">
    <form id="authCardForm" method="post">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="action" value="card">
        <input type="hidden" class="g-recaptcha" name="TOKEN_CAPTCHA" data-action="authCard"/>
        <div class="form-fields">
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardNumber" class="form-card-label">Номер карты <sup>*</sup></label>
                        <input type="text" id="cardNumber" name="cardNumber" value="" class="form-control input--digit" placeholder="Номер карты">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="pinCode" class="form-card-label">Пин-код <sup>*</sup></label>
                        <input type="text" id="pinCode" name="pinCode" value="" class="form-control input--digit" placeholder="Пин-код">
                    </div>
                </div>
            </div>
            <div class="modal-btns">
                <a href="#" class="btn btn-primary send-btn" id="authCardSubmit">Авторизоваться</a>
            </div>
        </div>
    </form>
    <div class="clear"></div>
</div>

<div class="auth-blocks second-block hidden">
    <p>В контактных данных Вашей карты не указан E-mail.<br />Введите его для создания аккаунта на сайте:</p>

    <form id="authCardEmail" method="post">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="action" value="email">

        <div class="form-fields">
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardEmail" class="form-card-label">E-mail <sup>*</sup></label>
                        <input type="email" id="cardEmail" name="cardEmail" class="form-control" autocomplete="off" placeholder="E-mail">
                        <div class="label-error"></div>
                    </div>
                </div>
            </div>
            <div id="cardEmailPassBlock" class="hidden">
                <p>
                    Этот E-mail указан в одном из аккаунтов на сайте.<br /> Введите пароль от этого аккаунта, чтобы авторизоваться и привязать карту к нему:
                </p>

                <div class="row">
                    <div class="col-xs-12 col-sm-12">
                        <div class="form-group">
                            <label for="cardEmailPass" class="form-card-label">Пароль <sup>*</sup></label>
                            <input type="password" id="cardEmailPass" name="cardEmailPass" class="form-control" autocomplete="off" placeholder="Пароль">
                            <div class="label-error"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-btns">
                <a href="#" class="btn btn-primary send-btn" id="authCardEmailSend">Создать аккаунт</a>
            </div>
        </div>
    </form>
    <div class="clear"></div>
</div>

<div class="auth-blocks third-block hidden">
    <p>Ваша карта не активирована.<br />Заполните форму, чтобы активировать её:</p>

    <form id="authCardContact" method="post" name="cardContact">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="action" value="contact">

        <div class="form-fields">
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardContactLastName" class="form-card-label">Фамилия <sup>*</sup></label>
                        <input type="text" id="cardContactLastName" name="cardContactLastName" required class="form-control input-names" value="" placeholder="Фамилия*">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardContactName" class="form-card-label">Имя <sup>*</sup></label>
                        <input type="text" id="cardContactName" name="cardContactName" required class="form-control input-names" value="" placeholder="Имя*">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardContactSecondName" class="form-card-label">Отчество</label>
                        <input type="text" id="cardContactSecondName" name="cardContactSecondName" required class="form-control input-names" value="" placeholder="Отчество">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardContactSex" class="form-card-label">Ваш пол <sup>*</sup></label>
                        <select id="cardContactSex" name="cardContactSex" required class="form-control">
                            <option value="Ж">Женский</option>
                            <option value="М">Мужской</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group form-field-date">
                        <label for="cardContactBirthday" class="form-card-label">День рождения <sup>*</sup></label>
                        <?$APPLICATION->IncludeComponent(
                            'bitrix:main.calendar',
                            '',
                            [
                                'SHOW_INPUT'  => 'N',
                                'FORM_NAME'   => 'cardContact',
                                'INPUT_NAME'  => 'cardContactBirthday',
                                'INPUT_VALUE' => '',
                                'SHOW_TIME'   => 'N'
                            ],
                            null,
                            ['HIDE_ICONS' => 'Y']
                        );?>
                        <input type="text" id="cardContactBirthday" name="cardContactBirthday" required class="form-control mask-date" value="" placeholder="День рождения*">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardContactPhone" class="form-card-label">Номер телефона <sup>*</sup></label>
                        <input type="tel" id="cardContactPhone" name="cardContactPhone" required class="form-control mask-phone" value="" placeholder="Номер телефона*">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardContactEmail" class="form-card-label">E-mail <sup>*</sup></label>
                        <input type="email" id="cardContactEmail" name="cardContactEmail" required class="form-control" value="" placeholder="E-mail*">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group form-oferta">
                        <label for="cardContactAgree" class="small-label">
                            <input type="checkbox" id="cardContactAgree" name="cardContactAgree" required checked>
                            я согласен с условиями
                            <a href="/support/dogovor-oferty/" target="_blank" class="link-gold link-decorat">публичной оферты</a> и обработкой моих персональных данных в порядке, предусмотренном публичной офертой.
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-btns">
                <button type="submit" class="btn btn-primary">Получить карту</button>
            </div>
        </div>
    </form>
    <div class="clear"></div>
</div>

<div class="auth-blocks fours-block hidden">
    <p>Мы выслали Вам два кода подтверждения:<br />
        - на телефон <span class="check-phone"></span> в виде СМС-сообщения<br />
        - на email <span class="check-email"></span> письмом.<br />
        Их нужно ввести в соответствующих текстовых полях.<br />
        <br />
        Если данные указаны неверно, то <a href="#" id="reSetProps" class="link-gold">отредактируйте их</a>.
    </p>

    <form id="authCardConfirm" method="post">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="action" value="confirm">
        <div class="form-fields">

            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardConfirmPhone" class="form-card-label">Код подтверждения телефона</label>
                        <input type="text" id="cardConfirmPhone" name="cardConfirmPhone" class="form-control" maxlength="5" required placeholder="Код подтверждения телефона">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="form-group">
                        <label for="cardConfirmEmail" class="form-card-label">Код подтверждения email'а</label>
                        <input type="text" id="cardConfirmEmail" name="cardConfirmEmail" class="form-control" maxlength="5" required placeholder="Код подтверждения email'а">
                    </div>
                </div>
            </div>

            <div class="modal-btns">
                <button type="submit" class="btn btn-primary">Подтвердить</button>
            </div>
        </div>
    </form>
</div>
</div>
</div>
</div>
</div>

<div class="bx-auth b-auth">
	<div class="row">
		<div class="col-sm-6">

			<h3>Вход</h3>

	<form name="form_auth" class="b-auth__enter" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>">

		<input type="hidden" name="AUTH_FORM" value="Y" />
		<input type="hidden" name="TYPE" value="AUTH" />
		<?if (strlen($arResult["BACKURL"]) > 0):?>
		<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
		<?endif?>
		<?foreach ($arResult["POST"] as $key => $value):?>
		<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
		<?endforeach?>

		<div class="form-group">
			<input type="email" name="USER_LOGIN" class="form-control" id="enterEmail" placeholder="email@domain.ru" value="<?=$arResult["LAST_LOGIN"]?>">
		</div>

		<div class="form-group">
			<input type="password" class="form-control" name="USER_PASSWORD" id="enterPassword" autocomplete="off" placeholder="Пароль">
		</div>

		<div class="form-group">
			<input type="submit" class="btn btn-primary-black" name="Login" value="Войти" />
            <input type="hidden" class="g-recaptcha" name="TOKEN_CAPTCHA" data-action="auth"/>
			<a href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>" rel="nofollow" class="b-auth__forgot pull-right">Забыли пароль?</a>
		</div>

		<?
		ShowMessage($arParams["~AUTH_RESULT"]);
		ShowMessage($arResult['ERROR_MESSAGE']);
		?>

        <div class="form-group">
            <a href="#" class="btn btn-primary-black" id="authCardLink">Войти с помощью бонусной карты</a>
        </div>
	</form>

  <? if($arResult["AUTH_SERVICES"]): ?>
  <?
  $APPLICATION->IncludeComponent("bitrix:socserv.auth.form", "",
	array(
		"AUTH_SERVICES" => $arResult["AUTH_SERVICES"],
		"CURRENT_SERVICE" => $arResult["CURRENT_SERVICE"],
		"AUTH_URL" => $arResult["AUTH_URL"],
		"POST" => $arResult["POST"],
		"SHOW_TITLES" => $arResult["FOR_INTRANET"]?'N':'Y',
		"FOR_SPLIT" => $arResult["FOR_INTRANET"]?'Y':'N',
		"AUTH_LINE" => $arResult["FOR_INTRANET"]?'N':'Y',
	),
	$component,
	array("HIDE_ICONS"=>"Y")
  );
  ?>
<? endif ?>

  </div>

<script type="text/javascript">
<?if (strlen($arResult["LAST_LOGIN"])>0):?>
try{document.form_auth.USER_PASSWORD.focus();}catch(e){}
<?else:?>
try{document.form_auth.USER_LOGIN.focus();}catch(e){}
<?endif?>
</script>
