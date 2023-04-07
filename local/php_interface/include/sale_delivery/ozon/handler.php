<?
namespace Sale\Handlers\Delivery;

use \Bitrix\Main\Loader;
use \Bitrix\Iblock\IblockTable;
use \Bitrix\Sale\Shipment;
use \Bitrix\Sale\Delivery\CalculationResult;
use \Bitrix\Sale\Delivery\Services\Base;
use \Bitrix\Sale\Location\LocationTable;
use \Jamilco\Delivery\Ozon;

class OzonHandler extends Base
{
    protected static $isCalculatePriceImmediately = true;
    protected static $whetherAdminExtraServicesShow = true;

    public function __construct(array $initParams)
    {
        parent::__construct($initParams);
    }

    public static function getClassTitle()
    {
        return 'Ozon Delivery';
    }

    public static function getClassDescription()
    {
        return 'Доставка до пунктов выдачи заказов Ozon';
    }

    protected function calculateConcrete(Shipment $shipment = null)
    {
        $result = new CalculationResult();

        // default price
        $result->setDeliveryPrice(roundEx($this->config["MAIN"]["DEFAULT_PRICE"], SALE_VALUE_PRECISION));

        $locationId = $_COOKIE['city_id'];
        if (!$locationId || defined('ADMIN_SECTION')) {
            $order = $shipment->getCollection()->getOrder();
            if (!$props = $order->getPropertyCollection()) return $result;
            if (!$locationProp = $props->getDeliveryLocation()) return $result;
            if (!$locationCode = $locationProp->getValue()) return $result;
            $locationId = self::getLocationID($locationCode);
        }

        $arSection = $this->getPricesSection($locationId);
        if ($arSection && $arSection['UF_PRICE']) {
            $result->setDeliveryPrice(roundEx($arSection['UF_PRICE'], SALE_VALUE_PRECISION));
        }

        // получим срок доставки
        if ($deliveryTime = Ozon::getDeliveryTimeByLocation($locationId)) {
            $result->setPeriodDescription($deliveryTime);
        }

        return $result;
    }

    public function isCompatible(Shipment $shipment = null)
    {
        $locationId = $_COOKIE['city_id'];
        if (!$locationId) {
            $order = $shipment->getCollection()->getOrder();
            if (!$props = $order->getPropertyCollection()) return false;
            if (!$locationProp = $props->getDeliveryLocation()) return false;
            if (!$locationCode = $locationProp->getValue()) return false;
            $locationId = self::getLocationID($locationCode);
        }

        return $this->checkLocationInSections($locationId);
    }

    protected function checkLocationInSections($locationId = 0)
    {
        $entity = \Bitrix\Iblock\Model\Section::compileEntityByIblock($this->config['MAIN']['IBLOCK_PVZ']);
        $se = $entity::getList(
            array(
                'filter' => array(
                    'IBLOCK_ID'   => $this->config['MAIN']['IBLOCK_PVZ'],
                    'UF_LOCATION' => $locationId
                ),
                'select' => array('ID')
            )
        );
        if ($arSect = $se->Fetch()) {
            return true;
        }

        return false;
    }

    protected function getPricesSection($locationId = 0)
    {
        $arLocs = self::getLocationPath($locationId);
        $entity = \Bitrix\Iblock\Model\Section::compileEntityByIblock($this->config['MAIN']['IBLOCK_PVZ']);

        $se = $entity::getList(
            array(
                'order'  => array('DEPTH_LEVEL' => 'DESC'),
                'filter' => array(
                    'IBLOCK_ID'   => $this->config['MAIN']['IBLOCK_PVZ'],
                    '!UF_PRICE'   => false,
                    'UF_LOCATION' => $arLocs
                ),
                'select' => array('ID', 'NAME', 'UF_PRICE')
            )
        );
        if ($arSect = $se->Fetch()) {
            return $arSect;
        }

        return false;
    }


    protected static function getLocationPath($locationId = 0)
    {
        $arResult = array();
        $loc = LocationTable::getPathToNode(
            $locationId,
            array(
                'filter' => array('>DEPTH_LEVEL' => 1),
                'select' => array('ID')
            )
        );
        while ($arLoc = $loc->Fetch()) {
            $arResult[] = $arLoc['ID'];
        }

        $arResult = array_reverse($arResult);

        return $arResult;
    }

    /**
     * @param string $locationCode
     *
     * @return int
     */
    protected static function getLocationID($locationCode = '')
    {
        if (strlen($locationCode) <= 0) return false;

        static $result = array();

        if (!isset($result[$locationCode])) {

            $dbRes = LocationTable::getList(
                array(
                    'filter' => array(
                        'CODE'              => $locationCode,
                        '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    ),
                    'select' => array('ID')
                )
            );

            if ($rec = $dbRes->fetch()) {
                $result[$locationCode] = $rec['ID'];
            }
        }

        return $result[$locationCode];
    }

    protected function getConfigStructure()
    {
        Loader::includeModule('jamilco.delivery');

        return array(
            "MAIN" => array(
                "TITLE"       => 'Настройки',
                "DESCRIPTION" => 'Настройки',
                "ITEMS"       => array(
                    "SERVER_URL"         => array(
                        "TYPE"     => "STRING",
                        "NAME"     => "Сервер: URL",
                        "DEFAULT"  => "",
                        "REQUIRED" => true,
                    ),
                    "SERVER_LOGIN"       => array(
                        "TYPE"     => "STRING",
                        "NAME"     => "Сервер: логин",
                        "DEFAULT"  => "",
                        "REQUIRED" => true,
                    ),
                    "SERVER_PASSWORD"    => array(
                        "TYPE"     => "STRING",
                        "NAME"     => "Сервер: пароль",
                        "DEFAULT"  => "",
                        "REQUIRED" => true,
                    ),
                    "SERVER_CONTRACT_ID" => array(
                        "TYPE"     => "STRING",
                        "NAME"     => "Сервер: ID контракта",
                        "DEFAULT"  => "",
                        "REQUIRED" => true,
                    ),
                    "IBLOCK_PVZ"         => array(
                        "TYPE"     => "ENUM",
                        "NAME"     => "Инфоблок пунктов выдачи",
                        "DEFAULT"  => Ozon::getPvzIblockID(),
                        "REQUIRED" => true,
                        "OPTIONS"  => $this->getIblocksList(),
                    ),
                    "DEFAULT_PRICE"      => array(
                        "TYPE"     => "NUMBER",
                        "NAME"     => "Стоимость доставки по умолчанию",
                        "DEFAULT"  => "300",
                        "REQUIRED" => true,
                    ),
                )
            )
        );
    }

    /**
     * список всех инфоблоков
     * @return array
     */
    protected function getIblocksList()
    {
        Loader::includeModule('iblock');
        $arResult = array();
        $res = IblockTable::getList(
            array(
                'order'  => array('IBLOCK_TYPE_ID' => 'ASC', 'SORT' => 'ASC', 'ID' => 'ASC'),
                'filter' => array()
            )
        );
        while ($arIblock = $res->Fetch()) {
            $arResult[$arIblock['ID']] = $arIblock['ID'].'. '.$arIblock['NAME'];
        }

        return $arResult;
    }

    public function isCalculatePriceImmediately()
    {
        return self::$isCalculatePriceImmediately;
    }

    public static function whetherAdminExtraServicesShow()
    {
        return self::$whetherAdminExtraServicesShow;
    }
}

?>