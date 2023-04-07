<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
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

?>
<div class="subscribe-block">
    <div class="subscribe-text">
        <div class="subscribe-title">Подпишитеcь на новости juicy couture</div>
        <div class="subscribe-desc">и получите промокод на скидку 500 рублей на первую покупку</div>
        <div class="btn__wrap">
            <a href="#" class="btn btn-primary" data-block="js-subscribe-popup">Подписаться</a>
        </div>
    </div>
</div>
<div id="js-subscribe-popup" class="popup js-popup-subscribe">
    <div class="popup__inner">
        <div class="popup__box">
            <a href="javascript:void(0)" class="i-cross js-popup-close"></a>

            <div class="popup-step1">
                <div class="popup-logo"></div>
                <p class="popup-title">Подпишитеcь и получите<br /> 500 рублей на следующую покупку</p>

                <form class="popup-subscribe-coupon" action="">
                    <div class="popup-email">
                        <input type="email" name="email" placeholder="Введите ваш e-mail...">
                    </div>
                    <div class="btn__wrap"><a href="#" class="btn btn--yellow">Подписаться</a></div>
                    <div class="clear"></div>
                    <p>Мы отправим вам промокод на 500 рублей на указанный вами email адрес.</p>
                </form>
                <div class="popup-footer">
                    <input type="checkbox" name="oferta" id="popup-oferta" checked="checked">
                    <label for="popup-oferta">
                        Я согласен с условиями <a href="/reference/contract-offer/">публичной оферты и обработкой моих персональных данных в порядке, предусмотренном публичной офертой juicycouture.ru</a>
                    </label>
                </div>
            </div>
            <div class="popup-step2 none">
                <div class="popup-logo"></div>
                <p class="popup-title">Спасибо за подписку!</p>

                <p class="popup-text">Мы скоро свяжемся с вами. Проверьте свой почтовый ящик, мы отправили вам промокод на скидку 500 рублей на вашу следующую онлайн покупку.</p>
            </div>
        </div>
    </div>
</div>
