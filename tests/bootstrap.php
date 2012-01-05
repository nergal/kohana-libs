<?php

if ( ! defined('SYSPATH')) {
    error_reporting(E_ALL | E_STRICT);

    require_once 'PHPUnit/Framework/TestCase.php';
    require_once __DIR__ . '/../classes/kohana/registry.php';

    class Kohana_Exception extends Exception { }

    class Kohana {
        static public function auto_load($classname) {
            if ( ! class_exists($classname)) {
                throw new Kohana_Exception($classname);
            }
        }
    }
}