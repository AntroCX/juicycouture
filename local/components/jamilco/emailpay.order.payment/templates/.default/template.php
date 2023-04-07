<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 * @var array $arResult
 * @var $APPLICATION CMain
 * @var $USER CUser
 * @var $component SaleOrderAjax
 * @var $templateFolder
 */

$context = Main\Application::getInstance()->getContext();
$request = $context->getRequest();
$server = $context->getServer();
?>
<? if (!empty($arResult["ORDER"])): ?>

    <?
    if ($arResult["ORDER"]["IS_ALLOW_PAY"] === 'Y')
    {
        if (!empty($arResult["PAYMENT"]))
        {
            foreach ($arResult["PAYMENT"] as $payment)
            {
                if ($payment["PAID"] != 'Y')
                {
                    if (!empty($arResult['PAY_SYSTEM_LIST'])
                        && array_key_exists($payment["PAY_SYSTEM_ID"], $arResult['PAY_SYSTEM_LIST'])
                    )
                    {
                        $arPaySystem = $arResult['PAY_SYSTEM_LIST'][$payment["PAY_SYSTEM_ID"]];

                        if (empty($arPaySystem["ERROR"]))
                        {
                            ?>
                            <br /><br />

                            <table class="sale_order_full_table">
                                <tr>
                                    <td class="ps_logo">
                                        <p>
                                            Внимание! Для продолжения процесса оплаты вы будете перенаправлены на страницу оплаты Яндекс.Кассы.
                                        </p>
                                        <?= (!empty($arParams['SITE_NAME'])) ? $arParams['SITE_NAME'] . ' - ' : '' ?>
                                        <?=Loc::getMessage("EOP_COMPANY_NAME")?>
                                    </td>
                                </tr>
                                <tr id="paySystemRow">
                                    <td>
                                        <? if (strlen($arPaySystem["ACTION_FILE"]) > 0 && $arPaySystem["NEW_WINDOW"] == "Y" && $arPaySystem["IS_CASH"] != "Y"): ?>
                                            <?
                                            $orderAccountNumber = urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]));
                                            $paymentAccountNumber = $payment["ACCOUNT_NUMBER"];
                                            ?>
                                            <script>
                                                window.open('<?=$arParams["PATH_TO_PAYMENT"]?>?ORDER_ID=<?=$orderAccountNumber?>&PAYMENT_ID=<?=$paymentAccountNumber?>');
                                            </script>
                                        <?=Loc::getMessage("EOP_PAY_LINK", array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".$orderAccountNumber."&PAYMENT_ID=".$paymentAccountNumber))?>
                                        <? if (CSalePdf::isPdfAvailable() && $arPaySystem['IS_AFFORD_PDF']): ?>
                                        <br/>
                                            <?=Loc::getMessage("EOP_PAY_PDF", array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".$orderAccountNumber."&pdf=1&DOWNLOAD=Y"))?>
                                        <? endif ?>
                                        <? else: ?>
                                            <?=$arPaySystem["BUFFERED_OUTPUT"]?>
                                        <? endif ?>
                                    </td>
                                </tr>
                            </table>

                            <?
                        }
                        else
                        {
                            ?>
                            <span style="color:red;"><?=Loc::getMessage("EOP_ORDER_PS_ERROR")?></span>
                            <?
                        }
                    }
                    else
                    {
                        ?>
                        <span style="color:red;"><?=Loc::getMessage("EOP_ORDER_PS_ERROR")?></span>
                        <?
                    }
                }
            }
        }
    }
    else
    {
        ?>
        <br /><strong><?=$arParams['MESS_PAY_SYSTEM_PAYABLE_ERROR']?></strong>
        <?
    }
    ?>
    <script>
        $(function(){
            $('#paySystemRow form').submit();
        });
    </script>
<? else: ?>

    <b><?=Loc::getMessage("EOP_ERROR_ORDER")?></b>
    <br /><br />

    <table class="sale_order_full_table">
        <tr>
            <td>
                <?=Loc::getMessage("EOP_ERROR_ORDER_LOST", array("#ORDER_ID#" => $arResult["ACCOUNT_NUMBER"]))?>
                <?=Loc::getMessage("EOP_ERROR_ORDER_LOST1")?>
            </td>
        </tr>
    </table>

<? endif ?>
