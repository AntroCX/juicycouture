<?php
/** @global CMain $APPLICATION */
use Bitrix\Main,
    Bitrix\Main\Loader,
    Bitrix\Main\Application,
    Bitrix\Main\Localization\Loc,
    Bitrix\Sale\Internals,
    Bitrix\Main\Web\Json;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$saleModulePermissions = $APPLICATION->GetGroupRight('sale');
$readOnly = ($saleModulePermissions < 'W');
if ($saleModulePermissions < 'R') {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

Loader::includeModule('sale');

$couponTypes = Internals\DiscountCouponTable::getCouponTypes(true);
$discountList = array();
$discountIterator = Internals\DiscountTable::getList(
    array(
        'select' => array('ID', 'NAME'),
        'filter' => array('=ACTIVE' => 'Y'),
        'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC')
    )
);
while ($discount = $discountIterator->fetch()) {
    $discount['ID'] = (int)$discount['ID'];
    $discount['NAME'] = (string)$discount['NAME'];
    $discountList[$discount['ID']] = '['.$discount['ID'].']'.($discount['NAME'] !== '' ? ' '.$discount['NAME'] : '');
}

$request = Main\Context::getCurrent()->getRequest();

if ($request->isPost()) {
    if ($request->get('update') == 'Y') {
        $message = array();

        $discountId = $request->get('DISCOUNT_ID');
        $typeId = $request->get('TYPE');
        $description = $request->get('DESCRIPTION');
        if (!$discountId) $message[] = 'Не выбрано "Правило работы с корзиной"';
        if (!$typeId) $message[] = 'Не выбран тип купона';
        if (!$_FILES['FILE']) $message[] = 'Не загружен файл (.xls) с купонами';
        if ($_FILES['FILE']) {
            if (!substr_count($_FILES['FILE']['name'], '.xls')) {
                $message[] = 'Загружен неверный файл';
            } else {
                $dir = $_SERVER['DOCUMENT_ROOT'].'/upload/coupons/';
                $uploadfile = $dir.'coupons.xls';
                CheckDirPath($dir);
                if (!move_uploaded_file($_FILES['FILE']['tmp_name'], $uploadfile)) {
                    $message[] = 'Указанный файл не может быть загружен';
                }
            }
        }

        if (!$message) {
            // откроем файл и сохраним купоны
            ini_set('mbstring.internal_encoding', 'cp1251'); // нужно для верного разбора xls-файла
            require_once($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/spreadsheet_excel_reader.php');

            $Excel = new Spreadsheet_Excel_Reader($uploadfile);
            $count = $Excel->sheets[0]['numRows'];

            $arCoupons = array();
            for ($rowNum = 1; $rowNum <= $count; $rowNum++) {
                $one = $Excel->sheets[0]['cells'][$rowNum][1];
                $arCoupons[] = trim($one);
            }
            array_shift($arCoupons);

            if (!$arCoupons) {
                $message[] = 'В файле не найдено купонов';
            } else {
                $arExistCoupons = array(); // список ранее созданных купонов
                $res = Internals\DiscountCouponTable::getList(
                    array(
                        'filter' => array(
                            //'DISCOUNT_ID' => $discountId, // нельзя создать два одинаковых купона, даже к разным скидкам
                            'TYPE'   => $typeId,
                            'COUPON' => $arCoupons,
                        )
                    )
                );
                while ($arOne = $res->Fetch()) {
                    $arExistCoupons[$arOne['COUPON']] = $arOne['DISCOUNT_ID'];
                }

                // сохраним купоны
                $counts = array(
                    'ADD'   => 0,
                    'EXIST' => 0,
                );
                $arFields = array(
                    'DISCOUNT_ID' => $discountId,
                    'TYPE'        => $typeId,
                    'ACTIVE'      => 'Y',
                    'DESCRIPTION' => $description,
                );
                foreach ($arCoupons as $coupon) {
                    if (array_key_exists($coupon, $arExistCoupons)) {
                        if ($arExistCoupons[$coupon] == $discountId) {
                            $counts['EXIST']++; // купон уже создан в этой акции
                        } else {
                            $counts['EXIST_ANOTHER']++; // купон уже создан в другой акции
                        }
                        continue;
                    }

                    $arFieldsOne = $arFields;
                    $arFieldsOne['COUPON'] = $coupon;
                    $result = Internals\DiscountCouponTable::add($arFieldsOne);
                    if (!$result->isSuccess()) {
                        $arFieldsOne['ERROR'] = $result->getErrorMessages();
                        //pr($arFieldsOne);
                    }
                    $counts['ADD']++;
                }

                $arOkText = array('Купоны:', ' - добавлены: '.$counts['ADD']);
                if ($counts['EXIST']) $arOkText[] = ' - ранее созданы (в этом правиле): '.$counts['EXIST'];
                if ($counts['EXIST_ANOTHER']) $arOkText[] = ' - ранее созданы (в других правилах): '.$counts['EXIST_ANOTHER'];

                $successMessage = new CAdminMessage(
                    array(
                        'DETAILS' => implode('<br />', $arOkText),
                        'TYPE'    => 'OK',
                        'MESSAGE' => 'Результат сохранения',
                        'HTML'    => true
                    )
                );
                echo $successMessage->Show();
            }
        } else {
            $errorMessage = new CAdminMessage(
                array(
                    'DETAILS' => implode('<br>', $message),
                    'TYPE'    => 'ERROR',
                    'MESSAGE' => 'Ошибки при сохранении',
                    'HTML'    => true
                )
            );
            echo $errorMessage->Show();
        }
    }
}

$APPLICATION->SetTitle('Загрузка купонов из файла');

$aTabs = array(
    array("DIV" => "coupons", "TAB" => "Загрузка купонов", "TITLE" => "Загрузка купонов из файла"),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
    <form method="post" Action="<?= $APPLICATION->GetCurPage() ?>" ENCTYPE="multipart/form-data" name="post_form">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="update" value="Y">

        <?
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>

        <tr>
            <td width="40%" class="adm-detail-content-cell-l">Файл (.xls) со списком купонов</td>
            <td class="adm-detail-content-cell-r">
                <input type="file" name="FILE">
            </td>
        </tr>
        <tr>
            <td width="40%" class="adm-detail-content-cell-l">Правило работы с корзиной</td>
            <td class="adm-detail-content-cell-r">
                <select name="DISCOUNT_ID">
                    <? foreach ($discountList as $key => $val) { ?>
                        <option value="<?= $key ?>"><?= $val ?></option>
                    <? } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="40%" class="adm-detail-content-cell-l">Тип купона</td>
            <td class="adm-detail-content-cell-r">
                <select name="TYPE">
                    <? foreach ($couponTypes as $key => $val) { ?>
                        <option value="<?= $key ?>"><?= $val ?></option>
                    <? } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="40%" class="adm-detail-content-cell-l">Комментарий</td>
            <td class="adm-detail-content-cell-r">
                <textarea name="DESCRIPTION"></textarea>
            </td>
        </tr>

        <?
        $tabControl->Buttons(array("back_url" => "/bitrix/admin/jamilco_discount_coupons_load.php"));
        $tabControl->End();
        $tabControl->ShowWarnings("post_form", $message);
        ?>
    </form>

<?


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
