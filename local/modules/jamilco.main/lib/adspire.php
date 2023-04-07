<?php

namespace Adspire;

use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;
use Bitrix\Main\Data\Cache;
use Bitrix\Iblock\SectionTable;

/**
 * Class Manager
 */
class Manager
{
    const CATALOG_IBLOCK_ID = 1;
    const SKU_IBLOCK_ID = 2;
    const DEFAULT_HOST = 'juicycouture.ru';

    const MAIN_SCRIPT = '<script src="//track.adspire.io/code/{name}/" defer></script>';

    /** @var Manager $instance */
    protected static $instance = null;

    /** @var int $ip */
    protected $ip = null;

    /** @var string $host */
    protected $host = null;

    /** @var array $containerElements */
    protected $containerElements = [
        'main' => 'window.adspire_track = window.adspire_track || [];',
        'ip'   => 'window.adspire_ip = #IP#;',
        'push' => 'window.adspire_track.push(#PUSH#);'
    ];

    /** @var array $elements */
    protected $elements = [];

    /**
    *  Возвращает экземпляр класса (singleton pattern)
    *
    * @return Manager
    */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Конструктор
     */
    private function __construct()
    {
        // IP адрес для элемента контейнера
        $this->ip = ip2long($_SERVER['REMOTE_ADDR']);
        $this->containerElements['ip'] = str_replace('#IP#', $this->ip, $this->containerElements['ip']);

        $context = Context::getCurrent();
        $scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
        $this->host = $scheme.'://'.$context->getServer()->getServerName();
    }

    private function __clone() {}

    /**
     * Возвращает подготовленную строку скрипта для вставки в страницу
     *
     * @return string
     */
    public function showMainScript()
    {
        return self::prepareMainScript();
    }

    /**
     * Возвращает подготовленный скрипт контейнера для вставки в страницу
     *
     * @var array $params
     *
     * @return string
     */
    public function getContainer($params = [])
    {
        if (isset($params['elements']) && is_array($params['elements'])) {
            $elements = $params['elements'];
        } else {
            $elements = array_keys($this->containerElements);
        }

        $containerElements = [];
        foreach ($elements as $element) {
            if ($element == 'push') {
                $push = Json::encode(
                    $this->elements['push'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                );
                $containerElements[] = str_replace('#PUSH#', $push, $this->containerElements[$element]);
            } else {
                $containerElements[] = $this->containerElements[$element];
            }
        }

        return '<script>'.implode('', $containerElements).'</script>';
    }

    /**
     * Записывает массив с данными для элемента контейнера
     *
     * @var array $value
     * @var bool isChange
     */
    public function setContainerElement($value, $isChange = false)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($isChange) {
                    foreach ($v as $subKey => $subValue) {
                        $this->elements[$k][$subKey] = $subValue;
                    }
                } else {
                    $this->elements[$k] = $v;
                }
            }
        }
    }

    /**
     *  Возвращает массив заполненных объектов product
     *
     * @param array $itemIds массив id
     *
     * @return array $products
     */
    public function fillProductObject($itemIds)
    {
        Loader::IncludeModule('iblock');
        Loader::IncludeModule('sale');
        Loader::IncludeModule('catalog');

        /** Данные о продуктах */
        $products = [];
        $itemIds = array_unique($itemIds);

        foreach ($itemIds as $itemId) {

            $cache = Cache::createInstance();
            if ($cache->initCache(3600, $itemId, '/s1/adspire/')) {
                $product = $cache->getVars();
            } else {
                $cache->startDataCache();

                $product = [];
                $rsSku = \CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => static::SKU_IBLOCK_ID,
                        'ID'        => $itemId
                    ],
                    false,
                    ['nTopCount' => 1],
                    [
                        'ID',
                        'NAME',
                        'PROPERTY_CML2_LINK'
                    ]
                );
                if ($skuParams = $rsSku->GetNext()) {
                    $product = [
                        'variant_id' => (int)$skuParams['ID'],
                        'pname'      => $skuParams['NAME'],
                        'currency'   => 'RUB'
                    ];

                    /** Дополнение данными от родителя */
                    $rsProduct = \CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID' => static::CATALOG_IBLOCK_ID,
                            'ID'        => $skuParams['PROPERTY_CML2_LINK_VALUE']
                        ],
                        false,
                        ['nTopCount' => 1],
                        [
                            'ID',
                            'NAME',
                            'IBLOCK_SECTION_ID'
                        ]
                    );
                    if ($productParams = $rsProduct->GetNext()) {
                        $product['pid']   = (int)$productParams['ID'];
                        $product['pname'] = $productParams['NAME'];
                        $product['cid']   = (int)$productParams['IBLOCK_SECTION_ID'];

                        /** Данные о разделах */
                        $parentSections = [];
                        $rsSections = SectionTable::getList(
                            [
                                'select'  => [
                                    'NAME'              => 'SECTION_SECTION.NAME',
                                    'SECTION_ID'        => 'SECTION_SECTION.ID',
                                    'IBLOCK_SECTION_ID' => 'SECTION_SECTION.IBLOCK_SECTION_ID',
                                ],
                                'filter'  => [
                                    '=ID' => $productParams['IBLOCK_SECTION_ID']
                                ],
                                'runtime' => [
                                    'SECTION_SECTION' => [
                                        'data_type' => '\Bitrix\Iblock\SectionTable',
                                        'reference' => [
                                            '=this.IBLOCK_ID'     => 'ref.IBLOCK_ID',
                                            '>=this.LEFT_MARGIN'  => 'ref.LEFT_MARGIN',
                                            '<=this.RIGHT_MARGIN' => 'ref.RIGHT_MARGIN',
                                        ],
                                        'join_type' => 'inner'
                                    ],
                                ],
                            ]
                        );
                        while ($parentSection = $rsSections->fetch()) {
                            $parentSections[$parentSection['SECTION_ID']] = $parentSection;
                        }

                        $sectionPath = [];
                        $sectionId = $productParams['IBLOCK_SECTION_ID'];
                        while ($sectionId > 0) {
                            $sectionPath[] = $parentSections[$sectionId]['NAME'];
                            $sectionId = $parentSections[$sectionId]['IBLOCK_SECTION_ID'];
                        }
                        $product['cname'] = array_reverse($sectionPath);
                    }
                }
                $cache->endDataCache($product);
            }

            $products[$itemId] = $product;
        }

        return $products;
    }

    /**
     * Заменяет в строке скрипта имя хоста
     */
    protected function prepareMainScript()
    {
        return str_replace('{name}', self::DEFAULT_HOST, self::MAIN_SCRIPT);
    }

    /**
     * Добавляет к URL имя хоста
     *
     * @var string $url
     *
     * @return string
     */
    public function formatURL($url)
    {
        if (strpos($url, 'http') === false) {
            return $this->host.$url;
        } else {
            return $url;
        }
    }
}
