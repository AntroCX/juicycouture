<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("CHK_EVENT", false);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\UserTable;
use \Bitrix\Main\Web\Json;
use \Jamilco\Main\Manzana;
use \Jamilco\Loyalty\Card;
use \Jamilco\Loyalty\Log;
use Juicycouture\Google\ReCaptcha\ReCaptcha;

Loader::includeModule('jamilco.main');
Loader::includeModule('jamilco.loyalty');

global $USER;

$arResult = [
    'RESULT'  => 'ERROR',
    'SECURE'  => Log::getInstance()->checkSecure(),
    'MESSAGE' => '',
];

$request = Application::getInstance()->getContext()->getRequest();
if ($request->isPost() && check_bitrix_sessid()) {
    $action = $request->get('action');
    $card = $request->get('cardNumber');

    if ($action && $card) {
        // форма "подтвердите емейл и телефон"
        if ($action == 'confirm') {
            $arOut = Manzana::getInstance()->checkCardPin(
                $card,
                '0000'
            ); // используется заглушка для пин-кода, т.к. карта должна быть не принадлежащей контакту
            if ($arOut['FIELD'] == 'CONTACT' && $_SESSION['CARD_DATA'][$card]) {
                $phoneCode = $request->get('cardConfirmPhone');
                $emailCode = $request->get('cardConfirmEmail');

                $arResult['ERROR'] = [];
                if ($_SESSION['CARD_DATA'][$card]['code']['phone'] != $phoneCode) $arResult['ERROR'][] = 'phone';
                if ($_SESSION['CARD_DATA'][$card]['code']['email'] != $emailCode) $arResult['ERROR'][] = 'email';

                if (count($arResult['ERROR'])) {

                } else {
                    // код валидации верен, создаем контакт в Манзане и привязываем к нему карту

                    $phone = $_SESSION['CARD_DATA'][$card]['phone'];
                    $email = $_SESSION['CARD_DATA'][$card]['email'];

                    // контакт
                    // поищем уже созданный контакт на указанные емейл \ телефон
                    $arContacts = Manzana::getInstance()->findContact($phone, $email);
                    // подойдет даже тот контакт, в котором хоть одно совпадает - либо емейл, либо телефон
                    if (!$arContacts[0]['Id']) $arContacts = Manzana::getInstance()->findContact($phone, '');
                    if (!$arContacts[0]['Id']) $arContacts = Manzana::getInstance()->findContact('', $email);

                    $contactId = $arContacts[0]['Id'];

                    if (!$contactId) {
                        // создадим новый контакт

                        $birthday = MakeTimeStamp($_SESSION['CARD_DATA'][$card]['birthday'], 'DD.MM.YYYY');

                        $arData = Manzana::getInstance()->contactAdd(
                            $_SESSION['CARD_DATA'][$card]['last_name'],
                            $_SESSION['CARD_DATA'][$card]['name'],
                            $_SESSION['CARD_DATA'][$card]['second_name'],
                            ($_SESSION['CARD_DATA'][$card]['sex'] == 'Ж') ? 'F' : 'M',
                            date('Y-m-d', $birthday),
                            $email,
                            $phone
                        );
                        if ($arData['RESULT'] == 'ERROR') {
                            $arResult['RESULT'] = 'ERROR';
                            $arResult['MESSAGE'] = $arData['MESSAGE'];
                        } else {
                            $contactId = $arData['VALUE'];
                        }
                    }

                    if ($contactId) {
                        // контакт найден, запустим процесс отправки формы пин-кода заново
                        $_SESSION['CARD_DATA'][$card]['contactId'] = $contactId;
                        $arResult['RESULT'] = 'OK';
                    }
                }
            }
        }

        // форма "карта не активирована"
        if ($action == 'contact') {
            // перепроверим карту
            $arOut = Manzana::getInstance()->checkCardPin(
                $card,
                '0000'
            ); // используется заглушка для пин-кода, т.к. карта должна быть не принадлежащей контакту
            if ($arOut['FIELD'] == 'CONTACT') {
                $lastName = $request->get('cardContactLastName');
                $name = $request->get('cardContactName');
                $secondName = $request->get('cardContactSecondName');
                $phone = $request->get('cardContactPhone');
                $email = $request->get('cardContactEmail');
                $sex = $request->get('cardContactSex');
                $birthday = $request->get('cardContactBirthday');

                $sex = ($sex == 'М') ? 'М' : 'Ж';
                $birthday = str_replace('/', '.', $birthday);

                $_SESSION['CARD_DATA'][$card] = [
                    'last_name'   => $lastName,
                    'name'        => $name,
                    'second_name' => $secondName,
                    'phone'       => $phone,
                    'email'       => $email,
                    'sex'         => $sex,
                    'birthday'    => $birthday,
                    'code'        => [
                        'phone' => Card::genCode(),
                        'email' => Card::genCode(),
                    ]
                ];

                $arFio = [$lastName, $name, $secondName];
                TrimArr($arFio, true);

                Card::sendSms($phone, $_SESSION['CARD_DATA'][$card]['code']['phone']);
                Card::sendEmail(implode(' ', $arFio), $email, $_SESSION['CARD_DATA'][$card]['code']['email'], '', 'LOYALTY_EMAIL_CONFIRM');

                $arResult['RESULT'] = 'SEND';
                $arResult['PROPS'] = ['PHONE' => $phone, 'EMAIL' => $email];
            }
        }

        // форма "в карте не указан емейл", введите его
        if ($action == 'email') {
            $email = $request->get('cardEmail');
            $pass = $request->get('cardEmailPass');

            $arContact = $_SESSION['CARD_CONTACT'][$card];

            if ($arContact && !$arContact['EMAIL']) {
                if ($pass > '') {
                    // попробуем авторизовать пользователя по введённому емейлу и паролю

                    if (!is_object($USER)) $USER = new \CUser();
                    $arAuthResult = $USER->Login($email, $pass, "Y");
                    if ($arAuthResult === true) {
                        // привяжем карту
                        Card::addCardToUser($card, $USER->GetID());

                        $arResult['RELOAD'] = 'Y';
                    } else {
                        $arResult['FIELD'] = 'PASS';
                    }
                } else {
                    $us = UserTable::getList(
                        [
                            'filter' => ['LOGIN' => $email],
                            'select' => ['ID', 'UF_BONUS_CARD_NUMBER'],
                            'limit'  => 1
                        ]
                    );
                    if ($arUser = $us->Fetch()) {
                        // аккаунт уже есть, надо вернуть форму с просьбой ввести пароль

                        $arResult['FIELD'] = 'EXIST';

                    } else {
                        // создадим новый аккаунт, привяжем к нему карту и авторизуем пользователя

                        $password = randString(8);
                        $group = \COption::GetOptionString('main', 'new_user_registration_def_group');

                        $arResult['CONTACT'] = $arContact;

                        $us = new \CUser;
                        $userId = $us->Add(
                            [
                                'LOGIN'            => $email,
                                'EMAIL'            => $email,
                                'NAME'             => $arResult['CONTACT']['NAME'],
                                'LAST_NAME'        => $arResult['CONTACT']['LAST_NAME'],
                                'PERSONAL_MOBILE'  => $arResult['CONTACT']['PHONE'],
                                'PASSWORD'         => $password,
                                'CONFIRM_PASSWORD' => $password,
                                'ACTIVE'           => 'Y',
                                'GROUP_ID'         => explode(',', $group),
                            ]
                        );

                        Card::addCardToUser($card, $userId);

                        $USER->Authorize($userId);

                        $arResult['RESULT'] = 'OK';
                        $arResult['RELOAD'] = 'Y';
                    }
                }
            }
        }

        // форма ввода карты и пинкода
        if ($action == 'card') {
            try {
                (new ReCaptcha($request['TOKEN_CAPTCHA']))->verify();
            } catch (\Exception $e) {
                echo Json::encode([
                    'RESULT' => 'ERROR',
                    'FIELD' => '',
                    'TEXT' => $e->getMessage(),
                ]);
                die;
            }
            $pin = $request->get('pinCode');

            $contactId = $_SESSION['CARD_DATA'][$card]['contactId']; // если здесь есть контакт, значит он был выпущен заново манзаной

            $arOut = Manzana::getInstance()->checkCardPin($card, $pin, $contactId);

            if ($arOut['RESULT'] == 'OK') {
                $arResult['RESULT'] = 'OK';
                if ($contactId) {
                    // контактные данные уже были введены пользователем
                    $arResult['CONTACT'] = [
                        'NAME'      => $_SESSION['CARD_DATA'][$card]['name'],
                        'LAST_NAME' => $_SESSION['CARD_DATA'][$card]['last_name'],
                        'PHONE'     => $_SESSION['CARD_DATA'][$card]['phone'],
                        'EMAIL'     => $_SESSION['CARD_DATA'][$card]['email'],
                        'SEX'       => $_SESSION['CARD_DATA'][$card]['sex'],
                        'BIRTHDAY'  => $_SESSION['CARD_DATA'][$card]['birthday'],
                    ];
                } else {
                    $arResult['CONTACT'] = [
                        'NAME'      => $arOut['CONTACT']['FirstName'],
                        'LAST_NAME' => $arOut['CONTACT']['LastName'],
                        'PHONE'     => $arOut['CONTACT']['MobilePhone'],
                        'EMAIL'     => $arOut['CONTACT']['EmailAddress'],
                        'SEX'       => $_SESSION['CARD_DATA'][$card]['sex'],
                        'BIRTHDAY'  => $_SESSION['CARD_DATA'][$card]['birthday'],
                    ];
                }

                $_SESSION['CARD_CONTACT'][$card] = $arResult['CONTACT'];

                if ($arResult['CONTACT']['EMAIL']) {
                    $us = UserTable::getList(
                        [
                            'filter' => ['EMAIL' => $arResult['CONTACT']['EMAIL']],
                            'select' => ['ID', 'UF_BONUS_CARD_NUMBER'],
                            'limit'  => 1
                        ]
                    );
                    if ($arUser = $us->Fetch()) {
                        // привяжем карту к найденному аккаунту
                        if ($arUser['UF_BONUS_CARD_NUMBER'] != $card) Card::addCardToUser($card, $arUser['ID']);

                        $USER->Authorize($arUser['ID']);

                        $arResult['RELOAD'] = 'Y';
                    } else {
                        // создадим новый аккаунт, привяжем к нему карту и авторизуем пользователя

                        $password = randString(8);
                        $group = \COption::GetOptionString('main', 'new_user_registration_def_group');

                        $us = new \CUser;
                        $userId = $us->Add(
                            [
                                'LOGIN'            => $arResult['CONTACT']['EMAIL'],
                                'EMAIL'            => $arResult['CONTACT']['EMAIL'],
                                'NAME'             => $arResult['CONTACT']['NAME'],
                                'LAST_NAME'        => $arResult['CONTACT']['LAST_NAME'],
                                'PERSONAL_MOBILE'  => $arResult['CONTACT']['PHONE'],
                                'PASSWORD'         => $password,
                                'CONFIRM_PASSWORD' => $password,
                                'ACTIVE'           => 'Y',
                                'GROUP_ID'         => explode(',', $group),
                            ]
                        );

                        Card::addCardToUser($card, $userId);

                        $USER->Authorize($userId);

                        $arResult['RELOAD'] = 'Y';
                    }
                } else {
                    $arResult['RESULT'] = 'NOT_EMAIL';
                }

            } else {
                $arResult['FIELD'] = $arOut['FIELD'];
            }
        }
    }
}
global $ddlEvents;
if($ddlEvents){
    ob_end_clean();
    foreach ($ddlEvents as $v) {
        $arResult['ddlEvents'][] = $v;
    }
}
echo Json::encode($arResult);