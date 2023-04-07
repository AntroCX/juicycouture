<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->SetTitle("Подписка на новости");

use Bitrix\Main\Localization\Loc;

use Bitrix\Main\Context,
    Bitrix\Main\Loader,
    \Bitrix\Sale\Order,
    \Jamilco\Main\Subscribers;

Loader::includeModule("iblock");
Loader::IncludeModule("sale");
?>

<?
if ($arParams['SHOW_SUBSCRIBE_PAGE'] !== 'Y') {
    LocalRedirect($arParams['SEF_FOLDER']);
}

if (strlen($arParams["MAIN_CHAIN_NAME"]) > 0) {
    $APPLICATION->AddChainItem(htmlspecialcharsbx($arParams["MAIN_CHAIN_NAME"]), $arResult['SEF_FOLDER']);
}
$APPLICATION->AddChainItem(Loc::getMessage("SPS_CHAIN_SUBSCRIBE_NEW"));
?>
<?
global $USER;
$userSubscriber = Subscribers::getInstance()->checkSubcscribers($USER->GetLogin());
$couponData = Subscribers::getInstance()->getCouponSubscribers($USER->GetLogin());
?>

<div class="lk-user-subscribe lk-user-block">
    <header>
        <div class="h5 lk-h5">
            <?= GetMessage('PROFILE_SUBSCRIBER'); ?>
        </div>
    </header>
    <form method="post" name="" id="js-subscribe" action="/local/ajax/subscribe_new.php" class="js-form-user"
          enctype="multipart/form-data">
        <input type="hidden" name="lang" value="<?= LANG ?>">
        <input type="hidden" name="ID" value="<?= $USER->GetID() ?>">
        <input type="hidden" name="ACTION"
               value="<?= (($userSubscriber["STATUS"] == 'YES') ? 'unsubscribe' : 'subscribe'); ?>"/>
        <input type="hidden" name="EMAIL" value="<?= $USER->GetLogin() ?>"/>
        <input type="hidden" name="TOKEN_CAPTCHA" class="g-recaptcha" data-action="subscribe">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p class="alert <? if ($userSubscriber["STATUS"] == 'YES'): ?>alert-success<? else: ?>alert-danger<? endif; ?>"
                       data-success="<?= GetMessage("PROFILE_TEXT_SUBSCRIBER"); ?>"
                       data-danger="<?= GetMessage("PROFILE_TEXT_NO_SUBSCRIBER"); ?>">
                        <? if ($userSubscriber["STATUS"] == 'YES'): ?>
                            <?= GetMessage("PROFILE_TEXT_SUBSCRIBER"); ?>
                        <? else: ?>
                            <?= GetMessage("PROFILE_TEXT_NO_SUBSCRIBER"); ?>
                        <? endif; ?>
                    </p>
                </div>
                <div class="col-12">
                    <input type="submit" name="send" class="btn float-right btn-default"
                           value="<?= (($userSubscriber["STATUS"] == 'YES') ? GetMessage("USER_SUBSCRUBE") : GetMessage("USER_NO_SUBSCRUBE")) ?>">
                </div>
                <? if (empty($userSubscriber["COUPON"])): ?>
                    <div class="col-12"><br>
                        <p><?= GetMessage("SUBSCRUBE_INFO"); ?></p></div>
                <? else: ?>
                    <div class="col-12">
                        <br>
                        <p><?= GetMessage("SUBSCRUBE_COUPON"); ?>
                            <span
                                <? if ($couponData["COUPON_USE"]['ORDER_ID'] > 0): ?>style="text-decoration:line-through"<? endif; ?>><?= $userSubscriber["COUPON"] ?></span>
                            <? if (!empty($couponData["COUPON_USE"]['ORDER_DATE'])): ?>
                                <br>
                                <small>Дата применения: <?= $couponData["COUPON_USE"]['ORDER_DATE']; ?></small>
                            <? endif; ?>
                        </p>
                    </div>
                <? endif; ?>
            </div>
        </div>
    </form>
</div>


