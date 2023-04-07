<?php
namespace Jamilco\OCS;

class Adspire {
    static function index() {
        $data = file_get_contents('php://input');
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/local/api/adspire.xml', $data);
        echo '<result>OK</result>';
    }
}