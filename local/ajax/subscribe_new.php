<?php
/**
 * Обработчик для двух форм
 *  - подписка в шапке сайта
 *  - функционал ЛК пользователя
 */
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('BX_NO_ACCELERATOR_RESET', true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Context,
    Bitrix\Main\Loader,
    \Jamilco\Main\Subscribers;
use Juicycouture\Google\ReCaptcha\ReCaptcha;

Loader::includeModule("iblock");
Loader::IncludeModule("jamilco.main");

global $USER;
$request = Context::getCurrent()->getRequest();
$userId = $request["ID"];
$mail = $request["EMAIL"];
$action = $request["ACTION"];
$uid = $request["UID"];
$comment = $request["COMMENT"];

$arResponse = array(
    'MESSAGE' => '',
    'RESULT' => 'E',
);

if (!empty($request) && !empty($mail)) {

    try {
        (new ReCaptcha($request['TOKEN_CAPTCHA']))->verify();
        $userSubscriber = Subscribers::getInstance()->checkSubcscribers($mail);

        if ($action == 'subscribe') {
            if ($userSubscriber['STATUS'] == 'NEW')
                $arResponse = Subscribers::getInstance()->setSubcscribers($mail, "Y");
            elseif ($userSubscriber['STATUS'] == 'NO' || $userSubscriber['STATUS'] == 'YES')
                $arResponse = Subscribers::getInstance()->setSubcscribers($mail);

        } elseif ($action == 'unsubscribe' && $userSubscriber['STATUS'] == 'YES') {
            $arResponse = Subscribers::getInstance()->unsetSubcscribers($mail, $comment);
        }
    } catch (\Exception $e) {
        $arResponse['MESSAGE'] = $e->getMessage();
    }
}

echo json_encode($arResponse);