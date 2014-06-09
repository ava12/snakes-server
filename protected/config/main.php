<?php

return array(
	'basePath' => realpath(__DIR__ . '/..'),
	'import' => array(
		'application.service.*',
		'application.models.*',
	),
	'components' => array(),
	'params' => array(
		'SessionLifetime' => 365 * 86400,
		'MaxTimestampDiff' => 3 * 60,
	),
);