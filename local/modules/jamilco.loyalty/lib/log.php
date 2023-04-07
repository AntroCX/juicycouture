<?
namespace Jamilco\Loyalty;

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable;
use \Bitrix\Main\Type\DateTime;

class Log
{
    const HL_IBLOCK_TABLE_NAME = 'loyalty_log';
    const SECURE_COUNT_BAD_1 = 5; // количество ошибочных номеров карт до блокировки
    const SECURE_COUNT_BAD_2 = 10; // количество ошибочных номеров карт до блокировки
    const SECURE_COUNT_BAD_TOTAL = 15; // общее количество ошибочных номеров карт до перманентной блокировки
    const SECURE_BANTIME_1 = 1; // количество дней для 1-ой блокировки
    const SECURE_BANTIME_2 = 3; // количество дней для 2-ой блокировки
    private static $instance;
    var $dataClass = '';

    private function __construct()
    {
        Loader::includeModule("highloadblock");

        $hlblock = HighloadBlockTable::getList(array('filter' => array('TABLE_NAME' => self::HL_IBLOCK_TABLE_NAME)))->Fetch();
        if (!empty($hlblock)) {
            $entity = HighloadBlockTable::compileEntity($hlblock);
            $this->dataClass = $entity->getDataClass();
        }
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * проверяет блокировку пользователя
     * @return bool
     */
    function checkSecure()
    {
        return false; // блокировка выключена

        if (ADMIN_SECTION === true) return false; // действия из административного раздела не блокируем

        global $USER;
        $dataClass = $this->dataClass;

        $arFilter = [
            'UF_IP'     => self::getIP(),
            'UF_RESULT' => 'not_found',
        ];

        // ищем последнюю валидную запись
        $res = $dataClass::getList(
            [
                'order'  => ['ID' => 'DESC'],
                'filter' => [
                    'UF_IP'   => self::getIP(),
                    'UF_TYPE' => 'balance',
                ],
                'limit'  => 1,
            ]
        );
        if ($lastValidLog = $res->Fetch()) {
            $arFilter['>UF_DATE'] = $lastValidLog['UF_DATE']; // выбираем логи ошибочных запросов после последней валидной записи
        }

        $res = $dataClass::getList(
            array(
                'order'  => ['ID' => 'DESC'],
                'filter' => $arFilter,
                'limit'  => self::SECURE_COUNT_BAD_TOTAL,
            )
        );
        $secureCountBad = $res->getSelectedRowsCount();

        $banTime = 0;
        if ($secureCountBad == self::SECURE_COUNT_BAD_1) {
            $banTime = self::SECURE_BANTIME_1;
            self::addLog(0, 'client', 'ban-1');
        } elseif ($secureCountBad == self::SECURE_COUNT_BAD_2) {
            $banTime = self::SECURE_BANTIME_2;
            self::addLog(0, 'client', 'ban-3');
        } elseif ($secureCountBad >= self::SECURE_COUNT_BAD_TOTAL) {
            self::addLog(0, 'client', 'ban-permanent');

            return true; // блокируем всегда
        }

        $lastLog = $res->Fetch();

        if ($lastLog) {
            $dayStart = new \DateTime($lastLog['UF_DATE']); // дата последнего события 'not_found'
            $dayNow = new \DateTime();
            $diffDays = $dayNow->diff($dayStart);

            $block = ($diffDays->days < $banTime) ? true : false;
        } else {
            $block = false; // если события 'not_found' отсутствуют, не блокируем пользователя
        }

        return $block;
    }

    /**
     * добавляет запись в лог
     *
     * @param int    $number
     * @param string $type
     * @param string $result
     */
    function addLog($number = 0, $type = 'balance', $result = '')
    {
        global $USER;
        $date = new DateTime();
        $arFields = array(
            'UF_DATE'   => $date->toString(),
            'UF_IP'     => self::getIP(),
            'UF_USER'   => $USER->GetID(),
            'UF_CARD'   => $number,
            'UF_TYPE'   => $type,
            'UF_RESULT' => $result,
        );

        $dataClass = $this->dataClass;
        $dataClass::add($arFields);
    }

    /**
     * @desc возвращает IP адрес текущего пользователя
     *
     * @return mixed
     */
    static function getIP()
    {
        $ip = false;
        if ($_SERVER['HTTP_CLIENT_IP']) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}

?>