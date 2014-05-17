<?php

/**
 * @group validate
 * @group nodb
 */
final class RequestValidatorTest extends PHPUnit_Framework_TestCase {

//- базовая проверка валидации запросов -------------------------------------

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_MISSING_FIELD
	 */
	public function testEmptyRequest() {
		$Request = new Request(array());
		$Request->validate();
	}

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_UNKNOWN_REQUEST
	 */
	public function testWrongRequest() {
		$Request = new Request(array('Request' => 'foo'));
		$Request->validate();
	}

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_WRONG_FORMAT
	 */
	public function testWrongRequestFormat() {
		$Request = new Request(array('Request' => array('foo', 'bar')));
		$Request->validate();
	}

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_MISSING_FIELD
	 */
	public function testMissingField() {
		$Request = new Request(array('Request' => 'player info'));
		$Request->validate();
	}

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_UNKNOWN_FIELD
	 */
	public function testUnknownField() {
		$Request = new Request(array('Request' => 'ping', 'Sid' => '1', 'PlayerId' => 1));
		$Request->validate();
	}

	/**
	 */
	public function testIgnoredFieldPresent() {
		$Request = new Request(array('Request' => 'info', 'Sid' => '1'));
		$Request->validate();
	}

	/**
	 */
	public function testIgnoredFieldMissing() {
		$Request = new Request(array('Request' => 'info'));
		$Request->validate();
	}

	/**
	 */
	public function testOptionalFieldPresent() {
		$Request = new Request(array('Request' => 'ratings', 'Sid' => '1', 'FirstIndex' => '1'));
		$Request->validate();
	}

	/**
	 */
	public function testOptionalFieldMissing() {
		$Request = new Request(array('Request' => 'ratings', 'Sid' => '1'));
		$Request->validate();
	}


//- служебные методы для детальной проверки валидации запросов --------------

	/**
	 * Генерирует запросы, в каждом из которых отсутствует одно из необходимых полей, и проверяет реакцию валидатора.
	 *
	 * @param array $Fields запрос, содержащий все необходимые поля, {имя => корректное_значение}
	 */
	private function CheckRequiredFieldValidation($Fields) {
		$Type = $Fields['Request'];
		foreach($Fields as $Name => $v) {
			if($Name == 'Request') continue;

			$Request = $Fields;
			unset($Request[$Name]);

			$Flag = false;
			try {
				$Request = new Request($Request);
				$Request->validate();
			}
			catch(NackException $e) {
				$Flag = true;
				$this->assertEquals(NackException::ERR_MISSING_FIELD, $e->getCode(), $e->getMessage());
			}
			$Message = ' ! допущен запрос "' . $Type . '" с отсутствующим полем "' . $Name . '"';
			$this->assertTrue($Flag, $Message);
		}
	}


	/**
	 * Генерирует запросы с альтернативными корректными значениями полей и проверяет реакцию валидатора.
	 *
	 * @param array $Fields корректные поля запроса {имя => значение}
	 * @param array $Valid альтернативные корректные значения полей [[путь, значение]*]
	 */
	private function CheckValidFieldValidation($Fields, $Valid) {
		$Type = $Fields['Request'];
		foreach($Valid as $Entry) {
			$Request = $Fields;
			$p = &$Request;
			foreach(explode('.', $Entry[0]) as $n) {
				$p = &$p[$n];
			}
			$p = $Entry[1];

			try {
				$Request = new Request($Request);
				$Request->validate();
			}
			catch(NackException $e) {
				$Message = ' ! запрос "' . $Type . '": отвергнуто корректное поле : ' . $e->getMessage();
				$this->fail($Message);
			}
		}
	}


	/**
	 * Генерирует запросы, в каждом из которых одно из полей имеет некорректный формат, и проверяет реакцию валидатора.
	 *
	 * @param array $Fields корректные поля запроса {имя => значение}
	 * @param array $Invalid список некорректных значений, [[путь, значение, ожидаемый _код?, путь.к.некорректному.полю?]*]
	 * @param int $DefaultCode ожидаемый по умолчанию код исключения, NackException::ERR_*
	 */
	private function CheckInvalidFieldValidation($Fields, $Invalid, $DefaultCode) {
		$Type = $Fields['Request'];
		foreach($Invalid as $Entry) {
			$Request = $Fields;
			$p = &$Request;
			foreach(explode('.', $Entry[0]) as $n) {
				$p = &$p[$n];
			}
			$p = $Entry[1];
			$Path = (isset($Entry[3]) ? $Entry[3] : $Entry[0]);

			$Flag = false;
			$Code = (isset($Entry[2]) ? $Entry[2] : $DefaultCode);
			try {
				$Request = new Request($Request);
				$Request->validate();
			}
			catch(NackException $e) {
				$Flag = true;
				$Msg = $e->getMessage();
				$this->assertEquals($Code, $e->getCode(), $Msg);
				preg_match('/[A-Za-z]+(?:\\.[A-Za-z]+)*/', $Msg, $Matches);
				if($Matches[0] <> $Path) {
					$Message = ' ! запрос "' . $Type . '": выдано некорректное поле "' . $Matches[0] . '" вместо "' . $Path . '"';
				}
			}
			$Message = ' ! допущен запрос "' . $Type . '" с некорректным полем "' . $Path . '" = ' . $Entry[1];
			$this->assertTrue($Flag, $Message);
		}
	}


	/**
	 * Генерирует корректные и некорректные запросы и проверяет реакцию валидатора.
	 *
	 * @param string $Type тип запроса (поле Request)
	 * @param array $Fields полный набор полей корректного запроса (кроме Request и Sid), {имя => корректное_значение}
	 * @param array $Required список обязательных полей (кроме Request), [имя_поля*]
	 * @param array $Valid список альтернативных корректных значений, [[путь, значение]*]
	 * @param array $Invalid список возможных некорректных значений, [[путь, значение, ожидаемый_код?, путь.к.некорректному.полю?]*]
	 * @param array $Unknown список недопустимых полей запроса, [[путь, значение, путь.к.некорректному.полю?]*]
	 */
	private function CheckRequestValidation($Type, $Fields, $Required, $Valid = array(), $Invalid = array(), $Unknown = array()) {
		if(in_array('Sid', $Required)) $Fields['Sid'] = '1';

		if(!$Required) $RequiredFields = array();
		else {
			$RequiredFields = array_intersect_key($Fields, array_combine($Required, $Required));
		}
		$Fields['Request'] = $Type;
		$RequiredFields['Request'] = $Type;


		// все требуемые поля присутствуют
		$Request = new Request($RequiredFields);
		try {
			$Request->validate();
		}
		catch(NackException $e) {
			$this->fail($e->getMessage());
		}

		// все поля присутствуют
		$Request = new Request($Fields);
		try {
			$Request->validate();
		}
		catch(NackException $e) {
			$this->fail($e->getMessage());
		}

		// отсутствует одно из требуемых полей
		$this->CheckRequiredFieldValidation($RequiredFields);

		if($Valid) {
			// альтернативное корректное значение
			$this->CheckValidFieldValidation($Fields, $Valid);
		}

		if($Invalid) {
			// некорректно одно из полей или элементов полей
			$this->CheckInvalidFieldValidation($Fields, $Invalid, NackException::ERR_WRONG_FORMAT);
		}

		if($Unknown) {
			// неизвестное имя поля или элемента поля
			$this->CheckInvalidFieldValidation($Fields, $Unknown, NackException::ERR_UNKNOWN_FIELD);
		}
	}


//- детальная проверка валидации запросов -----------------------------------

	private static $DefaultMap = array(
		'Description' => '',
		'HeadX' => '0',
		'HeadY' => '0',
		'Lines' => array(array('X' => '0', 'Y' => '0', 'Line' => 'A4--c6'))
	);

	private static $DefaultSnake = array(
		'Id' => '1',
		'Name' => 'ы',
		'Type' => 'N',
		'SkinId' => '1',
		'PlayerId' => '1',
		'PlayerName' => 'я'
	);

	private static $DefaultProgram = array(
		'Description' => 'ы',
		'Templates' => array('S', 'S', 'S', 'S'),
		'Maps' => array()
	);

//---------------------------------------------------------------------------

	public static function setUpBeforeClass() {
		self::$DefaultProgram['Maps'][] = self::$DefaultMap;
	}


	public function testInfoValidation() {
		$this->CheckRequestValidation('info', array(), array());
	}

	public function testWhoamiValidation() {
		$this->CheckRequestValidation('whoami', array(), array('Sid'));
	}

	public function testLoginDataValidation() {
		$Fields = array('Login' => 'foo');
		$Required = array('Login');
		$Valid = array();
		$Invalid = array(
			array('Login', 'ab'),
			array('Login', 'foobarbazfoobarbaz', NackException::ERR_TOO_LONG),
			array('Login', array('foo' => 'bar')),
		);
		$this->CheckRequestValidation('login data', $Fields, $Required, $Valid, $Invalid);
	}

	public function testLoginValidation() {
		$Fields = array(
			'Login' => 'foo',
			'Timestamp' => '1234567890',
			'Hash' => 'f0123456789abcdef0123456789abcdef0123456'
		);
		$Required = array('Login', 'Timestamp', 'Hash');
		$Valid = array();
		$Invalid = array(
			// Login проверяется в login data
			array('Timestamp', ''),
			array('Timestamp', '1.2'),
			array('Timestamp', 'abc'),
			array('Hash', '123'),
			array('Hash', '0123456789abcdef0123456789abcdef01234567f', NackException::ERR_TOO_LONG),
			array('Hash', '0123456789ABCDEF0123456789abcdef01234567'),
			array('Hash', '0123456789abcdeg0123456789abcdef01234567'),
		);
		$this->CheckRequestValidation('login', $Fields, $Required, $Valid, $Invalid);
	}

	public function testLogoutValidation() {
		$this->CheckRequestValidation('logout', array(), array('Sid'));
	}

	public function testPingValidation() {
		$this->CheckRequestValidation('ping', array(), array('Sid'));
	}

	public function testRatingsValidation() {
		$Fields = array(
			'FirstIndex' => '0',
			'Count' => '30',
			'SortBy' => array('>Rating')
		);
		$Required = array('Sid');
		$Valid = array(
			array('SortBy.0', '<Rating'),
		);
		$Invalid = array(
			array('FirstIndex', ''),
			array('FirstIndex', '-1'),
			array('FirstIndex', 'a'),
			array('Count', ''),
			array('Count', '-1'),
			array('Count', '0'),
			array('Count', '51'),
			array('SortBy', array()),
			array('SortBy.0', ''),
			array('SortBy.0', 'Rating'),
			array('SortBy.0', '<a_1'),
			array('SortBy.0', '>'),
		);
		$this->CheckRequestValidation('ratings', $Fields, $Required, $Valid, $Invalid);
	}

	public function testPlayerListValidation() {
		$Fields = array(
			'FirstIndex' => '0',
			'Count' => '30',
			'SortBy' => array('>Rating')
		);
		$Required = array('Sid');
		$Valid = array();
		$Invalid = array(
			// все поля проверяются в ratings
		);
		$this->CheckRequestValidation('player list', $Fields, $Required, $Valid, $Invalid);
	}

	public function testPlayerInfoValidation() {
		$Fields = array('PlayerId' => '1');
		$Required = array('Sid', 'PlayerId');
		$Valid = array();
		$Invalid = array(
			array('PlayerId', ''),
		);
		$this->CheckRequestValidation('player info', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSnakeListValidation() {
		$Fields = array(
			'SnakeTypes' => 'BN',
			'FirstIndex' => '0',
			'Count' => '30',
			'SortBy' => array('<SnakeName')
		);
		$Required = array('Sid');
		$Valid = array(
			array('SnakeTypes', 'B'),
			array('SnakeTypes', 'N'),
		);
		$Invalid = array(
			array('SnakeTypes', ''),
			array('SnakeTypes', 'BNB', NackException::ERR_TOO_LONG),
			array('SnakeTypes', 'bn'),
			array('SnakeTypes', 'A'),
			// FirstIndex, Count и SortBy проверяются в ratings
		);
		$this->CheckRequestValidation('snake list', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSkinListValidation() {
		$this->CheckRequestValidation('skin list', array(), array('Sid'));
	}

	public function testSnakeInfoValidation() {
		$Fields = array('SnakeId' => '1');
		$Required = array('Sid', 'SnakeId');
		$Valid = array();
		$Invalid = array(
			array('SnakeId', ''),
		);
		$this->CheckRequestValidation('snake info', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSnakeNewValidation() {
		$Fields = array(
			'SnakeName' => 'ы',
			'SnakeType' => 'B',
			'SkinId' => '1',
			'ProgramDescription' => '',
			'Templates' => array('S', 'S', 'S', 'S'),
			'Maps' => array(self::$DefaultMap)
		);
		$Required = array('Sid', 'SnakeName', 'SnakeType', 'SkinId', 'ProgramDescription', 'Templates', 'Maps');

		$Valid = array(
			array('SnakeType', 'N'),
			array('Templates.0', 'STVWXY'),
			array('Maps', array_fill(0, 9, self::$DefaultMap)),
			array('Maps.0.HeadX', '6'),
			array('Maps.0.HeadY', '6'),
			array('Maps.0.Lines', array()),
		);
		$Invalid = array(
			array('SnakeType', ''),
			array('SnakeType', 'BN'),
			array('SnakeType', 'n'),
			array('Templates.0', ''),
			array('Templates.0', 's'),
			array('Templates.0', 'STVWXYZ', NackException::ERR_TOO_LONG),
			array('Maps', array()),
			array('Maps', array_fill(0, 10, self::$DefaultMap)),
			array('Maps.0.HeadX', ''),
			array('Maps.0.HeadX', '-1'),
			array('Maps.0.HeadX', '7'),
			array('Maps.0.HeadY', ''),
			array('Maps.0.HeadY', '-1'),
			array('Maps.0.HeadY', '7'),
			array('Maps.0.Lines.0.X', ''),
			array('Maps.0.Lines.0.X', '-1'),
			array('Maps.0.Lines.0.X', '7'),
			array('Maps.0.Lines.0.Y', ''),
			array('Maps.0.Lines.0.Y', '-1'),
			array('Maps.0.Lines.0.Y', '7'),
			array('Maps.0.Lines.0.Line', ''),
			array('Maps.0.Lines.0.Line', str_repeat('A4', 50), NackException::ERR_TOO_LONG),
			array('Maps.0.Lines.0.Line', 'A'),
			array('Maps.0.Lines.0.Line', 'A-'),
			array('Maps.0.Lines.0.Line', '-0'),
			array('Maps.0.Lines.0.Line', 'A8'),
			array('Maps.0.Lines.0.Line', 'E0'),
		);
		$this->CheckRequestValidation('snake new', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSnakeDeleteValidation() {
		$Fields = array('SnakeId' => '1');
		$Required = array('Sid', 'SnakeId');
		$Valid = array();
		$Invalid = array(
			// SnakeId проверяется в snake info
		);
		$this->CheckRequestValidation('snake delete', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSnakeEditValidation() {
		$Fields = array(
			'SnakeId' => '1',
			'SnakeName' => 'ы',
			'SnakeType' => 'B',
			'SkinId' => '1',
			'ProgramDescription' => '',
			'Templates' => array('S', 'S', 'S', 'S'),
			'Maps' => array(self::$DefaultMap)
		);
		$Required = array('Sid', 'SnakeId');
		$Valid = array();
		$Invalid = array(
			// SnakeId проверяется в snake info
			// SnakeName, SnakeType, SkinId, ProgramDescription, Templates, Maps
			// проверяются в snake new
		);
		$this->CheckRequestValidation('snake edit', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSnakeAssignValidation() {
		$Fields = array('SnakeId' => '1');
		$Required = array('Sid', 'SnakeId');
		$Valid = array();
		$Invalid = array(
			// SnakeId проверяется в snake info
		);
		$this->CheckRequestValidation('snake assign', $Fields, $Required, $Valid, $Invalid);
	}

	public function testFightListValidation() {
		$Fields = array('FightListType' => 'ordered');
		$Required = array('Sid', 'FightListType');
		$Valid = array(
			array('FightListType', 'challenged'),
		);
		$Invalid = array(
			array('FightListType', ''),
			array('FightListType', 'foo'),
			array('FightListType', 'ORDERED'),
		);
		$this->CheckRequestValidation('fight list', $Fields, $Required, $Valid, $Invalid);
	}

	public function testFightInfoValidation() {
		$Fields = array('FightId' => '1');
		$Required = array('Sid', 'FightId');
		$Valid = array();
		$Invalid = array(
			array('FightId', ''),
		);
		$this->CheckRequestValidation('fight info', $Fields, $Required, $Valid, $Invalid);
	}

	public function testFightTestValidation() {
		$Fields = array(
			'TurnLimit' => '1000',
			'SnakeName' => 'ы',
			'SkinId' => '1',
			'ProgramDescription' => '',
			'Templates' => array('S', 'S', 'S', 'S'),
			'Maps' => array(self::$DefaultMap),
			'OtherSnakeIds' => array('', '', '')
		);
		$Required = array('Sid', 'SnakeName', 'SkinId', 'ProgramDescription', 'Templates', 'Maps', 'OtherSnakeIds');
		$Valid = array(
			array('TurnLimit', '1'),
			array('OtherSnakeIds', array('1', '2', '2')),
		);
		$Invalid = array(
			array('TurnLimit', ''),
			array('TurnLimit', '0'),
			array('TurnLimit', '1001'),
			array('OtherSnakeIds', array()),
			array('OtherSnakeIds', array('', '')),
			array('OtherSnakeIds', array('', '', '', '')),
			// SnakeName, SnakeType, SkinId, ProgramDescription, Templates, Maps
			// проверяются в snake new
		);
		$this->CheckRequestValidation('fight test', $Fields, $Required, $Valid, $Invalid);
	}

	public function testFightTrainValidation() {
		$Fields = array(
			'TurnLimit' => '1000',
			'SnakeIds' => array('1', '', '', '')
		);
		$Required = array('Sid', 'SnakeIds');
		$Valid = array(
			array('SnakeIds', array('1', '1', '1', '1')),
		);
		$Invalid = array(
			array('SnakeIds', array('', '', '')),
			array('SnakeIds', array('', '', '', '', '')),
			// TurnLimit проверяется в fight test
		);
		$this->CheckRequestValidation('fight train', $Fields, $Required, $Valid, $Invalid);
	}

	public function testFightChallengeValidation() {
		$Fields = array('PlayerIds' => array('1', '2', '3'));
		$Required = array('Sid', 'PlayerIds');
		$Valid = array();
		$Invalid = array(
			array('PlayerIds.1', ''),
			array('PlayerIds', array('1', '2')),
			array('PlayerIds', array('1', '2', '3', '4')),
		);
		$this->CheckRequestValidation('fight challenge', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSlotListValidation() {
		$this->CheckRequestValidation('slot list', array(), array('Sid'));
	}

	public function testSlotViewValidation() {
		$Fields = array('SlotIndex' => '0');
		$Required = array('Sid', 'SlotIndex');
		$Valid = array(
			array('SlotIndex', '9'),
		);
		$Invalid = array(
			array('SlotIndex', ''),
			array('SlotIndex', '10'),
			array('SlotIndex', '-1'),
			array('SlotIndex', 'a'),
		);
		$this->CheckRequestValidation('slot view', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSlotSaveValidation() {
		$Fields = array(
			'SlotIndex' => '0',
			'SlotName' => '',
			'FightId' => '1',
		);
		$Required = array('Sid', 'SlotIndex', 'SlotName', 'FightId');
		$Valid = array();
		$Invalid = array(
			array('FightId', ''),
		);
		$this->CheckRequestValidation('slot save', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSlotRenameValidation() {
		$Fields = array(
			'SlotIndex' => '0',
			'SlotName' => '',
		);
		$Required = array('Sid', 'SlotIndex', 'SlotName');
		$Valid = array();
		$Invalid = array(
		);
		$this->CheckRequestValidation('slot rename', $Fields, $Required, $Valid, $Invalid);
	}

	public function testSlotDeleteValidation() {
		$Fields = array('SlotIndex' => '0');
		$Required = array('Sid', 'SlotIndex');
		$Valid = array();
		$Invalid = array(
		);
		$this->CheckRequestValidation('slot delete', $Fields, $Required, $Valid, $Invalid);
	}

/*
	public function testValidation() {
		$Fields = array();
		$Required = array();
		$Valid = array();
		$Invalid = array(
			array('', ''),
			array('', ''),
		);
		$this->CheckRequestValidation('', $Fields, $Required, $Valid, $Invalid);
	}

*/
}