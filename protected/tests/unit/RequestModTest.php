<?php

require_once __DIR__ . '/RequestTestBase.php';

/**
 * @group db
 * @group db-mod
 */
final class RequestModTest extends RequestTestBase {

//- корректные, изменяющие состояние ----------------------------------------

	public function testSnakeNew() {
		$request = array(
			'Request' => 'snake new',
			'Sid' => '1',
			'SnakeName' => '1питон',
			'SnakeType' => 'B',
			'SkinId' => 1,
			'ProgramDescription' => 'проверка',
			'Templates' => array('W', 'X', 'Y', 'Z'),
			'Maps' => array(array(
				'Description' => '', 'HeadX' => 3, 'HeadY' => 3, 'Lines' => array(),
			)),
		);
		$game = new Game($request, true);
		$response = $game->setPlayer(2)->run();
		$this->assertEquals('snake new', $response['Response']);
		return $response['SnakeId'];
	}

	/**
	 * @depends testSnakeNew
	 */
	public function testSnakeList($snakeId) {
		$request = array('Request' => 'player info', 'PlayerId' => '2');
		$response = array(
			'Response' => 'player info',
			'PlayerId' => '2',
			'PlayerName' => 'ch2',
			'Rating' => 20,
			'FighterId' => '2',
			'PlayerSnakes' => array(
				array('SnakeId' => $snakeId, 'SnakeName' => '1питон', 'SnakeType' => 'B', 'SkinId' => 1),
				array('SnakeId' => '2', 'SnakeName' => 'sn2', 'SnakeType' => 'N', 'SkinId' => 1),
			),
		);
		self::checkValidRequest($request, $response, 2);

		$snake = Snake::model()->findByPk($snakeId);
		$this->assertEquals($snakeId, $snake->id);
		return $snakeId;
	}

	/**
	 * @depends testSnakeList
	 */
	public function testSnakeEdit($snakeId) {
		$request = array(
			'Request' => 'snake edit',
			'SnakeId' => $snakeId,
			'SnakeName' => 'snn',
			'SnakeType' => 'N',
		);

		self::checkValidRequest($request, NULL, 2);
		return $snakeId;
	}

	/**
	 * @depends testSnakeEdit
	 */
	public function testSnakeAssign($snakeId) {
		$request = array('Request' => 'snake assign', 'SnakeId' => $snakeId);
		self::checkValidRequest($request, NULL, 2);
		return $snakeId;
	}

	/**
	 * @depends testSnakeAssign
	 */
	public function testSnakeInfo($snakeId) {
		$request = array('Request' => 'snake info', 'SnakeId' => $snakeId);
		$response = array(
			'Response' => 'snake info',
			'SnakeId' => $snakeId,
			'SnakeName' => 'snn',
			'SnakeType' => 'N',
			'SkinId' => 1,
			'PlayerId' => '2',
			'PlayerName' => 'ch2',
			'ProgramDescription' => 'проверка',
			'Templates' => array('W', 'X', 'Y', 'Z'),
			'Maps' => array(array(
				'Description' => '', 'HeadX' => 3, 'HeadY' => 3, 'Lines' => array(),
			)),
		);
		self::checkValidRequest($request, $response, 2);
		return $snakeId;
	}

	/**
	 * @depends testSnakeInfo
	 */
	public function testSnakeDelete($snakeId) {
		$request = array('Request' => 'snake delete', 'SnakeId' => $snakeId);
		self::checkInvalidRequest($request, NackException::ERR_NOT_MY_SNAKE, 1);
		self::checkInvalidRequest($request, NackException::ERR_CANNOT_REMOVE_FIGHTER, 2);
		self::checkValidRequest(array('Request' => 'snake assign', 'SnakeId' => '2'), NULL, 2);
		self::checkValidRequest($request, NULL, 2);
	}

//---------------------------------------------------------------------------

	public function testFightTrain() {
		$request = array(
			'Request' => 'fight train',
			'TurnLimit' => 1,
			'SnakeIds' => array('', '3', '', '4'),
			'Sid' => '1',
		);
		$game = new Game($request, true);
		$response = $game->setPlayer(4)->run();
		$this->assertEquals('fight delayed', $response['Response']);
		$fightId = $response['FightId'];
		return $fightId;
	}

	/**
	 * @depends testFightTrain
	 */
	public function testFightDelayed($fightId) {
		$request = array('Request' => 'fight info', 'FightId' => $fightId, 'Sid' => '1');
		$game = new Game($request, true);

		$response = $game->setPlayer(4)->run();
		$response['Turns'][0] &= 0x3fc0;
		$response['FightTime'] = 0;

		$expected = array(
			'Response' => 'fight info',
			'FightId' => $fightId,
			'FightType' => 'train',
			'FightTime' => 0,
			'FightResult' => 'limit',
			'TurnLimit' => 1,
			'Turns' => array(0x2200),
			'Snakes' => array(
				NULL,
				array('SnakeId' => '3', 'SnakeName' => 'sn3', 'SnakeType' => 'N',
					'SkinId' => 1, 'PlayerId' => '3', 'PlayerName' => 'ch3'),
				NULL,
				array('SnakeId' => '4', 'SnakeName' => 'sn4', 'SnakeType' => 'N',
					'SkinId' => 1, 'PlayerId' => '4', 'PlayerName' => 'ch4'),
			),
			'SnakeStats' => array(
				NULL,
				array('Status' => 'free', 'FinalLength' => 10),
				NULL,
				array('Status' => 'free', 'FinalLength' => 10, 'ProgramDescription' => '',
					'Templates' => array('S', 'S', 'S', 'S'), 'Maps' => array(
						array('Description' => '', 'HeadX' => 3, 'HeadY' => 3, 'Lines' => array(
							array('X' => 3, 'Y' => 4, 'Line' => 'S0'))),
					), 'DebugData' => chr(32 + 0xB)),
			),
		);

		try {
			Util::compareArrays($expected, $response);
		} catch (Exception $e) {
			var_dump($response);
			throw $e;
		}

		return $fightId;
	}

	/**
	 * @depends testFightDelayed
	 */
	public function testFightList($fightId) {
		$request = array('Request' => 'fight list', 'FightListType' => 'ordered', 'Sid' => '1');
		$expected = array(
			'Response' => 'fight list',
			'FightListType' => 'ordered',
			'FightList' => array(array(
				'FightId' => $fightId,
				'FightType' => 'train',
				'FightTime' => 0,
				'PlayerId' => '4',
				'PlayerName' => 'ch4',
				'Snakes' => array(
					NULL,
					array('SnakeId' => '3', 'SnakeName' => 'sn3', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '3', 'PlayerName' => 'ch3'),
					NULL,
					array('SnakeId' => '4', 'SnakeName' => 'sn4', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '4', 'PlayerName' => 'ch4'),
				),
			)),
		);
		$game = new Game($request, true);
		$response = $game->setPlayer(4)->run();
		$response['FightList'][0]['FightTime'] = 0;
		Util::compareArrays($expected, $response);
		return $fightId;
	}

	/**
	 * @depends testFightList
	 */
	public function testFightDelete($fightId) {
		Fight::model()->deleteByPk($fightId);
	}

//---------------------------------------------------------------------------
}