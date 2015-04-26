<?php
if (!defined('JSON_UNESCAPED_UNICODE')) define('JSON_UNESCAPED_UNICODE', 0);

if (!defined('APP_PATH')) {
	define('APP_PATH', realpath(__DIR__ . '/..'));
}

Yii::$classMap += array(
	'KCAPTCHA' => APP_PATH . '/vendor/kcaptcha/kcaptcha.php',
);

return array(
	'basePath' => realpath(__DIR__ . '/..'),
	'import' => array(
		'application.service.*',
		'application.models.*',
		'application.controllers.*',
	),
	'components' => array(
		'user' => array(
			'class' => 'User',
			'clientLifetime' => 600,
			'serverLifetime' => 365 * 86400,
		),
		'db' => array(
			'tablePrefix' => '',
			'charset' => 'UTF8',
		),
		'session' => array(
			'autoStart' => false,
			'sessionName' => 'snakestmp',
			'timeout' => 2 * 60 * 60,
		),
		'urlManager' => array(
			'urlFormat' => 'path',
			'rules' => array(
				'logindata' => 'index/logindata',
				'login' => 'index/login',
				'logout' => 'index/logout',
				'captcha' => 'index/captcha',
				'register' => 'index/register',
				'game' => 'index/game',
				'ajax' => 'index/ajax',
				'admin/player/<id:\d+>' => 'admin/player'
			),
		),
	),
	'defaultController' => 'index',
	'params' => array(
		'MaxTimestampDiff' => 3 * 60,
		'SweepRatio' => 100, // p = 1/x
	),
);