<?php

 // if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
 //     $redirect = 'https://sdl.telkom.co.id' . $_SERVER['REQUEST_URI'];
 //     header('HTTP/1.1 301 Moved Permanently');
 //     header('Location: ' . $redirect);
 //     exit();
 // }

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');
// error_reporting(0);

// change the following paths if necessary
$yii=dirname(__FILE__).'/yii-1.1.17/framework/yii.php';
$config=dirname(__FILE__).'/protected/config/main.php';

// remove the following lines when in production mode
// defined('YII_DEBUG') or define('YII_DEBUG',false);
// specify how many levels of call stack should be shown in each log message
// defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);
Yii::createWebApplication($config)->run();
