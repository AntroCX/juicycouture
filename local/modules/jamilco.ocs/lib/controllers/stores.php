<?php
namespace Jamilco\OCS;

use \Jamilco\Main\Update;

class Stores
{
    static function index()
    {
        // обновление справочника "склады"
        $data = file_get_contents('php://input');

        $res = Update::changeStores($data);
        echo $res;
    }

    public static function getGeoCoords($addressStr)
    {

        $ch = curl_init('https://geocode-maps.yandex.ru/1.x/?geocode='.$addressStr);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if (substr_count($result, '<pos>')) {
            $obXml = new \SimpleXMLElement($result);
            $pos = $obXml->GeoObjectCollection->featureMember->GeoObject->Point->pos->__toString();

            return explode(" ", $pos);
        }

        return "";
    }
}