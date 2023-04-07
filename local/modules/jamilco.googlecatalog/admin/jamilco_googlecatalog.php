<?
/** @global CMain $APPLICATION */
use Bitrix\Main,
    Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$module_id = 'jamilco.googlecatalog';
if ($APPLICATION->GetGroupRight($module_id) == 'D')
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

Loader::includeModule('iblock');

/** НАСТРОЙКИ */

/** файлы загрузки */
$REL_DIR = '/upload/google_catalog/';
$DIR = $_SERVER['DOCUMENT_ROOT'].$REL_DIR;
$uploadfile = $DIR.'google_catalog.csv';
$uploadMatchingfile = $DIR.'google_catalog_matching.csv';

$isGoogleCategoryUpload = false;
$isCategoryMatchingUpload = false;
$isGoogleCategoryCreated = false;

/** НАСТРОЙКИ */

$APPLICATION->SetTitle('Google product category');

$googleIblockId = 0;
$res = CIBlock::GetList(array(), array("TYPE" => 'google_product_category'));
$arIblock = $res->GetNext();
if($arIblock)
    $googleIblockId = $arIblock["ID"];

    if (!$googleIblockId) {
        $errorMessage = new CAdminMessage(
            array(
                'DETAILS' => '',
                'TYPE' => 'ERROR',
                'MESSAGE' => 'Инфоблок категорий Google Merchant не найден',
                'HTML' => true
            )
        );
        echo $errorMessage->Show();
    }
    else {
        try {
            $request = Main\Context::getCurrent()->getRequest();

            if ($request->isPost()) {
                if ($request->get('save')) {
                    $arErrors = array();

                    if (!$_FILES['FILE']) $arErrors[] = 'Не загружен файл (.csv)';
                    if ($_FILES['FILE']) {
                        if (!substr_count(ToLower($_FILES['FILE']['name']), '.csv')) {
                            $arErrors[] = 'Загружен неверный файл';
                        } else {
                            CheckDirPath($DIR);
                            if (file_exists($uploadfile))
                                unlink($uploadfile);
                            if (!move_uploaded_file($_FILES['FILE']['tmp_name'], $uploadfile)) {
                                $arErrors[] = 'Указанный файл не может быть загружен';
                            }
                        }
                    }

                    if (empty($arErrors)) {

                        $googleIblockId = $arIblock["ID"];
                        $arGoogleCatalog = [];
                        if (($handle = fopen($uploadfile, "r")) !== FALSE) {
                            $row = 0;
                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                                $num = count($data);
                                for ($c = 0; $c < $num; $c++) {
                                    $arGoogleCatalog[$row][$c] = $data[$c];
                                }
                                $row++;
                            }
                            fclose($handle);
                        }

                        if (!empty($arGoogleCatalog)) {
                            global $USER_FIELD_MANAGER;
                            $arGoogleSectIds = [];
                            foreach ($arGoogleCatalog as $row) {
                                $parentSectId = '';
                                foreach ($row as $key => $item) {
                                    if (!empty($item)) {
                                        if ($key == 0 || $key % 2 == 0) {
                                            $sectGoogleId = $item;
                                        } else {
                                            $sectGoogleName = $item;

                                            if ($sectGoogleName && $sectGoogleId) {

                                                if (!in_array($sectGoogleId, $arGoogleSectIds)) {
                                                    $bs = new CIBlockSection;
                                                    $arFields = Array(
                                                        "ACTIVE" => 'Y',
                                                        "IBLOCK_SECTION_ID" => $parentSectId,
                                                        "IBLOCK_ID" => $googleIblockId,
                                                        "NAME" => $sectGoogleName,
                                                    );

                                                    $ID = $bs->Add($arFields);
                                                    $arGoogleSectIds[$ID] = $sectGoogleId;

                                                    $USER_FIELD_MANAGER->Update("IBLOCK_'.$googleIblockId.'_SECTION", $ID, array(
                                                        'UF_GOOGLE_ID' => $sectGoogleId
                                                    ));
                                                }
                                                $parentSectId = array_search($sectGoogleId, $arGoogleSectIds);
                                            }
                                        }
                                    } else {
                                        break;
                                    }
                                }
                            }
                        }


                    } else {
                        foreach ($arErrors as $error) {
                            $errorMessage = new CAdminMessage(
                                array(
                                    'DETAILS' => '',
                                    'TYPE' => 'ERROR',
                                    'MESSAGE' => $error,
                                    'HTML' => true
                                )
                            );
                            echo $errorMessage->Show();
                        }
                    }
                }
                if ($request->get('save2')) {

                    $arErrors = array();

                    if (!$_FILES['FILE']) $arErrors[] = 'Не загружен файл (.csv)';
                    if ($_FILES['FILE']) {
                        if (!substr_count(ToLower($_FILES['FILE']['name']), '.csv')) {
                            $arErrors[] = 'Загружен неверный файл';
                        } else {
                            CheckDirPath($DIR);
                            if (file_exists($uploadMatchingfile))
                                unlink($uploadMatchingfile);
                            if (!move_uploaded_file($_FILES['FILE']['tmp_name'], $uploadMatchingfile)) {
                                $arErrors[] = 'Указанный файл не может быть загружен';
                            }
                        }
                    }

                    if (empty($arErrors)) {

                        /** IBLOCK_ID каталога */
                        $catalogIblockId = 0;

                        /** пробуем найти */
                        if (!$catalogIblockId) {

                            $arIBlockIDs = [];
                            $rsCatalogs = CCatalog::GetList(
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
                            if (count($arIBlockIDs) <> 1) {
                                throw new Exception("Невозможно определить IBLOCK_ID каталога. Задайте параметр вручную.");
                            } else {
                                $catalogIblockId = $arIBlockIDs[0];
                            }
                        }

                        $arCatalogMatching = [];
                        if (($handle = fopen($uploadMatchingfile, "r")) !== FALSE) {
                            $row = 0;
                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                                $num = count($data);
                                for ($c = 0; $c < $num; $c++) {
                                    $arCatalogMatching[$row][$c] = $data[$c];
                                }
                                $row++;
                            }
                            fclose($handle);
                        }

                        $arGoogleIds_ = [];
                        foreach ($arCatalogMatching as $row){
                            $arGoogleIds_[] = $row[1];
                        }

                        $arGoogleIds_ = array_unique($arGoogleIds_);

                        $arSectByGoogleId = [];
                        if(!empty($arGoogleIds_)){
                            $res = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $googleIblockId, "UF_GOOGLE_ID" => $arGoogleIds_), false, array("ID", "UF_GOOGLE_ID"));
                            while($arSect = $res->Fetch()){
                                $arSectByGoogleId[$arSect["UF_GOOGLE_ID"]] = $arSect["ID"];
                            }
                        }
                        
                        if (!empty($arCatalogMatching) && $catalogIblockId) {
                            foreach ($arCatalogMatching as $row) {
                                $catSectId = (int)$row[0];
                                $googleSectId = (int)$row[1];
                                $arItemsIds = [];
                                if($catSectId && $googleSectId){
                                    $res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $catalogIblockId, "SECTION_ID" => $catSectId, "INCLUDE_SUBSECTIONS" => "N" ), false, false, array("ID"));
                                    while($arItem = $res->Fetch()){
                                        $arItemsIds[] = $arItem["ID"];
                                    }

                                    foreach($arItemsIds as $id)
                                        CIBlockElement::SetPropertyValuesEx($id, false, array("GOOGLE_PRODUCT_CATEGORY" => $arSectByGoogleId[$googleSectId]));
                                }
                            }
                        }

                    } else {
                        foreach ($arErrors as $error) {
                            $errorMessage = new CAdminMessage(
                                array(
                                    'DETAILS' => '',
                                    'TYPE' => 'ERROR',
                                    'MESSAGE' => $error,
                                    'HTML' => true
                                )
                            );
                            echo $errorMessage->Show();
                        }
                    }

                }
            }

            // файл с категориями Google загружен?
            if (file_exists($uploadfile)) {
                $isGoogleCategoryUpload = true;
            }

            // файл соотв. Google загружен?
            if (file_exists($uploadMatchingfile)) {
                $isCategoryMatchingUpload = true;
            }

            // категории Google созданы?
            $arFilSecCount = Array(
                "ACTIVE" => "Y",
                "IBLOCK_ID" => $googleIblockId
            );
            $subSecCount = CIBlockSection::GetCount($arFilSecCount);
            if ($subSecCount > 0)
                $isGoogleCategoryCreated = true;

            $aTabs = array(
                array("DIV" => "jamilco_googlecatalog", "TAB" => "Google product category", "TITLE" => "Загрузить список")
            );

            $tabControl = new CAdminTabControl("tabControl", $aTabs);
            ?>
            <form method="post" action="<?= $APPLICATION->GetCurPage() ?>" enctype="multipart/form-data"
                  name="post_form">
                <?= bitrix_sessid_post() ?>
                <?
                $tabControl->Begin();
                $tabControl->BeginNextTab();
                ?>
                <tr>
                    <td width="40%" class="adm-detail-content-cell-l">Файл (.csv) со списком категорий Google Merchant
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <input type="file" name="FILE">
                    </td>
                </tr>

                <tr>
                    <td width="40%" class="adm-detail-content-cell-l">
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <span class="<?=$isGoogleCategoryUpload ? 'chk-ok' : 'chk-fail'?>"></span><b>Файл загружен</b>
                    </td>
                </tr>
                <tr>
                    <td width="40%" class="adm-detail-content-cell-l">
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <span class="<?=$isGoogleCategoryCreated ? 'chk-ok' : 'chk-fail'?>"></span><b>Инфобок категорий Google Merchant создан</b>
                    </td>
                </tr>
                <?
                $tabControl->Buttons();
                ?>
                <input type="submit" name="save"
                       value="<?= $isGoogleCategoryUpload || $isGoogleCategoryCreated ? 'Изменить?' : 'Загрузить' ?>"
                       title="" class="adm-btn-save">
                <?
                $tabControl->End();
                ?>
            </form>
            <?
            echo BeginNote();
            ?>
            Файл для загрузки дерева категорий Google Merchant предполагается следующей структуры.<br>
            Каждая строка содержит путь от корня с указанием ID и названия категории:<br>
            <b><ид корн. раздела>, <название корн. раздела>, <ид 1-го раздела-потомка>, <название 1-го раздела-потомка>,</b> ...
            <?
            echo EndNote();
            ?>
            <br>
            <?
            $aTabs2 = array(
                array("DIV" => "jamilco_googlecatalog2", "TAB" => "Категории товаров на сайте", "TITLE" => "Загрузить таблицу соответствия")
            );

            $tabControl2 = new CAdminTabControl("tabControl", $aTabs2, true, true);
            $tabControl2->Begin();
            $tabControl2->BeginNextTab();
            ?>
            <form method="post" action="<?= $APPLICATION->GetCurPage() ?>" enctype="multipart/form-data"
                  name="post_form" style="clear: both;">
                <?= bitrix_sessid_post() ?>
                <tr>
                    <td width="40%" class="adm-detail-content-cell-l">Файл (.csv) со списком соответствия</td>
                    <td class="adm-detail-content-cell-r">
                        <input type="file" name="FILE">
                    </td>
                </tr>
                <tr>
                    <td width="40%" class="adm-detail-content-cell-l">
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <span class="<?=$isCategoryMatchingUpload ? 'chk-ok' : 'chk-fail'?>"></span><b>Файл соответствий загружен</b>
                    </td>
                </tr>
                <?
                $tabControl2->Buttons();
                ?>
                <input type="submit" name="save2" value="Загрузить" title="" class="adm-btn-save">
            </form>
            <?
            $tabControl2->End();
            ?>
            <?
            echo BeginNote();
            ?>
            Файл для выгрузки соответствия разделов сайта категориям Google Merchant должен иметь два столбца:<br>
            <b><ид раздела сайта>, <ид категории Google Merchant></b><br>
            После выгрузки для товаров из указанных разделов сайта будет проставлено свойство <b>Google product category</b>
            <?
            echo EndNote();
            ?>
            <br>
            <br>
        <?
        }
        catch (Exception $e){
            echo $e->getMessage();
            die();
        }
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");