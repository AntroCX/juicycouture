<?php
namespace Jamilco\Delivery;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Main\Page\Asset;
use \Bitrix\Sale\Location\LocationTable;

class Events
{
    public function OnProlog()
    {
        /*
        if (empty($_COOKIE['city_name']) && Loader::includeModule('statistic') && Loader::includeModule('sale')) {
            $city = new \CCity;
            $city->GetCityID();
            $arCity = $city->GetFullInfo();

            if ($arCity['CITY_NAME']['VALUE']) {
                $loc = LocationTable::getList([
                    'filter' => [
                        '=NAME.LANGUAGE_ID' => Context::getCurrent()->getLanguage(),
                        'NAME.NAME' => $arCity['CITY_NAME']['VALUE'],
                    ],
                    'select' => ['ID', 'NAME_RU' => 'NAME.NAME'],
                    'limit' => 1,
                ]);

                if ($arLoc = $loc->Fetch()) {
                    setcookie('city_id', $arLoc['ID'], 0, '/');
                    setcookie('city_name', $arLoc['NAME_RU'], 0, '/');
                    $_COOKIE['city_id'] = $arLoc['ID'];
                    $_COOKIE['city_name'] = $arLoc['NAME_RU'];
                }
            }
        }
        */

        if($GLOBALS['USER']->IsAdmin() && $GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_detail.php") {
            if ($_REQUEST['AJAX_DELIVERY'] == 'Y') {
                $GLOBALS["APPLICATION"]->RestartBuffer();
                \CModule::IncludeModule('sale');

                $dateId = \COption::GetOptionInt("jamilco.delivery", "delivery_date");
                $timeId = \COption::GetOptionInt("jamilco.delivery", "delivery_time");

                $isNew = \CSaleOrderPropsValue::Add(array(
                    'ORDER_ID' => $_REQUEST['id'],
                    'ORDER_PROPS_ID' => $dateId,
                    'CODE' => 'DELIVERY_DATE',
                    'NAME' => 'Дата доставки',
                    'VALUE' => $_REQUEST['date']
                ));
                \CSaleOrderPropsValue::Add(array(
                    'ORDER_ID' => $_REQUEST['id'],
                    'ORDER_PROPS_ID' => $timeId,
                    'CODE' => 'DELIVERY_TIME',
                    'NAME' => 'Время доставки',
                    'VALUE' => $_REQUEST['time']
                ));
                if(!$isNew) {
                    $arProps = array();
                    $rsProps = \CSaleOrderPropsValue::GetList(
                        array(),
                        array('ORDER_ID' => $_REQUEST['id'], 'ORDER_PROPS_ID' => array($dateId, $timeId))
                    );
                    while ($arrProps = $rsProps->Fetch()) {
                        $arProps[$arrProps['ORDER_PROPS_ID']] = $arrProps['ID'];
                    }
                    \CSaleOrderPropsValue::Update(
                        $arProps[$dateId],
                        array(
                            'ORDER_ID' => $_REQUEST['id'],
                            'ORDER_PROPS_ID' => $dateId,
                            'NAME' => 'Дата доставки',
                            'VALUE' => $_REQUEST['date']
                        )
                    );
                    \CSaleOrderPropsValue::Update(
                        $arProps[$timeId],
                        array(
                            'ORDER_ID' => $_REQUEST['id'],
                            'ORDER_PROPS_ID' => $timeId,
                            'NAME' => 'Время доставки',
                            'VALUE' => $_REQUEST['time']
                        )
                    );
                }
                \CSaleOrderChange::AddRecord($_REQUEST['id'], 'ORDER_DELIVERY_DOC_CHANGED', array('DELIVERY_DOC_NUM' => $_REQUEST['id'], 'DELIVERY_DOC_DATE' => $_REQUEST['date'].' '.$_REQUEST['time']));
                define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/change_delivery.txt");
                echo 'Y';
                die();
            }
        }
    }

    public function OnAdminTabControlBegin() {
        // изменение заказа
        if($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_edit.php") {
            self::blockChangeOrderItems();
        }

        if($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_detail.php" ||
            $GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_view.php") {
            \CUtil::InitJSCore(array("jquery", "popup", "ajax"));
            ob_start();?>
            <div id="j-delivery__modal" class="adm-workarea" style="display:none;">
                <?
                // редактирование интервалов времени доставки
                // разреш. пользователи
                $allowedUsers = [
                    'fartukova@jamilco.ru',
                    'cherepneva_o@jamilco.ru',
                ];
                global $USER;
                $userEmail = $USER->GetEmail();
                ?>
                <? if(in_array($userEmail, $allowedUsers) || $USER->IsAdmin()): ?>
                    <a class="edit_link" href="/bitrix/admin/fileman_file_edit.php?path=%2Fupload%2Fjamilco.delivery%2Fdelivery_timetable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Редактировать интервалы времени доставки</a>
                <? endif; ?>                <table>
                    <tbody>
                    <tr>
                        <td class="head">Дата доставки:</td>
                        <td>

                            <div class="adm-input-wrap adm-input-wrap-calendar">
                                <input class="adm-input adm-input-calendar" type="text" name="JAMILCO_DELIVERY_DATE" size="13" value="<?=date('d.m.Y')?>">
                                <span class="adm-calendar-icon" title="Нажмите для выбора даты" onclick="BX.calendar({node:this, field:'JAMILCO_DELIVERY_DATE', form: '', bTime: false, bHideTime: false});"></span>
                            </div>										</td>
                    </tr>
                    <tr>
                        <td class="head">Время доставки:</td>
                        <td>
                            <?/* --- ALL-229 / 238 / 256---*/ ?>
                            <? include $_SERVER['DOCUMENT_ROOT'].'/upload/jamilco.delivery/delivery_timetable.php'?>
                            <?/*
                            <select name="JAMILCO_DELIVERY_TIME" id="JAMILCO_DELIVERY_TIME">
                                <optgroup label="RED - будни - МКАД">
                                    <option value="09:00-19:00">c 09:00 до 19:00</option>
                                    <option value="18:00-23:00">с 18:00 до 23:00</option>
                                </optgroup>
                                <optgroup label="RED - вых - МКАД">
                                    <option value="10:00-20:00">c 10:00 до 20:00</option>
                                </optgroup>
                                <optgroup label="RED - будни - КАД">
                                    <option value="12:00-19:00">c 12:00 до 19:00</option>
                                    <option value="18:00-23:00">с 18:00 до 23:00</option>
                                </optgroup>
                                <optgroup label="RED - вых - КАД">
                                    <option value="12:00-21:00">c 12:00 до 21:00</option>
                                </optgroup>
                                <optgroup label="РЕГИОНЫ:">
                                </optgroup>
                                <optgroup label="КСЕ-будни:">
                                    <option value="09:00-18:00">c 9:00 до 18:00</option>
                                </optgroup>
                            </select>
                            */?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <style>
                .j-delivery-title-bar {
                    display: block;
                    text-align: center;
                }
                #popup-window-content-schema{
                    position: relative;
                }
                #popup-window-content-schema .edit_link{
                    position: absolute;
                    top: 2px;
                }
            </style>
            <script type="text/javascript">
                BX.ready(function() {
                    var $wrapper = $('#btn_allow_delivery').find('.adm-detail-content-cell-r'),
                        $body = $('.adm-workarea-wrap'),
                        order_id = <?=$_REQUEST['ID']?>;
                    if($('#btn_allow_delivery').length == 0) {
                        //$wrapper = $('#delivery').next().find('.adm-bus-table-container');
                        $wrapper = $('.adm-container-draggable[data-id="delivery"]').find('.adm-bus-table-container');
                    }
                    $wrapper.append('<a class="adm-btn" style="margin-left: 20px" id="j-delivery__open-modal">Время доставки</a>');
                    var schema = new BX.PopupWindow("schema", null, {
                        content: BX('j-delivery__modal'),//Контейнер
                        closeIcon: {right: "20px", top: "10px"},//Иконка закрытия
                        titleBar: {
                            content: BX.create("span", {
                                html: '<b>Информация о доставке</b>',
                                'props': {'className': 'j-delivery-title-bar'}
                            })
                        },//Название окна
                        zIndex: 0,
                        offsetLeft: 0,
                        offsetTop: 0,
                        draggable: {restrict: true},//Окно можно перетаскивать на странице
                        buttons: [
                            new BX.PopupWindowButton({
                                text: "Сохранить",
                                className: "popup-window-button-accept",
                                events: {click: function(){
                                    var date = $('#j-delivery__modal').find('input[name=JAMILCO_DELIVERY_DATE]').val(),
                                        time = $('#j-delivery__modal').find('select[name=JAMILCO_DELIVERY_TIME]').val(),
                                        $this = this;

                                    BX.ajax({
                                        method: 'get',
                                        dataType: 'html',
                                        url: '/bitrix/admin/sale_order_detail.php?'+'id='+order_id+'&date='+date+'&time='+time+'&AJAX_DELIVERY=Y',
                                        processData: false,
                                        async: true,
                                        onsuccess: function (data) {
                                            $this.popupWindow.close();
                                            location.reload();
                                        }
                                    })

                                }}
                            }),
                            new BX.PopupWindowButton({
                                text: "Закрыть",
                                className: "webform-button-link-cancel",
                                events: {click: function(){
                                    this.popupWindow.close();// закрытие окна
                                }}
                            })
                        ]
                    });

                    $('#j-delivery__open-modal').on('click', function() {
                        schema.show();
                    })
                });
            </script>

            <?
            $sContent = ob_get_clean();
            $GLOBALS['APPLICATION']->AddHeadString($sContent);
        }
    }

    static public function blockChangeOrderItems()
    {
        global $USER, $APPLICATION;
        $arUserGroups = $USER->GetUserGroupArray();

        $arOrder = \CSaleOrder::GetByID($_REQUEST['ID']);

        // если заказ оплачен и пользователь не админ
        if ($arOrder['PAYED'] == 'Y' && !in_array(1, $arUserGroups)) {
            \CUtil::InitJSCore(["jquery"]);
            Asset::getInstance()->addJs('/local/modules/jamilco.delivery/admin/order_change_block_basket.js');
            $APPLICATION->SetAdditionalCSS('/local/modules/jamilco.delivery/admin/order_change_block_basket.css');
        }
    }
}