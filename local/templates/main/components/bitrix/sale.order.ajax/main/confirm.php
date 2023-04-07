<? use Bitrix\Main\Web\Json;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?php
$orderId = $arResult['ORDER_ID'];
if (array_key_exists($orderId, $_SESSION['SEND_DATA_LAYER_ORDERS'])): ?>
    <script>
        window.GENERAL.order.dataLayerOrderAdd(<?= Json::encode($_SESSION['SEND_DATA_LAYER_ORDERS'][$orderId]) ?>);
    </script>
<?php endif; ?>
<?php
unset($_SESSION['SEND_DATA_LAYER_ORDERS'][$orderId]);
?>

<?php
$email = getOwnerEmail($orderId);
$storeName = \Jamilco\Main\Retail::getStoreName(true);
if ($orderId) {
    $transactionIdVal = (string)$arResult['ORDER_ID'];
    print "
    <script type='text/javascript'>
      (window['rrApiOnReady'] = window['rrApiOnReady'] || []).push(function() {
            try {
        rrApi.setEmail('" . $email . "', {'stockId': '" . $storeName . "'});
        rrApi.order({
        transaction: $transactionIdVal,
        items: [
      ";
        $res = CSaleBasket::GetList([], ['ORDER_ID' => $orderId]);
        while ($arItem = $res->Fetch()) {
            $mxResult = CCatalogSku::GetProductInfo($arItem['PRODUCT_ID']);
            $productIdVal = (int)$mxResult['ID'];
            $qntIdVal = (int)$arItem['QUANTITY'];
            $priceIdVal = number_format(round($arItem['PRICE']), 2, '.', '');
            print "{ 'id': $productIdVal, 'qnt': $qntIdVal, 'price': $priceIdVal},";
        }
    print "
    ]
                  });
        } catch(e) {}
      })
    </script>
  ";
}
?>

<div class="b-order__confirm text-center">
<?
if (!empty($arResult["ORDER"]))
{
	?>
    <!-- fb tracking -->
    <script>
        fbq('track', 'Purchase');
    </script>
    <!-- !fb tracking -->

    <div class="b-order__confirm-ok"></div>
	<b class="b-order__confirm-title"><?=GetMessage("SOA_TEMPL_ORDER_COMPLETE")?></b><br /><br />
	<table class="sale_order_full_table">
		<tr>
			<td>
				<?= GetMessage("SOA_TEMPL_ORDER_SUC", Array("#ORDER_DATE#" => $arResult["ORDER"]["DATE_INSERT"], "#ORDER_ID#" => $arResult["ORDER"]["ACCOUNT_NUMBER"]))?>
				<br /><br />
				<?= GetMessage("SOA_TEMPL_ORDER_SUC1", Array("#LINK#" => $arParams["PATH_TO_PERSONAL"])) ?>
			</td>
		</tr>
	</table>
	<?
	if (!empty($arResult["PAY_SYSTEM"]))
	{
		?>
		<br /><br />

		<table class="sale_order_full_table">
			<tr>
				<td class="ps_logo">
					<div class="pay_name"><?=GetMessage("SOA_TEMPL_PAY")?></div>
					<?=CFile::ShowImage($arResult["PAY_SYSTEM"]["LOGOTIP"], 100, 100, "border=0", "", false);?>
                    <?php
                    $arResult['ORDER']['DELIVERY_ID'] = (int)$arResult['ORDER']['DELIVERY_ID'];
                    $changePaySystemName = $arResult['ORDER']['DELIVERY_ID'] === OZON_DELIVERY || $arResult['ORDER']['DELIVERY_ID'] === PICKUP_DELIVERY;
                    ?>
					<div class="paysystem_name"><?= $changePaySystemName ? GetMessage('PAY_SYSTEM_' . $arResult['PAY_SYSTEM']['ID'] . '_NAME') : $arResult["PAY_SYSTEM"]["NAME"] ?></div><br>
				</td>
			</tr>
        </table>
             <?
                $arPaySystem = $arResult['PAY_SYSTEM_LIST'][$arResult['ORDER']['PAY_SYSTEM_ID']];
                if ($arResult["ORDER"]["IS_ALLOW_PAY"] === 'Y') {
                    if (!empty($arResult["PAYMENT"])) {
                        foreach ($arResult["PAYMENT"] as $payment) {
                            if ($payment["PAID"] != 'Y') {
                                if (!empty($arResult['PAY_SYSTEM_LIST'])
                                    && array_key_exists($payment["PAY_SYSTEM_ID"], $arResult['PAY_SYSTEM_LIST'])
                                ) {
                                    $arPaySystem = $arResult['PAY_SYSTEM_LIST'][$payment["PAY_SYSTEM_ID"]];
                                    if (empty($arPaySystem["ERROR"])) {
                                        if ($arPaySystem['NEW_WINDOW'] == 'Y') {
                                            $orderAccountNumber = urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]));
                                            $paymentAccountNumber = $payment["ACCOUNT_NUMBER"];
                                            ?>
                                            <p class="checkout__text"><?= $arPaySystem['NAME'] ?>
                                                <span class="radio__text-small"><?= $arPaySystem['DESCRIPTION'] ?></span></p>
                                            <br />
                                            <a class="btn btn-primary" id="goPay" href="<?= $arParams["PATH_TO_PAYMENT"] ?>?ORDER_ID=<?= $orderAccountNumber ?>&PAYMENT_ID=<?= $paymentAccountNumber ?>">Оплатить</a>
                                        <?
                                        } else {
                                            $arPaySystem['BUFFERED_OUTPUT'] = str_replace(
                                                ['btn--yellow', '<font class="tablebodytext">', '</font>'],
                                                ['btn-gold', '', ''],
                                                $arPaySystem['BUFFERED_OUTPUT']
                                            );
                                            if (!$arPaySystem['BUFFERED_OUTPUT']) $arPaySystem['BUFFERED_OUTPUT'] = '<p class="checkout__text">'.$arPaySystem['NAME'].'<span class="radio__text-small">'.$arPaySystem['DESCRIPTION'].'</span></p>';
                                            echo $arPaySystem['BUFFERED_OUTPUT'];
                                        }
                                    } else {
                                        ?>
                                        <span style="color:red;"><?= Loc::getMessage("SOA_ORDER_PS_ERROR") ?></span>
                                    <?
                                    }
                                } else {
                                    ?>
                                    <span style="color:red;"><?= Loc::getMessage("SOA_ORDER_PS_ERROR") ?></span>
                                <?
                                }
                            }
                        }
                    }
                } else {
                    ?>
                    <p class="checkout__text"><?= $arPaySystem['NAME'] ?><span class="radio__text-small"><?= $arPaySystem['DESCRIPTION'] ?></span></p>
                <?
                }
                ?>
            
			<?/*
			if (strlen($arResult["PAY_SYSTEM"]["ACTION_FILE"]) > 0)
			{
				?>
				<tr>
					<td>
						<?
						$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($arResult["ORDER"]['PAY_SYSTEM_ID']);

						if ($arResult["PAY_SYSTEM"]["NEW_WINDOW"] == "Y")
						{
							?>
							<script language="JavaScript">
								window.open('<?=$arParams["PATH_TO_PAYMENT"]?>?ORDER_ID=<?=urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]))?>&PAYMENT_ID=<?=$arResult['ORDER']["PAYMENT_ID"]?>');
							</script>
							<?= GetMessage("SOA_TEMPL_PAY_LINK", Array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]))."&PAYMENT_ID=".$arResult['ORDER']["PAYMENT_ID"]))?>
							<?
							if (CSalePdf::isPdfAvailable() && $service->isAffordPdf())
							{
								?><br />
								<?= GetMessage("SOA_TEMPL_PAY_PDF", Array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]))."&PAYMENT_ID=".$arResult['ORDER']["PAYMENT_ID"]."&pdf=1&DOWNLOAD=Y")) ?>
								<?
							}
						}
						else
						{
							if ($service)
							{
								
								$order = \Bitrix\Sale\Order::load($arResult["ORDER_ID"]);

								
								$paymentCollection = $order->getPaymentCollection();

								
								foreach ($paymentCollection as $payment)
								{
									if (!$payment->isInner())
									{
										$context = \Bitrix\Main\Application::getInstance()->getContext();
										$service->initiatePay($payment, $context->getRequest());
										break;
									}
								}
							}
							else
							{
								echo '<span style="color:red;">'.GetMessage("SOA_TEMPL_ORDER_PS_ERROR").'</span>';
							}
						}
						?>
					</td>
				</tr>
				<?
			}*/
			?>
		<?
	}
}
else
{
	?>
	<b><?=GetMessage("SOA_TEMPL_ERROR_ORDER")?></b><br /><br />

	<table class="sale_order_full_table">
		<tr>
			<td>
				<?=GetMessage("SOA_TEMPL_ERROR_ORDER_LOST", Array("#ORDER_ID#" => $arResult["ACCOUNT_NUMBER"]))?>
				<?=GetMessage("SOA_TEMPL_ERROR_ORDER_LOST1")?>
			</td>
		</tr>
	</table>
	<?
}
?>
</div>