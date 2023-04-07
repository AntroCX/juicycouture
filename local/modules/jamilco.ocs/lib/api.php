<?php
namespace Jamilco\OCS;

class Api {
    private $routes = array(); // массив с роутами

    /**
     * Api constructor.
     * @param array $arRoutes
     * @param $version
     */
    function __construct($version = 'v2')
    {
        require_once __DIR__.'/routes/'.$version.'.php';
        if(count($arRoutes) > 0) {
            $this->routes = $arRoutes;
        } else {
            self::set_error('Ошибка при подключении списка маршрутизаций');
        }
    }

    /**
     * метод инициализации API для OCS
     */
    public function init() {
        header("Content-type: text/xml");
        \CModule::IncludeModule('iblock');
        \CModule::IncludeModule('sale');
        \CModule::IncludeModule('catalog');
        $router = new \Jamilco\OCS\Router();
        $router->init($this->routes);
    }
}