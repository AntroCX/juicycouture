<?php
namespace Jamilco\OCS;

use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Loader;
use \Jamilco\Merch\Common as MerchCommon;
use \Jamilco\Main\Update;

class SKU {
    const IBLOCK_SKU_ID = 2;
    const IBLOCK_CATALOG_ID = 1;

    static $arLogDirs = [
        'CHANGE_SKU_QUANTITIES'        => 'sku_quantities',
        'CHANGE_RETAIL_SKU_QTY_SHOP'   => 'retail_sku_quantities_full',
        'CHANGE_RETAIL_SKU_QUANTITIES' => 'retail_sku_quantities',
        'CHANGE_SKU_PRICES'            => 'sku_prices',
    ];

    // массив отложенных команд
    static $arDelayCommand = [
        'CHANGE_SKU_QUANTITIES',
        'CHANGE_RETAIL_SKU_QTY_SHOP',
        'CHANGE_SKU_PRICES'
    ];

    static function change_prices() {
        $data = file_get_contents('php://input');
        $command = 'CHANGE_SKU_PRICES';

        $delay = (in_array($command, self::$arDelayCommand)) ? true : false;
        $logPath = $_SERVER['DOCUMENT_ROOT'].(($delay) ? LOG_PATH_DELAY : LOG_PATH).self::$arLogDirs[$command].'/';
        if ($data) {
            \CheckDirPath($logPath);
            file_put_contents($logPath.date('y.m.d-H.i.s').'.xml', $data); // Логирование входящих данных
        } elseif ($_REQUEST['file']) {
            $data = file_get_contents($logPath.$_REQUEST['file']);
        }

        if (!$data) {
            echo '<errors><error>Empty input data</error></errors>';
            die();
        }

        if (!$delay) {
            $res = Update::changePrices($data);
        } else {
            $res = '<result>ok</result>';
        }
        echo $res;
    }

    /**
     * Set product discount property
     *
     * @param $productId
     * @param bool $active
     */
    public static function setProductDiscountProperty($productId, $active = true)
    {
        Loader::includeModule('iblock');

        $cacheId = 'SKUPropertyDiscount';
        $cachePath = "/$cacheId/";
        $cacheTime = 3600 * 24 * 7; //week

        $value = false;
        if ($active) {
            $cache = Cache::createInstance();
            if ($cache->initCache($cacheTime, $cacheId, $cachePath)) {
                $enum = $cache->getVars();
            } elseif ($cache->StartDataCache()) {
                if (!Loader::includeModule('iblock')) {
                    $cache->abortDataCache();
                }

                $enum = \CIBlockPropertyEnum::GetList(
                    array(),
                    array(
                        'IBLOCK_ID' => self::IBLOCK_SKU_ID,
                        'XML_ID' => 'YES',
                        'CODE' => 'SPECIALOFFER'
                    )
                )->Fetch();

                if ($enum) {
                    $cache->endDataCache(array(
                        'ID' => $enum['ID']
                    ));
                } else {
                    $cache->abortDataCache();
                }
            }
            $value = $enum['ID'];
        }

        \CIBlockElement::SetPropertyValuesEx(
            $productId,
            self::IBLOCK_SKU_ID,
            array(
                'SPECIALOFFER' => array(
                    'VALUE' => $value
                )
            )
        );
    }

    static function change_quantity() {
        define("IBLOCK_SKU_ID", 2);
        $data = file_get_contents('php://input');

        $command = 'CHANGE_SKU_QUANTITIES';
        $delay = (in_array($command, self::$arDelayCommand)) ? true : false;
        $logPath = $_SERVER['DOCUMENT_ROOT'].(($delay) ? LOG_PATH_DELAY : LOG_PATH).self::$arLogDirs[$command].'/';
        CheckDirPath($logPath);

        if ($data) {
            /** Логирование входящих данных */
            file_put_contents($logPath.date('y.m.d-H.i.s').'.xml', $data);
        } else {
            if ($_REQUEST['file']) {
                $data = file_get_contents($logPath.$_REQUEST['file']);
            }
            if (!$data) {
                echo '<errors><error>Empty input data</error></errors>';
                die();
            }
        }


        if (!$delay || $_REQUEST['go'] == 'Y') {
            $arLog = [];
            $res = Update::changeQuantity($data, $arLog);
            //pr($arLog, 1);
        } else {
            $res = '<result>ok</result>';
        }

        echo $res;

        return false;
    }

    static function change_retail_quantity_all() {
        $command = 'CHANGE_RETAIL_SKU_QTY_SHOP';

        define("IBLOCK_SKU_ID", 2);
        $data = file_get_contents('php://input');

        /** Логирование входящих данных */
        $delay = (in_array($command, self::$arDelayCommand)) ? true : false;
        $logPath = $_SERVER['DOCUMENT_ROOT'].(($delay) ? LOG_PATH_DELAY : LOG_PATH).self::$arLogDirs[$command].'/';
        CheckDirPath($logPath);

        if ($data) {
            file_put_contents($logPath.date('y.m.d-H.i.s').'.xml', $data);
        } elseif ($_REQUEST['file']) {
            $data = file_get_contents($logPath.$_REQUEST['file']);
        }
        if (!$data) {
            echo '<errors><error>XML is empty</error></errors>';
            die();
        }

        if ($command == 'CHANGE_RETAIL_SKU_QUANTITIES' && !$_REQUEST['file']) {
            echo '<errors><error>XML will not be processed anymore because of the full one</error></errors>';
            die();
        }

        if (!$delay) {
            $res = Update::changeRetailSku($data);
        } else {
            $res = '<result>ok</result>';
        }
        echo $res;

        return false;
    }

    static function change_retail_quantity() {
        define("IBLOCK_SKU_ID", 2);
        $data = file_get_contents('php://input');

        /** Логирование входящих данных */
        $logPath = $_SERVER['DOCUMENT_ROOT'].'/local/api/log/retail_sku_quantities/';
        CheckDirPath($logPath);
        file_put_contents($logPath.date('y.m.d-H.i.s').'.xml', $data);

        if (!$_REQUEST['file']) {
            echo '<errors><error>XML will not be processed anymore because of the full one</error></errors>';
            die();
        }

        \CModule::IncludeModule('iblock');
        \CModule::IncludeModule('sale');
        \CModule::IncludeModule('catalog');
        // пример
        /* $data = '<?xml version="1.0" encoding="utf-8"?><items reset="reset"><sku code="681" quantity="13"/></items>';
        */
        try
        {
            //////////////////////////////////////////////// обнуление остатков
            $ocs_quantities_option = 'Y';
            $path_to_logs = \COption::GetOptionString("additionaloptions", "PATH_TO_LOG", "");
            $code = \COption::GetOptionString("additionaloptions", "OCS_CODE_TO_IDENTIFY_OFFERS", "");

            //// logs
            $file_to_clear = $_SERVER['DOCUMENT_ROOT'].$path_to_logs."to_clear.txt";
            $file_request = $_SERVER['DOCUMENT_ROOT'].$path_to_logs."request.txt";
            $file_dump = $_SERVER['DOCUMENT_ROOT'].$path_to_logs."dump.txt";

            file_put_contents($file_to_clear, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_dump, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_request, date("d.m.Y G:i:s")."\r\n", FILE_APPEND | LOCK_EX);
            file_put_contents($file_request, var_export($data, true)."\r\n", FILE_APPEND | LOCK_EX);


            if($code === ''){
                $code = 'NAME';
            }
            else{
                $code = 'PROPERTY_'.$code;
            }

            $xml = new \SimpleXMLElement($data);
            $bReset = false;
            $reset = $xml->attributes()->reset;

            if (!is_null($reset) && $reset->__toString() == 'reset')
                $bReset = true;

            $arSku = array();
            $arSkuIds = array();

            foreach ($xml->sku as $sku)
            {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();

                $arSku[] = $sku_code;
            }

            // получаем список SKU из запроса с текущим количеством
            $arSelect = Array("ID", $code, "CATALOG_QUANTITY", "PROPERTY_CML2_LINK");
            $arFilter = Array("IBLOCK_ID" => IBLOCK_SKU_ID , $code => $arSku);


            $res = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);


            while($ob = $res->GetNextElement())
            {
                $arFields = $ob->GetFields();
                $value_field = $code == "NAME" ? "NAME" : $code."_VALUE";
                $arSkuIds[$arFields[$value_field]] = array("id" => $arFields["ID"], "code" => $arFields[$value_field], "quantity" => $arFields["CATALOG_QUANTITY"], "product_id" => $arFields['PROPERTY_CML2_LINK_VALUE']);
            }

            $arSku = array();
            $arSkuID = array();

            foreach ($xml->sku as $sku)
            {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();
                $quantity = $attributes->quantity->__toString();

                $result = array(
                    'code' => $sku_code,
                    'quantity' => $quantity,
                    'result' => 'OK',
                );

                if (!empty($arSkuIds[$sku_code]))
                {
                    $arFields = array(
                        'PRODUCT_ID' => $arSkuIds[$sku_code]['id'],
                        'STORE_ID' => 10,
                        'AMOUNT' => intval($quantity)
                    );
                    $arSkuID[] = $arFields['PRODUCT_ID'];

                    if ($bReset === false)
                    {
                        $arFields['AMOUNT'] += $arSkuIds[$sku_code]['quantity'];
                    }


                    // говнозапрос, пока пойдет :)

                    $rsCatalog = \CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' => $arFields['PRODUCT_ID'], 'STORE_ID' => $arFields['STORE_ID']));
                    $arCatalog = $rsCatalog->Fetch();
                    if($arCatalog['ID']) {
                        $resultCatalog = \CCatalogStoreProduct::Update($arCatalog['ID'], $arFields);
                    } else {
                        $resultCatalog = \CCatalogStoreProduct::Add($arFields);
                    }

                    // обновляем количество в соответствии с запросом
                    if (!$resultCatalog)
                    {
                        $result['result'] = 'Error';
                        $result['message'] = 'Ошибка обновления';

                        //// logs
                        file_put_contents($file_dump, date("d.m.Y G:i:s")." error on update. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);

                    }
                    else
                    {
                        //// logs
                        file_put_contents($file_dump, date("d.m.Y G:i:s")." done. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);
                    }
                }
                else
                {
                    //// logs
                    file_put_contents($file_dump, date("d.m.Y G:i:s")." error. sku not found. ".$sku_code." = ".$quantity."\r\n", FILE_APPEND | LOCK_EX);

                    $result['result'] = 'Error';
                    $result['message'] = 'Не найдена SKU';
                }

                $arSku[] = $result;
            }

            $arProducts = array();

            foreach ($xml->sku as $sku) {
                $attributes = $sku->attributes();
                $sku_code = $attributes->code->__toString();
                $quantity = $attributes->quantity->__toString();

                if (!empty($arSkuIds[$sku_code])) {
                    $arProducts[$arSkuIds[$sku_code]['product_id']] += $quantity;
                }

            }

            foreach ($arProducts as $productId => $quantity) {
                \CIBlockElement::SetPropertyValuesEx($productId, 1, array('RETAIL_QUANTITY' => $quantity));
            }

            // обнулим "остаток в РМ" для всех товаров, которых не было в этой выгрузке
            $el = \CIBlockElement::getList(
                [],
                [
                    'IBLOCK_ID'                 => 1,
                    '!ID'                       => array_keys($arProducts),
                    '>PROPERTY_RETAIL_QUANTITY' => 0,
                ],
                false,
                false,
                ['ID']
            );
            while ($arItem = $el->Fetch()) {
                \CIBlockElement::SetPropertyValuesEx($arItem['ID'], 1, array('RETAIL_QUANTITY' => ''));
            }

            // обнулим остаток на розничном складе для всех ТП, которых не было в этой выгрузке
            $st = \CCatalogStoreProduct::GetList(
                array(),
                array(
                    '!PRODUCT_ID' => $arSkuID,
                    'STORE_ID'    => 10,
                    '>AMOUNT'     => 0
                )
            );
            while ($arCatalogProduct = $st->Fetch()) {
                \CCatalogStoreProduct::Update($arCatalogProduct['ID'], ['AMOUNT' => 0]);
            }

            // пересохранение сортировки по количеству доступных размеров
            if (Loader::IncludeModule('jamilco.merch')) MerchCommon::resortItemsByAvailability();

            \Jamilco\Omni\Channel::reSaveCityAvailables(true);
            \Jamilco\Main\Utils::clearCatalogCache(); // сброс кеша каталога

            if (!empty($arSku))
            {
                if ($bReset)
                    echo '<items reset="OK">';
                else
                    echo '<items>';

                foreach ($arSku as $key => $value)
                    echo "<sku code='{$value['code']}' quantity='{$value['quantity']}' result='{$value['result']}'" . ((array_key_exists('message', $value)) ? " message='{$value['message']}'" : "") . "/>";

                echo '</items>';
                //BXClearCache(true, "/catalog/");
            }
        }
        catch(\Exception $e)
        {
            echo '<errors>';
            echo "<error>{$e->getMessage()}</error>";
            echo '</errors>';
        }

        return false;
    }
}