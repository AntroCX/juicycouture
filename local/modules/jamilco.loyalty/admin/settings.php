<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог


IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("jamilco.loyalty");
if ($POST_RIGHT == "D") {
    echo 'Доступ запрещен';

    return;
}

$APPLICATION->SetTitle('Настройки программы лояльности Jamilco');

$ID = intval($ID);        // идентификатор редактируемой записи
$message = null;        // сообщение об ошибке
$bVarsFromForm = false; // флаг "Данные получены с формы", обозначающий, что выводимые данные получены с формы, а не из БД.

$iblockCatalogId = 0;
$iblockOffersId = 0;
$rsCatalog = \CCatalog::GetList(array(), array('PRODUCT_IBLOCK_ID' => 0));
while ($arrCatalog = $rsCatalog->Fetch()) {
    $iblockCatalogId = $arrCatalog['IBLOCK_ID'];
    $iblockOffersId = $arrCatalog['OFFERS_IBLOCK_ID'];
}

$arSections = array();

$rsSections = \CIBlockSection::GetList(array('LEFT_MARGIN' => 'ASC'), array('IBLOCK_ID' => $iblockCatalogId));
while ($arrSections = $rsSections->Fetch()) {
    $arSections[] = $arrSections;
}

$arProperties = [];
$pr = \CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblockCatalogId]);
while ($arProp = $pr->Fetch()) {
    $arProperties['CATALOG'][] = $arProp;
}
$pr = \CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblockOffersId]);
while ($arProp = $pr->Fetch()) {
    $arProperties['OFFERS'][] = $arProp;
}

if ($_REQUEST['update'] == 'Y') {
    $active = ($_REQUEST['ACTIVE'] == 'on') ? 1 : 0;
    $sale = ($_REQUEST['SALE'] == 'on') ? 1 : 0;
    $presale = ($_REQUEST['PRESALE'] == 'on') ? 1 : 0;
    $manzanaOrders = ($_REQUEST['MANZANA_ORDERS'] == 'on') ? 1 : 0;
    $manzanaDiscounts = ($_REQUEST['MANZANA_DISCOUNTS'] == 'on') ? 1 : 0;
    $manzanaSaleSend = ($_REQUEST['MANZANA_SALE_SEND'] == 'on') ? 1 : 0;

    // числа
    \COption::SetOptionInt("jamilco.loyalty", "active", $active);
    \COption::SetOptionInt("jamilco.loyalty", "sale", $sale);
    \COption::SetOptionInt("jamilco.loyalty", "presale", $presale);
    \COption::SetOptionInt("jamilco.loyalty", "writeoff", $_REQUEST['WRITEOFF']);
    \COption::SetOptionInt("jamilco.loyalty", "addbonus", $_REQUEST['ADDBONUS']);
    \COption::SetOptionInt("jamilco.loyalty", "type", $_REQUEST['TYPE']);
    \COption::SetOptionInt("jamilco.loyalty", "algorithm", $_REQUEST['ALGORITHM']);
    \COption::SetOptionInt("jamilco.loyalty", "manzana", $_REQUEST['MANZANA']);
    \COption::SetOptionInt("jamilco.loyalty", "manzanaorders", $manzanaOrders);
    \COption::SetOptionInt("jamilco.loyalty", "manzanadiscounts", $manzanaDiscounts);
    \COption::SetOptionInt("jamilco.loyalty", "manzanasalesend", $manzanaSaleSend);
    \COption::SetOptionInt("jamilco.loyalty", "manzanagift", $_REQUEST['MANZANA_GIFT']);
    \COption::SetOptionInt("jamilco.loyalty", "manzanasort", $_REQUEST['MANZANA_SORT']);

    // строки
    //\COption::SetOptionString("jamilco.loyalty", "saleproperty", $_REQUEST['SALE_PROPERTY']);

    \COption::SetOptionString("jamilco.loyalty", "selected", implode(',', $_REQUEST['SELECTED_SECTIONS']));
    \COption::SetOptionString("jamilco.loyalty", "double", implode(',', $_REQUEST['DOUBLE_SECTIONS']));
}

$active = \COption::GetOptionInt("jamilco.loyalty", "active");
$sale = \COption::GetOptionInt("jamilco.loyalty", "sale");
$presale = \COption::GetOptionInt("jamilco.loyalty", "presale");
$writeoff = \COption::GetOptionInt("jamilco.loyalty", "writeoff");
$addbonus = \COption::GetOptionInt("jamilco.loyalty", "addbonus");
$type = \COption::GetOptionInt("jamilco.loyalty", "type");
$algorithm = \COption::GetOptionInt("jamilco.loyalty", "algorithm");
$manzana = \COption::GetOptionInt("jamilco.loyalty", "manzana", 0);
$manzanaOrders = \COption::GetOptionInt("jamilco.loyalty", "manzanaorders", 0);
$manzanaDiscounts = \COption::GetOptionInt("jamilco.loyalty", "manzanadiscounts", 0);
$manzanaSaleSend = \COption::GetOptionInt("jamilco.loyalty", "manzanasalesend", 0);
$manzanaGift = \COption::GetOptionInt("jamilco.loyalty", "manzanagift", 0);
$selected = \COption::GetOptionString("jamilco.loyalty", "selected");
$double = \COption::GetOptionString("jamilco.loyalty", "double");
$saleProperty = \COption::GetOptionString("jamilco.loyalty", "saleproperty");
$manzanaSort = \COption::GetOptionInt("jamilco.loyalty", "manzanasort", 0);

$arSelected = explode(',', $selected);
$arDouble = explode(',', $double);

$showOcsOptions = ($manzana && $manzanaOrders) ? false : true;

$aTabs = [
    ["DIV" => "settings", "TAB" => "Настройки", "TITLE" => "Настройки"]
];

if ($showOcsOptions) {
    $aTabs[] = ["DIV" => "goods_writeoff", "TAB" => "Товары участвующие в программе", "TITLE" => "Товары участвующие в программе"];
    $aTabs[] = ["DIV" => "goods_add-bonus", "TAB" => "Товары за которые начисляются двойные баллы", "TITLE" => "Товары за которые начисляются двойные баллы"];
}

if (!\Jamilco\Loyalty\Common::discountsAreMoved()) {
    $aTabs[] = ["DIV" => "gifts", "TAB" => "Настройка подарков", "TITLE" => "Настройка подарков"];
}

$tabControl = new CAdminTabControl("tabControl", $aTabs);

?>
    <form method="POST" Action="<? echo $APPLICATION->GetCurPage() ?>" ENCTYPE="multipart/form-data" name="post_form">
        <? echo bitrix_sessid_post(); ?>
        <?
        $tabControl->Begin();

        $tabControl->BeginNextTab();
        ?>

        <input type="hidden" name="update" value="Y">
        <tr>
            <td width="40%">Активность</td>
            <td width="60%">
                <input type="checkbox" name="ACTIVE" <? if ($active == 1): ?>checked<? endif ?>>
            </td>
        </tr>

        <tr <?= ($showOcsOptions) ? '' : 'style="display:none;"' ?>>
            <td width="40%">Количество баллов к списанию (%)</td>
            <td width="60%">
                <input type="number" name="WRITEOFF" value="<?= $writeoff ?>">
            </td>
        </tr>

        <tr <?= ($showOcsOptions) ? '' : 'style="display:none;"' ?>>
            <td width="40%">Количество баллов к начислению (%)</td>
            <td width="60%">
                <input type="number" name="ADDBONUS" value="<?= $addbonus ?>">
            </td>
        </tr>

        <tr <?= ($showOcsOptions) ? '' : 'style="display:none;"' ?>>
            <td width="40%">Тип списания</td>
            <td width="60%">
                <label>
                    <input type="radio" name="TYPE" value="1" <? if ($type == 1): ?>checked<? endif ?>>
                    Фикс
                </label>
                <br>
                <label>
                    <input type="radio" name="TYPE" value="2" <? if ($type == 2): ?>checked<? endif ?>>
                    Интервал
                </label>
            </td>
        </tr>

        <tr <?= ($showOcsOptions) ? '' : 'style="display:none;"' ?>>
            <td width="40%">Алгоритм списания</td>
            <td width="60%">
                <label>
                    <input type="radio" name="ALGORITHM" value="1" <? if ($algorithm == 1): ?>checked<? endif ?>>
                    на все товары поровну
                </label>
                <br>
                <label>
                    <input type="radio" name="ALGORITHM" value="2" <? if ($algorithm == 2): ?>checked<? endif ?>>
                    на максимально возможное к списанию кол-во баллов
                </label>
                <br>
                <label>
                    <input type="radio" name="ALGORITHM" value="3" <? if ($algorithm == 3): ?>checked<? endif ?>>
                    на самую дорогую
                </label>
                <br>
                <label>
                    <input type="radio" name="ALGORITHM" value="4" <? if ($algorithm == 4): ?>checked<? endif ?>>
                    на первую в корзине
                </label>
            </td>
        </tr>

        <tr>
            <td width="40%">Сервер для запросов</td>
            <td width="60%">
                <label>
                    <input type="radio" name="MANZANA" value="0" <?= ($manzana != 1) ? 'checked' : '' ?>>
                    OCS
                </label>
                <br>
                <label>
                    <input type="radio" name="MANZANA" value="1" <?= ($manzana == 1) ? 'checked' : '' ?>>
                    Manzana
                </label>
            </td>
        </tr>

        <tr>
            <td width="40%">Отправлять в Manzana заказы</td>
            <td width="60%">
                <input type="checkbox" name="MANZANA_ORDERS" <?= ($manzanaOrders) ? 'checked' : '' ?>>
            </td>
        </tr>

        <tr>
            <td width="40%">Скидки перенесены в Manzana</td>
            <td width="60%">
                <input type="checkbox" name="MANZANA_DISCOUNTS" <?= ($manzanaDiscounts) ? 'checked' : '' ?>>
            </td>
        </tr>

        <tr>
            <td width="40%">Передавать в Manzana "старую цену" из OCS</td>
            <td width="60%">
                <input type="checkbox" name="MANZANA_SALE_SEND" <?= ($manzanaSaleSend) ? 'checked' : '' ?>>
            </td>
        </tr>

        <tr>
            <td width="40%">Подарки из Манзаны</td>
            <td width="60%">
                <label>
                    <input type="radio" name="MANZANA_GIFT" value="0" <?= ($manzanaGift != 1) ? 'checked' : '' ?>>
                    Автотоматически добавляется первый доступный
                </label>
                <br>
                <label>
                    <input type="radio" name="MANZANA_GIFT" value="1" <?= ($manzanaGift == 1) ? 'checked' : '' ?>>
                    Выбор покупателя
                </label>
            </td>
        </tr>

        <tr>
            <td width="40%">Направление сортировки товаров в корзине</td>
            <td width="60%">
                <label>
                    <input type="radio" name="MANZANA_SORT" value="0" <?= ($manzanaSort === 0) ? 'checked' : '' ?>>
                    Не сортировать
                </label>
                <br>
                <label>
                    <input type="radio" name="MANZANA_SORT" value="1" <?= ($manzanaSort === 1) ? 'checked' : '' ?>>
                    По возрастанию цены
                </label>
                <br>
                <label>
                    <input type="radio" name="MANZANA_SORT" value="2" <?= ($manzanaSort === 2) ? 'checked' : '' ?>>
                    По убыванию цены
                </label>
            </td>
        </tr>

        <? if ($showOcsOptions) { ?>
            <? $tabControl->BeginNextTab(); ?>

            <tr>
                <td width="40%">Участвующие разделы</td>
                <td width="60%">
                    <select name="SELECTED_SECTIONS[]" multiple="multiple" size="20" style="width: 250px;">
                        <? foreach ($arSections as $arSection): ?>
                            <? $strPre = ($arSection['DEPTH_LEVEL'] > 1) ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($arSection['DEPTH_LEVEL'] - 1)) : ''; ?>
                            <option value="<?= $arSection['ID'] ?>" <?= (in_array($arSection['ID'], $arSelected)) ? 'selected' : '' ?>>
                                <?= $strPre.$arSection['NAME'] ?>
                            </option>
                        <? endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td width="40%">Sale-товары участвуют</td>
                <td width="60%">
                    <input type="checkbox" name="SALE" <? if ($sale == 1): ?>checked<? endif ?>>
                </td>
            </tr>
            <?/*
            // факт "товар является sale-товаром определяется теперь не по свойству, а по наличию скидочной цены
        <tr>
            <td width="40%">Свойство, определяющее Sale-товары</td>
            <td width="60%">
                <select name="SALE_PROPERTY">
                    <optgroup label="Свойства товаров">
                        <? foreach ($arProperties['CATALOG'] as $arProp) { ?>
                            <? $value = 'CATALOG_'.$arProp['CODE']; ?>
                            <option value="<?= $value ?>" <?= ($value == $saleProperty) ? 'selected' : '' ?>><?= $arProp['NAME'] ?></option>
                        <? } ?>
                    </optgroup>
                    <optgroup label="Свойства ТП">
                        <? foreach ($arProperties['OFFERS'] as $arProp) { ?>
                            <? $value = 'OFFER_'.$arProp['CODE']; ?>
                            <option value="<?= $value ?>" <?= ($value == $saleProperty) ? 'selected' : '' ?>><?= $arProp['NAME'] ?></option>
                        <? } ?>
                    </optgroup>
                </select>
            </td>
        </tr>
        */
            ?>
            <tr>
                <td width="40%">Исключить товары с Presale-скидкой</td>
                <td width="60%">
                    <input type="checkbox" name="PRESALE" <? if ($presale == 1): ?>checked<? endif ?>>
                </td>
            </tr>

            <? $tabControl->BeginNextTab(); ?>

            <tr>
                <td width="40%">Разделы товаров с двойными бонусами</td>
                <td width="60%">
                    <select name="DOUBLE_SECTIONS[]" multiple="multiple" size="20" style="width: 250px;">
                        <? foreach ($arSections as $arSection): ?>
                            <? $strPre = ($arSection['DEPTH_LEVEL'] > 1) ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($arSection['DEPTH_LEVEL'] - 1)) : ''; ?>
                            <option value="<?= $arSection['ID'] ?>" <? if (in_array($arSection['ID'], $arDouble)): ?>selected<? endif ?>>
                                <?= $strPre.$arSection['NAME'] ?>
                            </option>
                        <? endforeach; ?>
                    </select>
                </td>
            </tr>

        <?
        }

        if (!\Jamilco\Loyalty\Common::discountsAreMoved()) {

            $tabControl->BeginNextTab();

            $arRules = \Jamilco\Loyalty\Gift::getRules();
            $arGifts = \Jamilco\Loyalty\Gift::getOffers();

            if ($arRules) {
                ?>
                <tr class="heading">
                    <td colspan="2"><b>Настроенные "правила"</b></td>
                </tr>
                <tr>
                    <td colspan="2" width="100%">
                        <table style="width: 100%;">
                            <tr>
                                <th>Правило</th>
                                <th>Пороговая сумма</th>
                                <th>Цена подарка</th>
                            </tr>
                            <? foreach ($arRules as $arOne) { ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <a href="/bitrix/admin/sale_discount_edit.php?lang=ru&ID=<?= $arOne['ID'] ?>"><?= $arOne['NAME'] ?></a></td>
                                    <td style="text-align: center;"><?= $arOne['DISCOUNT_SUM'] ?></td>
                                    <td style="text-align: center;"><?= $arOne['GIFT_PRICE'] ?></td>
                                </tr>
                            <? } ?>
                        </table>
                </tr>
            <? } ?>
            <? if ($arGifts) { ?>
                <tr class="heading">
                    <td colspan="2"><b>Товары-подарки</b></td>
                </tr>
                <tr>
                    <td colspan="2" width="100%">
                        <table style="width: 100%;">
                            <tr>
                                <th>Товар</th>
                                <th>Скрыто из каталога</th>
                                <th>ТП</th>
                                <th>Цена</th>
                                <th>Скидочная цена</th>
                                <th>Наличие в ИМ</th>
                                <th>Наличие в РМ</th>
                            </tr>
                            <?
                            foreach ($arGifts as $arOffer) {
                                ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=<?= $arOffer['PROPERTY_CML2_LINK_IBLOCK_ID'] ?>&type=catalog&ID=<?= $arOffer['PROPERTY_CML2_LINK_VALUE'] ?>&lang=ru&find_section_section=-1"><?= $arOffer['PROPERTY_CML2_LINK_NAME'] ?></a>
                                    </td>
                                    <td style="text-align: center;"><?= ($arOffer['PROPERTY_CML2_LINK_PROPERTY_HIDE_VALUE'] || $arOffer['PROPERTY_CML2_LINK_ACTIVE'] != 'Y') ? 'Да' : 'Нет' ?></td>
                                    <td style="text-align: center;">
                                        <a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=<?= $arOffer['IBLOCK_ID'] ?>&type=catalog&ID=<?= $arOffer['ID'] ?>&lang=ru&find_section_section=-1"><?= $arOffer['NAME'] ?></a>
                                    </td>
                                    <td style="text-align: center;"><?= $arOffer['CATALOG_PRICE_1'] ?></td>
                                    <td style="text-align: center;"><?= $arOffer['CATALOG_PRICE_2'] ?></td>
                                    <td style="text-align: center;"><?= $arOffer['CATALOG_QUANTITY'] ?></td>
                                    <td style="text-align: center;"><?= implode(', ', $arOffer['RETAIL_CITIES']) ?></td>
                                </tr>
                            <? } ?>
                        </table>
                    </td>
                </tr>
                <?
            }
        }

        $tabControl->Buttons(
            array(
                "back_url" => "/bitrix/admin/jamilco_loyalty_settings.php?lang=".LANG,
            )
        );

        $tabControl->End();
        $tabControl->ShowWarnings("post_form", $message);
        ?>
    </form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");