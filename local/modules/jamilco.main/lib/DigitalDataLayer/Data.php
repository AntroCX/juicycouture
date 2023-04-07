<?php

namespace DigitalDataLayer;

use Bitrix\Main\Web\Json;

/**
 * Class Data
 */
class Data
{
    const DDL_VERSION = '1.1.2';

    /** @var Data $instance */
    protected static $instance = null;

    /** @var array $data */
    protected $data = [];

    /**
     *  Возвращает экземпляр класса (singleton pattern)
     *
     * @return Data
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
    private function __construct() {

        /** Заполнение data дефолтными объектами */
        $this->data = [
            'version' => static::DDL_VERSION,

            /** Глобальная информация о сайте */
            'website' => [
                'type' => 'responsive'
            ],

            /** Текущая просматриваемая страница */
            'page' => [
                'type' => 'content',
                'category' => 'Content'
            ],

            /** Посетитель или авторизованный пользователь */
            'user' => [],

            /** Список товаров, отображаемых на странице */
            'listing' => [],

            /** Товар, отображаемый на странице */
            'product' => [],

            /** Состояние покупательской корзины */
            'cart' => [],

            /** Транзакция, которая была только что завершена */
            'transaction' => [],

            /** Список компаний */
            'campaigns' => [],

            /** Список компаний */
            'recommendation' => [],

            /** Изменения которые произошли в DDL */
            'changes' => [],

            /** События, опубликованные на данной странице */
            'events' => []
        ];
    }

    private function __clone() {}

    /**
     *  Устанавливает значение в объекте по ключу
     *
     * @param string $name имя объекта в data
     * @param array $value значение
     */
    function __set($name, array $value)
    {
        if (isset($this->data[$name])) {
            if (is_array($value)) {
                foreach($value as $k => $v) {
                    $this->data[$name][$k] = $v;
                }
            } else {
                $this->data[$name] = $value;
            }
        }
    }

    /**
     *  Возвращает значение в объекте по ключу
     *
     * @param string $name имя объекта в data
     *
     * @return mixed
     */
    function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
    }

    /**
     *  Добавляет значение в объект
     *
     * @param string $objectName имя объекта в data
     * @param mixed $value значение
     */
    public function add($objectName, $value)
    {
        if (isset($this->data[$objectName])) {
            $this->data[$objectName][] = $value;
        }
    }

    /**
     *  Возвращает объект data в виде массива
     *
     * @return array
     */
    public function asArray()
    {
        return $this->data;
    }

    /**
     *  Возвращает объект data в виде JS объекта
     *
     * @param bool $isWrap флаг оборачивать ли js объект в тег script
     *
     * @return string
     */
    public function asJsObject($isWrap = false)
    {
        $this->filterData();

        if ($isWrap) {
            return '<script>window.digitalData = ' .
                    Json::encode(
                        $this->asArray(),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                    ) .
                    '</script>';
        } else {
            return Json::encode(
                $this->asArray(),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            );
        }
    }

    /**
     *  Производит фильтрацию данных
     *
     *  - исключение пустых объектов
     *  - user должен быть всегда js объектом, когда он пустой
     *  - cart должен быть удален на странице confirmation
     */
    protected function filterData()
    {
        foreach ($this->data as $name => $objectData) {
            if (empty($objectData) && !in_array($name, ['events', 'changes', 'recommendation'])) {
                if ($name == 'user') {
                    $this->data[$name] = (object) null;
                } else {
                    unset($this->data[$name]);
                    if ($this->data['page']['type'] == 'confirmation') {
                        unset($this->data['cart']);
                    }
                }
            }
        }
    }
}
