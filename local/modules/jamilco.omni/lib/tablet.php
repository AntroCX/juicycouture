<?php
namespace Jamilco\Omni;

use \Bitrix\Main\Loader;
use \Bitrix\Main\GroupTable;
use \Bitrix\Main\UserTable;
use \Bitrix\Iblock\IblockTable;

class Tablet
{
    /**
     * проверяет, является ли текущий пользователь "сотрудником из РМ"
     * @return bool
     */
    public function ifTablet()
    {
        global $USER;
        // если пользователь авторизован и входит в группу "Сотрудники РМ", то - ДА
        if ($USER->isAuthorized()) {
            $groupID = self::getUserGroup();
            $arGroups = $USER->GetUserGroupArray();
            if (in_array($groupID, $arGroups)) return true;
        }

        return false;
    }

    public function getCurrentShopData()
    {
        if ($shopId = self::getCurrentShopID()) {
            $arShop = \CIblockElement::GetByID($shopId)->Fetch();
            $pr = \CIBlockElement::GetProperty($arShop['IBLOCK_ID'], $arShop['ID'], array(), Array("CODE" => "ADDRESS"));
            $arProp = $pr->Fetch();
            $arOut = array(
                'ID'      => $arShop['ID'],
                'NAME'    => $arShop['NAME'],
                'ADDRESS' => $arProp['VALUE']
            );

            return $arOut;
        }

        return false;
    }

    /**
     * возвращает ID магазина текущего "Сотрудника РМ"
     * @return bool
     */
    public function getCurrentShopID()
    {
        if (self::ifTablet()) {
            $arTablets = array();

            // получим магазин текущего пользователя
            global $USER;
            $arCurrentUser = UserTable::getList(
                array(
                    'filter' => array('ID' => $USER->GetID()),
                    'limit'  => 1,
                    'select' => array('ID', 'UF_SHOP')
                )
            )->Fetch();

            if ($arCurrentUser['UF_SHOP'] > 0) {
                return $arCurrentUser['UF_SHOP'];
            }
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function getCurrentShopList()
    {
        if (self::ifTablet()) {
            $arTablets = array();

            // получим магазин текущего пользователя
            global $USER;
            $arCurrentUser = UserTable::getList(
                array(
                    'filter' => array('ID' => $USER->GetID()),
                    'limit'  => 1,
                    'select' => array('ID', 'UF_SHOP')
                )
            )->Fetch();

            if ($arCurrentUser['UF_SHOP'] > 0) {
                // получим всех сотрудников из этого магазина

                $tabletIblockId = self::getTabletIblockID();
                $el = \CIblockElement::GetList(
                    array('NAME' => 'ASC'),
                    array(
                        'IBLOCK_ID'        => $tabletIblockId,
                        'ACTIVE'           => 'Y',
                        'PROPERTY_SHOP'    => $arCurrentUser['UF_SHOP'],
                        '!PROPERTY_TABLET' => false,
                    ),
                    false,
                    false,
                    array('ID', 'NAME', 'PROPERTY_TABLET')
                );
                while ($arOne = $el->Fetch()) {
                    $arTablets[] = array(
                        'ID'           => $arOne['ID'],
                        'NAME'         => $arOne['NAME'],
                        'UF_TABLET_ID' => $arOne['PROPERTY_TABLET_VALUE'],
                    );
                }
            }

            return $arTablets;
        }

        return false;
    }

    /**
     * возвращает ID группы пользователей "Сотрудники РМ"
     * @return int|bool
     */
    public function getUserGroup()
    {
        $gr = GroupTable::getList(array('filter' => array('STRING_ID' => 'tablet')));
        if ($arGroup = $gr->Fetch()) {
            return $arGroup['ID'];
        }

        return false;
    }

    /**
     * добавляем пользователей из *.csv файлов
     */
    public function addUsers()
    {
        $dir = str_replace('/lib', '/csv', __DIR__);
        $numberFile = $dir.'/numbers.csv';
        $shopsFile = $dir.'/shops.csv';

        $arNumbers = self::getFromFile($numberFile, 'number');
        $arShops = self::getFromFile($shopsFile, 'shop');
        $arTypes = self::getSiteType();
        if (!$arTypes) return false;

        foreach ($arNumbers as $key => $arOne) {
            $del = true;
            foreach ($arTypes['NUMBER'] as $one) {
                if (substr_count($arOne['SHOP'], $one)) $del = false;
            }
            if ($del) {
                unset($arNumbers[$key]);
            } else {
                // удалим из названия магазина все лишнее
                $arNumbers[$key]['SHOP'] = self::clearShopName($arOne['SHOP'], $arTypes['NUMBER']);
            }
        }

        foreach ($arShops as $key => $arOne) {
            if ($arOne['TYPE'] != $arTypes['SHOP']) {
                unset($arShops[$key]);
            } else {
                $arShops[$key]['SHOP'] = self::clearShopName($arOne['SHOP']);
            }
        }

        self::getShopsByNames($arNumbers, $arShops);

        self::saveShops($arShops);
        self::saveNumbers($arNumbers);
    }

    function saveShops($arShops = array())
    {
        $user = new \CUser();

        $groupId = self::getUserGroup();

        foreach ($arShops as $arOne) {
            if (!$arOne['SHOP_ID']) continue;
            $us = UserTable::GetList(array('filter' => array('EMAIL' => $arOne['EMAIL'])));
            if ($arUser = $us->Fetch()) {
                //pr($arUser);
            } else {
                $res = $user->Add(
                    array(
                        'LOGIN'            => $arOne['EMAIL'],
                        'EMAIL'            => $arOne['EMAIL'],
                        'PASSWORD'         => $arOne['EMAIL'],
                        'CONFIRM_PASSWORD' => $arOne['EMAIL'],
                        'GROUP_ID'         => array($groupId),
                        'UF_SHOP'          => $arOne['SHOP_ID'],
                        'PERSONAL_PHONE'   => $arOne['PHONE'],
                    )
                );
            }
        }
    }

    public function getTabletIblockID()
    {
        Loader::includeModule('iblock');

        $element = new \CIblockElement();

        $res = IblockTable::getList(
            array(
                'filter' => array('CODE' => 'tablet'),
                'limit'  => 1,
                'select' => array('ID')
            )
        );
        if ($arIblock = $res->Fetch()) {
            return $arIblock['ID'];
        }

        return false;
    }

    function saveNumbers($arNumbers = array())
    {
        Loader::includeModule('iblock');

        $element = new \CIblockElement();

        $tabletIblockId = self::getTabletIblockID();

        foreach ($arNumbers as $arOne) {
            if (!$arOne['SHOP_ID']) continue;

            $arFields = array(
                'IBLOCK_ID'        => $tabletIblockId,
                'ACTIVE'           => 'Y',
                'NAME'             => $arOne['NAME'],
                'DATE_ACTIVE_FROM' => ConvertTimeStamp(false, 'FULL'),
                'PROPERTY_VALUES'  => array(
                    'TABLET' => $arOne['NUMBER'],
                    'DOLZH'  => $arOne['DOLZH'],
                    'CITY'   => $arOne['CITY'],
                    'SHOP'   => $arOne['SHOP_ID'],
                )
            );

            $el = \CIblockElement::GetList(
                array(),
                array(
                    'IBLOCK_ID' => $arFields['IBLOCK_ID'],
                    'NAME'      => $arFields['NAME'],
                )
            );
            if (!$arItem = $el->Fetch()) {
                $element->Add($arFields);
            }
        }
    }

    public function getSiteType()
    {
        $arTypes = array(
            'New Balance'   => array(
                'dev-nb.prmedia.su',
                'newbalance.ru',
            ),
            'Timberland'    => array(
                'dev-tl.prmedia.su',
                'timberland.ru',
            ),
            'Juicy Couture' => array(
                'dev-jc.prmedia.su',
                'juicycouture.ru',
            ),
            'Wolford' => array(
                'dev-wf.prmedia.su',
                'wolford-russia.ru'
            ),
        );

        $arNumberTypes = array(
            'New Balance'   => array(
                'Нью Баланс',
                'Нью Баланс-2',
            ),
            'Timberland'    => array(
                'Тимберленд',
            ),
            'Juicy Couture' => array(
                'Джуси Кутюр',
            ),
            'Wolford' => array(
                'Wolford',
                'Волфорд',
            ),
        );

        $type = false;
        $server = $_SERVER['HTTP_HOST'];
        foreach ($arTypes as $oneType => $arOne) {
            if (in_array($server, $arOne)) {
                $type = $oneType;
                break;
            }
        }
        if ($type) {
            $arOut = array('SHOP' => $type, 'NUMBER' => $arNumberTypes[$type]);

            return $arOut;
        }

        return false;
    }

    public function getFromFile($file = '', $type = '')
    {
        $arOut = array();
        if ($file && $handle = fopen($file, "r")) {
            while (($data = fgetcsv($handle, '', ';')) !== false) {
                if ($type == 'number') {
                    $arOut[] = array(
                        'ID'     => trim($data[0]),
                        'NAME'   => trim($data[1]),
                        'NUMBER' => trim($data[2]),
                        '~SHOP'  => trim($data[3]),
                        'SHOP'   => trim($data[3]),
                        'DOLZH'  => trim($data[4]),
                        'CITY'   => self::getCity(trim($data[5])),
                    );
                } elseif ($type == 'shop') {
                    if (substr_count($data[0], 'New Balance')) $shopType = 'New Balance';
                    if (substr_count($data[0], 'Timberland')) $shopType = 'Timberland';
                    if (substr_count($data[0], 'Juicy Couture')) $shopType = 'Juicy Couture';
                    if (substr_count($data[0], 'Wolford')) $shopType = 'Wolford';
                    $data[0] = str_replace(array('New Balance', 'Timberland', 'Juicy Couture', 'Wolford'), '', $data[0]);
                    $arOut[] = array(
                        '~SHOP' => trim($data[0]),
                        'SHOP'  => trim($data[0]),
                        'PHONE' => trim($data[1]),
                        'EMAIL' => trim($data[2]),
                        'TYPE'  => $shopType,
                        'CITY'  => self::getCityByPhone(trim($data[1])),
                    );
                }
            }
            fclose($handle);
        }

        return $arOut;
    }

    public function getCityByPhone($phoneShop = '')
    {
        $arCity = array(
            'Москва'          => array('495', '499'),
            'Екатеринбург'    => array('343'),
            'Санкт-Петербург' => array('812'),
            'Ростов-на-Дону'  => array('863'),
        );

        foreach ($arCity as $city => $arPhone) {
            foreach ($arPhone as $phone) {
                if (substr_count($phoneShop, '('.$phone.')')) {
                    return $city;
                }
            }
        }

        return false;
    }

    public function getCity($city = '')
    {
        $city = str_replace(
            array(
                'Питер',
                'Ростов',
            ),
            array(
                'Санкт-Петербург',
                'Ростов-на-Дону',
            ),
            $city
        );

        return $city;
    }

    public function getShopsByNames(&$arNumbers, &$arShops)
    {
        $arNames = array();
        foreach ($arNumbers as $arOne) {
            $key = $arOne['SHOP'].'_'.$arOne['CITY'];
            $arNames[$key] = array(
                'NAME' => $arOne['SHOP'],
                'CITY' => $arOne['CITY'],
            );
        }

        foreach ($arShops as $arOne) {
            $key = $arOne['SHOP'].'_'.$arOne['CITY'];
            $arNames[$key] = array(
                'NAME' => $arOne['SHOP'],
                'CITY' => $arOne['CITY'],
            );
        }

        self::getShopsID($arNames);

        foreach ($arNumbers as &$arOne) {
            $key = $arOne['SHOP'].'_'.$arOne['CITY'];
            if ($arNames[$key]['ID']) {
                $arOne['SHOP_ID'] = $arNames[$key]['ID'];
            }
        }
        foreach ($arShops as &$arOne) {
            $key = $arOne['SHOP'].'_'.$arOne['CITY'];
            if ($arNames[$key]['ID']) {
                $arOne['SHOP_ID'] = $arNames[$key]['ID'];
            }
        }
    }

    public function getShopsID(&$arNames = array())
    {
        Loader::includeModule('iblock');

        $res = IblockTable::getList(
            array(
                'filter' => array('NAME' => 'Магазины'),
                'limit'  => 1,
                'select' => array('ID')
            )
        );
        if ($arIblock = $res->Fetch()) {
            $shopsIblockId = $arIblock['ID'];
        }

        $arShopCity = array();
        $se = \CIblockSection::GetList(
            array(),
            array(
                "IBLOCK_ID" => $shopsIblockId,
            )
        );
        while ($arSection = $se->Fetch()) {
            $arShopCity[$arSection['NAME']] = $arSection['ID'];
        }
        foreach ($arNames as $key => $arOne) {
            $arNames[$key]['ID'] = self::getShopID($arOne['NAME'], $shopsIblockId, $arShopCity[$arOne['CITY']]);
        }
    }

    public function getShopID($name = '', $iblockId = 0, $city = 0)
    {
        $arFilter = array(
            'IBLOCK_ID' => $iblockId,
            '?NAME'     => "%$name%",
        );
        if ($city) $arFilter['SECTION_ID'] = $city;
        $el = \CIblockElement::GetList(
            array(),
            $arFilter,
            false,
            array('nTopCount' => 1),
            array('ID')
        );
        if ($arItem = $el->Fetch()) {
            return $arItem['ID'];
        }

        return false;
    }

    public function clearShopName($shop = '', $arNumber = array())
    {
        foreach ($arNumber as $one) {
            $shop = str_replace($one, '', $shop);
        }

        $shop = str_replace(
            array(
                'магазин',
                'Магазин',
                '"',
                'в торгово-развлекательном комплексе',
                'в торгово-развлекательном центре',
                'в многофункциональном комплексе',
                'в торговом доме',
                'в торговом комплексе',
                'в торговом центре',
                'в торговой галерее',
            ),
            '',
            $shop
        );

        $shop = str_replace(
            array(
                'Вегас',
                'Авеню Саус-Вест',
                'Авеню',
                'Неглинная Плаза',
                'Капитолий VII',
                'Мега 2',
                'Мега-Белая Дача',
                'в ГУМе, Красная пл., д.3',
            ),
            array(
                'VEGAS',
                'Avenue South-West',
                'Avenue South-West',
                'Неглинная',
                'Капитолий',
                'МЕГА Химки',
                'МЕГА Белая Дача',
                'ГУМ',
            ),
            $shop
        );

        $shop = trim($shop);

        // точные замены
        if ($shop == 'Мега') $shop = 'МЕГА Теплый Стан';

        return $shop;
    }

}