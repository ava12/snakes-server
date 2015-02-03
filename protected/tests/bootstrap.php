<?php
mb_internal_encoding('UTF-8');
require_once(__DIR__ . '/../../../yii/framework/yiit.php');
Yii::createWebApplication(__DIR__ . '/../config/test.php');
Yii::$enableIncludePath = false;

error_reporting(-1);

date_default_timezone_set(@date_default_timezone_get());
Yii::app()->db->schema->refresh();