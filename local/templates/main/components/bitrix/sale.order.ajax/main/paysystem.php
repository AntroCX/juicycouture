<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?php
use \Bitrix\Main\Web\Json;
?>
<div class="b-order__props-payment">
    <div class="bx_section">
        <? if (!empty($arResult["PAY_SYSTEM"]) && is_array($arResult["PAY_SYSTEM"]) || $arResult["PAY_FROM_ACCOUNT"] == "Y") { ?>
            <div class="b-order__props-payment-title"><?= GetMessage("SOA_TEMPL_PAY_SYSTEM") ?></div>
        <?
        }

        uasort($arResult["PAY_SYSTEM"], "cmpBySort"); // resort arrays according to SORT value
        $i = 0;
        foreach ($arResult["PAY_SYSTEM"] as $key => $arPaySystem) {
            if ($arPaySystem['CHECKED'] === 'Y') {
                $arResult['PAY_SYSTEM_CHECK_ID'] = $arPaySystem['ID'];
            }
            if ($i === 0) {
                ?><div class="btn-group" data-toggle="buttons">
            <? } ?>
			<label <?php echo ($arPaySystem["ID"] == 8 ? 'style="display: none;"' : ''); ?> for="ID_PAY_SYSTEM_ID_<?= $arPaySystem["ID"] ?>" class="bx_element btn btn-radio <? if ($arPaySystem['ID'] == ONLINE_PAY_SYSTEM) echo "online-pay-discount-label-text" ?> <? if ($arPaySystem["CHECKED"] == "Y" && !($arParams["ONLY_FULL_PAY_FROM_ACCOUNT"] == "Y" && $arResult["USER_VALS"]["PAY_CURRENT_ACCOUNT"] == "Y")): ?>active<? endif ?>"
                   data-step-id="2" data-psa-name="<?= $arPaySystem["PSA_NAME"] ?>" onclick="paySystemInfo(this)"
            >

                <input type="radio"
                       id="ID_PAY_SYSTEM_ID_<?= $arPaySystem["ID"] ?>"
                       name="PAY_SYSTEM_ID"
                       value="<?= $arPaySystem["ID"] ?>"
                    <? if ($arPaySystem["CHECKED"] == "Y") echo " checked=\"checked\""; ?>
                    />

                <? if ($arParams["SHOW_PAYMENT_SERVICES_NAMES"] != "N"): ?>
                    <div class="bx_description">
                        <?= $arPaySystem['PSA_NAME'] ?>
                        <? if ($arPaySystem['ID'] == ONLINE_PAY_SYSTEM): ?>
                          <span class="online-pay-discount-label">-<?= ONLINE_PAY_DISCOUNT * 100 ?>%</span>
                        <? endif ?>
                    </div>
                <? endif; ?>

            </label>
            <? if ($i === (count($arResult['PAY_SYSTEM']) - 1)) { ?>
                </div>
            <? } ?>
        <?
            $i++;
        } ?>
        <div style="clear: both;"></div>
    </div>
</div>
<script>
    function paySystemInfo(target) {
        window.GENERAL.order.dataLayerPaySystem(target, <?= Json::encode($arResult['JS_OBJ']['WDL_ORDER']) ?>);
    }
</script>