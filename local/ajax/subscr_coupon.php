<?
use Jamilco\Delivery\Coupon;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('iblock');
CModule::IncludeModule('sale');
CModule::IncludeModule('subscribe');
CModule::IncludeModule('jamilco.delivery');

global $USER;

$arResponse = array(
    'EXIST'   => false,
    'MESSAGE' => '',
    'RESULT'  => 'error',
);
if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $email = htmlspecialchars($_POST["email"]);

    // проверим пользователя
    $us = CUser::GetList($by = 'ID', $order = 'ASC', array('EMAIL' => $email));
    if ($arUser = $us->Fetch()) {
        $arResponse['EXIST'] = true;
        $arResponse['MESSAGE'] = 'Воспользоваться предложением могут только новые пользователи сайта!';
    }

    // проверим подписчика
    if (!$arResponse['EXIST']) {
        $subscr = CSubscription::GetList(array("ID" => "ASC"), array("EMAIL" => $email));
        if (($subscr_arr = $subscr->Fetch())) {
            $arResponse['EXIST'] = true;
            $arResponse['MESSAGE'] = 'Указанный e-mail уже подписан на рассылку!';
        }
    }

    // проверим элемент инфоблока
    if (!$arResponse['EXIST']) {
        $res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => SUBSCR_COUPON_IBLOCK_ID, "NAME" => $email), false, array("nPageSize" => 1), array('ID'));
        if ($arFields = $res->Fetch()) {
            $arResponse['EXIST'] = true;
            $arResponse['MESSAGE'] = 'Указанный e-mail уже подписан на рассылку!';
        }
    }

    if (!$arResponse['EXIST']) {
        // добавляем в список подписчиков
        $RUB_ID = array();
        $rub = CRubric::GetList(array(), array("ACTIVE" => "Y", "LID" => SITE_ID));
        while ($arRubric = $rub->GetNext()) {
            $RUB_ID[] = $arRubric['ID'];
        }

        $arFields = array
        (
            "USER_ID"      => ($USER->IsAuthorized() ? $USER->GetID() : false),
            "FORMAT"       => "html",
            "EMAIL"        => $email,
            "ACTIVE"       => "Y",
            "RUB_ID"       => $RUB_ID,
            "SEND_CONFIRM" => "N"
        );

        $subscr = new CSubscription;
        $idsubrscr = $subscr->Add($arFields);
        define("SUBSCR_COUPON_IBLOCK_ID", 21);
        // добавляем в инфоблок
        $oElement = new CIBlockElement();
        if ($idElement = $oElement->Add(
            array(
                "ACTIVE"           => "Y",
                "IBLOCK_ID"        => SUBSCR_COUPON_IBLOCK_ID,
                "NAME"             => $email,
                "DATE_ACTIVE_FROM" => ConvertTimeStamp(false, 'FULL'),
            )
        )
        ) {
            $arResponse["RESULT"] = 'ok';

            // отправим письмо с купоном
            Coupon::sendCoupon($email);
        }
    }

} else {
    $arResponse['MESSAGE'] = 'Неверно введен e-mail';
}

echo json_encode($arResponse);

?>