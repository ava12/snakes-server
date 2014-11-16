<?php

require_once __DIR__ . '/RequestTestBase.php';

/**
 * @group db
 */
final class RequestTest extends RequestTestBase {

	protected static $playerId = 1;

//- некорректные ------------------------------------------------------------

	public function testInvalidPlayer() {
		$request = array('Request' => 'player info', 'PlayerId' => '123');
		self::checkInvalidRequest($request, NackException::ERR_UNKNOWN_PLAYER);
	}

	public function testInvalidSnake() {
		$request = array('Request' => 'snake info', 'SnakeId' => '123');
		self::checkInvalidRequest($request, NackException::ERR_UNKNOWN_SNAKE);
	}

	public function testInvalidFight() {
		$request = array('Request' => 'fight info', 'FightId' => '123');
		self::checkInvalidRequest($request, NackException::ERR_UNKNOWN_FIGHT);
	}

	public function testInvalidSlotIndex() {
		$request = array(
			'Request' => 'slot view',
			'SlotIndex' => 9,
		);
		self::checkInvalidRequest($request, NackException::ERR_UNKNOWN_SLOT);
	}

	public function testInvalidMapHead() {
		$request = array(
			'Request' => 'snake edit',
			'SnakeId' => '1',
			'Maps' => array(
				array(
					'Description' => '', 'HeadX' => 0, 'HeadY' => 0, 'Lines' => array(
						array('X' => 0, 'Y' => 0, 'Line' => 'S0'),
					),
				),
			),
		);
		self::checkInvalidRequest($request, NackException::ERR_INVALID_MAP);
	}

	public function testInvalidMapLine() {
		$request = array(
			'Request' => 'snake edit',
			'SnakeId' => '1',
			'Maps' => array(
				array(
					'Description' => '', 'HeadX' => 3, 'HeadY' => 3, 'Lines' => array(
						array('X' => 0, 'Y' => 0, 'Line' => 'S0S0'),
						array('X' => 1, 'Y' => 0, 'Line' => 'S0S0'),
					),
				),
			),
		);
		self::checkInvalidRequest($request, NackException::ERR_INVALID_MAP_LINE);
	}

	public function testInvalidChallengeOrdered() {
		$request = array(
			'Request' => 'fight challenge',
			'PlayerIds' => array('1', '2', '3'),
		);
		self::checkInvalidRequest($request, NackException::ERR_CANNOT_CHALLENGE, 5);
	}

	public function testInvalidChallengeEnemy() {
		$request = array(
			'Request' => 'fight challenge',
			'PlayerIds' => array('2', '3', '5'),
		);
		self::checkInvalidRequest($request, NackException::ERR_CANNOT_CHALLENGE, 1);
	}

	public function testWrongFightInfo() {
		$request = array('Request' => 'fight info', 'FightId' => '1');
		self::checkInvalidRequest($request, NackException::ERR_UNKNOWN_FIGHT, 5);
	}

	public function testWrongSlotInfo() {
		$request = array('Request' => 'slot view', 'SlotIndex' => 2);
		self::checkInvalidRequest($request, NackException::ERR_UNKNOWN_SLOT, 1);
	}


//- корректные, не изменяющие состояние -------------------------------------

	public function testInfoRequest() {
		$request = array('Request' => 'info', 'RequestId' => 'foo');
		$response = array(
			'Response' => 'info',
			'Version' => 1,
			'Compatible' => 1,
			'Lifetime' => Yii::app()->user->clientLifetime,
			'RequestId' => 'foo',
		);
		self::checkValidRequest($request, $response, false);
		self::checkValidRequest($request, $response, 1);
	}

	public function testWhoamiRequest() {
		$request = array('Request' => 'whoami');
		$player = Player::model()->findByPk(5);
		$response = array(
			'Response' => 'whoami',
			'PlayerId' => '5',
			'PlayerName' => $player->name,
			'FightId' => '0',
		);
		self::checkValidRequest($request, $response, 5);
	}

	public function testLogin() {
		$salt = '0123abcd';
		$hash = sha1(sha1('login:password') . $salt);
		$player = new Player;
		$errors = Util::saveModel($player, array(
			'name' => 'игрок',
			'login' => 'login',
			'hash' => $hash,
			'salt' => $salt,
		));
		if ($errors) {
			echo PHP_EOL;
			var_dump($errors);
			$this->assertTrue(false, 'ошибка при регистрации игрока');
		}

		$game = new Game();

		$loginData = $game->setRequest(array('Request' => 'login data', 'Login' => 'login'))->run();

		$stamp = time();
		$request = array(
			'Request' => 'login',
			'Login' => 'login',
			'Timestamp' => $stamp,
			'Hash' => sha1(sha1(sha1('login:password') . $loginData['Salt']) . $stamp),
		);

		$response = $game->setRequest($request)->run();
		$expected = array(
			'Response' => 'login',
			'PlayerId' => $player->id,
			'PlayerName' => 'игрок',
			'Sid' => $response['Sid'],
		);
		try {
			Util::compareArrays($expected, $response);
		} catch (Exception $e) {
			echo PHP_EOL;
			var_dump($response);
			throw $e;
		}

		$player->delete();
	}

	public function testPingRequest() {
		self::checkValidRequest(array('Request' => 'ping'));
	}

	public function testPlayerListRequest() {
		$request = array(
			'Request' => 'player list',
			'FirstIndex' => 1,
			'Count' => 3,
			'SortBy' => array('>PlayerName'),
		);
		$response = array(
			'Response' => 'player list',
			'FirstIndex' => 0,
			'SortBy' => array('>PlayerName'),
			'TotalCount' => 5,
			'PlayerList' => array(
				array('PlayerId' => '5', 'PlayerName' => 'p', 'Rating' => 0),
				array('PlayerId' => '4', 'PlayerName' => 'ch4', 'Rating' => 40),
				array('PlayerId' => '3', 'PlayerName' => 'ch3', 'Rating' => 30),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testRatingsRequest() {
		$request = array(
			'Request' => 'ratings',
			'FirstIndex' => 2,
			'Count' => 3,
			'SortBy' => array('<Rating'),
		);
		$response = array(
			'Response' => 'ratings',
			'FirstIndex' => 0,
			'SortBy' => array('<Rating'),
			'TotalCount' => 4,
			'Ratings' => array(
				array('PlayerId' => '1', 'PlayerName' => 'ch1', 'Rating' => 10,
					'SnakeId' => '1', 'SnakeName' => 'sn', 'SkinId' => 1),
				array('PlayerId' => '2', 'PlayerName' => 'ch2', 'Rating' => 20,
					'SnakeId' => '2', 'SnakeName' => 'sn2', 'SkinId' => 1),
				array('PlayerId' => '3', 'PlayerName' => 'ch3', 'Rating' => 30,
					'SnakeId' => '3', 'SnakeName' => 'sn3', 'SkinId' => 1),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testPlayerInfoRequest() {
		$request = array('Request' => 'player info', 'PlayerId' => '5');
		$response = array(
			'Response' => 'player info',
			'PlayerId' => '5',
			'PlayerName' => 'p',
			'Rating' => NULL,
			'FighterId' => NULL,
			'PlayerSnakes' => array(
				array('SnakeId' => '5', 'SnakeName' => 'sn', 'SnakeType' => 'B', 'SkinId' => 1),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testSnakeListRequest() {
		$request = array(
			'Request' => 'snake list',
			'SnakeTypes' => 'BN',
			'FirstIndex' => 3,
			'Count' => 3,
			'SortBy' => array('>SnakeName', '>PlayerName'),
		);
		$response = array(
			'Response' => 'snake list',
			'SnakeTypes' => 'BN',
			'FirstIndex' => 3,
			'SortBy' => array('>SnakeName', '>PlayerName'),
			'TotalCount' => 5,
			'SnakeList' => array(
				array('SnakeId' => '5', 'SnakeName' => 'sn', 'SnakeType' => 'B',
					'SkinId' => 1, 'PlayerId' => '5', 'PlayerName' => 'p'),
				array('SnakeId' => '1', 'SnakeName' => 'sn', 'SnakeType' => 'N',
					'SkinId' => 1, 'PlayerId' => '1', 'PlayerName' => 'ch1'),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testOwnSnakeInfoRequest() {
		$request = array('Request' => 'snake info', 'SnakeId' => '1');
		$response = array(
			'Response' => 'snake info',
			'SnakeId' => '1',
			'SnakeName' => 'sn',
			'SnakeType' => 'N',
			'SkinId' => 1,
			'PlayerId' => '1',
			'PlayerName' => 'ch1',
			'ProgramDescription' => '',
			'Templates' => array('S', 'S', 'S', 'S'),
			'Maps' => array(
				array('Description' => '', 'HeadX' => 3, 'HeadY' => 3, 'Lines' => array(array(
					'X' => 3, 'Y' => 4, 'Line' => 'S0',
				))),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testOtherSnakeInfoRequest() {
		$request = array('Request' => 'snake info', 'SnakeId' => '1');
		$response = array(
			'Response' => 'snake info',
			'SnakeId' => '1',
			'SnakeName' => 'sn',
			'SnakeType' => 'N',
			'SkinId' => 1,
			'PlayerId' => '1',
			'PlayerName' => 'ch1',
		);
		self::checkValidRequest($request, $response, 2);
	}

	public function testOtherBotInfoRequest() {
		$request = array('Request' => 'snake info', 'SnakeId' => '5');
		$response = array(
			'Response' => 'snake info',
			'SnakeId' => '5',
			'SnakeName' => 'sn',
			'SnakeType' => 'B',
			'SkinId' => 1,
			'PlayerId' => '5',
			'PlayerName' => 'p',
			'ProgramDescription' => '',
			'Templates' => array('S', 'S', 'S', 'S'),
			'Maps' => array(
				array('Description' => '', 'HeadX' => 3, 'HeadY' => 3, 'Lines' => array(array(
					'X' => 3, 'Y' => 4, 'Line' => 'S0',
				))),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testSkinListRequest() {
		$request = array('Request' => 'skin list');
		$response = array(
			'Response' => 'skin list',
			'SkinList' => array(
				array('SkinId' => 1, 'SkinName' => '- по умолчанию -'),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testFightListRequest() {
		$request = array('Request' => 'fight list', 'FightListType' => 'ordered');
		$response = array(
			'Response' => 'fight list',
			'FightListType' => 'ordered',
			'FightList' => array(array(
				'FightId' => '1',
				'FightType' => 'challenge',
				'FightTime' => 1000000000,
				'PlayerId' => '1',
				'PlayerName' => 'ch1',
				'Snakes' => array(
					array('SnakeId' => '1', 'SnakeName' => 'sn', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '1', 'PlayerName' => 'ch1'),
					array('SnakeId' => '2', 'SnakeName' => 'sn2', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '2', 'PlayerName' => 'ch2'),
					array('SnakeId' => '3', 'SnakeName' => 'sn3', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '3', 'PlayerName' => 'ch3'),
					array('SnakeId' => '4', 'SnakeName' => 'sn4', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '4', 'PlayerName' => 'ch4'),
				),
			)),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testFightInfoRequest() {
		$request = array('Request' => 'fight info', 'FightId' => '1');
		$response = array(
			'Response' => 'fight info',
			'FightId' => '1',
			'FightType' => 'challenge',
			'FightTime' => 1000000000,
			'FightResult' => 'limit',
			'TurnLimit' => 1,
			'Turns' => array(0x2aa4),
			'Snakes' => array(
					array('SnakeId' => '1', 'SnakeName' => 'sn', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '1', 'PlayerName' => 'ch1'),
					array('SnakeId' => '2', 'SnakeName' => 'sn2', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '2', 'PlayerName' => 'ch2'),
					array('SnakeId' => '3', 'SnakeName' => 'sn3', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '3', 'PlayerName' => 'ch3'),
					array('SnakeId' => '4', 'SnakeName' => 'sn4', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '4', 'PlayerName' => 'ch4'),
			),
			'SnakeStats' => array(
				array('Status' => 'free', 'FinalLength' => 10, 'InitialRating' => 0,
					'FinalRating' => 0, 'ProgramDescription' => '',
					'Templates' => array('S', 'S', 'S', 'S'),
					'Maps' => array(array('Description' => '', 'HeadX' => 3, 'HeadY' => 3, 'Lines' => array(
						array('X' => 3, 'Y' => 4, 'Line' => 'S0'),
					))), 'DebugData' => chr(40)),
				array('Status' => 'free', 'FinalLength' => 10, 'InitialRating' => 0, 'FinalRating' => 0),
				array('Status' => 'free', 'FinalLength' => 10, 'InitialRating' => 0, 'FinalRating' => 0),
				array('Status' => 'free', 'FinalLength' => 10, 'InitialRating' => 0, 'FinalRating' => 0),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testSlotListRequest() {
		$request = array('Request' => 'slot list');
		$response = array(
			'Response' => 'slot list',
			'SlotList' => array(NULL,
				array('SlotName' => 'test', 'FightId' => '1',
					'FightType' => 'challenge', 'FightTime' => 1000000000),
				NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
		);
		self::checkValidRequest($request, $response, 1);
	}

	public function testSlotViewRequest() {
		$request = array('Request' => 'slot view', 'SlotIndex' => 1);
		$response = array(
			'Response' => 'slot view',
			'SlotIndex' => 1,
			'SlotName' => 'test',
			'FightType' => 'challenge',
			'FightTime' => 1000000000,
			'FightResult' => 'limit',
			'TurnLimit' => 1,
			'Turns' => array(0x2aa4),
			'Snakes' => array(
					array('SnakeId' => '1', 'SnakeName' => 'sn', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '1', 'PlayerName' => 'ch1'),
					array('SnakeId' => '2', 'SnakeName' => 'sn2', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '2', 'PlayerName' => 'ch2'),
					array('SnakeId' => '3', 'SnakeName' => 'sn3', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '3', 'PlayerName' => 'ch3'),
					array('SnakeId' => '4', 'SnakeName' => 'sn4', 'SnakeType' => 'N',
						'SkinId' => 1, 'PlayerId' => '4', 'PlayerName' => 'ch4'),
			),
			'SnakeStats' => array(
				array('Status' => 'free', 'FinalLength' => 10, 'InitialRating' => 0,
					'FinalRating' => 0, 'ProgramDescription' => '',
					'Templates' => array('S', 'S', 'S', 'S'),
					'Maps' => array(array('Description' => '', 'HeadX' => 3, 'HeadY' => 3, 'Lines' => array(
						array('X' => 3, 'Y' => 4, 'Line' => 'S0'),
					))), 'DebugData' => chr(40)),
				array('Status' => 'free', 'FinalLength' => 10, 'InitialRating' => 0, 'FinalRating' => 0),
				array('Status' => 'free', 'FinalLength' => 10, 'InitialRating' => 0, 'FinalRating' => 0),
				array('Status' => 'free', 'FinalLength' => 10, 'InitialRating' => 0, 'FinalRating' => 0),
			),
		);
		self::checkValidRequest($request, $response, 1);
	}

//---------------------------------------------------------------------------
}