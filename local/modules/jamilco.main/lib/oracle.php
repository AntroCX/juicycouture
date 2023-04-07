<?php
namespace Jamilco\Main;

use \Bitrix\Main\Loader;

class Oracle
{
    protected $db = null;
    protected $errors = [];
    private static $instance;

    // количество секунд для признания сервера недоступным
    const ORACLE_CHECK_TIMEOUT = 15;

    // путь до блокирующего файла
    const ORACLE_LOCK_FILE = '/local/oracle.lock';
    const ORACLE_LOG_DIR = '/local/log/';

    // параметры подключения
    protected $options = [
        //'ORA_STR'   => '89.179.170.205/lvb',
        //'ORA_STR'   => '195.151.233.21/lvb',
        'ORA_STR'   => IS_DEV? '195.151.233.21/lvb': '127.0.0.1/lvb',
        'ORA_LOGIN' => 'website',
        'ORA_PASS'  => 'NeK79esu'
    ];

    private function __construct()
    {

    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * устанавливает соединение
     */
    private function openConnect()
    {
        if ($this->db) return true;
        if (self::isLockFileExists()) {
            $this->errors[] = 'Удаленный сервер недоступен';

            return false;
        }

        if (function_exists('oci_connect')) {
            $this->db = oci_connect(
                $this->options['ORA_LOGIN'],
                $this->options['ORA_PASS'],
                $this->options['ORA_STR'],
                "UTF8"
            );
            if (!$this->db) {
                $e = oci_error();
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
                $this->errors[] = $e;
            }
        } else {
            $this->errors[] = "Расширение для работы с БД Oracle не установлено на хостинге";
        }
    }

    /**
     * отсылает запрос
     *
     * @param string $query  - текст запроса
     * @param array  $arVars - список прикрепленных переменных
     *
     * @return array
     */
    public function getQuery($query = '', $arVars = [])
    {
        if (!$this->db) $this->openConnect();

        $arReturn = [
            'result' => [],
            'errors' => $this->errors
        ];

        if (!$this->db) return $arReturn;

        $parse = oci_parse($this->db, $query);

        foreach ($arVars as $varCode => $varValue) {
            oci_bind_by_name($parse, ":".$varCode, $$varCode, 500);
        }

        $r = oci_execute($parse);
        if (!$r) {
            $this->errors[] = "Удостоверьтесь в корректном вводе данных";
        } else {
            while ($row = oci_fetch_array($parse, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $arReturn['result'][] = $row;
            }

            foreach ($arVars as $varCode => $varValue) {
                $arVars[$varCode] = $$varCode;
            }
            if ($arVars) $arReturn['vars'] = $arVars;
        }
        if (count($arReturn['result']) == 1) $arReturn['result'] = array_shift($arReturn['result']);

        return $arReturn;
    }

    /**
     * проверяет наличие блокирующего файла
     * @return bool
     */
    static public function isLockFileExists()
    {
        $lockFile = $_SERVER['DOCUMENT_ROOT'].self::ORACLE_LOCK_FILE;

        return file_exists($lockFile);
    }

    /**
     * проверяет доступность сервера и устанавливает\удаляет блокирующий файл
     * @return bool
     */
    public function checkServer()
    {
        $res = false;
        $lockFile = $_SERVER['DOCUMENT_ROOT'].self::ORACLE_LOCK_FILE;

        $res = self::getHttpStatus($this->options['ORA_STR']);

        // если запрос ушел верно, либо вернулась ошибка о пустом ответе, то сервер считается доступным
        if ($res['TYPE'] == 'SUCCESS' || $res['ERROR'] == 'Empty reply from server') {
            if (file_exists($lockFile)) unlink($lockFile);
            $res = true;
        } else {
            if (!file_exists($lockFile)) file_put_contents($lockFile, date('Y-m-d H:i:s'));
            $res = false;
        }

        $logDir = $_SERVER['DOCUMENT_ROOT'].self::ORACLE_LOG_DIR;
        CheckDirPath($logDir);

        $f = fopen($logDir.'oracle.log', 'a');
        fwrite($f, date('Y-m-d H:i:s')." - ".$res."\r\n");
        fclose($f);

        return $res;
    }

    public function getProps($articul, $img = 'F')
    {
        $query = "select  website.get_article_info('{$articul}', '{$img}') from dual";
        $arData = $this->getQuery($query);
        $xmlString = '';

        foreach ($arData['result'] as $arItem) {
            $xmlString = $arItem->load();
        }
        $xml = simplexml_load_string($xmlString);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        return $array;
    }

    public function getShopsNew($articul)
    {
        Loader::includeModule('catalog');

        $query = "select website.get_rests_by_sku('{$articul}') from dual";
        $arData = $this->getQuery($query);
        $xmlString = '';
        foreach ($arData['result'] as $arShop) {
            $xmlString = $arShop->load();
        }
        $arAmount = [];
        $xml = simplexml_load_string($xmlString);
        foreach ($xml->stores->store as $key => $item) {
            foreach ($item[0]->attributes() as $a => $id) {
                if ($id && $a == 'id') {
                    $arAmount[intval($id)] = (int)$item[0];
                }
            }
        }

        $arResult = [];
        if (count($arAmount) > 0) {
            $st = \CCatalogStore::GetList(
                ['SORT' => 'ASC', 'ID' => 'ASC'],
                ['XML_ID' => array_keys($arAmount), '!ADDRESS' => false, 'ACTIVE' => 'Y']
            );
            while ($arStore = $st->Fetch()) {
                $arResult[] = [
                    'QUANTITY' => $arAmount[$arStore['XML_ID']],
                    'INFO'     => $arStore,
                ];
            }
        }

        return $arResult;
    }

    public function setOrderPaid($orderId)
    {
        Loader::IncludeModule('sale');

        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sberbank_log_pay_order.txt', print_r('Оплата заказа: '.$orderId."\r\n", 1), FILE_APPEND);

        $query = "BEGIN website.set_order_paid('".$orderId."'); END;";
        $arOcsResult = $this->getQuery($query);

        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sberbank_log_pay_order.txt', print_r($arOcsResult, 1), FILE_APPEND);

        // св-во "ОШИБКА"
        $ORDER_PROP_OCS_ERROR = 0;
        $ORDER_PROP_OCS_ERROR_MSG = 0;
        $r = \Bitrix\Sale\Property::getList(
            [
                'select' => ['*'],
                'filter' => [
                    'CODE' => ['OCS_ERROR', 'OCS_ERROR_MSG']
                ]
            ]
        );
        while ($arProp = $r->fetch()) {
            if ($arProp['CODE'] == 'OCS_ERROR') {
                $ORDER_PROP_OCS_ERROR = $arProp['ID'];
            } elseif ($arProp['CODE'] == 'OCS_ERROR_MSG') {
                $ORDER_PROP_OCS_ERROR_MSG = $arProp['ID'];
            };
        }

        // св-ва Заказа
        $order = \Bitrix\Sale\Order::load($orderId);
        $propertyCollection = $order->getPropertyCollection();

        if($arOcsResult['errors']){
            // устанавливаем "Ошибку"
            if ($ORDER_PROP_OCS_ERROR && $ORDER_PROP_OCS_ERROR_MSG) {
                $propertyValue = $propertyCollection->getItemByOrderPropertyId($ORDER_PROP_OCS_ERROR);
                $propertyValue->setField('VALUE', 'Y');

                $error_msg = implode(', ', $arOcsResult['errors']);
                $error_msg = strip_tags($error_msg);
                $error_msg = preg_replace("/[\r\n]/", "", $error_msg);
                $error_msg = trim(substr($error_msg, 0, 500));
                $propertyValue = $propertyCollection->getItemByOrderPropertyId($ORDER_PROP_OCS_ERROR_MSG);
                $propertyValue->setField('VALUE', $error_msg);
                $order->save();
            }

            //log
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sberbank_log_pay_order.txt', print_r('ERROR: '.$orderId."\r\n", 1), FILE_APPEND);
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sberbank_log_pay_order.txt', print_r($arOcsResult['errors']."\r\n", 1), FILE_APPEND);

            $log_name = 'set_order_paid';
            $dir = realpath(dirname(__FILE__))."/log/";
            $msg = print_r($arOcsResult['errors'], 1);
            $this->addToLog($log_name, $dir, $msg);

            // send email
            $receivers = 'ermolenko@jamilco.ru, bokhov@jamilco.ru, fartukova@jamilco.ru';
            $subject = "Произошла ошибка при проставлении признака оплаты для заказа № $orderId.";
            $this->sendEmail($msg, $receivers, $subject);
        } else {
            // сбрасываем "Ошибку"
            if ($ORDER_PROP_OCS_ERROR && $ORDER_PROP_OCS_ERROR_MSG) {
                $propertyValue = $propertyCollection->getItemByOrderPropertyId($ORDER_PROP_OCS_ERROR);
                if ($propertyValue->getField('VALUE') == 'Y') {
                    $propertyValue->setField('VALUE', 'N');

                    $propertyValue = $propertyCollection->getItemByOrderPropertyId($ORDER_PROP_OCS_ERROR_MSG);
                    $propertyValue->setField('VALUE', '');
                    $order->save();
                }
            }
        }

        return $arOcsResult;
    }

    public function getShops($articul, $size)
    {
        $query = "SELECT BONUS.REPORT_001('".$articul."', 'ALL', '".$size."', null) FROM dual";

        return $this->getQuery($query);
    }

    public function getFeedFields($articul)
    {
        if (!is_array($articul)) return 'Error. $articul must be an array';

        if (!$this->db) $this->openConnect();
        if (!$this->db) return 'Error: '.implode(', ', $this->errors);

        $query = "BEGIN :cursbv := ord.send_models_to_ishop_by_site('".implode(',', $articul)."'); END;";

        $stid = oci_parse($this->db, $query);
        $curs = oci_new_cursor($this->db);

        $articul_var = implode(',', $articul);
        oci_bind_by_name($stid, ':p_art', $articul_var, 32000);
        oci_bind_by_name($stid, ":cursbv", $curs, -1, OCI_B_CURSOR);

        oci_execute($stid);
        oci_execute($curs);  // Выполняет REF CURSOR как обычный идентификатор выражения

        oci_fetch_all($curs, $resultArray, null, null, OCI_FETCHSTATEMENT_BY_ROW);

        return $resultArray;
    }

    /**
     * возвращает HTTP-статус при запросе удаленного сервера
     *
     * @param string $url
     *
     * @return array
     */
    static function getHttpStatus($url = '')
    {
        $arResult = ['TYPE' => 'ERROR'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, 1521);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::ORACLE_CHECK_TIMEOUT);
        $page = curl_exec($ch);

        $err = curl_error($ch);
        if (!empty($err)) {
            $arResult['ERROR'] = $err;

            return $arResult;
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $arResult['TYPE'] = 'SUCCESS';
        $arResult['CODE'] = $httpcode;

        return $arResult;
    }

    public function getOcsId($article = '')
    {
        $query = "select website.get_sku_id('{$article}') from dual";
        $arData = $this->getQuery($query);
        foreach ($arData['result'] as $one) {
            return $one;
        }

        return false;
    }

    /**
     * @param        $msg
     * @param        $receivers
     * @param        $subject
     * @param string $file
     */
    public function sendEmail($msg, $receivers, $subject, $file = '')
    {
        if (!$msg) {
            return;
        }
        if (!$receivers) {
            return;
        }
        if (!$subject) {
            return;
        }

        $headers = "From: ".\COption::GetOptionString('main', 'email_from', '')."\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        if ($file) {
            //boundary
            $semi_rand = md5(time());
            $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

            //headers for attachment
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$mime_boundary}\"";

            //multipart boundary
            $body = "--{$mime_boundary}\r\n"."Content-Type: text/html; charset=\"UTF-8\"\r\n".
                "Content-Transfer-Encoding: 7bit\r\n\r\n".date("d.m.Y  H:i:s").".\r\n".$msg."\r\n\r\n";

            //preparing attachment
            if (!empty($file) > 0) {
                if (is_file($file)) {
                    $body .= "--{$mime_boundary}\r\n";
                    $fp = @fopen($file, "rb");
                    $data = @fread($fp, filesize($file));

                    @fclose($fp);
                    $data = chunk_split(base64_encode($data));
                    $body .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\r\n".
                        "Content-Description: ".basename($file)."\r\n".
                        "Content-Disposition: attachment;\r\n"." filename=\"".basename($file)."\"; size=".filesize($file).";\r\n".
                        "Content-Transfer-Encoding: base64\r\n\r\n".$data."\r\n\r\n";
                }
            }
            $body .= "--{$mime_boundary}--";

        } else {
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $body = date("d.m.Y  H:i:s").".\r\n".$msg;
        }

        mail($receivers, $subject, $body, $headers);

    }

    /**
     * @param $log_name - название
     * @param $dir - директория
     * @param $msg - сообщение
     * @param int $days - сколько дней хранить файлы
     */
    public function addToLog($log_name, $dir, $msg, $days = 30){
       if(!$log_name)
           return;
       if(!$dir)
           return;
       if(!$msg)
           return;

        $log_file = $dir.date("d.m.Y")."_".$log_name.".log";

        CheckDirPath($log_file);

        /** delete old log files */
        if($days > 0) {
            foreach (glob($dir . "*" . $log_name . ".log") as $file) {
                $time_stamp = filectime($file);
                if ($time_stamp && (time() - $time_stamp > 86400 * (int)$days)) {
                    unlink($file);
                }
            }
        }

        $msg_start = "\r\n\r\n".date("d.m.Y H:i:s")."\r\n";

        file_put_contents($log_file, $msg_start.$msg, FILE_APPEND);
    }
}

