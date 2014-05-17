<?php

$t = require __DIR__ . '/secret.php';
$t = array('components' => array('db' => $t['test_db']));
return CMap::mergeArray(require __DIR__ . '/main.php', $t);
