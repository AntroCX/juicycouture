<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**@var array $arResult */
/**@var array $arParams */
use \Jamilco\Main\Oracle;

$arResult['OCS_DISABLE'] = Oracle::getInstance()->isLockFileExists();
?>
<div class="b-order__bonus-card b-loyalty <?= ($arResult['CARD']) ? '' : 'mobile-hidden-form' ?> <?= ($arParams['HIDE'] == 'Y') ? 'hidden' : '' ?>">
    <div class="b-order__bonus-card-title hidden-block-title">У Вас есть бонусная карта?</div>
    <div class="b-order__bonus-card-fields b-loyalty__content">
        <? if ($arParams['IS_AJAX_MODE']) $APPLICATION->RestartBuffer(); ?>
        <? if ($arResult['OCS_DISABLE']) { ?>
        <div class="b-order-basket__side-bonus-text">
            В данный момент применение карты не доступно. Пожалуйста, попробуйте оформить заказ позже.
        </div>
        <? } else { ?>
        <div class="form-group js-loyalty-form row" data-handler="<?= $componentPath ?>">
            <?= bitrix_sessid_post() ?>
            <div class="col-xs-6 col-lg-8">
                <input class="form-control js-loyalty-card-input" type="text" maxlength="13" placeholder="Номер бонусной карты" name="loyaltyCardNumber" value="<?= $arResult['CARD'] ?>" <? if ($arResult['SECURE'] == 'Y'): ?>disabled="disabled"<? endif; ?>/>
            </div>
            <div class="col-xs-6 col-lg-4">
                <button class="btn btn-default btn-block js-loyalty-submit">Применить</button>
            </div>
        </div>
        <?php
        $errorText = '';
        if ($arResult['ERROR'] == 'NOT_FOUND') $errorText = 'Карта не найдена';
        if ($arResult['SECURE'] == 'Y') $errorText = 'Если у Вас возникли проблемы с вводом номера карты, обратитесь в службу поддержки по телефону <b>8-800-770-75-97</b>';
        if ($arResult['BONUSES']['CARD_BALANCE'] === 0) $errorText = 'Недостаточно бонусов для списания';
        ?>
        <div class="b-order-basket__side-bonus-result <?= $errorText !== '' ? '' : 'hidden' ?>" id="bonusError"><?= $errorText ?></div>
        <div class="b-loyalty__content-info">
            <? if ($arResult['BONUSES']['CARD_BALANCE'] > 0): ?>
                <div class="row">
                    <div class="col-sm-6">
                        <div>
                            <? if ($arResult['BONUSES']['CARD_BALANCE'] > 0): ?>
                                <div>Бонусы на карте: <?= CurrencyFormat($arResult['BONUSES']['CARD_BALANCE'], 'RUB') ?></div>
                            <? else: ?>
                                <div>Недостаточно бонусов для списания</div>
                            <? endif; ?>

                            <div>Бонусы к списанию: <?= $arResult['BONUSES']['WRITEOFF_SUM_FORMAT'] ?></div>
                        </div>
                    </div>
                    <? if ($arResult['BONUSES']['CARD_BALANCE'] > 0 && $arResult['BONUSES']['ADDITIONAL_SUM'] > 0): ?>
                        <div class="col-sm-6">
                            <div class="checkbox">
                                <label>
                                    <?php
                                    $checked = ($arResult['CONFIRM'] == 'Y' && $arResult['APPLY'] == 'Y') ? 'checked="checked"' : '';
                                    ?>
                                    <input type="checkbox"
                                           name="applyBonuses"
                                           class="js-loyalty-popup"
                                           data-target="#loyalty-popup"
                                           data-confirm="<?= $arResult['CONFIRM'] ?>"
                                           id="APPLY_BONUS"
                                        <?= $checked ?>
                                           value="Y"
                                    />
                                    Использовать бонусы
                                </label>
                            </div>
                        </div>
                    <? endif; ?>
                </div>
            <? endif; ?>
            <? if ($arResult['BONUSES']['ADDITIONAL_SUM'] > 0): ?>
                <div class="b-loyalty__annotation">
                    За покупку вам будет начислено до <?= CurrencyFormat($arResult['BONUSES']['ADDITIONAL_SUM'], 'RUB')?> баллов на карту бонусной программы <?= $arResult['CARD'] ?>
                </div>
            <? endif; ?>
        </div>
        <? } ?>
    </div>
    <?
    if ($arResult['BONUSES']['CARD_BALANCE'] > 0): ?>
        <div class="modal fade loyalty-popup" tabindex="-1" role="dialog" id="loyalty-popup">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                        <h4 class="modal-title">ПОДТВЕРДИТЕ ВЛАДЕНИЕ КАРТОЙ</h4>
                    </div>
                    <div class="modal-body">
                        <div class="loyalty-popup__send-code-info js-loyalty-code-info">
                            <p>Мы вышлем Вам код подтверждения, который нужно будет указать в специальном текстовом поле.</p>
                            <p>
                                <?php
                                $isEmail = strlen($arResult['MASK']['EMAIL']) > 0;
                                $isPhone = strlen($arResult['MASK']['PHONE']) > 0;
                                ?>
                                <? if ($isEmail): ?>
                                    Код может быть отправлен на E-mail: <b><?= $arResult['MASK']['EMAIL'] ?></b><br>
                                <? endif; ?>
                                <? if ($isPhone): ?>
                                    Код может быть отправлен на телефон: <b><?= $arResult['MASK']['PHONE'] ?></b><br>
                                <? endif ?>
                                <? if (!$isEmail && !$isPhone): ?>
                                    К сожалению, контактные данные не указаны, обратитесь в службу поддержки телефону <span class="nobr"><b>8 800 500 02 32</b></span>
                                <? endif; ?>
                            </p>
                            <? if ($isEmail || $isPhone): ?>
                                <div class="loyalty-popup__send-code-action">
                                    <h4>Каким способом отправить код?</h4>
                                    <? if ($isEmail): ?>
                                        <input type="button" value="на E-mail" class="btn btn-wide btn-default js-loyalty-confirm-email" data-confirm="email">
                                    <? endif; ?>
                                    <? if ($isPhone): ?>
                                        <input type="button" value="на телефон" class="btn btn-wide btn-default js-loyalty-confirm-phone" data-confirm="phone">
                                    <? endif; ?>
                                </div>
                            <? endif; ?>
                        </div>
                        <div class="loyalty-popup__send-code-confirm js-loyalty-code-confirm">
                            <p>Мы выслали Вам код подтверждения, укажите его в специальном текстовом поле.</p>
                            <p>
                                <span class="js-loyalty-confirm-email">Код был отправлен на E-mail: <b><?= $arResult['MASK']['EMAIL'] ?></b><br></span>
                                <span class="js-loyalty-confirm-phone">Код был отправлен на телефон: <b><?= $arResult['MASK']['PHONE'] ?></b><br></span>
                            </p>
                            <div class="loyalty-popup__confirm-code">
                                <input type="tel" class="form-control loyalty-popup__confirm-code-input js-loyalty-confirm-code" maxlength="5" placeholder="Код подтверждения">
                                <input type="button" value="подтвердить" class="btn btn-default js-loyalty-confirm-submit">
                                <div class="js-loyalty-confirm-error">Неверный код подтверждения</div>
                                <div class="js-loyalty-confirm-ok">
                                    <div class="loyalty-popup__confirm-success">Владение картой подтверждено. Вы можете закрыть это окно.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <? endif; ?>

    <? if ($arParams['IS_AJAX_MODE']) die(); ?>
</div>