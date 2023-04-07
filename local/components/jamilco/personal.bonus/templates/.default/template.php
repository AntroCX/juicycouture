<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/* @var array $arResult */
/* @var array $arParams */

use \Bitrix\Main\Config\Option;

$phone = Option::get('main', 'phone', '88007707597');
$phonePrint = Option::get('main', 'phone_print', '8 800 770 75 97');

$helpText = 'обратитесь в службу поддержки по телефону <a href="tel:'.$phone.'">'.$phonePrint.'</a>';
$secureText = 'Если у Вас возникли проблемы с вводом номера карты, '.$helpText;
?>
<div class="bonus-personal">
<div class="account-address">
    <? if (!$arResult['number']) { ?>
        <p>Вы можете получить виртуальную карту, заполнив заявку онлайн.</p>
        <p>Если у Вас уже есть карта, то Вы можете её привязать к своему аккаунту, что позволит Вам удобно пользоваться ей при оформлении заказа и просматривать историю всех операций по карте.</p>
        <div class="bonus-btns">
            <a href="#" class="btn btn-primary popup-link" id="newCardLink" data-target="newCard">Получить виртуальную карту</a>
            <a href="#" class="btn btn-primary popup-link" id="addCardLink" data-target="addCard">Привязать карту к аккаунту</a>
        </div>
        <br /><br />
    <? } else { ?>
        <h3 class="title-main title-main--supersmall">Баланс карты №<?= $arResult['number'] ?></h3>
        <div class="bonus-balance">
            <ul class="favorites-menu__list">
                <li><span>Активные бонусы</span><span><?= (int)$arResult['balance']['AVAILABLE'] ?></span></li>
                <li><span>Неподтвержденные бонусы</span><span><?= (int)$arResult['balance']['UNCONFIRMED'] ?></span></li>
                <li><span>Использованные бонусы</span><span><?= (int)$arResult['balance']['USED'] ?></span></li>
                <li>
                    <a href="#" class="btn btn-primary popup-link" data-target="historyCheckModal">История операций</a>
                    <a href="#" class="btn btn-primary popup-link" data-target="editCard">Контактные данные</a>
                </li>
                <li><a href="#" class="link-gold" id="deleteCard">Удалить привязанную карту</a></li>
            </ul>
        </div>
    <? } ?>
    <?$APPLICATION->IncludeComponent(
        "bitrix:main.include",
        "",
        array(
            "AREA_FILE_SHOW" => "file",
            "PATH"           => "/local/include/personal_bonuses.php"
        )
    );?>
</div>
<? if ($arResult['number']) { ?>
    <?/*>
    <div id="historyModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content form-card">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    <h2 class="modal-title">История операций по карте</h2>
                </div>
                <div class="modal-body">
                    <div class="bonus-table">
                        <table>
                            <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Операция</th>
                                <th>Сумма</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?= (!$arResult['history']) ? '<tr><td colspan="3">Операций по карте не найдено</td></tr>' : '' ?>
                            <? foreach ($arResult['history'] as $arOne) { ?>
                                <tr>
                                    <td><?= $arOne['DATE']['FORMAT'] ?></td>
                                    <td><?= $arOne['NAME'] ?></td>
                                    <td><?= $arOne['PRICE'] ?></td>
                                </tr>
                            <? } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
    <?*/?>

    <div id="historyCheckModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    <h2 class="modal-title">История операций по карте</h2>
                </div>
                <div class="modal-body">
                    <div class="bonus-table">
                        <table>
                            <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Номер</th>
                                <th>Сумма</th>
                                <th>Оплачено баллами</th>
                                <th>Начислено баллов</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?= (!$arResult['check']) ? '<tr><td colspan="5">Операций по карте не найдено</td></tr>' : '' ?>
                            <? foreach ($arResult['check'] as $arOne) { ?>
                                <tr>
                                    <td><?= $arOne['DATE_FORMAT'] ?></td>
                                    <td class="number">
                                        <?= ($arOne['URL']) ? '<a href="'.$arOne['URL'].'" target="_blank">' : '<a href="#" class="load-check-data" data-id="'.$arOne['ID'].'">' ?>
                                        <?= $arOne['NUMBER'] ?>
                                        <?= ($arOne['URL']) ? '</a>' : '</a>' ?>
                                    </td>
                                    <td><?= $arOne['SUMM_DISCOUNTED'] ?></td>
                                    <td><?= $arOne['PAID_BY_BONUS'] ?></td>
                                    <td><?= $arOne['BONUS'] ?></td>
                                </tr>
                                <tr>
                                    <td colspan="5"><?= $arOne['SHOP'] ?></td>
                                </tr>
                            <? } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <div id="historyCheckItemsModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    <h2 class="modal-title">Чек <span class="check-num"></span></h2>
                </div>
                <div class="modal-body">
                    <div class="bonus-table">
                        <table>
                            <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Артикул</th>
                                <th>Количество</th>
                                <th>Сумма</th>
                                <th>Оплачено баллами</th>
                            </tr>
                            </thead>
                            <tbody class="items-list"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editCard" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content form-card">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    <h2 class="modal-title">Данные по карте</h2>
                </div>
                <div class="modal-body">
                    <form id="editCardForm" name="newCard">
                        <input type="hidden" name="action" value="changeCard">
                        <div class="form-fields">
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CH_CARD_LAST_NAME" class="form-card-label">Фамилия <sup>*</sup></label>
                                        <input type="text" id="CH_CARD_LAST_NAME" name="last_name" required class="form-control input-names" value="<?= $arResult['data']['LAST_NAME'] ?>" placeholder="Фамилия*">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CH_CARD_NAME" class="form-card-label">Имя <sup>*</sup></label>
                                        <input type="text" id="CH_CARD_NAME" name="name" required class="form-control input-names" value="<?= $arResult['data']['NAME'] ?>" placeholder="Имя*">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CH_CARD_SECOND_NAME" class="form-card-label">Отчество</label>
                                        <input type="text" id="CH_CARD_SECOND_NAME" name="second_name" class="form-control input-names" value="<?= $arResult['data']['SECOND_NAME'] ?>" placeholder="Отчество">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CH_CARD_SEX" class="form-card-label">Ваш пол <sup>*</sup></label>
                                        <select id="CH_CARD_SEX" name="sex" required class="form-control">
                                            <option value="Ж" <?= ($arResult['data']['SEX'] == 'Ж') ? 'selected' : '' ?>>Женский</option>
                                            <option value="М" <?= ($arResult['data']['SEX'] == 'М') ? 'selected' : '' ?>>Мужской</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group form-field-date">
                                        <label for="CH_CARD_BIRTHDAY" class="form-card-label">День рождения <sup>*</sup></label>
                                        <?$APPLICATION->IncludeComponent(
                                            'bitrix:main.calendar',
                                            '',
                                            [
                                                'SHOW_INPUT'  => 'N',
                                                'FORM_NAME'   => 'newCard',
                                                'INPUT_NAME'  => 'birthday',
                                                'INPUT_VALUE' => '',
                                                'SHOW_TIME'   => 'N'
                                            ],
                                            null,
                                            ['HIDE_ICONS' => 'Y']
                                        );?>
                                        <input type="text" id="CH_CARD_BIRTHDAY" name="birthday" required class="form-control mask-date" value="<?= $arResult['data']['BIRTHDAY'] ?>" placeholder="День рождения*">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CH_CARD_PHONE" class="form-card-label">Номер телефона <sup>*</sup></label>
                                        <input type="tel" id="CH_CARD_PHONE" name="phone" required class="form-control mask-phone" value="<?= $arResult['data']['PHONE'] ?>" placeholder="Номер телефона*">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CH_CARD_EMAIL" class="form-card-label">Ваш Email <sup>*</sup></label>
                                        <input type="email" id="CH_CARD_EMAIL" name="email" required class="form-control" value="<?= $arResult['data']['EMAIL'] ?>" placeholder="Ваш Email*">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CH_CARD_AGREE" class="small-label">
                                            <input type="checkbox" id="CH_CARD_AGREE" name="CARD_AGREE" required checked>
                                            я согласен с условиями
                                            <a href="/support/dogovor-oferty/" target="_blank" class="link-gold link-decorat">публичной оферты</a> и обработкой моих персональных данных в порядке, предусмотренном публичной офертой.
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-btns">
                                <button type="submit" class="btn btn-primary">Сохранить данные</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


<? } else { ?>
    <div id="newCard" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content form-card">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    <h2 class="modal-title">Получить виртуальную карту</h2>
                </div>
                <div class="modal-body">
                    <form id="newCardForm" name="newCard">
                        <input type="hidden" name="action" value="newCard">
                        <div class="form-fields">
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CARD_LAST_NAME" class="form-card-label">Фамилия <sup>*</sup></label>
                                        <input type="text" id="CARD_LAST_NAME" name="last_name" required class="form-control input-names" value="<?= $arResult['USER']['LAST_NAME'] ?>" placeholder="Фамилия*">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CARD_NAME" class="form-card-label">Имя <sup>*</sup></label>
                                        <input type="text" id="CARD_NAME" name="name" required class="form-control input-names" value="<?= $arResult['USER']['NAME'] ?>" placeholder="Имя*">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CARD_SECOND_NAME" class="form-card-label">Отчество</label>
                                        <input type="text" id="CARD_SECOND_NAME" name="second_name" class="form-control input-names" value="<?= $arResult['USER']['SECOND_NAME'] ?>" placeholder="Отчество">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CARD_SEX" class="form-card-label">Ваш пол <sup>*</sup></label>
                                        <select id="CARD_SEX" name="sex" required class="form-control">
                                            <option value="Ж">Женский</option>
                                            <option value="М">Мужской</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group form-field-date">
                                        <label for="CARD_BIRTHDAY" class="form-card-label">День рождения <sup>*</sup></label>
                                        <?$APPLICATION->IncludeComponent(
                                            'bitrix:main.calendar',
                                            '',
                                            [
                                                'SHOW_INPUT'  => 'N',
                                                'FORM_NAME'   => 'newCard',
                                                'INPUT_NAME'  => 'birthday',
                                                'INPUT_VALUE' => '',
                                                'SHOW_TIME'   => 'N'
                                            ],
                                            null,
                                            ['HIDE_ICONS' => 'Y']
                                        );?>
                                        <input type="text" id="CARD_BIRTHDAY" name="birthday" required class="form-control mask-date" value="" placeholder="День рождения*">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CARD_PHONE" class="form-card-label">Номер телефона <sup>*</sup></label>
                                        <input type="tel" id="CARD_PHONE" name="phone" required class="form-control mask-phone" value="<?= $arResult['USER']['PERSONAL_MOBILE'] ?>" placeholder="Номер телефона*">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="CARD_EMAIL" class="form-card-label">Ваш Email <sup>*</sup></label>
                                        <input type="email" id="CARD_EMAIL" name="email" required class="form-control" value="<?= $arResult['USER']['EMAIL'] ?>" placeholder="Ваш Email*">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group form-oferta">
                                        <label for="CARD_AGREE" class="small-label">
                                            <input type="checkbox" id="CARD_AGREE" name="CARD_AGREE" required checked>
                                            я согласен с условиями
                                            <a href="/reference/contract-offer/" target="_blank" class="link-gold link-decorat">публичной оферты</a> и обработкой моих персональных данных в порядке, предусмотренном публичной офертой.
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-btns">
                                <button type="submit" class="btn btn-primary">Получить карту</button>
                            </div>
                        </div>
                    </form>
                    <div id="newCardCheck" class="hidden">
                        <p>Мы выслали Вам два кода подтверждения: на телефон <span class="check-phone"></span> в виде СМС-сообщения и на email
                            <span class="check-email"></span> письмом.<br />
                            Их нужно ввести в соответствующих текстовых полях.<br />
                            <br />
                            Если данные указаны неверно, то <a href="#" id="reSetProps" class="link-gold">отредактируйте их</a>.
                        </p>

                        <div class="form-filed form-field--label">
                            <label for="newCardCheckPhone" class="form-card-label">Код подтверждения телефона</label>
                            <input type="text" id="newCardCheckPhone" class="form-control" maxlength="5" placeholder="Код подтверждения телефона">
                            <div class="label-error"></div>
                        </div>
                        <div class="form-filed form-field--label">
                            <label for="newCardCheckEmail" class="form-card-label">Код подтверждения email'а</label>
                            <input type="text" id="newCardCheckEmail" class="form-control" maxlength="5" placeholder="Код подтверждения email'а">
                            <div class="label-error"></div>
                        </div>

                        <div class="modal-btns">
                            <a href="#" class="btn btn-primary send-btn" id="newCardCheckSubmit">Подтвердить</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="addCard" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content form-card">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    <h2 class="modal-title">Привязать карту к аккаунту</h2>
                </div>
                <div class="modal-body">
                    <div class="first-block">
                        <div class="form-filed form-field--label">
                            <label for="cardNumber" class="form-card-label">Номер карты</label>
                            <input type="text" id="cardNumber" name="cardNumber" maxlength="13" required value="" <?= ($arResult['SECURE'] == 'Y') ? 'disabled="disabled"' : '' ?> class="form-control js-loyalty-card-input" placeholder="Номер карты">
                            <label class="label-error"><?= ($arResult['SECURE'] == 'Y') ? $secureText : '' ?></label>
                        </div>
                        <div class="modal-btns">
                            <?= ($arResult['SECURE'] != 'Y') ? '<a href="#" class="btn btn-primary send-btn" id="addCardSubmit">Применить</a>' : '' ?>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="second-block hidden">
                        <div class="js-loyalty-code-info">
                            <p>Мы вышлем Вам код подтверждения, <br />который нужно будет указать в специальном текстовом поле.</p>

                            <p>
                    <span class="card-email-if hidden">
                        Код может быть отправлен на E-mail: <b class="card-email"></b><br>
                    </span>
                    <span class="card-phone-if hidden">
                        Код может быть отправлен на телефон: <b class="card-phone"></b><br>
                    </span>
                    <span class="card-contact-no hidden">
                        К сожалению, контактные данные не указаны, обратитесь в службу поддержки телефону <span class="nobr"><a href="tel:=<?= $phone ?>"><?= $phonePrint ?></a></span>
                    </span>
                            </p>

                            <div class="card-confirm-code-action card-contact-yes hidden">
                                <h4>Каким способом отправить код?</h4>
                    <span class="card-email-if hidden">
                        <input type="button" value="на E-mail" class="btn btn-primary card-confirm" style="width:45%;" data-confirm="email">
                    </span>
                    <span class="card-phone-if hidden">
                        <input type="button" value="на телефон" class="btn btn-primary card-confirm" style="width:45%;" data-confirm="phone">
                    </span>
                            </div>
                        </div>
                    </div>
                    <div class="third-block hidden">
                        <div class="loyalty-popup__send-code-confirm js-loyalty-code-confirm">
                            <p>Мы выслали Вам код подтверждения, укажите его в специальном текстовом поле.</p>

                            <p>
                                <span class="card-email-send hidden">Код был отправлен на E-mail: <b class="card-email"></b><br></span>
                                <span class="card-phone-send hidden">Код был отправлен на телефон: <b class="card-phone"></b><br></span>
                            </p>

                            <div class="form-filed form-field--label">
                                <label for="loyaltyCardCode" class="form-card-label">Код подтверждения</label>
                                <input type="text" id="loyaltyCardCode" class="form-control" maxlength="5" placeholder="Код подтверждения">
                            </div>
                            <div class="modal-btns">
                                <a href="#" class="btn btn-primary send-btn" id="checkCardSubmit">Подтвердить</a>
                            </div>

                            <div class="clear"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<? } ?>
</div>
<script type="text/javascript">
    window.helpText = '<?=$helpText?>';
    window.secureText = '<?=$secureText?>';
    window.bonusCard = '<?=$arResult['number']?>';
</script>