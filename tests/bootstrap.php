<?php

if ( ! defined('SYSPATH')) {
    error_reporting(E_ALL | E_STRICT);

    define('APPPATH', '/tmp/');
    define('SYSPATH', '/tmp/');

    require_once 'PHPUnit/Framework/TestCase.php';
    require_once __DIR__ . '/../classes/kohana/registry.php';
    require_once __DIR__ . '/../classes/kohana/service.php';


    interface Kohana_Cache_Tagging { }
    class Kohana_Cache { }
    class Kohana_Cache_Memcache extends Kohana_Cache { }
    class Kohana_Database { }
    class Kohana_DB { }
    class Kohana_Database_MySQL extends Kohana_Database { }
    class Kohana_Date {
        const MINUTE = 1;
    }
    class View {
        static public function factory() { }
    }


    require_once __DIR__ . '/../classes/cache/memcache.php';
    require_once __DIR__ . '/../classes/database.php';
    require_once __DIR__ . '/../classes/helper.php';
    require_once __DIR__ . '/../classes/date.php';
    require_once __DIR__ . '/../classes/cache.php';
    require_once __DIR__ . '/../classes/db.php';
    require_once __DIR__ . '/../classes/database/mysql.php';

    class Kohana_Exception extends Exception { }
}