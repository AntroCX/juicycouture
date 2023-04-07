<?
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

global $USER;

use Bitrix\Main,
    Bitrix\Main\Mail\Event;

define("NO_KEEP_STATISTIC", true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\loader::IncludeModule('sale');

@set_time_limit(0);
@ignore_user_abort(true);

$request = Main\Context::getCurrent()->getRequest()->toArray();

if(check_bitrix_sessid() && $request['account_number'] && !empty($request["user_email"]) && !empty($request["total_sum"]) && !empty($request["user_phone"])) {
    Event::SendImmediate(array(
        "EVENT_NAME" => "SALE_CALLBACK_NOTICE",
        "LID" => "s1",
        "C_FIELDS" => array(
            "USER_EMAIL" => $request['user_email'],
            "ACCOUNT_NUMBER" => $request['account_number'],
            "TOTAL_SUM" => $request['total_sum'],
            "USER_PHONE" => $request['user_phone']
        ),
    ));

    $order = \Bitrix\Sale\Order::loadByAccountNumber($request['account_number']);
    $arProps = $order->getPropertyCollection()->getArray();
    $arPropId = [];
    $arValues = [];
    foreach($arProps["properties"] as $arProp){
        if($arProp["CODE"] == "IS_CALLBACK" || $arProp["CODE"] == "IS_CALLBACK_DATE"){
            $arPropId[$arProp["CODE"]] = $arProp["ID"];
            $arValues[$arProp["CODE"]] = $arProp["VALUE"];
        }
    }

    $date = ConvertTimeStamp(strtotime(date('Y-m-d H:i:s', time())), 'FULL');
    $sendBy = $USER->GetEmail();
    foreach($arPropId as $propCode => $propId) {
        if($propCode == 'IS_CALLBACK') {
            $value = 'Y';
        }
        if($propCode == 'IS_CALLBACK_DATE') {
            $arValues[$propCode][] = $date." (".$sendBy.")";
            $value = $arValues[$propCode];
        }
        $propValue = $order->getPropertyCollection()->getItemByOrderPropertyId($propId);
        $propValue->setValue($value);
        $order->save();
    }

    echo "<b>Письмо отправлено:</b><br>".implode("<br>", $arValues["IS_CALLBACK_DATE"]);

}
else{
    echo "Ошибка. Неверные параметры запроса.";
}
