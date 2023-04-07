<?
namespace Jamilco\Delivery;

use \Bitrix\Main\Loader;
use \Bitrix\Iblock\TypeTable;
use \Bitrix\Iblock\IblockTable;
use \Bitrix\Iblock\SectionTable;
use \Bitrix\Iblock\ElementTable;
use \Bitrix\Iblock\Model\Section as ModelSection;
use \Bitrix\Sale\Delivery\Services\Table as ServicesTable;
use \Bitrix\Main\Config\ConfigurationException;
use \Bitrix\Main\Grid\Declension;

class Ozon
{
    const SOAP_NAMESPACE = 'http://Ocourier.Services.Delivery/';
    var $deliveryType = array('Постомат', 'Самовывоз'); // разрешенные типы доставки из Ozon Delivery

    var $foundHandler = false;
    var $config = array();
    var $arSections = array();
    var $authParams = false; // LOGIN, PASSWORD для http-авторизации на удаленном сервере
    var $element = false;
    var $section = false;
    var $arLog = array();

    /**
     * возвращает список пунктов выдачи
     *
     * @param int   $locationId - ID местоположения
     * @param int   $limit
     * @param array $filter     - массив полей для фильтрации ПВЗ
     *
     * @return array
     */
    public static function getPvzList($locationId = 0, $limit = 0, array $filter = [])
    {
        $arResult = array();

        $arFieldsReturn = array('ID', 'CODE', 'NAME', 'DETAIL_TEXT');

        $ozon = new self;

        // получим раздел с указанным LOCATION
        $entity = ModelSection::compileEntityByIblock($ozon->config['IBLOCK_PVZ']);
        $se = $entity::getList(
            array(
                'filter' => array(
                    'IBLOCK_ID'   => $ozon->config['IBLOCK_PVZ'],
                    'UF_LOCATION' => $locationId,
                ),
                'select' => array('ID'),
            )
        );
        if ($arSect = $se->Fetch()) {
            $arPage = false;
            $arGeoPoints = []; // массив точек на карте, для того, чтобы запретить два ПВЗ в одной точке
            if ($limit > 0) $arPage = array('nTopCount' => $limit);
            $el = \CIblockElement::GetList(
                array(
                    'SORT'             => 'ASC',
                    'PROPERTY_ADDRESS' => 'ASC',
                ),
                array_merge(
                    array(
                        'IBLOCK_ID'  => $ozon->config['IBLOCK_PVZ'],
                        'SECTION_ID' => $arSect['ID'],
                        'ACTIVE'     => 'Y',
                    ),
                    $filter
                ),
                false,
                $arPage
            );
            while ($obItem = $el->GetNextElement()) {
                $arItem = $obItem->GetFields();
                $arProps = $obItem->GetProperties();
                $geoPoint = $arProps['GEO_LAT']['VALUE'].'-'.$arProps['GEO_LON']['VALUE'];
                if (in_array($geoPoint, $arGeoPoints)) continue;
                $arGeoPoints[] = $geoPoint;

                foreach ($arItem as $key => $val) {
                    if (!in_array($key, $arFieldsReturn)) unset($arItem[$key]);
                }
                foreach ($arProps as $code => $arProp) {
                    $arItem['PROPERTIES'][$code] = $arProp['VALUE'];
                }
                // сделаем адрес без города и региона
                $arItem['ADDRESS'] = explode(',', $arItem['PROPERTIES']['ADDRESS']);
                TrimArr($arItem['ADDRESS'], true);
                if ($arItem['PROPERTIES']['ADDRESS_REGION'] > '') array_shift($arItem['ADDRESS']);
                if ($arItem['PROPERTIES']['ADDRESS_CITY'] > '') array_shift($arItem['ADDRESS']);
                $arItem['ADDRESS'] = implode(', ', $arItem['ADDRESS']);

                $arItem['PROPERTIES']['METRO'] = trim($arItem['PROPERTIES']['METRO']);
                if ($arItem['PROPERTIES']['METRO'] == ',') $arItem['PROPERTIES']['METRO'] = '';

                $arItem['DETAIL_TEXT'] = htmlspecialcharsBack($arItem['DETAIL_TEXT']);
                $arItem['DETAIL_TEXT'] = htmlspecialcharsBack($arItem['DETAIL_TEXT']);
                $arItem['PROPERTIES']['HOW_TO_GET'] = htmlspecialcharsBack($arItem['PROPERTIES']['HOW_TO_GET']);
                $arItem['PROPERTIES']['HOW_TO_GET'] = htmlspecialcharsBack($arItem['PROPERTIES']['HOW_TO_GET']);

                $arResult[$arItem['CODE']] = $arItem;
            }
        }

        return $arResult;
    }

    /**
     * возвращает срок доставки до первого ПВЗ из указанного местоположения
     *
     * @param $locationID
     *
     * @return bool|string
     */
    public static function getDeliveryTimeByLocation($locationID)
    {
        $arPvzList = self::getPvzList($locationID, 1);
        foreach ($arPvzList as $code => $arOne) {
            $deliveryTime = self::getDeliveryTime($arOne);

            return $deliveryTime;
        }

        return false;
    }

    /**
     * возвращает срок доставки до конкретного ПВЗ
     *
     * @param string $pvzCode
     *
     * @return bool|string
     */
    public static function getDeliveryTime($arOne = [])
    {
        $pvzCode = $arOne['CODE'];
        if ($arOne['PROPERTIES']['TIME'] > 0) {
            $day = $arOne['PROPERTIES']['TIME'];
        } else {
            $ozon = new self;
            $day = $ozon->getDeliveryVariantEstimationTimeList($pvzCode);
            $day = (int)$day;
            \CIBlockElement::SetPropertyValuesEx($arOne['ID'], $ozon->config['IBLOCK_PVZ'], array('TIME' => $day));
        }

        if (!$day) $day = 7; // если озон не вернул ответ

        $dayDeclension = new Declension('день', 'дня', 'дней');
        if ($day) return $day.' '.$dayDeclension->get($day);

        return false;
    }

    /**
     * возвращает ID инфоблока с ПВЗ (если нужно - создает его)
     *
     * @return bool
     */
    static public function getPvzIblockID()
    {
        Loader::includeModule('iblock');

        // инфоблок "Ozon Delivery. Пункты выдачи"
        $res = IblockTable::getList(
            array(
                'filter' => array('CODE' => 'ozon_pvz')
            )
        );
        if ($arIblock = $res->Fetch()) {
            return $arIblock['ID'];
        } else {
            $ib = TypeTable::getList(
                array(
                    'filter' => array(
                        'ID' => array('reference', 'references', 'technical')
                    ),
                    'limit'  => 1
                )
            );
            if ($arType = $ib->Fetch()) {
                $iblock = new \CIBlock();
                $arIblock['ID'] = $iblock->Add(
                    array(
                        "ACTIVE"          => 'Y',
                        "NAME"            => 'Ozon Delivery. Пункты выдачи',
                        "CODE"            => 'ozon_pvz',
                        "LIST_PAGE_URL"   => '',
                        "DETAIL_PAGE_URL" => '',
                        "IBLOCK_TYPE_ID"  => $arType['ID'],
                        "SITE_ID"         => array('s1'),
                        "SORT"            => 500,
                        "GROUP_ID"        => Array("2" => "R"),
                        "INDEX_ELEMENT"   => "N",
                        "INDEX_SECTION"   => "N",
                        "VERSION"         => 1,
                        "FIELDS"          => array(
                            "IBLOCK_SECTION" => array(
                                "IS_REQUIRED" => "Y",
                            ),
                            "CODE"           => array(
                                "IS_REQUIRED" => "Y",
                            ),
                        )
                    )
                );

                if ($arIblock['ID']) {
                    // свойства разделов
                    $userType = new \CUserTypeEntity();
                    $userType->Add(
                        array(
                            'ENTITY_ID'    => 'IBLOCK_'.$arIblock['ID'].'_SECTION',
                            'FIELD_NAME'   => 'UF_PRICE',
                            'USER_TYPE_ID' => 'integer',
                            'SORT'         => '100',
                            'SETTINGS'     => array()
                        )
                    );
                    $userType->Add(
                        array(
                            'ENTITY_ID'    => 'IBLOCK_'.$arIblock['ID'].'_SECTION',
                            'FIELD_NAME'   => 'UF_LOCATION',
                            'USER_TYPE_ID' => 'integer',
                            'SORT'         => '110',
                            'SETTINGS'     => array()
                        )
                    );

                    // свойства элементов
                    $ibp = new \CIBlockProperty();
                    $ibp->Add(
                        array(
                            "NAME"          => "Тип", // Самовывоз \ Постомат
                            "ACTIVE"        => "Y",
                            "SORT"          => "100",
                            "CODE"          => "TYPE",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "Y",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Адрес",
                            "ACTIVE"        => "Y",
                            "SORT"          => "200",
                            "CODE"          => "ADDRESS",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Регион",
                            "ACTIVE"        => "Y",
                            "SORT"          => "210",
                            "CODE"          => "ADDRESS_REGION",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Город",
                            "ACTIVE"        => "Y",
                            "SORT"          => "220",
                            "CODE"          => "ADDRESS_CITY",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Улица",
                            "ACTIVE"        => "Y",
                            "SORT"          => "230",
                            "CODE"          => "ADDRESS_STREET",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Дом",
                            "ACTIVE"        => "Y",
                            "SORT"          => "240",
                            "CODE"          => "ADDRESS_HOUSE",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Примерка обуви",
                            "ACTIVE"        => "Y",
                            "SORT"          => "300",
                            "CODE"          => "FITTING_SHOES",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Примерка одежды",
                            "ACTIVE"        => "Y",
                            "SORT"          => "310",
                            "CODE"          => "FITTING_CLOTHES",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Оплата картой",
                            "ACTIVE"        => "Y",
                            "SORT"          => "320",
                            "CODE"          => "CARD_PAYMENT",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Частичная выдача",
                            "ACTIVE"        => "Y",
                            "SORT"          => "330",
                            "CODE"          => "HALF_TAKE",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Запрет приема наличных",
                            "ACTIVE"        => "Y",
                            "SORT"          => "340",
                            "CODE"          => "FORBIDDEN_CASH",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Как добраться",
                            "ACTIVE"        => "Y",
                            "SORT"          => "350",
                            "CODE"          => "HOW_TO_GET",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Телефон",
                            "ACTIVE"        => "Y",
                            "SORT"          => "400",
                            "CODE"          => "PHONE",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Доставщик",
                            "ACTIVE"        => "Y",
                            "SORT"          => "410",
                            "CODE"          => "CONTRACTOR_NAME",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "GEO [lat]",
                            "ACTIVE"        => "Y",
                            "SORT"          => "420",
                            "CODE"          => "GEO_LAT",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "GEO [lon]",
                            "ACTIVE"        => "Y",
                            "SORT"          => "430",
                            "CODE"          => "GEO_LON",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Код",
                            "ACTIVE"        => "Y",
                            "SORT"          => "440",
                            "CODE"          => "CODE",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Станция метро",
                            "ACTIVE"        => "Y",
                            "SORT"          => "500",
                            "CODE"          => "METRO",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );
                    $ibp->Add(
                        array(
                            "NAME"          => "Срок доставки",
                            "ACTIVE"        => "Y",
                            "SORT"          => "600",
                            "CODE"          => "TIME",
                            "PROPERTY_TYPE" => "S",
                            "IS_REQUIRED"   => "N",
                            "IBLOCK_ID"     => $arIblock['ID'],
                        )
                    );

                    return $arIblock['ID'];
                }
            }
        }

        return false;
    }

    /**
     * @throws ConfigurationException
     */
    function __construct()
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('sale') || !Loader::includeModule('webservice')) {
            throw new ConfigurationException('One of the modules is not installed: iblock, sale, webservice');
        }

        $this->getSettings();

        if (!$this->foundHandler) {
            throw new ConfigurationException('Delivery handler not found');
        }
    }

    /**
     * получает настройки из службы доставки
     */
    function getSettings()
    {
        $res = ServicesTable::getList(array('filter' => array('CLASS_NAME' => '%OzonHandler')));
        if ($arRes = $res->Fetch()) {
            $this->foundHandler = true;
            $this->config = $arRes['CONFIG']['MAIN'];
        }
    }

    function getAuthParams($method = '')
    {
        // список методов, в которых contractID вместо contractId
        $arContractID = array(
            'GetDeliveryVariantEstimationTimeList',
        );
        $contractID = (in_array($method, $arContractID)) ? 'contractID' : 'contractId';

        $arParams = array(
            'login'     => $this->config['SERVER_LOGIN'],
            'password'  => $this->config['SERVER_PASSWORD'],
            $contractID => $this->config['SERVER_CONTRACT_ID'],
        );

        return $arParams;
    }

    /**
     * возвращает срок доставки до конкретного ПВЗ в днях
     *
     * @param string $pvzCode
     *
     * @return bool|string
     */
    function getDeliveryVariantEstimationTimeList($pvzCode = '')
    {
        $method = 'GetDeliveryVariantEstimationTimeList';
        $arParams = $this->getAuthParams($method);
        $arParams['deliveryVariantID'] = $pvzCode;

        $arResult = $this->sendRequest($method, $arParams);

        $day = false; // количество дней доставки
        if ($arResult['RESULT'] == 'OK') {
            $arTimeList = $arResult['RESPONSE']['soap:Body'][$method.'Response'][$method.'Reply']['Items']['DeliveryVariantEstimationTime'];
            if ($arTimeList['EstimationTime']) {
                $arOne = $arTimeList;
            } else {
                $arOne = array_shift($arTimeList);
            }
            $arOne['FromPlaceName'] = ToLower($arOne['FromPlaceName']);
            $day = (int)$arOne['EstimationTime'];

        } else {
            pr($arResult['MESSAGE']);

            return false;
        }

        return $day;
    }

    /**
     * возвращает список вариантов доставки
     *
     * @param string $cityName - Название города
     * @param string $type     - [Самовывоз, Курьерская, Постомат]
     *
     * @return array
     */
    function getDeliveryVariantList($cityName = '', $type = '')
    {
        $method = 'GetDeliveryVariantList';
        $arParams = $this->getAuthParams($method);
        if ($cityName) $arParams['cityName'] = $cityName;
        if ($type) $arParams['type'] = $type;

        $arVariantList = array();
        $arResult = $this->sendRequest($method, $arParams);

        if ($arResult['RESULT'] == 'OK') {
            $arVariantList = $arResult['RESPONSE']['soap:Body'][$method.'Response']['GetDeliveryVariantReply']['Items']['DeliveryVariant'];
        } else {
            pr($arResult['MESSAGE']);

            return false;
        }

        return $arVariantList;
    }

    /**
     * устанавливает во все разделы привязку к местоположениям из модуля ИМ
     */
    function checkSectionLocations()
    {
        if (!$this->section) $this->section = new \CIblockSection();
        $section = $this->section;

        $entity = ModelSection::compileEntityByIblock($this->config['IBLOCK_PVZ']);

        $arData = array();

        // первый уровень разделов
        $se = $entity::getList(
            array(
                'filter' => array(
                    'IBLOCK_ID'   => $this->config['IBLOCK_PVZ'],
                    'DEPTH_LEVEL' => 1,
                ),
                'select' => array('IBLOCK_ID', 'IBLOCK_SECTION_ID', 'ID', 'NAME', 'UF_LOCATION'),
            )
        );
        while ($arSect = $se->Fetch()) {
            if (!$arSect['UF_LOCATION']) {
                $arSect['NAME'] = $this->checkRegionName($arSect['NAME']);
                $loc = \Bitrix\Sale\Location\LocationTable::getList(
                    array(
                        'order'  => array('ID' => 'ASC'),
                        'filter' => array(
                            '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                            'NAME.NAME'         => '%'.$arSect['NAME'].'%',
                            //'TYPE.CODE'         => 'REGION', // может быть REGION и CITY
                        ),
                        'select' => array(
                            'ID',
                            'NAME_RU'   => 'NAME.NAME',
                            'TYPE_CODE' => 'TYPE.CODE',
                        ),
                        'limit'  => 1,
                    )
                );
                if ($arLoc = $loc->Fetch()) {
                    $arSect['UF_LOCATION'] = $arLoc['ID'];
                    $section->Update($arSect['ID'], array('UF_LOCATION' => $arLoc['ID']));
                }
            }
            $arData[$arSect['ID']] = $arSect['UF_LOCATION'];
        }

        // второй уровень разделов
        $se = $entity::getList(
            array(
                'filter' => array(
                    'IBLOCK_ID'   => $this->config['IBLOCK_PVZ'],
                    'DEPTH_LEVEL' => 2,
                ),
                'select' => array('IBLOCK_ID', 'IBLOCK_SECTION_ID', 'ID', 'NAME', 'UF_LOCATION'),
            )
        );
        while ($arSect = $se->Fetch()) {
            if (!$arSect['UF_LOCATION']) {
                $arSect['NAME'] = $this->checkRegionName($arSect['NAME']);
                $parentId = $arData[$arSect['IBLOCK_SECTION_ID']];
                $loc = \Bitrix\Sale\Location\LocationTable::getList(
                    array(
                        'order'  => array('ID' => 'ASC'),
                        'filter' => array(
                            '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                            'NAME.NAME'         => '%'.$arSect['NAME'].'%',
                            'PARENT_ID'         => $parentId,
                            //'TYPE.CODE'         => 'CITY', // могут быть как города, так и села
                        ),
                        'select' => array(
                            'ID',
                            'NAME_RU'   => 'NAME.NAME',
                            'TYPE_CODE' => 'TYPE.CODE',
                        ),
                        'limit'  => 1,
                    )
                );
                if ($arLoc = $loc->Fetch()) {
                    $arSect['UF_LOCATION'] = $arLoc['ID'];
                    $section->Update($arSect['ID'], array('UF_LOCATION' => $arLoc['ID']));
                } else {
                    // местоположение может лежать глубже
                    $arParents = array($parentId);
                    $loc = \Bitrix\Sale\Location\LocationTable::getList(
                        array(
                            'order'  => array('ID' => 'ASC'),
                            'filter' => array(
                                '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                                'PARENT_ID'         => $parentId,
                            ),
                            'select' => array(
                                'ID',
                                'NAME_RU'   => 'NAME.NAME',
                                'TYPE_CODE' => 'TYPE.CODE',
                            ),
                        )
                    );
                    while ($arLoc = $loc->Fetch()) {
                        $arParents[] = $arLoc['ID'];
                    }

                    $loc = \Bitrix\Sale\Location\LocationTable::getList(
                        array(
                            'order'  => array('ID' => 'ASC'),
                            'filter' => array(
                                '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                                'NAME.NAME'         => '%'.$arSect['NAME'].'%',
                                'PARENT_ID'         => $arParents,
                                //'TYPE.CODE'         => 'CITY', // могут быть как города, так и села
                            ),
                            'select' => array(
                                'ID',
                                'NAME_RU'   => 'NAME.NAME',
                                'TYPE_CODE' => 'TYPE.CODE',
                            ),
                            'limit'  => 1,
                        )
                    );
                    if ($arLoc = $loc->Fetch()) {
                        $arSect['UF_LOCATION'] = $arLoc['ID'];
                        $section->Update($arSect['ID'], array('UF_LOCATION' => $arLoc['ID']));
                    }
                }
            }
            $arData[$arSect['ID']] = $arSect['UF_LOCATION'];
        }
    }

    function checkRegionName($name = '')
    {
        $name = str_replace(
            array(
                'Ханты-Мансийский Автономный округ - Югра',
                'Северная Осетия - Алания',
                'Саха /Якутия/',
                'Норильск.',
                'Хабаровск.',
            ),
            array(
                'Ханты-Мансийский автономный округ',
                'Республика Северная Осетия-Алания',
                'Республика Саха (Якутия)',
                'Норильск',
                'Хабаровск',
            ),
            $name
        );

        if ($name == 'Алтай') $name = 'Республика Алтай';

        return $name;
    }

    /**
     * получает и сохраняет список ПВЗ
     */
    function saveDeliveryVariants()
    {
        $arVariantList = $this->getDeliveryVariantList();
        $this->arLog['COUNT']['ALL'] = count($arVariantList);
        $arIDs = [];
        foreach ($arVariantList as $arOne) {
            $this->arLog['COUNT'][$arOne['ObjectTypeName']]++;
            if (!in_array($arOne['ObjectTypeName'], $this->deliveryType)) continue;
            if ($arOne['Region']) {
                $arOne['RegionData'] = 'Y';
                $arData[$arOne['Region']][$arOne['Settlement']][] = $arOne;
            } else {
                $arOne['RegionData'] = 'N';
                $arData[$arOne['Settlement']][] = $arOne;
            }
        }

        foreach ($arData as $region => $arRegionData) {
            if ($arRegionData[0]['RegionData'] == 'N') {
                foreach ($arRegionData as $arOne) {
                    $id = $this->saveOneVariant($region, false, $arOne);
                    $arIDs[] = $id;
                }
            } else {
                foreach ($arRegionData as $settlement => $arCityData) {
                    foreach ($arCityData as $arOne) {
                        $id = $this->saveOneVariant($region, $settlement, $arOne);
                        $arIDs[] = $id;
                    }
                }
            }
        }

        $this->deactiveOldVariants($arIDs); // деактивируем старые ПВЗ

        $this->checkSectionLocations(); // проверяет привязку разделов к Местоположениям
    }

    function deactiveOldVariants($arIDs = [])
    {
        if (!$arIDs) return false;

        if (!$this->element) $this->element = new \CIblockElement();
        $element = $this->element;

        $el = \CIblockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $this->config['IBLOCK_PVZ'],
                '!ID'       => $arIDs
            ],
            false,
            false,
            ['ID']
        );
        while ($arItem = $el->Fetch()) {
            $element->Update($arItem['ID'], ['ACTIVE' => 'N']);
        }
    }

    function saveOneVariant($region = '', $settlement = '', $arData = array())
    {
        if (!$this->element) $this->element = new \CIblockElement();
        $element = $this->element;

        foreach ($arData as $key => $val) {
            if (is_array($val) && !$val) $arData[$key] = '';
        }

        $arFields = array(
            'IBLOCK_ID'         => $this->config['IBLOCK_PVZ'],
            'IBLOCK_SECTION_ID' => $this->getSectionID($region, $settlement),
            'ACTIVE'            => self::ifConditional($arData['Enabled']),
            'NAME'              => (string)$arData['Name'],
            'CODE'              => (string)$arData['Id'],
            'DETAIL_TEXT'       => (string)$arData['Description'],
            'DETAIL_TEXT_TYPE'  => 'text',
            'PROPERTY_VALUES'   => array(
                'TYPE'            => (string)$arData['ObjectTypeName'],
                'ADDRESS'         => (string)$arData['Address'],
                'ADDRESS_REGION'  => (string)$arData['Region'],
                'ADDRESS_CITY'    => (string)$arData['Settlement'],
                'ADDRESS_STREET'  => (string)$arData['Streets'],
                'ADDRESS_HOUSE'   => (string)$arData['Placement'],
                'FITTING_SHOES'   => self::ifConditional($arData['FittingShoesAvailable']),
                'FITTING_CLOTHES' => self::ifConditional($arData['FittingClothesAvailable']),
                'CARD_PAYMENT'    => self::ifConditional($arData['CardPaymentAvailable']),
                'HOW_TO_GET'      => (string)$arData['HowToGet'],
                'PHONE'           => (string)$arData['Phone'],
                'CONTRACTOR_NAME' => (string)$arData['ContractorName'],
                'GEO_LAT'         => (string)$arData['Lat'],
                'GEO_LON'         => (string)$arData['Long'],
                'CODE'            => (string)$arData['Code'],
                'METRO'           => ($arData['SubwayStations']['MetroStation']) ? $arData['SubwayStations']['MetroStation']['MetroStationName'].', '.$arData['SubwayStations']['MetroStation']['MetroStationLineName'] : '',
                'HALF_TAKE'       => self::ifConditional($arData['PartialGiveOutAvailable']),
                'FORBIDDEN_CASH'  => self::ifConditional($arData['IsCashForbidden']),
                'MAX_PRICE'       => (string)$arData['MaxPrice']
            ),
        );

        $arSelectElem = array("ID", "NAME", "DATE_ACTIVE_FROM");

        if (!empty($arFields["PROPERTY_VALUES"])) {
            foreach ($arFields["PROPERTY_VALUES"] as $propKey => $propVal) {
                $arSelectElem[] = "PROPERTY_".$propKey;
            }
        }

        $arFilterElem = array(
            "IBLOCK_ID"   => $arFields['IBLOCK_ID'],
            "ACTIVE_DATE" => "Y",
            "ACTIVE"      => "Y",
            "CODE"        => $arFields['CODE']
        );

        $itemId = false;
        $el = \CIBlockElement::GetList([], $arFilterElem, false, ['nTopCount' => 1], $arSelectElem);

        if ($arItem = $el->Fetch()) {
            $itemId = $arItem['ID'];
            // обновляем
            $change = false;
            $propCode = "";
            foreach ($arItem as $key => $val) {
                if ($key == 'ID') continue;
                if (strpos($key, "PROPERTY_", 0) !== false) {
                    $propCode = str_replace("PROPERTY_", "", $key);
                    $propCode = mb_substr($propCode, 0, -6);
                    if ($arItem[$key] != $arFields["PROPERTY_VALUES"][$propCode]) $change = true;
                } else {
                    if ($arItem[$key] != $arFields[$key]) $change = true;
                }
            }

            if ($change) {
                $element->Update($arItem['ID'], $arFields);

                $this->arLog['ELEMENT']['UPDATED']++;
            } else {
                $this->arLog['ELEMENT']['FOUNDED']++;
            }
        } else {
            // добавляем
            $res = $element->Add($arFields);
            if (!$res) {
                $arFields['ERROR'] = $element->LAST_ERROR;
                pr($arFields);

                return false;
            } else {
                $itemId = $res;
            }

            $this->arLog['ELEMENT']['CREATED']++;
        }

        return $itemId;
    }

    static function ifConditional($text = '')
    {
        $text = ToLower($text);
        if ($text == 'true') return 'Y';

        return 'N';
    }

    function getSectionID($region = '', $settlement = '')
    {
        $region = trim($region);
        $settlement = trim($settlement);

        if (!$this->section) $this->section = new \CIblockSection();

        $return = '';
        if ($region > '') {
            if (!$this->arSections[$region]) {
                $se = SectionTable::getList(
                    array(
                        'filter' => array(
                            'IBLOCK_ID'   => $this->config['IBLOCK_PVZ'],
                            'NAME'        => $region,
                            'DEPTH_LEVEL' => 1,
                        ),
                        'select' => array('ID'),
                        'limit'  => 1,
                    )
                );
                if (!$arSect = $se->Fetch()) {
                    $section = $this->section;
                    $arSect['ID'] = $section->Add(
                        array(
                            'IBLOCK_ID' => $this->config['IBLOCK_PVZ'],
                            'NAME'      => $region,
                            'ACTIVE'    => 'Y',
                        )
                    );
                    $this->arLog['SECTION']['CREATED'][] = $region;
                } else {
                    $this->arLog['SECTION']['FOUNDED']++;
                }
                $this->arSections[$region] = array('ID' => $arSect['ID']);
            }
            if ($this->arSections[$region]) {
                $return = $this->arSections[$region]['ID'];
                if ($settlement) {
                    if (!$this->arSections[$region]['SECTIONS'][$settlement]) {
                        $se = SectionTable::getList(
                            array(
                                'filter' => array(
                                    'IBLOCK_ID'         => $this->config['IBLOCK_PVZ'],
                                    'IBLOCK_SECTION_ID' => $this->arSections[$region]['ID'],
                                    'NAME'              => $settlement,
                                    'DEPTH_LEVEL'       => 2,
                                ),
                                'select' => array('ID'),
                                'limit'  => 1,
                            )
                        );
                        if (!$arSect = $se->Fetch()) {
                            $section = $this->section;
                            $arSect['ID'] = $section->Add(
                                array(
                                    'IBLOCK_ID'         => $this->config['IBLOCK_PVZ'],
                                    'IBLOCK_SECTION_ID' => $this->arSections[$region]['ID'],
                                    'NAME'              => $settlement,
                                    'ACTIVE'            => 'Y',
                                )
                            );
                            $this->arLog['SECTION']['CREATED']++;
                        } else {
                            $this->arLog['SECTION']['FOUNDED']++;
                        }
                        $this->arSections[$region]['SECTIONS'][$settlement] = $arSect['ID'];
                    }

                    if ($this->arSections[$region]['SECTIONS'][$settlement]) $return = $this->arSections[$region]['SECTIONS'][$settlement];
                }
            }
        }

        return $return;
    }

    /**
     * отсылает запрос
     *
     * @param string $method
     * @param array  $params
     *
     * @return array
     * @throws ConfigurationException
     */
    function sendRequest($method = '', $params = array())
    {
        $path = $this->config['SERVER_URL'];
        $namespace = self::SOAP_NAMESPACE;
        $authParams = $this->authParams;

        $request = new \CSOAPRequest($method, $namespace, $params);

        $arOut = array(
            "RESULT"   => "ERROR",
            "MESSAGE"  => "",
            "RESPONSE" => array(),
        );
        $xml = $request->payload();

        if ($ch = curl_init()) {
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            if (!empty($authParams)) {
                curl_setopt($ch, CURLOPT_USERPWD, $authParams['LOGIN'].":".$authParams['PASSWORD']); // username and password - declared at the top of the doc
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
            }
            $headers = array(
                "Content-type: text/xml",
                "Accept: text/xml",
                "Content-length: ".strlen($xml),
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $data = curl_exec($ch);

            if (!$data || curl_errno($ch)) {
                $arOut['RESULT'] = 'ERROR';
                $arOut['MESSAGE'] = curl_error($ch);

                if (!$data) {
                    $arOut['MESSAGE'] = 'The SOAP Server has returned an empty string';
                }
            } else {
                curl_close($ch);
                $arOut['RESULT'] = 'OK';
                $arOut['RESPONSE'] = self::xmlstr_to_array($data);
                $arError = self::getError($arOut['RESPONSE']);
                if ($arError['ErrorCode'] > 0) {
                    $arOut['RESULT'] = 'ERROR';
                    $arOut['MESSAGE'] = $arError['Message'];
                }
            }
        }

        $this->arLog['REQUEST'][$method] = array(
            'RESULT'  => $arOut['RESULT'],
            'MESSAGE' => $arOut['MESSAGE'],
        );

        return $arOut;
    }

    /**
     * ищет и возвращает массив ошибки
     *
     * @param array $arData
     *
     * @return bool
     */
    static function getError($arData = array())
    {
        foreach ($arData as $key => $arOne) {
            if (is_array($arOne)) {
                if ($key == 'ErrorMessage') {
                    return $arOne;
                } else {
                    return self::getError($arOne);
                }
            }
        }

        return false;
    }

    static function xmlstr_to_array($xmlstr)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xmlstr);
        $root = $doc->documentElement;
        $output = self::domnode_to_array($root);
        $output['@root'] = $root->tagName;

        return $output;
    }

    static function domnode_to_array($node)
    {
        $output = array();
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = self::domnode_to_array($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        if (!isset($output[$t])) {
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    } elseif ($v || $v === '0') {
                        $output = (string)$v;
                    }
                }
                if ($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
                    $output = array('@content' => $output); //Change output into an array.
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = array();
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string)$attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v) == 1 && $t != '@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }

        return $output;
    }
}

?>