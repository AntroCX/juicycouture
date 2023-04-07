<?php
namespace Jamilco\Ocs;
use Bitrix\Main\Loader;
use Jamilco\Ocs;

use Bitrix\Highloadblock;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Application;
use Bitrix\Iblock\PropertyIndex\Manager;

class Product
{
    /** лог */
    private static $arLog = [];

    /** артикул */
    private static $artnumber = null;

    /** картинки */
    private static $arImages = [];

    /** sku имеет размерн. ряд */
    private static $hasSize = null;

    /** предположит. тип размера */
    private static $guessSizeType = null;

    /** ИБ ид */
    private static $productIblockId = null;
    private static $offerIblockId = null;

    /** ид св-ва картинки */
    private static $picturePropId = null;

    /** ИБ картинок */
    private static $picturesIblockId = null;

    /** ид св-ва ИБ картинок */
    private static $picturesIblockPropId = null;

    /** Ид эл-та в ИБ картинок */
    private static $picturesIblockLinkId = null;

    /** ид св-ва привязки торг. предл. */
    private static $cml2PropId = null;

    /** св-ва типа "метка" */
    private static $arrIblockLabelsProps = null;

    /** xml данные */
    private static $arrXmlProps = null; // св-ва товара
    private static $arrXmlPropsSku = null; // св-ва sku

    public static function index(){
        if($_REQUEST['file']){
            $data = file_get_contents($_SERVER['DOCUMENT_ROOT'].Ocs\ProductConfig::$pathToLog.$_REQUEST['file']);
        }
        else {
            $data = file_get_contents('php://input');
        }

        if (!$data) {
            echo '<result>Нет данных</result>';
        }
        else {
            if(Ocs\ProductConfig::$mode == "test"){
                $result = 'ok';
                $path_to_logs = Application::getDocumentRoot().Ocs\ProductConfig::$pathToLog;
                if (!Directory::isDirectoryExists($path_to_logs)) {
                    Directory::createDirectory($path_to_logs);
                }
                $file_prod = $path_to_logs.date('y.m.d-H.i.s');
                file_put_contents($file_prod.".xml", $data, FILE_APPEND | LOCK_EX);
            }else {
                $result = Ocs\Product::process($data);
            }
            echo '<result>' . $result . '</result>';
        }
    }

    /**
     * Главный метод обработки
     *
     * @param $data
     * @return bool|string
     */
    public static function process($data)
    {
        global $USER;

        //// logs
        //$path_to_logs = COption::GetOptionString("additionaloptions", "PATH_TO_LOG", "")."PRODUCT/";
        $path_to_logs = Application::getDocumentRoot().Ocs\ProductConfig::$pathToLog;
        if (!Directory::isDirectoryExists($path_to_logs)) {
            Directory::createDirectory($path_to_logs);
        }
        $file_prod = $path_to_logs.date('y.m.d-H.i.s');

        $result = '';
        try{

            $r = self::checkXml($data);
            if($r){
                throw new \Exception( $r);
            }

            $r = self::checkIblock();
            if($r)
                throw new \Exception($r);

            $obXml = new \SimpleXMLIterator($data);

            // артикул
            self::$artnumber = $obXml->{Ocs\ProductConfig::$xmlArticulTag}->__toString();

            if(empty(self::$artnumber)){
                throw new \Exception( "Не задан артикул.");
            }

            if(in_array(self::$artnumber, Ocs\ProductConfig::$arExcludedArticuls)) {
                throw new \Exception( "Артикул в списке исключений.");
            }

            self::$arLog['ARTNUMBER'] = self::$artnumber;

            $file_prod .= '-' . preg_replace("/[^a-z0-9\.-]/s", "_", strtolower(self::$artnumber));

            // картинки товара
            self::checkImages($obXml);
            if(empty(self::$arImages)){
                self::$arLog['WARN'][] = "Картинки товара не найдены";
            }

            // получаем данные из xml
            $arrXmlVals = [];

            // поля товара
            $arrXmlVals['product']['fields'] = self::getXmlFields($obXml, Ocs\ProductConfig::$arrFieldsMap);

            // св-ва товара
            $arrXmlValsProps = self::getXmlData($obXml, self::$arrXmlProps, null, ['photos', 'sku']);
            // св-ва товара из атрибутов
            $arrXmlValsAttr = self::getXmlDataAttr($obXml, Ocs\ProductConfig::$arrPropsAttr, 'properties');
            $arrXmlVals['product']['props'] = array_merge($arrXmlValsProps, $arrXmlValsAttr);
            // св-во "пол"
            self::checkGender($arrXmlVals['product']['props']);
            // св-во "Технология"
            self::checkTechnology($arrXmlVals['product']['props']);

            // св-ва sku
            $arrXmlValsPropsSku = self::getXmlData($obXml, self::$arrXmlPropsSku, 'sku', ['photos']);
            // проверяем, что sku имеет размерный ряд
            self::checkHasSize($arrXmlValsPropsSku);
            // добавляем цвет в sku
            self::checkColor($obXml, $arrXmlValsPropsSku);

            // все поля и св-ва из xml
            $arrXmlVals = array_merge($arrXmlVals, $arrXmlValsPropsSku);

            // получаем св-ва ИБ
            // формируем все св-ва для установки
            $arrAllIblockProps = $arrItemProps = [];
            foreach (Ocs\ProductConfig::$arrPropsMap as $item_type => $arProp) {
                foreach ($arProp as $prop) {
                    if (is_array($prop)) {
                        $arrAllIblockProps[$item_type] = array_merge($arrAllIblockProps[$item_type], array_values($prop));
                    } else {
                        $arrAllIblockProps[$item_type][] = $prop;
                    }
                }
            }

            // св-ва ИБ товара
            $arrItemProps['product']['props'] = self::getIblockProps(self::$productIblockId, $arrAllIblockProps['product'], self::$arrIblockLabelsProps);

            // перед формированием св-в sku, определяем св-во размера
            $r = self::checkSize($obXml, $arrAllIblockProps['sku']);
            if($r){
                throw new \Exception($r);
            }

            // св-ва ИБ sku
            $arrItemProps['sku']['props'] = self::getIblockProps(self::$offerIblockId, $arrAllIblockProps['sku']);

            // свойства для установки в товаре
            $PROP = [];
            $PROP['product'] = self::prepareIblockProps(
                $arrXmlVals['product']['props'],
                $arrItemProps['product']['props'],
                Ocs\ProductConfig::$arrPropsMap['product'],
                false,
                self::$arrIblockLabelsProps
            );

            // свойства для установки в sku
            foreach ($arrXmlVals['sku']['item'] as $arrXmlValsSku) {
                $PROP['sku'][$arrXmlValsSku[Ocs\ProductConfig::$xmlArticulTag]] = self::prepareIblockProps(
                    $arrXmlValsSku,
                    $arrItemProps['sku']['props'],
                    Ocs\ProductConfig::$arrPropsMap['sku'],
                    false
                );
            }

            // проверяем, есть ли товар
            $PRODUCT_ID = 0;
            $rsItems = \CIBlockElement::GetList(
                [],
                [
                    "IBLOCK_ID" => self::$productIblockId,
                    "PROPERTY_" . Ocs\ProductConfig::$articulPropCode => self::$artnumber
                ],
                false,
                false,
                ["ID", "IBLOCK_ID"]
            );

            if ($arItems = $rsItems->Fetch()) {
                $PRODUCT_ID = $arItems["ID"];
            };

            if ($PRODUCT_ID && !Ocs\ProductConfig::$updateItem) {
                throw new \Exception("В настройках запрещено обновление товаров.");
            }

            if($PRODUCT_ID && !Ocs\ProductConfig::$updatePictures)
                self::$arLog['WARN'][] = "В настройках запрещено обновление картинок товара";

            if ($PRODUCT_ID) {    //если товар уже есть, то апдейтим

                global $USER;
                $el = new \CIBlockElement;

                $arLoadProductArray = [];

                // картинки в товаре (св-во)
                if(
                    Ocs\ProductConfig::$picturesLocation == 'item' &&
                    Ocs\ProductConfig::$updatePictures
                ){
                    // не обновляем, если не разрешено обновлять одну
                    if(!Ocs\ProductConfig::$updatePicturesEvenIfOne && count(self::$arImages) == 1){

                    }else {
                        self::makeImages($PROP['product'], 'create');
                        // удаляем старые картинки, если есть новые
                        if (!empty($PROP['product'][self::$picturePropId])) {
                            self::makeImages($PROP['product'], 'delete', $PRODUCT_ID);
                        }
                    }
                }

                $arLoadProductArray = Array(
                    "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
                    "ACTIVE" => "Y",            // активен
                    "DETAIL_TEXT_TYPE" => "html",
                );

                // картинки в товаре (поля)
                if(
                    Ocs\ProductConfig::$picturesLocation == 'item' &&
                    Ocs\ProductConfig::$updatePictures
                ) {
                    if (self::$arImages[0])
                        $arLoadProductArray['PREVIEW_PICTURE'] = \CFile::MakeFileArray(self::$arImages[0]);
                    if (self::$arImages[1]) {
                        $arLoadProductArray['DETAIL_PICTURE'] = \CFile::MakeFileArray(self::$arImages[1]);
                    } elseif($arLoadProductArray['PREVIEW_PICTURE']) {
                        $arLoadProductArray['DETAIL_PICTURE'] = $arLoadProductArray['PREVIEW_PICTURE'];
                    }
                }

                $arLoadProductArray = array_merge($arLoadProductArray, $arrXmlVals['product']['fields']);

                $r = $el->Update($PRODUCT_ID, $arLoadProductArray);
                if ($r === false) {
                    throw new \Exception("Ошибка обновления товара. " . $el->LAST_ERROR);
                } else {
                    self::$arLog['UPDATED']['PRODUCT_ID'][] = $PRODUCT_ID;
                }

                // обновляем св-ва товара
                \CIBlockElement::SetPropertyValuesEx($PRODUCT_ID, false, $PROP['product']);

                // теперь добавляем торговые предложения

                foreach ($PROP['sku'] as $artnum => $item) {

                    $rsItems = \CIBlockElement::GetList(
                        [],
                        array("IBLOCK_ID" => self::$offerIblockId, "NAME" => $artnum),
                        false,
                        false,
                        array("ID", "IBLOCK_ID", "NAME")
                    );
                    $offerToUpdate = $rsItems->Fetch();

                    $toUpdate = 0;
                    if ($offerToUpdate["NAME"] == $artnum) {     //если артикулы совпадают апдейтим
                        $toUpdate = 1;
                    }

                    // картинки в sku
                    if(
                        Ocs\ProductConfig::$picturesLocation == 'sku' &&
                        (!$toUpdate || ($toUpdate && Ocs\ProductConfig::$updatePictures))
                    ) {
                        // не обновляем, если не разрешено обновлять одну
                        if($toUpdate && !Ocs\ProductConfig::$updatePicturesEvenIfOne && count(self::$arImages) == 1){

                        }else {
                            self::makeImages($item, 'create');
                        }
                    }

                    if ($toUpdate) {

                        // удаляем старые картинки в sku, если есть новые
                        if (
                            Ocs\ProductConfig::$picturesLocation == 'sku' &&
                            Ocs\ProductConfig::$updatePictures &&
                            (Ocs\ProductConfig::$updatePicturesEvenIfOne || count(self::$arImages) > 1) &&
                            !empty($item[self::$picturePropId])
                        ) {
                            self::makeImages($item, 'delete', $offerToUpdate["ID"]);
                        }

                        global $USER;
                        $el = new \CIBlockElement;
                        $arLoadProductArray = Array(
                            "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
                            "NAME" => $artnum,
                            "ACTIVE" => "Y",            // активен
                        );

                        // картинки в sku (поля)
                        if(
                            Ocs\ProductConfig::$picturesLocation == 'sku' &&
                            Ocs\ProductConfig::$updatePictures
                        ) {
                            if (self::$arImages[0])
                                $arLoadProductArray['PREVIEW_PICTURE'] = \CFile::MakeFileArray(self::$arImages[0]);
                            if (self::$arImages[1]) {
                                $arLoadProductArray['DETAIL_PICTURE'] = \CFile::MakeFileArray(self::$arImages[1]);
                            } elseif($arLoadProductArray['PREVIEW_PICTURE']) {
                                $arLoadProductArray['DETAIL_PICTURE'] = $arLoadProductArray['PREVIEW_PICTURE'];
                            }
                        }

                        $r = $el->Update($offerToUpdate["ID"], $arLoadProductArray);
                        if ($r === false) {
                            throw new \Exception("Ошибка обновления sku при обновлении товара. " . $el->LAST_ERROR);
                        } else {
                            self::$arLog['UPDATED']['SKU_ID'][] = $offerToUpdate["ID"];
                        }

                        //привязка к эл-ту каталога
                        //$item[$cml2PropId] = $PRODUCT_ID;

                        // обновляем св-ва
                        \CIBlockElement::SetPropertyValuesEx($offerToUpdate["ID"], false, $item);


                    } else {                                                                   //добавляем
                        $el = new \CIBlockElement;

                        $arLoadProductArray = Array(
                            "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
                            "IBLOCK_ID" => self::$offerIblockId,
                            "NAME" => $artnum, // название = артикул sku,
                            "ACTIVE" => Ocs\ProductConfig::$activateSku? "Y": "N",
                        );

                        // картинки в sku (поля)
                        if(Ocs\ProductConfig::$picturesLocation == 'sku') {
                            if (self::$arImages[0])
                                $arLoadProductArray['PREVIEW_PICTURE'] = \CFile::MakeFileArray(self::$arImages[0]);
                            if (self::$arImages[1]) {
                                $arLoadProductArray['DETAIL_PICTURE'] = \CFile::MakeFileArray(self::$arImages[1]);
                            } elseif($arLoadProductArray['PREVIEW_PICTURE']) {
                                $arLoadProductArray['DETAIL_PICTURE'] = $arLoadProductArray['PREVIEW_PICTURE'];
                            }
                        }

                        if (!($SKU_ID = $el->Add($arLoadProductArray))) {
                            throw new \Exception("Ошибка добавления sku при обновлении товара. " . $el->LAST_ERROR);
                        } else {

                            self::$arLog['CREATED']['SKU_ID'][] = $SKU_ID;

                            //привязка к эл-ту каталога
                            $item[self::$cml2PropId] = $PRODUCT_ID;

                            // обновляем св-ва
                            \CIBlockElement::SetPropertyValuesEx($SKU_ID, false, $item);

                            // добавляем sku в каталог
                            $r = self::addItemToCatalog($SKU_ID, \Bitrix\Catalog\ProductTable::TYPE_OFFER);
                            if ($r) {
                                throw new \Exception("Ошибка при добавлении в каталог. SKU_ID = ${SKU_ID}. " . $r);
                            }
                        }
                    }

                }

            } else { //если товара нет добавляем

                // добавляем в ИБ
                $el = new \CIBlockElement;

                // картинки в товаре
                if(Ocs\ProductConfig::$picturesLocation == 'item'){
                    self::makeImages($PROP['product'], 'create');
                }

                // симв. код
                $params = array(
                    "max_len" => "100",
                    "change_case" => "L",
                    "replace_space" => "_",
                    "replace_other" => "_",
                    "delete_repeat_replace" => "true",
                    "use_google" => "false",
                );
                $code = \CUtil::translit($arrXmlVals['product']['fields']['NAME'], "ru", $params);
                $code = preg_replace("/[^A-Za-z0-9_\-]/", "_", $code);

                $arLoadProductArray = Array(
                    'MODIFIED_BY' => $GLOBALS['USER']->GetID(), // элемент изменен текущим пользователем
                    'IBLOCK_ID' => self::$productIblockId,
                    'CODE' => $code,
                    'ACTIVE' => 'Y', // активен
                    "DETAIL_TEXT_TYPE" => "html",
                );

                // картинки в товаре (поля)
                if(Ocs\ProductConfig::$picturesLocation == 'item') {
                    if (self::$arImages[0])
                        $arLoadProductArray['PREVIEW_PICTURE'] = \CFile::MakeFileArray(self::$arImages[0]);
                    if (self::$arImages[1]) {
                        $arLoadProductArray['DETAIL_PICTURE'] = \CFile::MakeFileArray(self::$arImages[1]);
                    } elseif($arLoadProductArray['PREVIEW_PICTURE']) {
                        $arLoadProductArray['DETAIL_PICTURE'] = $arLoadProductArray['PREVIEW_PICTURE'];
                    }
                }

                $arLoadProductArray = array_merge($arLoadProductArray, $arrXmlVals['product']['fields']);

                if (!($PRODUCT_ID = $el->Add($arLoadProductArray))) {

                    // пробуем артикул, если уже существует
                    $sym_code = preg_replace("/[^A-Za-z0-9_\-]/", "_", self::$artnumber) . BX_UTF_PCRE_MODIFIER;
                    $arLoadProductArray['CODE'] = ToLower($sym_code);

                    if (!($PRODUCT_ID = $el->Add($arLoadProductArray))) {
                        throw new \Exception("Ошибка добавления нового товара. " . $el->LAST_ERROR);
                    }

                }

                self::$arLog['CREATED']['PRODUCT_ID'][] = $PRODUCT_ID;

                // добавляем св-ва
                \CIBlockElement::SetPropertyValuesEx($PRODUCT_ID, false, $PROP['product']);

                // добавляем торговые предложения

                foreach ($PROP['sku'] as $artnum => $item) {

                    // картинки в sku
                    if(Ocs\ProductConfig::$picturesLocation == 'sku') {
                        self::makeImages($item, "create");
                    }

                    //добавляем в ИБ
                    $el = new \CIBlockElement;
                    $arLoadProductArray = Array(
                        "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
                        "IBLOCK_ID" => self::$offerIblockId,
                        "NAME" => $artnum, // навзние = артикул sku
                        "ACTIVE" => Ocs\ProductConfig::$activateSku? "Y": "N",
                        "DETAIL_PICTURE" => \CFile::MakeFileArray(self::$arImages[0])
                    );

                    // картинки в sku (поля)
                    if(Ocs\ProductConfig::$picturesLocation == 'sku') {
                        if (self::$arImages[0])
                            $arLoadProductArray['PREVIEW_PICTURE'] = \CFile::MakeFileArray(self::$arImages[0]);
                        if (self::$arImages[1]) {
                            $arLoadProductArray['DETAIL_PICTURE'] = \CFile::MakeFileArray(self::$arImages[1]);
                        } elseif($arLoadProductArray['PREVIEW_PICTURE']) {
                            $arLoadProductArray['DETAIL_PICTURE'] = $arLoadProductArray['PREVIEW_PICTURE'];
                        }
                    }

                    if (!($SKU_ID = $el->Add($arLoadProductArray))) {
                        throw new \Exception($el->LAST_ERROR);
                    } else {
                        self::$arLog['CREATED']['SKU_ID'][] = $SKU_ID;
                    }

                    // привязка к эл-ту каталога
                    $item[self::$cml2PropId] = $PRODUCT_ID;

                    // обновляем св-ва
                    \CIBlockElement::SetPropertyValuesEx($SKU_ID, false, $item);

                    // вычисляем размеры для фильтра
                    // \Jamilco\Main\Utils::checkSize($SKU_ID);

                    // добавляем sku в каталог
                    $r = self::addItemToCatalog($SKU_ID, \Bitrix\Catalog\ProductTable::TYPE_OFFER);
                    if ($r) {
                        throw new \Exception("Ошибка при добавлении в каталог. SKU_ID = ${SKU_ID}. " . $r);
                    }

                }

                // добавляем товар в каталог
                /*
                $r = self::addItemToCatalog($PRODUCT_ID, \Bitrix\Catalog\ProductTable::TYPE_SKU);
                if($r){
                    throw new \Exception("product_id = ${PRODUCT_ID}. ".$r);
                }
                */

                // сбрасываем фасетный индекс
                Manager::DeleteIndex(self::$productIblockId);
                Manager::markAsInvalid(self::$productIblockId);
            }

            // Пересчет св-ва материал
            //\Jamilco\Main\Filter::checkMaterialsInItem($PRODUCT_ID);

        } catch (\Exception $e) {
            $result = strip_tags($e->getMessage());
        }

        // удаляем временную папку с картинками
        foreach (self::$arImages as $file) {
            unlink($file);
        }

        if($result){
            self::$arLog['STATUS'] = 'ERROR';
            self::$arLog['MSG'] = $result;
        }
        elseif (!empty(self::$arLog['WARN'])){
            self::$arLog['STATUS'] = 'WARN';
        }

        if(empty(self::$arLog['STATUS']))
            self::$arLog['STATUS'] = 'OK';

        if(!$_REQUEST['file']) {
            file_put_contents($file_prod.".xml", $data, FILE_APPEND | LOCK_EX);
            self::$arLog['XML_FILE'] = $file_prod;
        }else{
            self::$arLog['XML_FILE'] = $_REQUEST['file'];
        }

        if(
            Ocs\ProductConfig::$sendMail &&
            (!Ocs\ProductConfig::$sendOnlyMailError || self::$arLog['STATUS'] != 'OK')
        ) {
            // отправляем e-mail уведомление
            self::sendMessage(self::$arLog);
        }

        return print_r(self::$arLog,1);
    }

    /**
     * Получаем праметры ИБ каталога
     *
     * @return string
     */
    private static function checkIblock()
    {
        Loader::IncludeModule('catalog');
        $result = '';
        try {
            $rsCats = \CCatalog::GetList();
            while ($arCats = $rsCats->Fetch()) {
                if ($arCats["IBLOCK_TYPE_ID"] == "catalog") {

                    /** @var  $productIblockId */
                    self::$productIblockId = (int)$arCats["IBLOCK_ID"];

                } elseif ($arCats["IBLOCK_TYPE_ID"] == "offers") {

                    /** @var  $offerIblockId */
                    self::$offerIblockId = (int)$arCats["IBLOCK_ID"];
                }
            }

            if (!self::$productIblockId || !self::$offerIblockId) {
                $arIBlockIDs = [];
                $rsCatalogs = \CCatalog::GetList(
                    array(),
                    array('PRODUCT_IBLOCK_ID' => 0),
                    false,
                    false,
                    array('IBLOCK_ID')
                );
                while ($arCatalog = $rsCatalogs->Fetch()) {
                    $arCatalog['IBLOCK_ID'] = (int)$arCatalog['IBLOCK_ID'];
                    if ($arCatalog['IBLOCK_ID'] > 0)
                        $arIBlockIDs[] = $arCatalog['IBLOCK_ID'];
                }
                /** предполагаем один каталог на сайте */
                if (count($arIBlockIDs) == 1) {
                    self::$productIblockId = $arIBlockIDs[0];
                }

                $arIBlockIDs = [];
                $rsCatalogs = \CCatalog::GetList(
                    array(),
                    array('PRODUCT_IBLOCK_ID' => self::$productIblockId),
                    false,
                    false,
                    array('IBLOCK_ID')
                );
                while ($arCatalog = $rsCatalogs->Fetch()) {
                    $arCatalog['IBLOCK_ID'] = (int)$arCatalog['IBLOCK_ID'];
                    if ($arCatalog['IBLOCK_ID'] > 0)
                        $arIBlockIDs[] = $arCatalog['IBLOCK_ID'];
                }
                /** предполагаем один каталог на сайте */
                if (count($arIBlockIDs) == 1) {
                    self::$offerIblockId = $arIBlockIDs[0];
                }
            }

            if (!self::$productIblockId || !self::$offerIblockId) {
                throw new \Exception('не найдены инфоблоки товаров.');
            }

            if(Ocs\ProductConfig::$picturesLocation == 'item'){
                $iblockId = self::$productIblockId;
            }elseif(Ocs\ProductConfig::$picturesLocation == 'sku') {
                $iblockId = self::$offerIblockId;
            }
            $rs = \CIBlockProperty::GetList(
                [],
                [
                    "IBLOCK_ID" => $iblockId,
                    "CODE" => Ocs\ProductConfig::$picturePropCode
                ]
            );
            if ($arProp = $rs->GetNext()) {
                self::$picturePropId = $arProp["ID"];
            } else {
                throw new \Exception("невозможно определить свойство для загрузки изображений.");
            }

            if(Ocs\ProductConfig::$picturesIblock) {
                $rs = \CIBlock::GetList(
                    [],
                    ["CODE" => Ocs\ProductConfig::$picturesIblock]
                );
                if ($arIblock = $rs->GetNext()) {
                    self::$picturesIblockId = $arIblock["ID"];
                } else {
                    throw new \Exception("невозможно определить ИБ для загрузки изображений.");
                }
            }

            if(Ocs\ProductConfig::$picturesIblockProp && self::$picturesIblockId) {
                $rs = \CIBlockProperty::GetList(
                    [],
                    [
                        "IBLOCK_ID" => self::$picturesIblockId,
                        "CODE" => Ocs\ProductConfig::$picturesIblockProp
                    ]
                );
                if ($arProp = $rs->GetNext()) {
                    self::$picturesIblockPropId = $arProp["ID"];
                } else {
                    throw new \Exception("невозможно определить свойство ИБ картинок для загрузки изображений.");
                }
            }

            $rs = \CIBlockProperty::GetList([], array("IBLOCK_ID" => self::$offerIblockId, "CODE" => Ocs\ProductConfig::$cml2PropCode));
            if ($arProp = $rs->GetNext()) {
                self::$cml2PropId = $arProp["ID"];
            } else {
                throw new \Exception('невозможно определить свойство для привязки элемента к каталогу.');
            }

            // св-ва типа "метка"
            $arrIblockLabelsProps_ = Ocs\ProductConfig::$arrPropsMap["product"]["метка"];
            if (!is_array($arrIblockLabelsProps_)) {
                self::$arrIblockLabelsProps[] = $arrIblockLabelsProps_;
            } else {
                self::$arrIblockLabelsProps = $arrIblockLabelsProps_;
            }

            // св-ва товара
            self::$arrXmlProps = array_keys(Ocs\ProductConfig::$arrPropsMap['product']);
            // св-ва sku
            self::$arrXmlPropsSku = array_keys(Ocs\ProductConfig::$arrPropsMap['sku']);


        } catch (\Exception $e) {
            $result = strip_tags($e->getMessage());
        }

        return $result;
    }

    /**
     * Сохраняем картинки во временной папке
     *
     * @param $obXml
     */
    static private function checkImages($obXml)
    {
        // получаем коды картинок
        $rsPhotos = $obXml->{Ocs\ProductConfig::$xmlPhotoTag[0]}->{Ocs\ProductConfig::$xmlPhotoTag[1]};
        $arrXmlPhotos = [];
        foreach ($rsPhotos as $rsPhoto) {
            $arrXmlPhotos[] = array(
                'base64' => $rsPhoto->__toString()
            );
        }
        $pathToImg = Application::getDocumentRoot() . Ocs\ProductConfig::$tmpImagePath;
        if (!Directory::isDirectoryExists($pathToImg)) {
            Directory::createDirectory($pathToImg);
        }

        // картинки
        foreach ($arrXmlPhotos as $key => $arrXmlPhoto) {
            $file = $pathToImg . uniqid();
            if ($key == 0) $file .= "_first_photo";
            $file .= ".jpg";
            self::$arImages[] = $file;
            file_put_contents($file, base64_decode($arrXmlPhoto["base64"]));
        }
    }

    /**
     *
     *
     * @param $item
     * @param $action
     * @param null $del_id
     */
    static private function makeImages(&$item, $action, $del_id = null){
        switch ($action){
            case 'create':
                if(self::$picturesIblockId && self::$picturesIblockPropId){
                    // добавляем в ИБ картинок
                    if(!self::$picturesIblockLinkId) {
                        $el = new \CIBlockElement;
                        $name = preg_replace("/[^A-Za-z0-9_\-]/", "_", self::$artnumber);
                        // проверяем, если уже есть
                        $res = \CIBlockElement::GetList(
                            [],
                            ['IBLOCK_ID' => self::$picturesIblockId, 'NAME' => $name],
                            false,
                            false,
                            ['ID']
                        );
                        if ($arItem = $res->fetch()) {
                            $PICTURE_ITEM_ID = $arItem['ID'];
                        }else {
                            $arLoadProductArray = Array(
                                'MODIFIED_BY' => $GLOBALS['USER']->GetID(), // элемент изменен текущим пользователем
                                'IBLOCK_ID'   => self::$picturesIblockId,
                                'NAME'        => $name,
                                'ACTIVE'      => 'Y', // активен
                            );
                            if (!($PICTURE_ITEM_ID = $el->Add($arLoadProductArray))) {
                                throw new \Exception("Ошибка при добавлении картинки в ИБ картинок.");
                            }
                        }
                        if($PICTURE_ITEM_ID){
                            $iblockPictures = [];
                            foreach (self::$arImages as $file) {
                                $iblockPictures[] = \CFile::MakeFileArray($file);
                            }
                            \CIBlockElement::SetPropertyValuesEx(
                                $PICTURE_ITEM_ID,
                                self::$picturesIblockId,
                                [self::$picturesIblockPropId => $iblockPictures]
                            );
                            self::$picturesIblockLinkId = $PICTURE_ITEM_ID;
                        }
                    }
                    $item[self::$picturePropId] = self::$picturesIblockLinkId;
                }else {
                    $item[self::$picturePropId] = [];
                    foreach (self::$arImages as $file) {
                        $item[self::$picturePropId][] = \CFile::MakeFileArray($file);
                    }
                }

                if(!$item[self::$picturePropId])
                    unset($item[self::$picturePropId]);
                break;
            case 'delete':
                if($del_id) {
                    \CIBlockElement::SetPropertyValuesEx(
                        $del_id,
                        false,
                        array(self::$picturePropId => Array("VALUE" => array("del" => "Y")))
                    );
                }
                break;
        }
    }

    /**
     * Определяем св-во для размера
     *
     * @param $obXml
     * @param $arrIblockProps
     * @return string
     */
    private static function checkSize($obXml, &$arrIblockProps)
    {
        $result = '';
        try {
            $sizeTypeVal = trim($obXml->{Ocs\ProductConfig::$xmlSizeTypeTag}->__toString());
            $sizeProp = Ocs\ProductConfig::$arrSizePropMap[$sizeTypeVal];
            if(empty($sizeProp) && self::$guessSizeType){
                $sizeProp = self::$guessSizeType;
            }
            if (!empty($sizeProp)) { // тип размера найден
                // определим св-во размера
                Ocs\ProductConfig::$arrPropsMap['sku']['size'] = $sizeProp;
                foreach ($arrIblockProps as $key => $prop) {
                    if (
                        array_search($prop, array_values(Ocs\ProductConfig::$arrSizePropMap)) !== false &&
                        $prop != $sizeProp
                    ) {
                        unset($arrIblockProps[$key]);
                    }
                }
            }else{
                if(self::$hasSize)
                    throw new \Exception("Sku имеет размер, но св-во размера не может быть определено.");
            }
        }catch (\Exception $e){
            $result = strip_tags($e->getMessage());
        }
        return $result;
    }

    /**
     * Определяем цвет
     *
     * @param $obXml
     * @param $arrXmlVals
     */
    private static function checkColor($obXml, &$arrXmlVals){
        $xml_color = trim($obXml->{Ocs\ProductConfig::$xmlColorTag[0]}->__toString());
        if (!$xml_color)
            $xml_color = trim($obXml->{Ocs\ProductConfig::$xmlColorTag[1]}->__toString());
        if($xml_color) {
            foreach ($arrXmlVals['sku']['item'] as $k => $arVal) {
                $arrXmlVals['sku']['item'][$k][Ocs\ProductConfig::$xmlColorTag[0]] = $xml_color;
            }
        }
        else{
            self::$arLog['WARN'][] = 'Цвет не задан';
        }
    }

    /**
     * Определяем пол
     *
     * @param $obXml
     * @param $arrXmlVals
     */
    private static function checkGender(&$arrXmlVals){
        $xmlAgeVal = $arrXmlVals[Ocs\ProductConfig::$xmlAgeTag];
        $xmlGenderVal = $arrXmlVals[Ocs\ProductConfig::$xmlGenderTag];
        $setGenderVal = '';
        $arGenderVals = [];
        $db_res = \CIBlockPropertyEnum::GetList(
          [],
          ['IBLOCK_ID' => self::$productIblockId, 'CODE' => Ocs\ProductConfig::$genderPropCode]
        );
        while($arProp = $db_res->fetch()){
            $arGenderVals[] = $arProp['VALUE'];
        }
        // вначале пробуем определить детское
        // ожидаем: <age>Детское</age> или <age>Взрослое</age>
        foreach ($arGenderVals as $val){
            $cmpValue = substr($val, 0, 3); // первые три символа
            if (substr_count(ToLower($xmlAgeVal), ToLower($cmpValue))) {
                $setGenderVal = $val;
                break;
            }
        }
        // если не детское, определяем пол
        // ожидаем: <sex>МУЖ</sex> или <sex>ЖЕН</sex>
        if(!$setGenderVal){
            foreach ($arGenderVals as $val){
                $cmpValue = substr($val, 0, 3); // первые три символа
                if (substr_count(ToLower($xmlGenderVal), ToLower($cmpValue))) {
                    $setGenderVal = $val;
                    break;
                }
            }
        }
        if($setGenderVal){
            $arrXmlVals[Ocs\ProductConfig::$xmlGenderTag] = $setGenderVal;
        }
    }

    /**
     * Определяем св-во "Технология"
     *
     * @param $obXml
     * @param $arrXmlVals
     */
    private static function checkTechnology(&$arrXmlVals){
        $xmlTechnologyVal = $arrXmlVals[Ocs\ProductConfig::$xmlTechnologyTag];
        if(is_array($xmlTechnologyVal))
            TrimArr($xmlTechnologyVal);
        if(empty($xmlTechnologyVal)) return;

        $linkIblockId = 0;
        $db_res = \CIBlockProperty::GetList(
            [],
            ["IBLOCK_ID" => self::$productIblockId, "CODE" => Ocs\ProductConfig::$technologyPropCode]
        );
        if($arProp = $db_res->fetch()){
            $linkIblockId = $arProp["LINK_IBLOCK_ID"];
        }

        if($linkIblockId) {
            $db_res = \CIBlockElement::GetList(
              [],
              ['IBLOCK_ID' => $linkIblockId],
              false,
              false,
              ["ID", "NAME"]
            );
            while($arItem = $db_res->fetch()) {
                if (is_array($xmlTechnologyVal)) {
                    foreach ($xmlTechnologyVal as $k => $val) {
                        if ($arItem['ID'] == trim($val)) {
                            $arrXmlVals[Ocs\ProductConfig::$xmlTechnologyTag][$k] = $arItem["NAME"];
                        }
                    }
                } else {
                    if ($arItem['ID'] == trim($xmlTechnologyVal)) {
                        $arrXmlVals[Ocs\ProductConfig::$xmlTechnologyTag] = $arItem["NAME"];
                        break;
                    }
                }
            }
        }

    }

    /**
     * Проверяем, что sku имеет размерный ряд
     *
     * @param $arrXmlValsProps
     */
    private static function checkHasSize($arrXmlValsProps){
        $sizes = array_column($arrXmlValsProps["sku"]["item"], 'size');
        $not_empty_sizes = array_filter($sizes);
        if(count($sizes) != count($not_empty_sizes)) {
            self::$arLog['WARN'][] = 'Не во всех sku задан размер.';
        }
        if($not_empty_sizes){
            $size = reset($not_empty_sizes);
            self::$hasSize = true;
            // попробуем определить тип размера на слуйчай, если не будет явного соотв. по типу товара
            preg_match("/[SMLX\/]/is", $size, $matches);
            if($matches[0])
                self::$guessSizeType = 'SIZES_CLOTHES';
        }else{
            self::$hasSize = false;
        }
    }

    /**
     * Добавляем товар в каталог
     *
     * @param $itemId
     * @param $type
     * @return string
     */
    private static function addItemToCatalog($itemId, $type)
    {

        $result = '';

        try {

           if(!$type){
               throw new \Exception('Не задан тип товара каталога.');
           }

            $useStoreControl = (string)\Bitrix\Main\Config\Option::get('catalog', 'default_use_store_control') === 'Y';

            $arFields = array(
                "ID"             => $itemId,
                "TYPE"           => $type,
                'QUANTITY_TRACE' => \Bitrix\Catalog\ProductTable::STATUS_DEFAULT,
                'CAN_BUY_ZERO'   => \Bitrix\Catalog\ProductTable::STATUS_DEFAULT,
                'WEIGHT'         => Ocs\ProductConfig::$catalogWeight,
                'MEASURE'        => Ocs\ProductConfig::$catalogMeasureId

            );

            if (!$useStoreControl) {
                // выключен складской учет
                $arFields['QUANTITY'] = 0;
            }

            $r = \Bitrix\Catalog\Model\Product::Add(
                ['fields' => $arFields]
            );
            if (!$r->isSuccess()) {
                throw new \Exception("Ошибка добавления в каталог.");
            }

            // добавление коэффициента единицы измерения товара
            $r = \Bitrix\Catalog\MeasureRatioTable::add(
                array(
                    'PRODUCT_ID' => $itemId,
                    'RATIO' => Ocs\ProductConfig::$catalogMeasureRatio
                )
            );
            if (!$r->isSuccess()) {
                throw new \Exception("Ошибка добавления коэффициента единицы измерения для товара. " . implode('. ', $r->getErrorMessages()));
            }

            if(Ocs\ProductConfig::$useCatalogBasePriceInitial) {
                // инициализация базовой цены
                $r = \Bitrix\Catalog\Model\Price::add(
                    array(
                        'PRODUCT_ID' => $itemId,
                        'CATALOG_GROUP_ID' => Ocs\ProductConfig::$catalogBasePriceId,
                        'PRICE' => Ocs\ProductConfig::$catalogBasePriceInitial,
                        'CURRENCY' => 'RUB'
                    )
                );
                if (!$r->isSuccess()) {
                    throw new \Exception("Ошибка добавления цены. " . implode('. ', $r->getErrorMessages()));
                }
            }

        }catch (\Exception $e){
            $result = strip_tags($e->getMessage());
        }

        return $result;

    }

    /**
     * Получаем поля товара
     *
     * @param $obXml
     * @param $arFieldsMap
     * @return array
     */
    private static function getXmlFields($obXml, $arFieldsMap)
    {
        $arFields = [];
        foreach ($arFieldsMap as $xmlField => $iblockField) {
            $val = trim($obXml->{$xmlField}->__toString());
            if(empty($val))
                continue;
            switch ($xmlField){
                case "active_from":
                case "active_to":
                    $val = ConvertDateTime($val, \CSite::GetDateFormat("FULL"), "ru");
                    $arFields[$iblockField] = $val;
                    break;
                case "sections_id":
                    $val = self::getDelimiterVals($val);
                    if(!empty($val)){
                        if(is_array($val)){
                            $arFields["IBLOCK_SECTION"] = $val;
                        }else{
                            $arFields["IBLOCK_SECTION_ID"] = $val;
                        }
                    }
                    break;
                default:
                    $arFields[$iblockField] = $val;
            }

        }

        return $arFields;
    }

    /**
     * извлекаем данные в соотв. с заданными полями
     *
     * @param $sxi
     * @param $arrFields
     * @param null $startNode
     * @param array $excludeNodes
     * @return array
     */
    private static function getXmlData($sxi, $arrFields, $startNode = null, $excludeNodes = [])
    {
        $a = [];

        if($startNode !== null)
            $sxi = $sxi->{$startNode};

        for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {

            $key = trim($sxi->key());
            if(array_search($key, $excludeNodes) !== false)
                continue;

            if($sxi->hasChildren()){
                $arr = self::getXmlData($sxi->current(), $arrFields, null, $excludeNodes);
                if(!empty($arr)){
                    if(count($arr) > 1)
                        $a[$key][] = $arr;
                    else
                        $a[$key] = $arr;
                }
            }
            else{
                if(array_search($key, $arrFields) !== false){
                    $val = trim($sxi->current()->__toString());
                    $val = self::getDelimiterVals($val);
                    $a[$key] = $val;
                }
            }
        }
        return $a;
    }


    /**
     * извлекаем данные из атрибутов
     *
     * @param $sxi
     * @param $arrAttr
     * @param null $startNode
     * @param bool $arrToHtml - конвертировать массив
     * @return array
     */
    private static function getXmlDataAttr ($sxi, $arrAttr, $startNode = null){
        $a = [];

        if($startNode !== null)
            $sxi = $sxi->{$startNode};

        foreach ($arrAttr as $attrName){
            $node = $sxi->xpath("property[@name='" . $attrName . "']")[0];
            if($attrName == 'метка'){
                $val = trim($node->__toString());
                $val = self::getDelimiterVals($val);
                $a[$attrName] = $val;
            }elseif ($attrName == 'материал') {
                for ($node->rewind(); $node->valid(); $node->next()) {
                    $title = trim($node->current()->attributes());
                    foreach ($node->current() as $prop) {
                        $name = trim($prop[0]->attributes());
                        $val = trim($prop->__toString());
                        $a[$attrName][$title] .= (($a[$attrName][$title]) ? ', ' : '') . $name . ": " . $val;
                    }
                }
                if (in_array($attrName, Ocs\ProductConfig::$arrPropsAttrHtml)) {
                    $a_ = '';
                    foreach ($a[$attrName] as $title => $val) {
                        $a_ .= $title . ': ' . $val . "<br>";
                    }
                    $a[$attrName] = $a_;
                }
            }
        }

        return $a;
    }

    /**
     * получаем заданные свойства элемента ИБ
     *
     * @param $iblockId
     * @param $arrPropsCodes
     * @param array $arrLabelsProps
     * @return array
     */
    private static function getIblockProps($iblockId, $arrPropsCodes, $arrLabelsProps = [])
    {
        \Bitrix\Main\Loader::includeModule('highloadblock');

        /** @var  $arrProps - массив свойств инфоблока (ид, тип, привязка к ИБ ..) */
        $arrProps = array();
        $rsProps = \CIBlockProperty::GetList(
            array(),
            array("IBLOCK_ID" => $iblockId)
        );
        while ($arProps = $rsProps->Fetch()) {
            if (array_search($arProps['CODE'], $arrPropsCodes) === false &&
                array_search($arProps['CODE'], $arrLabelsProps) === false ) {
                continue;
            }
            $arrProps[$arProps["CODE"]] = array(
                "ID"                 => $arProps["ID"],
                "PROPERTY_TYPE"      => $arProps["PROPERTY_TYPE"],
                "USER_TYPE"          => $arProps["USER_TYPE"],
                "USER_TYPE_SETTINGS" => $arProps["USER_TYPE_SETTINGS"],
                "MULTIPLE"           => $arProps["MULTIPLE"],
                "LINK_IBLOCK_ID"     => $arProps['LINK_IBLOCK_ID']
            );
        }

        /** @var  $arrPropsResult - результир. массив свойств инфоблока
         *
         * добавим значения для св-в типа список и привязка к эл-ту
         * эти свойства будут обновляться по значению
         */
        $arrPropsResult = [];
        foreach ($arrProps as $propCode => $arrProp) {

            $propType = $arrProp["PROPERTY_TYPE"];

            if($arrProp["USER_TYPE"] == "directory") { //если свойство HL-блок
                $propType = "HL";
                $arrPropsResult[$propType][$propCode] = array(
                    "PROP_ID"   => $arrProp["ID"]
                );

                $tbl_name = $arrProp["USER_TYPE_SETTINGS"]["TABLE_NAME"];

                $rsDataTbl = Highloadblock\HighloadBlockTable::getList(array('filter' => array('TABLE_NAME' => $tbl_name)));
                if (!($hldata = $rsDataTbl->fetch())) {
                } else {
                    $entity = Highloadblock\HighloadBlockTable::compileEntity($hldata);  // получаем рабочую сущность
                    $entity_data_class = $entity->getDataClass(); // экземпляр класса

                    $rsData = $entity_data_class::getList(
                    array(
                        "select" => array("*"),
                        "filter" => array(),
                        "limit"  => false,
                        "order"  => array("ID" => "ASC")
                    ));
                    while($arData = $rsData->Fetch()){
                        $arrPropsResult[$propType][$propCode]['VALS'][] = $arData;
                    }
                }
            } elseif ($propType == "L") {  //если свойство типа Список
                $arrPropsResult[$propType][$propCode] = array(
                    "PROP_ID"   => $arrProp["ID"],
                    //"MULTIPLE"  => $arrProp["MULTIPLE"] // пока не будем проверять корректность на множественность значений
                );

                $rsInd = \CIBlockPropertyEnum::GetList(
                    array(),
                    array("IBLOCK_ID" => $iblockId, "PROPERTY_ID" => $arrProp['ID'])
                );

                while ($arInd = $rsInd->GetNext()) {
                    $arrPropsResult[$propType][$propCode]['VALS'][$arInd['ID']] = array(
                        'VALUE'    => $arInd['VALUE'],
                        'VALUE_ID' => $arInd['ID']
                    );
                }
            } elseif ($propType == "E") { //если свойство типа Привязка к элементу
                $arrPropsResult[$propType][$propCode] = array(
                    "PROP_ID"   => $arrProp["ID"],
                    //"MULTIPLE"  => $arrProp["MULTIPLE"] // пока не будем проверять корректность на множественность значений
                );

                $rsTypeE = \CIBlockElement::GetList(
                    array(),
                    array(
                        'IBLOCK_ID' => $arrProp['LINK_IBLOCK_ID']
                    ),
                    false,
                    false
                );
                while ($arrTypeE = $rsTypeE->GetNext()) {
                    $arrPropsResult[$propType][$propCode]['VALS'][$arrTypeE["ID"]] = array(
                        'VALUE_ID' => $arrTypeE["ID"],
                        'VALUE'    => $arrTypeE["NAME"],
                        'LINKED_ITEM' => $arrTypeE
                    );
                }
            } else{
                $arrPropsResult[$propType][$propCode] = array(
                    "PROP_ID"   => $arrProp["ID"]
                );
            }
        }
        return $arrPropsResult;
    }

    /**
     * формируем массив свойств для обновления
     * добавляем новые значения, если их нет
     * @param       $arXmlProps
     * @param       $arIblockPropsByType
     * @param       $arMapping
     * @param bool  $makeNewVals
     * @param array $arrLabelsProps
     *
     * @return array
     */
    private static function prepareIblockProps($arXmlProps, $arIblockPropsByType, $arMapping, $makeNewVals = false, $arrLabelsProps = [])
    {
        $arPreparedProps = [];
        foreach($arXmlProps as $xmlPropCode => $xmlProp){
            foreach($arIblockPropsByType as $propType => $arIblockProps) {
                $iblockPropCode = $arMapping[$xmlPropCode];
                $iblockPropId = $arIblockProps[$iblockPropCode]["PROP_ID"];

                if(array_search($iblockPropCode, $arrLabelsProps)!== false) // св-во типа "метка"
                    continue;

                if (array_search($iblockPropCode, array_keys($arIblockProps)) !== false) {
                    switch($propType){
                        case 'N': // число
                        case 'S': // строка
                            $arPreparedProps[$iblockPropCode] = $xmlProp;
                            break;
                        case 'L'://список
                            if(is_array($xmlProp)) { // неск. значений
                                foreach ($xmlProp as $value) {
                                    $f_found = 0;
                                    foreach($arIblockProps[$iblockPropCode]["VALS"] as $arValue) {
                                        $cmpValue = ToLower(trim($arValue['VALUE']));
                                        if (ToLower(trim($value)) == $cmpValue) {
                                            $arPreparedProps[$iblockPropId][] = $arValue['VALUE_ID'];
                                            $f_found = 1;
                                            break;
                                        }
                                    }
                                    if(!$f_found && $makeNewVals){
                                        // добавляем значение, если его нет
                                        if ($valueId = \CIBlockPropertyEnum::Add(
                                            Array('PROPERTY_ID' => $iblockPropId, 'VALUE' => $value)
                                        )) {
                                            $arPreparedProps[$iblockPropId][] = $valueId;
                                        }
                                    }
                                }
                            }else{
                                $f_found = 0;
                                foreach($arIblockProps[$iblockPropCode]["VALS"] as $arValue) {
                                    $cmpValue = ToLower(trim($arValue['VALUE']));

                                    if (ToLower(trim($xmlProp)) == $cmpValue) {
                                        $arPreparedProps[$iblockPropId][] = $arValue['VALUE_ID'];
                                        $f_found = 1;
                                        break;
                                    }
                                }
                                if(!$f_found && $makeNewVals){
                                    // добавляем значение, если его нет
                                    if ($valueId = \CIBlockPropertyEnum::Add(
                                        Array('PROPERTY_ID' => $iblockPropId, 'VALUE' => $xmlProp)
                                    )) {
                                        $arPreparedProps[$iblockPropId][] = $valueId;
                                    }
                                }
                            }
                            break;
                        case 'HL': //HL-блок
                            if(is_array($xmlProp)) { // неск. значений
                                foreach ($xmlProp as $value) {
                                    $f_found = 0;
                                    foreach($arIblockProps[$iblockPropCode]["VALS"] as $arValue) {
                                        $cmpValue = ToLower(trim($arValue['UF_NAME']));
                                        if (ToLower(trim($value)) == $cmpValue) {
                                            $arPreparedProps[$iblockPropId][] = $arValue["UF_XML_ID"];
                                            $f_found = 1;
                                            break;
                                        }
                                    }
                                    if(!$f_found && $makeNewVals) {
                                        // добавляем значение, если его нет
                                        // todo
                                    }
                                }
                            }else{
                                $f_found = 0;
                                foreach($arIblockProps[$iblockPropCode]["VALS"] as $arValue) {
                                    $cmpValue = ToLower(trim($arValue['UF_NAME']));
                                    if (ToLower(trim($xmlProp)) == $cmpValue) {
                                        $arPreparedProps[$iblockPropId][] = $arValue["UF_XML_ID"];
                                        $f_found = 1;
                                        break;
                                    }
                                }
                                if(!$f_found && $xmlProp && in_array($iblockPropCode, Ocs\ProductConfig::$arMandatoryProps)){
                                    self::$arLog['WARN'][] = "Не заведено значение ${xmlProp} для св-ва ${iblockPropCode}";
                                }
                                if(!$f_found && $makeNewVals) {
                                    // добавляем значение, если его нет
                                    // todo
                                }
                            }
                            break;
                        case 'E': //привязка к ИБ
                            if(is_array($xmlProp)) { // неск. значений
                                foreach ($xmlProp as $value) {
                                    $f_found = 0;
                                    foreach($arIblockProps[$iblockPropCode]["VALS"] as $arValue) {
                                        $cmpValue = ToLower(trim($arValue['VALUE']));
                                        if (ToLower(trim($value)) == $cmpValue) {
                                            $arPreparedProps[$iblockPropId][] = $arValue['VALUE_ID'];
                                            $f_found = 1;
                                            break;
                                        }
                                    }
                                    if(!$f_found && $makeNewVals){
                                        // добавляем значение, если его нет
                                        // todo
                                    }
                                }
                            }else{
                                $f_found = 0;
                                foreach($arIblockProps[$iblockPropCode]["VALS"] as $arValue) {
                                    $cmpValue = ToLower(trim($arValue["VALUE"]));
                                    if (ToLower(trim($xmlProp)) == $cmpValue) {
                                        $arPreparedProps[$iblockPropId][] = $arValue['VALUE_ID'];
                                        $f_found = 1;
                                        break;
                                        // добавляем значение, если его нет
                                        // todo

                                    }
                                }
                                if(!$f_found && $makeNewVals){
                                    // добавляем значение, если его нет
                                    // todo
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        // св-во типа метка
        if(!empty($arrLabelsProps)) {
            $arPreparedLabelProps = self::makeLabelProp($arXmlProps, $arMapping, $arrLabelsProps, $arIblockPropsByType);
            foreach ($arPreparedLabelProps as $labelPropId => $labelProp){
                $arPreparedProps[$labelPropId] = $labelProp;
            }
        }
        return $arPreparedProps;
    }

    /**
     * возвращем св-во типа "метка"
     *
     * @param $arXmlProps
     * @param $arMapping
     * @param $arrLabelsProps
     * @param $arIblockPropsByType
     * @return array
     */
    private static function makeLabelProp($arXmlProps, $arMapping, $arrLabelsProps, $arIblockPropsByType)
    {
        // для определения метки по значению
        $arIblock2XmlLabels = array(
            "NEWPRODUCT"  => "Новинка",
            "SALELEADER"  => "Хит продаж"
        );
        $arPreparedProps = [];
        $arLabelsToSet = [];

        foreach (array_keys($arXmlProps) as $xmlProp){
            $iblockProp = $arMapping[$xmlProp];
            if(!empty($iblockProp)) {
                if (is_array($iblockProp)) {
                    foreach ($iblockProp as $iblockProp_) {
                        $xmlLabel_ = Ocs\ProductConfig::$arIblock2XmlLabels[$iblockProp_];
                        $f_set = 0;
                        if(is_array($arXmlProps[$xmlProp])){
                            if(array_search($xmlLabel_, $arXmlProps[$xmlProp]) !== false)// св-во заданно в xml-выгрузке
                                $f_set = 1;
                        }
                        else{
                            if($xmlLabel_== $arXmlProps[$xmlProp])// св-во заданно в xml-выгрузке
                                $f_set = 1;
                        }
                        if (
                            !empty($xmlLabel_) && // cв-во явл. меткой в xml
                            array_search($iblockProp_, $arrLabelsProps) !== false && // св-во явл. меткой в ИБ
                            $f_set
                        )
                        {
                            $arLabelsToSet[] = $iblockProp_;
                        }
                    }
                } else {
                    $xmlLabel = Ocs\ProductConfig::$arIblock2XmlLabels[$iblockProp];
                    $f_set = 0;
                    if(is_array($arXmlProps[$xmlProp])){
                        if(array_search($xmlLabel, $arXmlProps[$xmlProp]) !== false)// св-во заданно в xml-выгрузке
                            $f_set = 1;
                    }
                    else{
                        if($xmlLabel == $arXmlProps[$xmlProp])// св-во заданно в xml-выгрузке
                            $f_set = 1;
                    }

                    if (
                        !empty($xmlLabel) && // cв-во явл. меткой в xml
                        array_search($iblockProp, $arrLabelsProps) !== false && // св-во явл. меткой в ИБ
                        $f_set
                    )
                    {
                        $arLabelsToSet[] = $iblockProp;
                    }
                }
            }
        }
        foreach ($arrLabelsProps as $labelProp){
            $labelPropId = $arIblockPropsByType["L"][$labelProp]["PROP_ID"];
            $labelValueId = reset($arIblockPropsByType["L"][$labelProp]["VALS"])["VALUE_ID"];
            if(array_search($labelProp, $arLabelsToSet) !== false){ // устанавливаем
                $arPreparedProps[$labelPropId][] = $labelValueId;
            }
            else{ // снимаем
                $arPreparedProps[$labelPropId] = false;
            }
        }
        return $arPreparedProps;
    }

    /**
     * определяем основной раздел каталога
     *
     * @param        $string
     * @param        $iblockId
     * @param string $delimiter
     *
     * @return int
     */
    private static function getMainSectionId($string, $iblockId, $delimiter = '/')
    {
        $sectionId = 0;
        $arSecNames = explode($delimiter, $string);
        $arSections = [];
        $tree = \CIBlockSection::GetTreeList(
            $arFilter = array('IBLOCK_ID' => $iblockId),
            $arSelect = array()
        );
        while($sec = $tree->GetNext()) {
            $arSections[] = $sec;
        }
        foreach($arSecNames as $secName){
            foreach($arSections as $section)
            if(ToLower(trim($secName)) == ToLower(trim($section["NAME"]))){
                $sectionId = $section["ID"];
                break;
            }
        }
        return $sectionId;
    }

    /**
     * преобразуем значения св-ва с разделителями в список
     *
     * @param        $prop
     * @param string $delimiter
     *
     * @return array
     */
    private static function getDelimiterVals($prop, $delimiter = ',')
    {
        $propList = explode($delimiter, $prop);
        if(count($propList) > 1){
            foreach ($propList as &$prop_)
                $prop_ = trim($prop_);
            $prop = $propList;
        }
        return $prop;
    }

    private static function checkXml($data)
    {
        $result = '';
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        if (!$doc->loadXML($data)) {
            foreach(libxml_get_errors() as $xml_error) {
                $arrXmlErr[] = $xml_error->message;
            }
            $result = implode('. ', $arrXmlErr);
        }
        return $result;
    }

    /**
     * Отправить Email
     *
     * @param $arLog
     */
    private static function sendMessage($arLog)
    {
        if(empty(Ocs\ProductConfig::$arMailReceivers))
            return;

        $mailReceivers = implode(',', Ocs\ProductConfig::$arMailReceivers);

        $subject =  Ocs\ProductConfig::$serverName.". OCS PRODUCT.";
        if($arLog['STATUS'] == 'ERROR')
            $subject .= " Ошибка!";
        elseif($arLog['STATUS'] == 'WARN')
            $subject .= " Предупреждение!";

        // log file
        $tmpFile = Application::getDocumentRoot().Ocs\ProductConfig::$pathToLog.'product.log';
        file_put_contents($tmpFile, print_r($arLog,1));

        // boundary
        $semi_rand = md5(time());
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

        $headers = "From: ".\COption::GetOptionString('main', 'email_from', '')."\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed;\r\n";
        $headers .= " boundary=\"{$mime_boundary}\"";

        $msg = date("d.m.Y  H:i:s").".";
        if($arLog['ARTNUMBER'])
            $msg .= " ARTNUMBER = ${arLog['ARTNUMBER']}.";
        $msg .= " STATUS = ${arLog['STATUS']}.";

        // multipart boundary
        $body = "--{$mime_boundary}\r\n" . "Content-Type: text/html; charset=\"UTF-8\"\r\n" .
            "Content-Transfer-Encoding: 7bit\r\n\r\n" .$msg. "\r\n\r\n";

        if(is_file($tmpFile)){
            $body .= "--{$mime_boundary}\r\n";
            $fp =    @fopen($tmpFile,"rb");
            $data =  @fread($fp,filesize($tmpFile));

            @fclose($fp);
            $data = chunk_split(base64_encode($data));
            $body .= "Content-Type: application/octet-stream; name=\"".basename($tmpFile)."\"\r\n" .
                "Content-Description: ".basename($tmpFile)."\r\n" .
                "Content-Disposition: attachment;\r\n" . " filename=\"".basename($tmpFile)."\"; size=".filesize($tmpFile).";\r\n" .
                "Content-Transfer-Encoding: base64\r\n\r\n" . $data . "\r\n\r\n";
        }
        $body .= "--{$mime_boundary}--";

        mail($mailReceivers, $subject, $body, $headers);

        unlink($tmpFile);
    }
}