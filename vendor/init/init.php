<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/16
 * Time: 14:29
 */
//关闭自动转义，用程序处理
PHP_VERSION < '5.3.0' && set_magic_quotes_runtime(0);

//程序开始运行时间
define('START_TIME', microtime(true));

//当前时间戳
define('TIME_STAMP', time());

//是否开启了自动转义
define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

//网站所在的目录路径
define('WEB_ROOT', substr(__DIR__, 0, -11));//网站根目录