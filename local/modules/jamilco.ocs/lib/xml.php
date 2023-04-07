<?php
namespace Jamilco\OCS;

class Xml {
    var $obXml;

    /**
     * @param $arOrder
     */
    public function order2xml($arOrder) {
        if(is_array($arOrder)) {
            $xmlOrder = new \SimpleXMLElement('order');

            echo $xmlOrder->asXML();
        } else {
            $this->set_error('Ошибка при вызове метода Jamilco\OCS\Xml::order2xml($arOrder), параметр $arOrder должен быть массивом');
        }
    }

    /**
     * Метод преобразует xml в массив
     * @param $xml
     * @return array
     */
    public function xml2arr($xml) {
        $arResult = array();

        return $arResult;
    }

    /**
     * Метод при любых ошибках возвращает OCS статус об ошибке
     * @param string $message
     */
    public function set_error($message = 'Ошибка при выполнении запроса') {
        $xmlError = new \SimpleXMLElement('<error></error>');
        $xmlError->addChild('status', $message);
        echo html_entity_decode($xmlError->asXML(), ENT_NOQUOTES, 'UTF-8');
    }
}

