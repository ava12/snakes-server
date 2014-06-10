<?php

return array(
// рабочие настройки БД
	'db' => array(
		'connectionString' => 'mysql:host=localhost;dbname=snakes',
		'username' => 'login',
		'password' => 'password',
	),

// тестовые настройки БД
	'test_db' => array(
		'connectionString' => 'mysql:host=localhost;dbname=snakes_test',
		'username' => 'login',
		'password' => 'password',
	),
);