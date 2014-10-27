<?php

date_default_timezone_set(@date_default_timezone_get());

if (!defined('YII_DEBUG')) define('YII_DEBUG', true);
require_once '../yii/framework/yii.php';
Yii::createWebApplication('protected/config/config.php')->run();
