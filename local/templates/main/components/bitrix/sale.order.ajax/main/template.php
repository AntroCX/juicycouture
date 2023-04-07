<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @var string $templateFolder */

use \Bitrix\Main\Web\Json;
use Juicycouture\Google\ReCaptcha\ReCaptchaAsset;

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if($request->isAjaxRequest() && $arResult['ERROR']){
    $APPLICATION->RestartBuffer();
    echo Json::encode(['error' => $arResult['ERROR']]);
    die();
}

if ($USER->IsAuthorized() || $arParams["ALLOW_AUTO_REGISTER"] == "Y") {
    if ($arResult["USER_VALS"]["CONFIRM_ORDER"] == "Y" || $arResult["NEED_REDIRECT"] == "Y") {
        if (strlen($arResult["REDIRECT_URL"]) > 0) {
            $APPLICATION->RestartBuffer();
            ?>
            <script type="text/javascript">
                window.top.location.href = '<?=CUtil::JSEscape($arResult["REDIRECT_URL"])?>';
            </script>
            <?
            die();
        }

    }
}

$this->addExternalJS('//api-maps.yandex.ru/2.1/?lang=ru_RU');
\Jamilco\Blocks\Block::load(['b-autocomplete']);
?>

<?
$confirmOrderPage = false;
if (
    ($USER->IsAuthorized() || $arParams["ALLOW_AUTO_REGISTER"] != "N") &&
    ($arResult["USER_VALS"]["CONFIRM_ORDER"] == "Y" || $arResult["NEED_REDIRECT"] == "Y")) {
    $confirmOrderPage = true;
}

if (!count($arResult['BASKET_ITEMS']) && !$confirmOrderPage):
    ?>
    <?php
        $basketRecordId = array_keys($_SESSION['SEND_DATA_LAYER_BASKET_REMOVE'])[0];
        if ($basketRecordId) {
            echo "
                <script>
                window.dataLayer = window.dataLayer || [];
                dataLayer.push(" . Json::encode($_SESSION['SEND_DATA_LAYER_BASKET_REMOVE'][$basketRecordId]) . ");
                </script>
            ";
            unset($_SESSION['SEND_DATA_LAYER_BASKET_REMOVE']);
        }
    ?>
    <div class="basket-empty">
        <h1 class="basket-empty__title">Ваша корзина пуста</h1><a class="basket-empty__link" href="/catalog/">Перейти в каталог</a>
    </div>

<? else: ?>
    <script>
        window.GENERAL.catalog.dataLayerCheckoutStep(1, <?= Json::encode($arResult['JS_OBJ']['WDL_ORDER']) ?>);
    </script>
    <a name="order_form"></a>

    <h1 class="h1">Оформление заказа</h1>

    <div id="order_form_div" class="b-order">
        <NOSCRIPT>
            <div class="errortext"><?=GetMessage("SOA_NO_JS")?></div>
        </NOSCRIPT>

        <?
        if (!function_exists("getColumnName")) {
            function getColumnName($arHeader)
            {
                return (strlen($arHeader["name"]) > 0) ? $arHeader["name"] : GetMessage("SALE_".$arHeader["id"]);
            }
        }

        if (!function_exists("cmpBySort")) {
            function cmpBySort($array1, $array2)
            {
                if (!isset($array1["SORT"]) || !isset($array2["SORT"])) return -1;
                if ($array1["SORT"] > $array2["SORT"]) return 1;
                if ($array1["SORT"] < $array2["SORT"]) return -1;
                if ($array1["SORT"] == $array2["SORT"]) return 0;
            }
        }
        ?>

        <div class="bx_order_make row">
            <?
            if(!$USER->IsAuthorized() && $arParams["ALLOW_AUTO_REGISTER"] == "N")
            {
                if(!empty($arResult["ERROR"]))
                {
                    foreach($arResult["ERROR"] as $v)
                        echo ShowError($v);
                }
                elseif(!empty($arResult["OK_MESSAGE"]))
                {
                    foreach($arResult["OK_MESSAGE"] as $v)
                        echo ShowNote($v);
                }

                include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/auth.php");
            }
            else
            {
                if($arResult["USER_VALS"]["CONFIRM_ORDER"] == "Y" || $arResult["NEED_REDIRECT"] == "Y")
                {
                    if(strlen($arResult["REDIRECT_URL"]) == 0)
                    {
                        include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/confirm.php");
                    }
                } else {
                if ($_POST["is_ajax_post"] != "Y") {
                    ?>
                    <script type="text/javascript">
                        // загрузчик крутится до тех пор, пока не загрузятся все скрипты
                        BX.showWait();
                    </script>
                    <form action="<?=$APPLICATION->GetCurPage();?>" method="POST" name="ORDER_FORM" id="ORDER_FORM" enctype="multipart/form-data">
                        <?=bitrix_sessid_post()?>
                        <div id="order_form_content">
                            <?
                            } else {
                                $APPLICATION->RestartBuffer();
                            }

                            if($_REQUEST['PERMANENT_MODE_STEPS'] == 1) { ?>
                                <input type="hidden" name="PERMANENT_MODE_STEPS" value="1" />
                            <? }

                            if(!empty($arResult["ERROR"]) && $arResult["USER_VALS"]["FINAL_STEP"] == "Y") {
                                foreach($arResult["ERROR"] as $v)
                                    echo ShowError($v);
                                ?>
                                <script type="text/javascript">
                                    top.BX.scrollToNode(top.BX('ORDER_FORM'));
                                </script>
                            <? } ?>
                            <div class="col-sm-5 basket-col">

                                <?
                                include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/summary.php");

                                if (strlen($arResult["PREPAY_ADIT_FIELDS"]) > 0) echo $arResult["PREPAY_ADIT_FIELDS"];
                                ?>
                            </div>
                            <div class="col-sm-7">
                                <div class="b-order__block">
                                    <div class="b-order__block-title">
                                        <h4>Оформление заказа</h4>
                                    </div>
                                    <div class="b-order__block-body b-order__props">
                                        <?

                                        include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/person_type.php");
                                        include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/props.php");
                                        if ($arParams["DELIVERY_TO_PAYSYSTEM"] == "p2d") {
                                            include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/paysystem.php");
                                            include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/delivery.php");
                                        } else {
                                            include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/delivery.php");
                                            include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/paysystem.php");
                                        }
                                        include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/bonus_and_comment.php");

                                        include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/related_props.php");

                                        include $_SERVER['DOCUMENT_ROOT'].$templateFolder.'/digital_data_layer.php';

                                        ?>
                                    </div>

                                    <div class="order-submit-block">
                                        <div class="form-group">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" checked name="i-agree"> я соглашаюсь с условиями <a href="/reference/contract-offer/" target="_blank">публичной оферты</a> и обработкой моих персональных данных в порядке, предусмотренном публичной офертой
                                                </label>
                                            </div>
                                        </div>
                                        <a href="#" id="ORDER_CONFIRM_BUTTON" class="checkout btn btn-primary-black btn-block">Подтвердить заказ</a>
                                    </div>
                                </div>
                            </div>
                            <div class="clear"></div>
                            <? if ($_POST["is_ajax_post"] != "Y") { ?>
                        </div>
                        <input type="hidden" name="confirmorder" id="confirmorder" value="N">
                        <input type="hidden" name="profile_change" id="profile_change" value="N">
                        <input type="hidden" name="is_ajax_post" id="is_ajax_post" value="Y">
                        <input type="hidden" name="json" value="Y">
                        <input type="hidden" class="g-recaptcha" name="TOKEN_CAPTCHA" data-action="order"/>
                    </form>
                    <? if ($arParams["DELIVERY_NO_AJAX"] == "N") { ?>
                    <div style="display:none;"><?$APPLICATION->IncludeComponent("bitrix:sale.ajax.delivery.calculator", "", array(), null, array('HIDE_ICONS' => 'Y')); ?></div>
                <? } ?>
                <?
                } else {
                ?>
                    <script type="text/javascript">
                        window.siteKey = '<?= ReCaptchaAsset::getSiteKey() ?>';
                        top.BX('confirmorder').value = 'Y';
                        top.BX('profile_change').value = 'N';
                    </script>
                    <?
                    die();
                }
                }
            }
            ?>
        </div>
    </div >

    <?if(CSaleLocation::isLocationProEnabled()):?>

        <div style="display: none">
            <?// we need to have all styles for sale.location.selector.steps, but RestartBuffer() cuts off document head with styles in it?>
            <?$APPLICATION->IncludeComponent(
                "bitrix:sale.location.selector.steps",
                ".default",
                array(),
                false
            );?>
            <?$APPLICATION->IncludeComponent(
                "bitrix:sale.location.selector.search",
                ".default",
                array(),
                false
            );?>
        </div>

    <?endif?>

    <div class="modal fade" id="js-popup-gifts" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close"><span aria-hidden="true"></span></button>
                    <h4 class="modal-title">Выберите подарок</h4>
                </div>

                <div class="modal-body">
                    <div class="gift-list"></div>
                </div>

            </div>
        </div>
    </div>

<? endif; ?>
<?
include_once \Bitrix\Main\Application::getDocumentRoot() . '/local/includes/quickModal.php';
