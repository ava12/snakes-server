<?php

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
			),
		),
	),
	'defaultController' => 'index',
	'params' => array(
		'MaxTimestampDiff' => 3 * 60,
	),
);