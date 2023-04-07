<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог

use \Bitrix\Main\GroupTable;
use \Bitrix\Main\Web\Json;
use \Jamilco\Omni\Channel;

IncludeModuleLangFile(__FILE__);

global $APPLICATION;

$POST_RIGHT = $APPLICATION->GetGroupRight("jamilco.omni");
if ($POST_RIGHT == "D") {
    echo 'Доступ запрещен';

    return;
}

$arCatalogIblock = Channel::getCatalogIblock();
$arIblockProperties = [];
$pr = CIBlockProperty::GetList(["SORT" => "ASC"], ["IBLOCK_ID" => $arCatalogIblock['IBLOCK_ID']]);
while ($arProp = $pr->Fetch()) {
    $arIblockProperties[$arProp['CODE']] = $arProp['NAME'];
}

$arUserGroups = [];
$gr = GroupTable::getList();
while ($arGroup = $gr->Fetch()) {
    $arUserGroups[$arGroup['ID']] = $arGroup['NAME'];
}


$arItemSections = [];
$se = \CIBlockSection::GetList(
    ['LEFT_MARGIN' => 'ASC'],
    ['IBLOCK_ID' => $arCatalogIblock['IBLOCK_ID']]
);
while ($arSection = $se->Fetch()) {
    $arItemSections[$arSection['ID']] = str_repeat('..  ', ($arSection['DEPTH_LEVEL'] - 1)).$arSection['NAME'];
}

$itemFlags = Channel::$itemFlags;
$arItemFlags = [];
foreach ($itemFlags as $flag) {
    $arItemFlags[$flag] = $flag;
}

// свойства модуля
$arModuleProperties = [
    'prop.article' => [
        'TITLE'   => 'Артикул товара',
        'INPUT'   => 'SELECT',
        'TYPE'    => 'STRING',
        'LIST'    => $arIblockProperties,
        'DEFAULT' => 'ARTNUMBER',
    ],
    'flags.hidden' => [
        'TITLE'   => 'Скрытые флаги',
        'INPUT'   => 'SELECT',
        'TYPE'    => 'STRING',
        'LIST'    => $arItemFlags,
        'MULTI'   => 'Y',
        'DEFAULT' => '',
    ],
    /*
    'sale.retail'        => [
        'TITLE'   => 'Sale-товары можно бронировать',
        'TYPE'    => 'INT',
        'INPUT'   => 'CHECKBOX',
        'DEFAULT' => 0
    ],
    */
    /*
    'delay.nodeliveried' => [
        'TITLE'   => 'Откладывать товары, недоступные к доставке',
        'TYPE'    => 'INT',
        'INPUT'   => 'CHECKBOX',
        'DEFAULT' => 0
    ],
    */
    'item.invet'   => [
        'TITLE'       => 'Инвентаризация',
        'TYPE'        => 'INT',
        'INPUT'       => 'CHECKBOX',
        'DEFAULT'     => 0,
        'DESCRIPTION' => 'В городах с РМ доступны только товары из РМ. В других городах - только со склада ИМ',
    ],
    'sale.delivery' => [
        'TITLE'       => 'SaleCanRetail распространяется на OMNI_Delivery',
        'TYPE'        => 'INT',
        'INPUT'       => 'CHECKBOX',
        'DEFAULT'     => 0,
        'DESCRIPTION' => 'Sale-товары можно доставлять по каналу OMNI_Delivery, если в РМ стоит флаг SaleCanRetail',
    ],
];

$se = \CIblockSection::GetList(
    [],
    [
        'IBLOCK_ID'   => IBLOCK_CATALOG_ID,
        'ACTIVE'      => 'Y',
        'DEPTH_LEVEL' => 1,
        'CODE'        => 'outlet',
    ]
);
if ($arSect = $se->Fetch()) {
    $arModuleProperties['split.outlet'] = [
        'TITLE'   => 'Отделить скидочную карточку товара',
        'TYPE'    => 'INT',
        'INPUT'   => 'CHECKBOX',
        'DEFAULT' => 0
    ];
}

$arOrderProps = [];
$op = \CSaleOrderProps::GetList([], ['ACTIVE' => 'Y']);
while ($orderProp = $op->Fetch()) {
    $arOrderProps[$orderProp['CODE']] = $orderProp;
}

if ($arOrderProps['UPSALE']) {
    $arModuleProperties['upsale.group'] = [
        'TITLE'   => 'Группа пользователей для UpSale',
        'INPUT'   => 'SELECT',
        'TYPE'    => 'STRING',
        'LIST'    => $arUserGroups,
        'DEFAULT' => '1',
        'GROUP'   => 'Флаг UpSale',
    ];
    $arModuleProperties['upsale.section'] = [
        'TITLE'   => 'Товары для UpSale',
        'INPUT'   => 'SELECT',
        'TYPE'    => 'STRING',
        'LIST'    => $arItemSections,
        'MULTI'   => 'Y',
        'DEFAULT' => '',
        'GROUP'   => 'Флаг UpSale',
    ];
}

// полученные данные
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if ($request->isAjaxRequest() && $request->isPost()) {
    $APPLICATION->RestartBuffer();

    $action = $request->get('action');

    if ($action == 'saveData') {
        // сохранение данных
        $data = $request->get('data');
        Channel::saveData($data);
    } elseif ($action == 'saveProperty') {
        $data = $request->get('data');
        foreach ($data as $key => $val) {
            $arProp = $arModuleProperties[$key];
            if ($arProp['TYPE'] == 'INT') {
                \COption::SetOptionInt("jamilco.omni", $key, $val);
            } else {
                \COption::SetOptionString("jamilco.omni", $key, $val);
            }
        }

        \COption::SetOptionString("jamilco.omni", "exclude.sections", $data['sections']);
        \COption::SetOptionString("jamilco.omni", "exclude.locations", $data['locations']);

    } elseif ($action == 'reSaveCities') {
        $reSaveAll = ($request->get('all') == 'Y') ? true : false;
        $arLog = Channel::reSaveCityAvailables($reSaveAll);
        \Jamilco\Main\Utils::clearCatalogCache(); // сброс кеша каталога
        //pr($arLog,1);
    } elseif ($action == 'clearChildFlags') {
        $id = $request->get('id');
        Channel::clearChildFlags($id);
        $arData = Channel::getIblocksData();
        echo Json::encode($arData['FLAGS']);
    }

    die();
}


CJSCore::Init(array('jquery'));

$APPLICATION->SetTitle('Панель управления Omni Channel');

$APPLICATION->AddHeadScript('/local/modules/jamilco.omni/admin/channel.js');
$APPLICATION->SetAdditionalCSS('/local/modules/jamilco.omni/admin/channel.css');

$aTabs = [
    ["DIV" => "catalog", "TAB" => "Настройки товаров", "TITLE" => "Настройки товаров"],
    ["DIV" => "shop", "TAB" => "Настройки магазинов", "TITLE" => "Настройки магазинов"],
    ["DIV" => "exclude", "TAB" => "Ограничения по городам", "TITLE" => "Ограничения по городам"],
    ["DIV" => "settings", "TAB" => "Настройки модуля", "TITLE" => "Настройки модуля"],
];

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$message = null;        // сообщение об ошибке
?>
    <form method="POST" Action="<? echo $APPLICATION->GetCurPage() ?>" ENCTYPE="multipart/form-data" name="post_form" class="channel">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="update" value="Y">

        <?
        $tabControl->Begin();
        $tabControl->BeginNextTab();

        $arData = Channel::getIblocksData();
        $arCatalog = $arData['CATALOG'];
        $arShops = $arData['SHOP'];
        ?>

        <tr class="catalog-head">
            <th></th>
            <? foreach ($arCatalog['FLAGS'] as $flag) { ?>
                <th title="<?= $arCatalog['FLAGS_TITLE'][$flag] ?>"><?= $flag ?></th>
            <? } ?>
            <th></th>
        </tr>

        <? $tabControl->BeginNextTab(); ?>

        <tr class="shop-head">
            <th></th>
            <? foreach ($arShops['FLAGS'] as $flag) { ?>
                <th title="<?= $arCatalog['FLAGS_TITLE'][$flag] ?>"><?= $flag ?></th>
            <? } ?>
            <th></th>
        </tr>

        <? $tabControl->BeginNextTab(); ?>

        <?
        $sections = \COption::GetOptionString("jamilco.omni", "exclude.sections");
        $locations = \COption::GetOptionString("jamilco.omni", "exclude.locations");

        $locations = explode(',', $locations);
        TrimArr($locations);
        if (!$locations) $locations = \Jamilco\Omni\Utils::getDefaultLocations();
        ?>

        <tr>
            <td colspan="2" style="text-align:center;">
                <h4>Указанные на этой странице товары будут недоступны ко всем типам доставки во всех локациях, кроме указанных (и всех вложенных)</h4>
            </td>
        </tr>
        <tr class="has-property">
            <td>
                <div class="adm-submenu-item-name">
                    <span class="adm-submenu-item-name-link-text" style="white-space: nowrap; padding-right: 10px;">
                        Выберите товарные разделы для исключений
                    </span>
                </div>
            </td>
            <td>
                <?= \Jamilco\Omni\Utils::showSectionFilter($sections) ?>
            </td>
        </tr>
        <tr>
            <td>
                <div class="adm-submenu-item-name">
                    <span class="adm-submenu-item-name-link-text" style="white-space: nowrap; padding-right: 10px;">
                        Укажите локации для исключения<br />
                    </span>
                </div>
            </td>
            <td>
                <div class="locations">
                    <? foreach ($locations as $lcc) { ?>
                        <div class="location">
                            <? $APPLICATION->IncludeComponent(
                                "bitrix:sale.location.selector.search",
                                "",
                                Array(
                                    "CACHE_TIME"                 => "0",
                                    "CACHE_TYPE"                 => "N",
                                    "CODE"                       => "",
                                    "FILTER_BY_SITE"             => "N",
                                    "ID"                         => $lcc,
                                    "INITIALIZE_BY_GLOBAL_EVENT" => "",
                                    "INPUT_NAME"                 => "LOCATION[]",
                                    "JS_CALLBACK"                => "saveProperty",
                                    "JS_CONTROL_GLOBAL_ID"       => "",
                                    "PROVIDE_LINK_BY"            => "id",
                                    "SHOW_DEFAULT_LOCATIONS"     => "Y",
                                    "SUPPRESS_ERRORS"            => "N",
                                )
                            ); ?>
                        </div>
                    <? } ?>
                    <? for ($i = 1; $i <= 3; $i++) { ?>
                        <div class="location">
                            <? $APPLICATION->IncludeComponent(
                                "bitrix:sale.location.selector.search",
                                "",
                                Array(
                                    "CACHE_TIME"                 => "0",
                                    "CACHE_TYPE"                 => "N",
                                    "CODE"                       => "",
                                    "FILTER_BY_SITE"             => "N",
                                    "ID"                         => "",
                                    "INITIALIZE_BY_GLOBAL_EVENT" => "",
                                    "INPUT_NAME"                 => "LOCATION[]",
                                    "JS_CALLBACK"                => "saveProperty",
                                    "JS_CONTROL_GLOBAL_ID"       => "",
                                    "PROVIDE_LINK_BY"            => "id",
                                    "SHOW_DEFAULT_LOCATIONS"     => "Y",
                                    "SUPPRESS_ERRORS"            => "N",
                                )
                            ); ?>
                        </div>
                    <? } ?>
                </div>
            </td>
        </tr>


        <? $tabControl->BeginNextTab(); ?>

        <?
        $group = '';
        foreach ($arModuleProperties as $propertyCode => $arProp) {
            if ($arProp['TYPE'] == 'INT') {
                $arProp['VALUE'] = \COption::GetOptionInt("jamilco.omni", $propertyCode, $arProp['DEFAULT']);
            } else {
                $arProp['VALUE'] = \COption::GetOptionString("jamilco.omni", $propertyCode, $arProp['DEFAULT']);
                if ($arProp['MULTI'] == 'Y') $arProp['VALUE'] = explode(',', $arProp['VALUE']);
            }

            if ($arProp['GROUP'] > '' && $arProp['GROUP'] != $group) {
                $group = $arProp['GROUP'];
                echo '<tr><td colspan="2" style="text-align:center; padding-top: 20px;"><h4>'.$group.'</h4></td></tr>';
            }
            ?>
            <tr class="has-property">
                <td>
                    <div class="adm-submenu-item-name" title="<?= $arProp['DESCRIPTION'] ?>">
                        <span class="adm-submenu-item-name-link-text" style="white-space: nowrap; padding-right: 10px;"><?= $arProp['TITLE'] ?></span>
                    </div>
                </td>
                <td>
                    <? if ($arProp['INPUT'] == 'SELECT') { ?>
                        <? $count = (count($arProp['LIST']) > 10) ? 10 : count($arProp['LIST']); ?>
                        <select name="<?= $propertyCode ?>" <?= ($arProp['MULTI'] == 'Y') ? 'multiple size="'.$count.'"' : '' ?>>
                            <? foreach ($arProp['LIST'] as $key => $val) { ?>
                                <? $selected = ($key == $arProp['VALUE'] || in_array($key, $arProp['VALUE'])); ?>
                                <option value="<?= $key ?>" <?= ($selected) ? 'selected="selected"' : '' ?>><?= $val ?></option>
                            <? } ?>
                        </select>
                    <? } elseif ($arProp['INPUT'] == 'TEXT') { ?>
                        <input type="text" autocomplete="off" name="<?= $propertyCode ?>" value="<?= $arProp['VALUE'] ?>">
                    <? } else { ?>
                        <input type="checkbox" autocomplete="off" name="<?= $propertyCode ?>" <?= ($arProp['VALUE']) ? 'checked="checked"' : '' ?>>
                    <? } ?>
                </td>
            </tr>
        <? } ?>

        <?
        $tabControl->Buttons();
        $tabControl->End();
        $tabControl->ShowWarnings("post_form", $message);
        ?>

        <script type="text/javascript">
          window.catalog = <?=Json::encode($arCatalog)?>;
          window.shop = <?=Json::encode($arShops)?>;
          window.flags = <?=Json::encode($arData['FLAGS'])?>;
        </script>
    </form>

<?


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
