<?php

date_default_timezone_set(@date_default_timezone_get());
$_REQUEST += $_GET + $_POST + $_COOKIE; // надежнее, чем request_order

if (!defined('YII_DEBUG')) define('YII_DEBUG', true); // необязательно
require_once '../yii/framework/yii.php';
Yii::createWebApplication('protected/config/config.php')->run();
