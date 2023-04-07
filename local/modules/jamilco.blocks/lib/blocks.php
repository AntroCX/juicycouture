<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 27.04.16
 * Time: 19:34
 */
namespace Jamilco\Tickets;

/**
 * Class Blocks - быстро накидал класс для работы с блоками
 * @package Jamilco\Tickets
 */
class Blocks {
    private static $blockDir = 'local/blocks/';
    private function load_from_array($ar_name) {
        foreach ($ar_name as $name) {
            self::load_css($name);
            self::load_js($name);
        }
    }

    private function load_from_name($name) {
        self::load_css($name);
        self::load_js($name);
    }


    private function load_css($name) {
        $path = self::$blockDir.$name;
        foreach (glob($path.'/*.css', GLOB_BRACE) as $key => $file) {
            $GLOBALS['APPLICATION']->SetAdditionalCSS('/'.$file);
        }
    }
    
    private function load_js($name) {
        $path = self::$blockDir.$name;
        foreach (glob($path.'/*.min.js', GLOB_BRACE) as $key => $file) {
            $GLOBALS['APPLICATION']->AddHeadScript('/'.$file);
        }
    }

    public static function load($name) {
        if(is_array($name)) {
            self::load_from_array($name);
        } else {
            self::load_from_name($name);
        }
    }
}

