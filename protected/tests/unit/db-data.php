<?php

$mapLine = str_repeat('-', 62) . 'S0' . str_repeat('-', 34);

return array(
	'player' => array(
		array('id', 'login', 'name', 'fighter_id', 'rating', 'delayed_id'),
		array(
			array(1, 'ch1', 'ch1', 1, 10, NULL),
			array(2, 'ch2', 'ch2', 2, 20, 2),
			array(3, 'ch3', 'ch3', 3, 30, NULL),
			array(4, 'ch4', 'ch4', 4, 40, NULL),
			array(5, 'p', 'p', NULL, NULL, NULL),
		)
	),

	'snake' => array(
		array('id', 'player_id', 'name', 'type', 'skin_id', array('templates', 'maps')),
		array(
			array(1, 1, 'sn', 'N', 1, array(
				array('S', 'S', 'S', 'S'),
				array(array('head_x' => 3, 'head_y' => 3, 'lines' => $mapLine))
			)),
			array(2, 2, 'sn2', 'N', 1, array(
				array('S', 'S', 'S', 'S'),
				array(array('head_x' => 3, 'head_y' => 3, 'lines' => $mapLine))
			)),
			array(3, 3, 'sn3', 'N', 1, array(
				array('S', 'S', 'S', 'S'),
				array(array('head_x' => 3, 'head_y' => 3, 'lines' => $mapLine))
			)),
			array(4, 4, 'sn4', 'N', 1, array(
				array('S', 'S', 'S', 'S'),
				array(array('head_x' => 3, 'head_y' => 3, 'lines' => $mapLine))
			)),
			array(5, 5, 'sn', 'B', 1, array(
				array('S', 'S', 'S', 'S'),
				array(array('head_x' => 3, 'head_y' => 3, 'lines' => $mapLine))
			)),
		)
	),

	'fight' => array(
		array(
			'id', 'refs', 'type', 'time', 'player_id',
			'turn_limit', 'result', array('turns', 'snakes', 'stats'),
		),
		array(
			array(1, 5, 'challenge', 1000000000, 1, 1, 'limit', array(
				array(0xA42A),
				array(
					array('id' => 1, 'player_id' => 1, 'maps' => array(array())),
					array('id' => 2, 'player_id' => 2, 'maps' => array(array())),
					array('id' => 3, 'player_id' => 3, 'maps' => array(array())),
					array('id' => 4, 'player_id' => 4, 'maps' => array(array())),
				),
				array(
					array('result' => 'free', 'length' => 10, 'debug' => chr(40)),
					array('result' => 'free', 'length' => 10, 'debug' => chr(42)),
					array('result' => 'free', 'length' => 10, 'debug' => chr(44)),
					array('result' => 'free', 'length' => 10, 'debug' => chr(46)),
				),
			)),
			array(2, 1, 'train', NULL, 2, 2, NULL, array(NULL, array(), array())),
		)
	),

	'delayedfight' => array(
		array('fight_id', 'delay_till'),
		array(
			array(2, 2000000000),
		)
	),

	'fightlist' => array(
		array('player_id', 'time', 'type', 'fight_id'),
		array(
			array(1, 1000000000, 'ordered', 1),
			array(2, 1000000000, 'challenged', 1),
			array(3, 1000000000, 'challenged', 1),
			array(4, 1000000000, 'challenged', 1),
		)
	),

	'fightslot' => array(
		array('player_id', 'index', 'fight_id', 'name'),
		array(
			array(1, 1, 1, 'test'),
		)
	),
);