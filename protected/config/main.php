<?php

return array(
	'basePath' => realpath(__DIR__ . '/..'),
	'import' => array(
		'application.service.*',
		'application.models.*',
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
	),
	'params' => array(
		'MaxTimestampDiff' => 3 * 60,
	),
);