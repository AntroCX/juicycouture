<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

global $DB;
use \Bitrix\Main,
    \Bitrix\Sale;

Main\Loader::IncludeModule('sale');

if(intval($_REQUEST['step'])>0 )
    $step = intval($_REQUEST['step']);
else
    $step = 1;

$max = 1000;

$arSelect = array("ID", "ACCOUNT_NUMBER", "DATE_INSERT_FORMAT", "DELIVERY_ID");

$arRuntime = [];
$dbProperties = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('select' => array('CODE')));
while ($arProperty = $dbProperties->fetch()) {
    $sCode = $arProperty['CODE'];
    $arSelect["PROPERTY_{$sCode}_VALUE"] = "PROPERTY_{$sCode}.VALUE";
    $arRuntime["PROPERTY_{$sCode}"] = new \Bitrix\Main\Entity\ReferenceField("PROPERTY_{$sCode}",
     '\Bitrix\Sale\Internals\OrderPropsValueTable', array(
         '=this.ID' => 'ref.ORDER_ID',
         '=ref.CODE' => new Main\DB\SqlExpression('?', $sCode),
     ));
}

$sort = array("DATE_INSERT" => "ASC");
$filter = array(
    ">=DATE_INSERT" => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), mktime(0, 0, 0, date("m"), date('d'), date("Y")-1)),
    "STATUS_ID" => "F",
    //"PAYED" => "Y",
);

$dbOrders = Sale\Internals\OrderTable::getList(array(
    'order' => $sort,
    'filter' => $filter,
    'select' => $arSelect,
    'limit' => $max,
    'offset' => ($step-1)*$max,
    'runtime' => $arRuntime
)
);

$arOrders = [];
while ($arOrder = $dbOrders->fetch()) {
    $arOrders[$arOrder["ID"]] = array(
        "ID"                 => $arOrder['ID'],
        "ACCOUNT_NUMBER"     => $arOrder['ACCOUNT_NUMBER'],
        "DATE_INSERT_FORMAT" => $arOrder['DATE_INSERT_FORMAT'],
        "USER_NAME"          => $arOrder['PROPERTY_NAME_VALUE']." ".$arOrder['PROPERTY_LAST_NAME_VALUE'],
        "USER_EMAIL"         => $arOrder['PROPERTY_EMAIL_VALUE'],
        "OMNI_CHANNEL"       => $arOrder['PROPERTY_OMNI_CHANNEL_VALUE'],
        "DELIVERY_ID"        => $arOrder['DELIVERY_ID']
    );
}
unset($arOrder);

if(empty($arOrders)){
    echo "all done.";
    die();
}
else{
    echo 'step: '.$step;
}

// output file
if($step == 1) {
    $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/upload/ot4et.csv', 'w');
    fputcsv($fp, array("ACCOUNT_NUMBER", "ITEMS", "QUANTITY", "PRICE","DISCOUNT", "DATE_INSERT", "USER_NAME", "USER_EMAIL", "COUPON", "DELIVERY"), chr(9));
}
else
    $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/upload/ot4et.csv', 'a');

$arOrdersFinal = [];
foreach ($arOrders as $orderId => $arOrder) {
    $basePrice = '';
    $price = '';
    $basketItems = [];

    $order = Sale\Order::load($arOrder['ID']);

    // find coupon
    $discountData = $order->getDiscount()->getApplyResult();
    $coupon = '';
    if (!empty($discountData['COUPON_LIST'])) {
        $coupon = end($discountData['COUPON_LIST'])['COUPON'];
    }

    if ($coupon) {
        $delivery = '';
        if(!empty($arOrder["OMNI_CHANNEL"])){
            $delivery = $arOrder["OMNI_CHANNEL"];
        }
        else {
            switch ($arOrder["DELIVERY_ID"]) {
                case 'ocs:ocs':
                    $delivery = 'courier';
                    break;
                case 'new30:profile':
                    $delivery = 'ozon';
                    break;
            }
        }
        if(empty($delivery)){
            $arDelivery = CSaleDelivery::GetByID($arOrder["DELIVERY_ID"]);
            if(!empty($arDelivery["NAME"]))
                $delivery = $arDelivery["NAME"];
            else
                $delivery = $arOrder["DELIVERY_ID"];
        }

        $basket = $order->getBasket();
        $price = $basket->getPrice();
        $basePrice = $basket->getBasePrice();
        $basketItems = [];
        foreach ($basket as $basketItem) {
            $basketItems[] = array(
                "NAME"     => $basketItem->getField('NAME'),
                "QUANTITY" => $basketItem->getQuantity()
            );
        }

        $arOrdersFinal[$orderId] = array_merge($arOrders[$orderId], array(
            "COUPON"             => $coupon,
            "PRICE"              => (int)$price,
            "DISCOUNT"           => (int)$basePrice - (int)$price,
            "BASKET"             => $basketItems,
            "DELIVERY"           => $delivery
            )
        );
    }

}

foreach ($arOrdersFinal as $arOrder){
    fputcsv($fp, array($arOrder["ACCOUNT_NUMBER"], '', '', $arOrder["PRICE"], $arOrder["DISCOUNT"], $arOrder["DATE_INSERT_FORMAT"],$arOrder["USER_NAME"],$arOrder["USER_EMAIL"],$arOrder["COUPON"], $arOrder["DELIVERY"]), chr(9));

    foreach ($arOrder["BASKET"] as $key => $basketItem){
        fputcsv($fp, array('', $basketItem["NAME"], $basketItem["QUANTITY"]), chr(9));
    }
}
fclose($fp);
?>
<html>
<form name="restore" id="restore" action="<?=$_SERVER['PHP_SELF']?>" enctype="multipart/form-data" method="POST" onsubmit="this.action='<?=$_SERVER['PHP_SELF']?>?step=<?=($step+1)?>'">
    <script>
      function reloadPage(val, delay)
      {
        document.getElementById('restore').action='<?= $_SERVER['PHP_SELF']?>?step=' + val;
        if (null!=delay)
          window.setTimeout("document.getElementById('restore').submit()",1000);
        else
          document.getElementById('restore').submit();
      }
    </script>
</form>
</html>
<script>reloadPage(<?=($step+1)?>, 1);</script>
<?die();?>