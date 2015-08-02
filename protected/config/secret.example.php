<?php

return array(
// рабочие настройки БД
	'db' => array(
		'connectionString' => 'mysql:host=localhost;dbname=snakes',
		'tablePrefix' => 'snakes_',
		'username' => 'login',
		'password' => 'password',
	),

// тестовые настройки БД
	'test_db' => array(
		'connectionString' => 'mysql:host=localhost;dbname=snakes_test',
		'tablePrefix' => 'snakes_',
		'username' => 'login',
		'password' => 'password',
	),
);