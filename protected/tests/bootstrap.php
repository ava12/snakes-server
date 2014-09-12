<?php
mb_internal_encoding('UTF-8');
require_once(__DIR__ . '/../../../yii/framework/yiit.php');
Yii::createWebApplication(__DIR__ . '/../config/test.php');
if (function_exists('iconv')) {
	$encodings = iconv_get_encoding('all');
	if ($encodings['output_encoding'] <> $encodings['internal_encoding']) {
		ob_start('ob_iconv_handler');
	}
}
Yii::$enableIncludePath = false;
error_reporting(-1);
