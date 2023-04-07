<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("CHK_EVENT", false);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\Web\Json;
use \Jamilco\Loyalty;
use \Jamilco\Main\Manzana;

Loader::includeModule('iblock');
Loader::includeModule('jamilco.loyalty');

global $USER;

$arResult = [
    'RESULT'  => 'ERROR',
    'SECURE'  => Loyalty\Log::getInstance()->checkSecure(),
    'MESSAGE' => '',
];

$request = Application::getInstance()->getContext()->getRequest();
if ($request->isPost()) {
    $action = $request->get('action');
    $card = $request->get('card');

    if ($action == 'checkItems') {
        $id = $request->get('id');

        $arItems = Manzana::getInstance()->getCheckItems($id);
        $arResult['RESULT'] = 'OK';
        $arResult['DATA'] = $arItems;

    } elseif ($action == 'checkCard') {

        // проверка карты
        $arData = Loyalty\Card::getClientData($card);
        if ($arData) {
            $arResult['RESULT'] = 'OK';
            $arResult['CARD'] = $arData['MASK'];
        }

    } elseif ($action == 'codeCard') {

        // отправка кода подтверждения
        $type = $request->get('type');
        Loyalty\Card::checkClientSend($card, $type);

        $arResult['RESULT'] = 'OK';

    } elseif ($action == 'confirmCard') {

        // проверка кода подтверждения
        $code = $request->get('code');
        if (Loyalty\Card::confirmCode($card, $code)) {
            $arResult['RESULT'] = 'OK';
        }

    } elseif ($action == 'deleteCard') {

        // удаление привязки к карте
        if ($USER->isAuthorized()) {
            $userId = $USER->GetID();
            $arUser = \CUser::GetByID($userId)->Fetch();

            $cUser = new \CUser;

            // если карта на самом деле привязана к текущему пользователю, то удалим привязку
            if ($arUser['UF_BONUS_CARD_NUMBER'] == $card) {
                $cUser->Update($userId, ['UF_BONUS_CARD_NUMBER' => '']);
            }
        }

    } elseif ($action == 'newCard') {

        // новая виртуальная карта
        $lastName = $request->get('last_name');
        $name = $request->get('name');
        $secondName = $request->get('second_name');
        $sex = $request->get('sex');
        $birthday = $request->get('birthday');
        $phone = $request->get('phone');
        $email = $request->get('email');

        $sex = ($sex == 'М') ? 'М' : 'Ж';
        $birthday = str_replace('/', '.', $birthday);

        $findCard = Loyalty\Card::findCard($phone, $email);

        if ($findCard) {
            $arResult['RESULT'] = 'find';
            $arResult['CARD'] = $findCard;
        } else {
            $_SESSION['NEW_CARD'] = [
                'last_name'   => $lastName,
                'name'        => $name,
                'second_name' => $secondName,
                'phone'       => $phone,
                'email'       => $email,
                'sex'         => $sex,
                'birthday'    => $birthday,
                'code'        => [
                    'phone' => Loyalty\Card::genCode(),
                    'email' => Loyalty\Card::genCode(),
                ]
            ];

            $arFio = [$lastName, $name, $secondName];
            TrimArr($arFio, true);

            Loyalty\Card::sendSms($phone, $_SESSION['NEW_CARD']['code']['phone']);
            Loyalty\Card::sendEmail(implode(' ', $arFio), $email, $_SESSION['NEW_CARD']['code']['email'], '', 'LOYALTY_EMAIL_CONFIRM');

            $arResult['RESULT'] = 'not_find';
            $arResult['PROPS'] = ['PHONE' => $phone, 'EMAIL' => $email];

            //$arResult['CODE'] = $_SESSION['NEW_CARD']['code'];
        }

    } elseif ($action == 'newCardCheck') {

        $phoneCode = $request->get('phone');
        $emailCode = $request->get('email');

        $arResult['ERROR'] = [];
        if ($_SESSION['NEW_CARD']['code']['phone'] != $phoneCode) $arResult['ERROR'][] = 'phone';
        if ($_SESSION['NEW_CARD']['code']['email'] != $emailCode) $arResult['ERROR'][] = 'email';
        if (!count($arResult['ERROR'])) {
            // создаем \ редактируем карту и привязываем ее, если она не привязана
            $cardResult = Loyalty\Card::createCard(
                $_SESSION['NEW_CARD']['last_name'],
                $_SESSION['NEW_CARD']['name'],
                $_SESSION['NEW_CARD']['second_name'],
                $_SESSION['NEW_CARD']['phone'],
                $_SESSION['NEW_CARD']['email'],
                $_SESSION['NEW_CARD']['birthday'],
                $_SESSION['NEW_CARD']['sex']
            );
            if ($cardResult['RESULT'] == 'OK') {
                $arResult['RESULT'] = 'OK';
                $card = $arResult['CARD'] = $cardResult['CARD'];
                Loyalty\Card::addCardToUser($card);
            } else {
                $arResult['RESULT'] = 'ERROR';
                $arResult['MESSAGE'] = $cardResult['MESSAGE'];
            }
        }

    } elseif ($action == 'changeCard') {

        // редактировать данные по карте
        if ($USER->isAuthorized()) {
            $userId = $USER->GetID();
            $arUser = \CUser::GetByID($userId)->Fetch();

            $cUser = new \CUser;

            $card = $arUser['UF_BONUS_CARD_NUMBER'];

            if ($card) {
                $lastName = $request->get('last_name');
                $name = $request->get('name');
                $secondName = $request->get('second_name');
                $phone = $request->get('phone');
                $email = $request->get('email');
                $birthday = $request->get('birthday');
                $sex = $request->get('sex');

                $sex = ($sex == 'М') ? 'М' : 'Ж';
                $birthday = str_replace('/', '.', $birthday);

                $arResult = Loyalty\Card::createCard($lastName, $name, $secondName, $phone, $email, $birthday, $sex, $card);

                unset($_SESSION['LOYALTY_CLIENT_DATA']);
            }
        }
    }
}

echo Json::encode($arResult);