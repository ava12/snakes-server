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
	),
	'params' => array(
		'MaxTimestampDiff' => 3 * 60,
	),
);