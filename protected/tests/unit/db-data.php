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
		array(
			'id', 'base_id', 'refs', 'current', 'player_id',
			'name', 'type', 'skin_id', 'templates'
		),
		array(
			array(1, 1, 2, 1, 1, 'sn', 'N', 1, 'S,S,S,S'),
			array(2, 2, 2, 1, 2, 'sn2', 'N', 1, 'S,S,S,S'),
			array(3, 3, 2, 1, 3, 'sn3', 'N', 1, 'S,S,S,S'),
			array(4, 4, 2, 1, 4, 'sn4', 'N', 1, 'S,S,S,S'),
			array(5, 5, 2, 1, 5, 'sn', 'B', 1, 'S,S,S,S'),
		)
	),

	'map' => array(
		array('snake_id', 'index', 'head_x', 'head_y', 'lines'),
		array(
			array(1, 0, 3, 3, $mapLine),
			array(2, 0, 3, 3, $mapLine),
			array(3, 0, 3, 3, $mapLine),
			array(4, 0, 3, 3, $mapLine),
			array(5, 0, 3, 3, $mapLine),
		)
	),

	'fight' => array(
		array(
			'id', 'refs', 'type', 'time', 'player_id', 'turn_limit', 'turn_count', 'turns', 'result'
		),
		array(
			array(1, 5, 'challenge', 1000000000, 1, 1, 1, chr(0x2a).chr(0xa4), 'limit'),
			array(2, 1, 'train', NULL, 2, 2, 0, NULL, NULL),
		)
	),

	'snakestat' => array(
		array(
			'fight_id', 'index', 'snake_id', 'result', 'length',
			'pre_rating', 'post_rating', 'debug'
		),
		array(
			array(1, 0, 1, 'free', 10, 0, 0, chr(40)),
			array(1, 1, 2, 'free', 10, 0, 0, chr(42)),
			array(1, 2, 3, 'free', 10, 0, 0, chr(44)),
			array(1, 3, 4, 'free', 10, 0, 0, chr(46)),

			array(2, 0, 1, NULL, NULL, NULL, NULL, NULL),
			array(2, 1, 3, NULL, NULL, NULL, NULL, NULL),
			array(2, 2, 4, NULL, NULL, NULL, NULL, NULL),
			array(2, 3, 5, NULL, NULL, NULL, NULL, NULL),
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