<?php
namespace Jamilco\OCS;

class Router extends Xml{

    /**
     * Метод для проверки валидности url запроса
     * @param $currentURL
     * @return bool
     */
    private function checkUrl($currentURL) {
        if(strpos($currentURL, '.xml') === false) {
            $this->set_error('Неправильный запрос к API');
            return false;
        }
        return true;
    }

    /**
     * Метод для возврата массива роутинга
     * @return array
     */
    private function parseURL() {
        $currentURL = $GLOBALS['APPLICATION']->GetCurPage();
        if($this->checkUrl($currentURL)) {
            $arURL = explode('/', $currentURL);
            return array_slice($arURL, 3);
        }
        return array();
    }

    private function handlerV2($arRoutes) {
        $command = trim($_REQUEST['command']);
        $checkKeys = array_keys($arRoutes);
        if(in_array($command, $checkKeys)) {
            $className = key($arRoutes[$command]);
            $method = $arRoutes[$command][$className];
            $this->autoloadClass($className);
            call_user_func(array(__NAMESPACE__.'\\'.$className, $method));
        } else {
            self::set_error('Данная команда отсутсвует в списке разрешенных');
        }
    }

    /**
     * обработчик запросов - новая схема
     * @param $arURL
     * @param $arRoutes
     */
    private function handlerV1($arURL, $arRoutes) {
        foreach ($arURL as $key => $url) {
            $arURL[$key] = str_replace('.xml','',$url);
        }
        foreach ($arRoutes as $className => $arRoute) {
            $classPos = array_search($className, $arURL);
            if($classPos !== false) {
                $this->autoloadClass($className);
                unset($arURL[$classPos]);
                $urlCount = count($arURL);
                switch ($urlCount) {
                    case 0 :
                        if(in_array('index', $arRoute)) {
                            call_user_func(array(__NAMESPACE__.'\\'.$className, 'index'));
                        } else {
                            $this->set_error('Данный route не прописан в правилах');
                        }
                        break;
                    case 1 :
                        if(in_array($arURL[1], $arRoute)) {
                            call_user_func(array(__NAMESPACE__.'\\'.$className, $arURL[1]));
                        } else {
                            $this->set_error('Данный route не прописан в правилах');
                        }
                        break;
                    case 2 :
                        if(in_array($arURL[1], $arRoute)) {
                            call_user_func(array(__NAMESPACE__ . '\\' . $className, $arURL[1]), $arURL[2]);
                        } else {
                            $this->set_error('Данный route не прописан в правилах');
                        }
                        break;
                    default:
                        $this->set_error('Неправильный запрос к API');
                }
            }
        }
    }

    /**
     * обработчик запросов к API, который вызывает нужный метод для конкретного роута
     * @param $arRoutes
     * @param $version
     */
    public function handler($arRoutes, $version) {
        switch ($version) {
            case 'v1' :
                if($arURL = $this->parseURL()) {
                    self::handlerV1($arURL, $arRoutes);
                }
                break;
            case 'v2':
                self::handlerV2($arRoutes);
                break;
            default:
                self::set_error('Ошибка при выборе версии API');
        }
    }

    /**
     * Автоподгрузка классов
     * @param $className
     */
    private function autoloadClass($className) {
        if((@include __DIR__ . '/controllers/' . $className . '.php') === false)
        {
            $this->set_error('Класс '.$className.' не существует');
        }
    }

    /**
     * Инициализация класса Router
     * @param $arRoutes
     * @param string $version
     */
    public function init($arRoutes, $version = 'v2') {
        if(is_array($arRoutes)) {
            $this->handler($arRoutes, $version);
        } else {
            $this->set_error('Jamilco\OCS\Router метод route($arRoutes), ошибка в параметре $arRoutes');
        }
    }
}