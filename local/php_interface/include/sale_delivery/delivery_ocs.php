<?php

CModule::IncludeModule("sale");


class CDeliveryOCS {
    function Init()
    {
        return array(
            /* Основное описание */
            "SID" => "ocs",
            "NAME" => "Доставка курьером OCS",
            "DESCRIPTION" => "",
            "DESCRIPTION_INNER" =>
                "Обработчик, связывается с OCS и получает стоимость доставки в зависимости от указанного города",
            "BASE_CURRENCY" => \COption::GetOptionString("sale", "default_currency", "RUB"),

            "HANDLER" => __FILE__,

            /* Методы обработчика */
            "DBGETSETTINGS" => array("CDeliveryOCS", "GetSettings"),
            "DBSETSETTINGS" => array("CDeliveryOCS", "SetSettings"),
            "GETCONFIG" => array("CDeliveryOCS", "GetConfig"),

            "COMPABILITY" => array("CDeliveryOCS", "Compability"),
            "CALCULATOR" => array("CDeliveryOCS", "Calculate"),

            /* Список профилей доставки */
            "PROFILES" => array(
                "ocs" => array(
                    "TITLE" => "доставка",
                    "DESCRIPTION" => "Срок доставки до 3 дней",

                    "RESTRICTIONS_WEIGHT" => array(0), // без ограничений
                    "RESTRICTIONS_SUM" => array(0), // без ограничений
                ),
            )
        );
    }

    // настройки обработчика
    function GetConfig()
    {
        $arConfig = array(
            "CONFIG_GROUPS" => array(
                "all" => "Стоимость доставки",
            ),

            "CONFIG" => array(),
        );

        // настройками обработчика в данном случае являются значения стоимости доставки в различные группы местоположений.
        // для этого сформируем список настроек на основе списка групп

        $dbLocationGroups = \CSaleLocationGroup::GetList();
        while ($arLocationGroup = $dbLocationGroups->Fetch())
        {
            $arConfig["CONFIG"]["price_".$arLocationGroup["ID"]] = array(
                "TYPE" => "STRING",
                "DEFAULT" => "",
                "TITLE" =>
                    "Стоимость доставки в группу \""
                    .$arLocationGroup["NAME"]."\" "
                    ."(".\COption::GetOptionString("sale", "default_currency", "RUB").')',
                "GROUP" => "all",
            );
        }

        return $arConfig;
    }

    // подготовка настроек для занесения в базу данных
    function SetSettings($arSettings)
    {
        // Проверим список значений стоимости. Пустые значения удалим из списка.
        foreach ($arSettings as $key => $value)
        {
            if (strlen($value) > 0)
                $arSettings[$key] = doubleval($value);
            else
                unset($arSettings[$key]);
        }

        // вернем значения в виде сериализованного массива.
        // в случае более простого списка настроек можно применить более простые методы сериализации.
        return serialize($arSettings);
    }

    // подготовка настроек, полученных из базы данных
    function GetSettings($strSettings)
    {
        // вернем десериализованный массив настроек
        return unserialize($strSettings);
    }

    // введем служебный метод, определяющий группу местоположения и возвращающий стоимость для этой группы.
    function __GetLocationPrice($LOCATION_ID, $arConfig)
    {
        // получим список групп для переданного местоположения
        $dbLocationGroups = \CSaleLocationGroup::GetLocationList(array("LOCATION_ID" => $LOCATION_ID));

        while ($arLocationGroup = $dbLocationGroups->Fetch())
        {
            if (
                array_key_exists('price_'.$arLocationGroup["LOCATION_GROUP_ID"], $arConfig)
                &&
                strlen($arConfig['price_'.$arLocationGroup["LOCATION_GROUP_ID"]]["VALUE"] > 0)
            )
            {
                // если есть непустая запись в массиве настроек для данной группы, вернем ее значение
                return $arConfig['price_'.$arLocationGroup["LOCATION_GROUP_ID"]]["VALUE"];
            }
        }

        // если не найдено подходящих записей, вернем false
        return false;
    }

    // метод проверки совместимости в данном случае практически аналогичен рассчету стоимости
    function Compability($arOrder, $arConfig)
    {
        // проверим наличие стоимости доставки
        $price = 400;

        if ($price === false)
            return array(); // если стоимость не найдено, вернем пустой массив - не подходит ни один профиль
        else
            return array('ocs'); // в противном случае вернем массив, содержащий идентфиикатор единственного профиля доставки
    }

    // собственно, рассчет стоимости
    function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
    {
        
        if($arOrder['PRICE'] < 10000) {

            $arLocation = \CSaleLocation::GetByID($arOrder['LOCATION_FROM']);

            $arDelivery = \Jamilco\Main\Oracle::getInstance()->getDeliveryPrices($arLocation['CITY_NAME_ORIG'], $arLocation['REGION_NAME_ORIG']);

        }   else {
            $arDelivery['PRICE'] = 0;
        }
        // служебный метод рассчета определён выше, нам достаточно переадресовать на выход возвращаемое им значение.
        return array(
            "RESULT" => "OK",
            "VALUE" => $arDelivery['PRICE']
        );
    }

}

AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryOCS', 'Init'));
