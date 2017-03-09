<?php

/**
 * 全局常量定义
 */

defined('DIR') || define('DIR', 'WIN' === strtoupper(substr(PHP_OS, 0, 3)) ? "\\" : DIRECTORY_SEPARATOR);

defined('YMD') || define('YMD', 'Y-m-d');
defined('YMT') || define('YMT', 'Y-m-t');
defined('YMDHIS') || define('YMDHIS', 'Y-m-d H:i:s');
defined('REQUEST_TIME') || define('REQUEST_TIME', $_SERVER['REQUEST_TIME']);
defined('CUR_TIME') || define('CUR_TIME', time());
defined('ONE_DAY') || define('ONE_DAY', 86400);
defined('REQUEST_UUID') || define('REQUEST_UUID', uniqid(mt_rand()));
