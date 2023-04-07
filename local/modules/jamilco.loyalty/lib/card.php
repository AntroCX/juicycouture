<?

namespace Jamilco\Loyalty;

use \Bitrix\Main\Data\Cache;
use \Jamilco\Main\Oracle;
use \Jamilco\Main\Manzana;
use \Jamilco\Loyalty\Log;

class Card
{
    /**
     * WOLFORD, New Balance, MARC O'POLO, Timberland, LEE COOPER, Juicy Couture, DKNY,DKNY ACCESSORIES
     */
    const SMS_BRAND = 'Juicy Couture';
    static $smsText = '#CODE# - код подтверждения';

    /**
     * возвращает баланс карты
     *
     * @param int  $number     - номер карты
     * @param bool $returnFull - вернуть все данные по карте (а не только положительный остаток)
     *
     * @return mixed
     */
    public static function getBalance($number = 0, $returnFull = false)
    {
        // проверка безопасности
        if (Log::getInstance()->checkSecure()) return false;

        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0);

        // кешируем запрос баланса на пять минут
        $cacheTime = 60 * 5; // 5 минут
        $cachePath = '/s1/card/balance/';
        $cacheId = [
            SITE_ID,
            LANGUAGE_ID,
            $manzanaUse,
            'card',
            'balance',
            $number
        ];
        $сache = Cache::createInstance();
        if ($сache->initCache($cacheTime, implode('|', $cacheId), $cachePath)) {
            $arResult = $сache->getVars();
        } elseif ($сache->startDataCache()) {
            $arResult = [];

            if ($manzanaUse == 1) {
                $arData = Manzana::getInstance()->cardBalance($number);
                if ($arData['RESULT'] == 'OK' && !Manzana::getInstance()->checkCardType($number)) {
                    $arData['RESULT'] = 'ERROR';
                }
                if ($arData['RESULT'] == 'OK') {
                    $arResult['AVAILABLE'] = (int)$arData['DATA']['CardActiveBalance'];         // баланс активных баллов
                    $arResult['UNCONFIRMED'] = (int)$arData['DATA']['CardUnconfirmBalance'];    // баланс неподтвержденных бонусов
                    $arResult['USED'] = (int)$arData['DATA']['CardSumm'];                       // баланс использованных бонусов
                }
            } else {
                $query = "SELECT available_bonus_points(".$number."), unconfirmed_bonus_points(".$number."), used_bonus_points(".$number.") FROM dual";
                $arData = Oracle::getInstance()->getQuery($query);
                foreach ($arData['result'] as $key => $data) {
                    if (substr_count($key, 'AVAILABLE_BONUS_POINTS')) $arResult['AVAILABLE'] = (int)$data;      // баланс активных баллов
                    if (substr_count($key, 'UNCONFIRMED_BONUS_POINTS')) $arResult['UNCONFIRMED'] = (int)$data;  // баланс неподтвержденных бонусов
                    if (substr_count($key, 'USED_BONUS_POINTS')) $arResult['USED'] = (int)$data;                // баланс использованных бонусов
                }
            }

            Log::getInstance()->addLog($number, 'balance', $arResult['AVAILABLE']);

            $сache->endDataCache($arResult);
        }

        return ($returnFull) ? $arResult : $arResult['AVAILABLE'];
    }

    /**
     * возвращает данные клиента по номеру карты
     *
     * @param int  $number - номер карты
     * @param bool $apply  - бонусы уже списаны из корзины, значит карта уже была подтверждена
     *
     * @return mixed
     */
    public static function getClientData($number = 0, $apply = false, $skipSaved = false, $fullData = false)
    {
        unset($_SESSION['LOYALTY_CARD_NUMBER']);

        // проверка безопасности
        if (Log::getInstance()->checkSecure()) return false;

        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0);

        if (!array_key_exists($number, $_SESSION['LOYALTY_CLIENT_DATA']) || $skipSaved || $fullData) {
            if ($manzanaUse) {
                if ($fullData) {
                    $arData = Manzana::getInstance()->findContact('', '', $number, false);
                    $cardBrand = ''; // сюда будет записан бренд карты
                    if ($arData[0]['Id'] && !Manzana::getInstance()->checkCardType($number, $cardBrand)) {
                        unset($arData[0]);
                    }
                    if ($arData[0]['Id']) {
                        $arContact = $arData[0];

                        $arResult['FIO'] = [$arContact['LastName'], $arContact['FirstName'], $arContact['MiddleName']];
                        TrimArr($arResult['FIO'], true);
                        $arResult['FIO'] = implode(' ', $arResult['FIO']);
                        $arResult['CARD'] = $number;
                        $arResult['PHONE'] = trim($arContact['MobilePhone']);
                        $arResult['EMAIL'] = trim($arContact['EmailAddress']);
                        $arResult['BIRTHDAY'] = MakeTimeStamp($arContact['BirthDate'], 'YYYY-MM-DD');
                        $arResult['BIRTHDAY'] = date('d.m.Y', $arResult['BIRTHDAY']);
                        $arResult['SEX'] = ($arContact['GenderCode'] == 1) ? 'М' : 'Ж';
                    } else {
                        Log::getInstance()->addLog($number, 'client', 'not_found');

                        return false;
                    }
                } else {
                    $arData = Manzana::getInstance()->cardBalance($number);
                    $cardBrand = ''; // сюда будет записан бренд карты
                    if ($arData['RESULT'] == 'OK' && !Manzana::getInstance()->checkCardType($number, $cardBrand)) {
                        $arData['RESULT'] = 'ERROR';
                    }
                    if ($arData['RESULT'] == 'OK') {
                        $arContact = $arData['DATA']['CONTACT'];
                        $arResult['FIO'] = [$arContact['LastName'], $arContact['FirstName'], $arContact['MiddleName']];
                        TrimArr($arResult['FIO'], true);
                        $arResult['FIO'] = implode(' ', $arResult['FIO']);
                        $arResult['CARD'] = $number;
                        $arResult['PHONE'] = trim($arContact['Phone']);
                        $arResult['EMAIL'] = trim($arContact['Email']);
                        //$arResult['BIRTHDAY'] = trim(str_replace('Дата рождения', '', $one));
                        //$arResult['SEX'] = trim(str_replace('Пол', '', $one));
                    } else {
                        Log::getInstance()->addLog($number, 'client', 'not_found');

                        return false;
                    }
                }
            } else {
                $query = "SELECT website.get_client_anketa(param5 => '$number') FROM dual";
                $arData = Oracle::getInstance()->getQuery($query);
                $arClient = [];
                foreach ($arData['result'] as $result) {
                    $arClient = $result;
                    break;
                }
                $arClient = preg_split('/\\r\\n?|\\n/', $arClient);
                if (!$arClient || $arClient[0] == 'Нет данных') {
                    Log::getInstance()->addLog($number, 'client', 'not_found');

                    return false;
                }

                $arResult = [];
                foreach ($arClient as $one) {
                    if (substr_count($one, 'ФИО')) $arResult['FIO'] = trim(str_replace('ФИО', '', $one));
                    if (substr_count($one, 'Номер карты')) $arResult['CARD'] = trim(str_replace('Номер карты', '', $one));
                    if (substr_count($one, 'Телефон')) $arResult['PHONE'] = trim(str_replace('Телефон', '', $one));
                    if (substr_count($one, 'Дата рождения')) $arResult['BIRTHDAY'] = trim(str_replace('Дата рождения', '', $one));
                    if (substr_count($one, 'E-mail')) $arResult['EMAIL'] = trim(str_replace('E-mail', '', $one));
                    if (substr_count($one, 'Пол')) $arResult['SEX'] = trim(str_replace('Пол', '', $one));
                }
            }

            if ($arResult['PHONE']) $arResult['PHONE'] = self::MakePhoneNumber($arResult['PHONE']);
            $arResult['MASK'] = self::getMasked($arResult['PHONE'], $arResult['EMAIL']);

            Log::getInstance()->addLog($number, 'client', 'found');

            if ($skipSaved) {
                foreach ($arResult as $key => $val) {
                    $_SESSION['LOYALTY_CLIENT_DATA'][$number][$key] = $val;
                }
            } else {
                $_SESSION['LOYALTY_CLIENT_DATA'][$number] = $arResult;
                $apply = false; // карта только что была запрошена, она не может быть подтверждена
            }
        }
        if ($apply) $_SESSION['LOYALTY_CLIENT_DATA'][$number]['CONFIRM'] = 'Y';

        $_SESSION['LOYALTY_CARD_NUMBER'] = $number; // последний валидный ответ о клиенте - есть последняя введенная карта

        return $_SESSION['LOYALTY_CLIENT_DATA'][$number];
    }

    /**
     * проверка клиента: отсылаем письмо или смс
     *
     * @param int    $number - номер карты
     * @param string $type   - [phone | email]
     *
     * @return bool
     */
    public static function checkClientSend($number = 0, $type = 'phone')
    {
        if (!$number) $number = $_SESSION['LOYALTY_CARD_NUMBER'];
        if (!$number) return false;

        Log::getInstance()->addLog($number, 'sendCode', $type);

        $arData = self::getClientData($number);
        $code = self::generateSecureCode($number);
        if ($type == 'phone' && $arData['PHONE'] > '') {
            return self::sendSms($arData['PHONE'], $code);
        } elseif ($type == 'email' && $arData['EMAIL'] > '') {
            $arData['FIO'] = (is_array($arData['FIO'])) ? implode(' ', $arData['FIO']) : $arData['FIO'];

            return self::sendEmail($arData['FIO'], $arData['EMAIL'], $code, $number);
        }

        return false;
    }

    /**
     * проверяет введенный код
     *
     * @param int    $number
     * @param string $code
     *
     * @return bool
     */
    public static function confirmCode($number = 0, $code = '', $addToUser = true)
    {
        if (!$number) $number = $_SESSION['LOYALTY_CARD_NUMBER'];
        if (!$number) return false;
        $arData = self::getClientData($number);
        if ($arData['CODE'] == $code) {
            $_SESSION['LOYALTY_CLIENT_DATA'][$number]['CONFIRM'] = 'Y';

            if ($addToUser) self::addCardToUser($number);

            return true;
        }

        return false;
    }

    /**
     * сохраняем карту в свойства пользователя
     *
     * @param int $number
     */
    public static function addCardToUser($number = '', $userId = 0)
    {
        global $USER;
        if (!$userId && !$USER->isAuthorized()) return false;

        $number = trim($number);
        if (!$number) return false;

        $cUser = new \CUser;

        // уберём карту из профилей других пользователей
        $us = \CUser::GetList(
            $by = 'ID',
            $order = 'ASC',
            ['UF_BONUS_CARD_NUMBER' => $number],
            ['FIELDS' => ['ID']]
        );
        while ($arUser = $us->Fetch()) {
            $cUser->Update($arUser['ID'], ['UF_BONUS_CARD_NUMBER' => '']);
        }

        // привяжем к указанному \ текущему пользователю
        if (!$userId) $userId = $USER->GetID();

        $arUser = \CUser::GetByID($userId)->Fetch();
        if (!$arUser['UF_BONUS_CARD_NUMBER']) {
            $cUser->Update($userId, ['UF_BONUS_CARD_NUMBER' => $number]);
        }
    }

    /**
     * генерируем секретный проверочный код для карты и сохраняем его в сессию
     *
     * @param int $number
     *
     * @return int
     */
    public static function generateSecureCode($number = 0)
    {
        // если код по карте уже был сгенерирован, то не генерируем новый
        if ($_SESSION['LOYALTY_CLIENT_DATA'][$number]['CODE']) {
            $code = $_SESSION['LOYALTY_CLIENT_DATA'][$number]['CODE'];
        } else {
            // генерируем новый код
            $code = self::genCode();

            // сохраняем код в сессию для последующей проверки
            $_SESSION['LOYALTY_CLIENT_DATA'][$number]['CODE'] = $code;
        }

        return $code;
    }

    /**
     * генерация кода
     *
     * @param int    $len
     * @param string $symbols
     *
     * @return mixed
     */
    public static function genCode($len = 5, $symbols = '0123456789')
    {
        $code = randString($len, $symbols);

        return $code;
    }

    /**
     * приводит телефонный номер к единому формату
     *
     * @param $phone
     *
     * @return string
     */
    public static function MakePhoneNumber($phone)
    {
        $result = preg_match_all('/\d/', $phone, $found);
        $res = implode('', $found[0]);
        if (($found[0][0] == '7' || $found[0][0] == '8') && strlen($res) >= '11' && $found[0][1] != 0) {
            $phone = '7'.substr($res, 1, 10);
        } elseif (($found[0][0].$found[0][1] == '80') && strlen($res) >= '11') {
            $phone = '38'.substr($res, 1, 10);
        } elseif (($found[0][0].$found[0][1].$found[0][2] == '380') && strlen($res) >= '12') {
            $phone = '380'.substr($res, 3, 9);
        } elseif (($found[0][0].$found[0][1].$found[0][2] == '375') && strlen($res) >= '12') {
            $phone = '375'.substr($res, 3, 9);
        } elseif (strlen($res) == '10' && $res{0} == 0) {
            $phone = '38'.$res;
        } elseif (strlen($res) == '9') {
            $phone = '375'.$res;
        } elseif (strlen($res) == '10') {
            $phone = '7'.$res;
        } elseif (strlen($res) == '14') {
            $phone = $res;
        } else {
            $phone = '';
        }

        return $phone;
    }

    /**
     * маскирует телефон и емейл
     *
     * @param string $phone
     * @param string $email
     *
     * @return array
     */
    public static function getMasked($phone = '', $email = '')
    {
        $arResult = [
            'PHONE' => $phone,
            'EMAIL' => $email,
        ];
        if ($phone > '') {
            $phone = self::MakePhoneNumber($phone);
            if (strlen($phone) == 11 && substr($phone, 0, 1) == 7) {
                // 79000000000 -> +7(***)***-00-00
                $arResult['PHONE'] = '+7(***)***-'.substr($phone, 7, 2).'-'.substr($phone, 9, 2);
            }
            if (strlen($phone) == 12 && (substr($phone, 0, 3) == 380 || substr($phone, 0, 3) == 375)) {
                // 375000000000, 380000000000 -> +380(**)***-00-00
                $arResult['PHONE'] = '+'.substr($phone, 0, 3).'(**)***-'.substr($phone, 8, 2).'-'.substr($phone, 10, 2);
            }
        }

        if ($email > '') {
            /**
             * делим емейл на две части по @
             * в каждой части оставляем по половине символов (но не больше трех (без учета доменной зоны))
             */

            $arEmail = explode('@', $email);
            $arMaskEmail = [];
            foreach ($arEmail as $key => $val) {
                $max = ($key == 1) ? 6 : 3; // максимальное количество видимых символов
                if ($key == 0) {
                    $count = ceil(strlen($val) / 2); // количество видимых символов
                    if ($count > $max) $count = $max;
                    $arMaskEmail[] = substr($val, 0, $count).str_repeat('*', strlen($val) - $count);
                } elseif ($key == 1) {
                    // отделим доменную зону
                    $val = explode('.', $val);
                    $domen = array_pop($val);
                    $val = implode('.', $val);
                    $count = ceil(strlen($val) / 2); // количество видимых символов
                    if ($count > $max) $count = $max;
                    $arMaskEmail[] = str_repeat('*', strlen($val) - $count).substr($val, strlen($val) - $count, $count).'.'.$domen;
                }
            }
            $arResult['EMAIL'] = implode('@', $arMaskEmail);
        }

        return $arResult;
    }

    /**
     * отсылает письмо с кодом проверки
     *
     * @param string $fio
     * @param string $email
     * @param string $code
     * @param string $number
     *
     * @return bool
     */
    public static function sendEmail($fio = '', $email = '', $code = '', $number = '', $eventName = 'LOYALTY_CONFIRM')
    {
        \CEvent::Send(
            $eventName,
            SITE_ID,
            [
                'FIO'   => $fio,
                'EMAIL' => $email,
                'CARD'  => $number,
                'CODE'  => $code,
            ],
            'N'
        );
        \CEvent::ExecuteEvents();

        return true;
    }

    /**
     * отправляет смс
     *
     * @param string $phone - телефонный номер получателя
     * @param string $text  - текст смс
     *
     * @return bool
     */
    public static function sendSms($phone = '', $code = '')
    {
        if (!$phone || !$code) return false;
        $text = str_replace('#CODE#', $code, self::$smsText);
        $phone = self::MakePhoneNumber($phone);
        if($phone) {
            $phone = '+'.$phone;
        $query = "begin website.send_sms('$phone', '$text', '".self::SMS_BRAND."'); end;";
        $arData = Oracle::getInstance()->getQuery($query);
        }else{
            $arData['errors'] = 'empty phone';
        }
        if (!$arData['errors']) return true; // нет ошибок - значит все хорошо

        return false;
    }

    /**
     * получаем историю чеков
     *
     * @param int $number
     *
     * @return array
     */
    public static function getCheckHistory($number = 0, $skipCache = false)
    {
        $arResult = [];

        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0);
        if (!$manzanaUse) return [];

        // кешируем запрос баланса на пять минут
        $cacheTime = 60 * 5; // 5 минут
        $cachePath = '/s1/card/checks/';
        $cacheId = [
            SITE_ID,
            LANGUAGE_ID,
            $manzanaUse,
            'card',
            'checks',
            $number
        ];
        $сache = Cache::createInstance();
        if (!$skipCache && $сache->initCache($cacheTime, implode('|', $cacheId), $cachePath)) {
            $arResult = $сache->getVars();
        } elseif ($сache->startDataCache()) {
            $arData = Manzana::getInstance()->getCheckHistory($number, !$skipCache);
            $arResult = [
                'HISTORY' => [],
            ];
            foreach ($arData['VALUE'] as $arOne) {
                $date = ($arOne['DATE']) ? $arOne['DATE']->getTimestamp() : '';
                $arCheck = $arOne;
                $arCheck['DATE_FORMAT'] = FormatDate('d F Y', $date);

                $arResult['HISTORY'][] = $arCheck;
            }

            $сache->endDataCache($arResult);
        }

        return $arResult;
    }

    /**
     * получаем историю изменений по карте
     *
     * @param int $number
     *
     * @return array
     */
    public static function getCardHistory($number = 0, $skipCache = false)
    {
        $arResult = [];

        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0);

        // кешируем запрос баланса на пять минут
        $cacheTime = 60 * 5; // 5 минут
        $cachePath = '/s1/card/history/';
        $cacheId = [
            SITE_ID,
            LANGUAGE_ID,
            $manzanaUse,
            'card',
            'balance',
            $number
        ];
        $сache = Cache::createInstance();
        if (!$skipCache && $сache->initCache($cacheTime, implode('|', $cacheId), $cachePath)) {
            $arResult = $сache->getVars();
        } elseif ($сache->startDataCache()) {
            if ($manzanaUse) {
                $arData = Manzana::getInstance()->getHistory($number, !$skipCache);
                $arResult = [
                    //'BONUSES' => [], // не используется
                    'HISTORY' => [],
                ];
                foreach ($arData['VALUE'] as $arOne) {
                    $date = MakeTimeStamp($arOne['CreatedDate'], 'YYYY-MM-DD');
                    $arCheck = [
                        'DATE'  => [
                            'TIME'   => $date,
                            'FORMAT' => FormatDate('d F Y', $date),
                        ],
                        'CHECK' => $arOne['ChequeNumber'],
                        'NAME'  => $arOne['RuleName'],
                        'PRICE' => $arOne['Debet'],
                    ];
                    if ($arCheck['PRICE'] == 0) $arCheck['PRICE'] = -$arOne['Credit'];

                    $arResult['HISTORY'][] = $arCheck;
                }
            } else {
                $query = "select website.get_bonus_hist(".$number.") from dual";
                $arData = Oracle::getInstance()->getQuery($query);
                $bonusText = '';
                foreach ($arData['result'] as $arOne) {
                    $bonusText = $arOne->load();
                    $arBonus = preg_split('/\\r\\n?|\\n/', $bonusText);
                    $list = false;
                    foreach ($arBonus as $one) {
                        // не используется
                        //if (substr_count($one, 'ТЕКУЩАЯ СУММА:')) $arResult['BONUSES']['ACTIVE'] = trim(str_replace('ТЕКУЩАЯ СУММА:', '', $one));
                        //if (substr_count($one, 'ВСЕГО НАЧИСЛЕНО:')) $arResult['BONUSES']['ALL'] = trim(str_replace('ВСЕГО НАЧИСЛЕНО:', '', $one));
                        //if (substr_count($one, 'ВСЕГО СПИСАНО:')) $arResult['BONUSES']['CHARGED'] = trim(str_replace('ВСЕГО СПИСАНО:', '', $one));
                        if ($list) {
                            preg_match("/([\d]{2}.[\d]{2}.[\d]{2})([\s]+)([\d]+)?([\s]+)([\W\s]+)([\s]+)([\d-]+)?([\s]+)([\W]+)/", $one, $matches);
                            $date = explode('.', $matches[1]);
                            $date = MakeTimeStamp($date[0].'.'.$date[1].'.20'.$date[2], 'DD.MM.YYYY');
                            $arResult['HISTORY'][] = [
                                'DATE'  => [
                                    'TIME'   => $date,
                                    'FORMAT' => FormatDate('d F Y', $date),
                                ],
                                'CHECK' => $matches[3],
                                'NAME'  => $matches[5],
                                'PRICE' => $matches[7],
                                //'CONFIRM' => $matches[9], // не используется
                            ];
                        }
                        if (substr_count($one, 'Дата/НомерЧека/Операция/Сумма/Подтверждено')) $list = true;
                    }
                    break;
                }
            }

            $сache->endDataCache($arResult);
        }

        return $arResult;
    }

    /**
     * ищем карту по телефону и\или емейлу пользователя
     *
     * @param string $phone
     * @param string $email
     *
     * @return int|array
     */
    public static function findCard($phone = '', $email = '', $getOne = false)
    {
        $phone = trim($phone);
        $email = trim($email);
        if (!$phone && !$email) return false;

        $phone = self::MakePhoneNumber($phone);
        $phone = '('.substr($phone, 1, 3).')'.substr($phone, 4);

        $arCards = self::findCardRequest($phone, $email);
        if (!$arCards && $phone) $arCards = self::findCardRequest($phone, '');
        if (!$arCards && $email) $arCards = self::findCardRequest('', $email);

        TrimArr($arCards);

        if ($getOne) return $arCards[0];

        return $arCards;
    }

    /**
     * запрос на поиск карты
     *
     * @param string $phone
     * @param string $email
     *
     * @return bool
     */
    private static function findCardRequest($phone = '', $email = '')
    {
        $arCards = [];

        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0);

        if ($manzanaUse) {
            $arData = Manzana::getInstance()->findCard($phone, $email);
            foreach ($arData as $cardId => $cardNumber) {
                // добавляем только те карты, которые прошли проверку на бренд
                $cardBrand = '';
                if (Manzana::getInstance()->checkCardType($cardNumber, $cardBrand)) {
                    $arCards[] = $cardNumber;
                }
            }
            TrimArr($arCards);

            return $arCards;
        } else {
            $query = "select website.get_card_num('".$phone."', '".$email."') from dual";
            $arData = Oracle::getInstance()->getQuery($query);
            if ($arData['result']) {
                foreach ($arData['result'] as $card) {
                    $arCards = explode(',', $card);
                    TrimArr($arCards);

                    return $arCards;
                }
            }
        }

        return $arCards;
    }

    /**
     * создаем \ редактируем виртуальную карту
     *
     * @param string $name
     * @param string $secondName
     * @param string $lastName
     * @param string $phone
     * @param string $email
     * @param string $birthday - формат DD.MM.YYYY
     * @param string $sex      - [М | Ж]
     * @param string $card
     *
     * @return string
     */
    public static function createCard($lastName = '', $name = '', $secondName = '', $phone = '', $email = '', $birthday = '', $sex = '', $card = '')
    {
        $manzanaUse = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0);

        $arResult = ['RESULT' => 'OK'];

        if ($manzanaUse) {
            $birthday = MakeTimeStamp($birthday, 'DD.MM.YYYY');
            if ($card) {
                // update contact
                $arContacts = Manzana::getInstance()->findContact('', '', $card, false); // без кеша
                if ($arContacts[0]['Id']) {
                    $arData = Manzana::getInstance()->contactUpdate(
                        $arContacts[0]['Id'],
                        $lastName,
                        $name,
                        $secondName,
                        ($sex == 'Ж') ? 'F' : 'M',
                        date('Y-m-d', $birthday),
                        $email,
                        $phone
                    );

                    if ($arData['RESULT'] == 'ERROR') {
                        $arResult['RESULT'] = 'ERROR';
                        $arResult['MESSAGE'] = $arData['MESSAGE'];
                    } else {
                        $arResult['CARD'] = $card;
                    }
                } else {
                    $arResult['RESULT'] = 'ERROR';
                    $arResult['MESSAGE'] = 'Контакт не найден';
                }
            } else {
                // add contact
                $contactId = false;

                // поищем уже созданный контакт на указанные емейл \ телефон
                $arContacts = Manzana::getInstance()->findContact($phone, $email, '', false); // без кеша
                // подойдет даже тот контакт, в котором хоть одно совпадает - либо емейл, либо телефон
                if (!$arContacts[0]['Id']) $arContacts = Manzana::getInstance()->findContact($phone, '', '', false); // без кеша
                if (!$arContacts[0]['Id']) $arContacts = Manzana::getInstance()->findContact('', $email, '', false); // без кеша

                if ($arContacts[0]['Id']) {
                    $contactId = $arContacts[0]['Id'];
                    // update contact
                    $arData = Manzana::getInstance()->contactUpdate(
                        $contactId,
                        $lastName,
                        $name,
                        $secondName,
                        ($sex == 'Ж') ? 'F' : 'M',
                        date('Y-m-d', $birthday),
                        $email,
                        $phone
                    );

                    /*
                    // ошибка при редактировании контакта не должна приводить к неверному ответу
                    if ($arData['RESULT'] == 'ERROR') {
                        $arResult['RESULT'] = 'ERROR';
                        $arResult['MESSAGE'] = $arData['MESSAGE'];
                    }
                    */
                } else {
                    // add contact
                    $arData = Manzana::getInstance()->contactAdd(
                        $lastName,
                        $name,
                        $secondName,
                        ($sex == 'Ж') ? 'F' : 'M',
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

                if ($contactId && $arResult['RESULT'] == 'OK') {
                    // проверим, есть ли карта, если нету - привяжем новую
                    $arData = Manzana::getInstance()->findCard('', '', false, $contactId);
                    $arCards = [];
                    foreach ($arData as $cardId => $cardNumber) {
                        // добавляем только те карты, которые прошли проверку на бренд
                        $cardBrand = '';
                        if (Manzana::getInstance()->checkCardType($cardNumber, $cardBrand)) {
                            $arCards[] = $cardNumber;
                        }
                    }
                    TrimArr($arCards);

                    if (count($arCards)) {
                        $arResult['CARD'] = array_shift($arCards);
                    } else {
                        // карту нужно привязать

                        $arData = Manzana::getInstance()->addCard($contactId);
                        if ($arData['RESULT'] == 'ERROR') {
                            $arResult['RESULT'] = 'ERROR';
                            $arResult['MESSAGE'] = $arData['MESSAGE'];
                        } else {
                            $cardNumber = Manzana::getInstance()->cardGet($arData['VALUE']);
                            $arResult['CARD'] = $cardNumber;
                        }
                    }
                }
            }
        } else {
            $phone = self::MakePhoneNumber($phone);
            $phone = '('.substr($phone, 1, 3).')'.substr($phone, 4);

            $card = trim($card);
            $cardQuery = ($card) ? "'".$card."'" : 'null';

            $query = "begin
:l_card_num := website.get_new_card('".$lastName."','".$name."','".$secondName."','".$phone."','".$email."','".$birthday."','".$sex."',".$cardQuery.");
commit;
end;";

            $arVars = ['l_card_num' => ''];
            $arData = Oracle::getInstance()->getQuery($query, $arVars);

            $resCard = explode(
                "
",
                $arData['vars']['l_card_num']
            );
            $resCard = $resCard[0];
            if (substr_count($resCard, 'ORA')) {
                $resCard = explode(':', $resCard);
                $resCard = trim($resCard[1]);

                $arResult['RESULT'] = 'ERROR';
                $arResult['MESSAGE'] = $resCard;

                return $arResult;
            } else {
                if (!$card) $card = $resCard;
            }

            if (!$card) $card = self::findCard($phone, $email, true);

            $arResult['CARD'] = $card;
        }

        return $arResult;
    }


    /**
     * создает для пользователя карту
     *
     * @param int $userId
     *
     * @return mixed
     */
    public static function createCardToUser($userId = 0)
    {
        $us = \Bitrix\Main\UserTable::getList(
            [
                'filter' => ['ID' => $userId],
                'limit'  => 1
            ]
        );
        $arUser = $us->Fetch();
        $phone = ($arUser['PERSONAL_MOBILE']) ? $arUser['PERSONAL_MOBILE'] : $arUser['PERSONAL_PHONE'];
        $email = $arUser['EMAIL'];

        // создаем \ редактируем карту и привязываем ее, если она не привязана
        $cardResult = self::createCard(
            $arUser['LAST_NAME'],
            $arUser['NAME'],
            $arUser['SECOND_NAME'],
            $phone,
            $email,
            $arUser['PERSONAL_BIRTHDAY']->format('d.m.Y'),
            ($arUser['PERSONAL_GENDER'] == 'F') ? 'Ж' : 'М'
        );

        if ($cardResult['RESULT'] == 'OK') {
            $arResult['RESULT'] = 'OK';
            $card = $arResult['CARD'] = $cardResult['CARD'];
            self::addCardToUser($card);
        } else {
            $arResult['RESULT'] = 'ERROR';
            $arResult['MESSAGE'] = $cardResult['MESSAGE'];
        }

        return $card;
    }
}

