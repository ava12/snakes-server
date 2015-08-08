<?php

/**
 * @group validator
 * @group nodb
 */
final class RequestValidatorTest extends CTestCase {

//- базовая проверка валидации запросов -------------------------------------

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_MISSING_FIELD
	 */
	public function testEmptyRequest() {
		RequestValidator::validate(array());
	}

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_UNKNOWN_REQUEST
	 */
	public function testWrongRequest() {
		RequestValidator::validate(array('Request' => 'foo'));
	}

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_WRONG_FORMAT
	 */
	public function testWrongRequestFormat() {
		RequestValidator::validate(array('Request' => array('foo', 'bar')));
	}

	/**
	 * @expectedException NackException
	 * @expectedExceptionCode NackException::ERR_MISSING_FIELD
	 */
	public function testMissingField() {
		RequestValidator::validate(array('Request' => 'player info'));
	}

	/**
	 */
	public function testUnknownField() {
		RequestValidator::validate(array('Request' => 'ping', 'Sid' => '1', 'PlayerId' => 1));
	}

	/**
	 */
	public function testIgnoredFieldPresent() {
		RequestValidator::validate(array('Request' => 'info', 'Sid' => '1'));
	}

	/**
	 */
	public function testIgnoredFieldMissing() {
		RequestValidator::validate(array('Request' => 'info'));
	}

	/**
	 */
	public function testOptionalFieldPresent() {
		RequestValidator::validate(array('Request' => 'ratings', 'Sid' => '1', 'FirstIndex' => '1'));
	}

	/**
	 */
	public function testOptionalFieldMissing() {
		RequestValidator::validate(array('Request' => 'ratings', 'Sid' => '1'));
	}


//- служебные методы для детальной проверки валидации запросов --------------

	/**
	 * Генерирует запросы, в каждом из которых отсутствует одно из необходимых полей, и проверяет реакцию валидатора.
	 *
	 * @param array $fields запрос, содержащий все необходимые поля, {имя => корректное_значение}
	 */
	private function CheckRequiredFieldValidation($fields) {
		$type = $fields['Request'];
		foreach($fields as $name => $v) {
			if($name == 'Request') continue;

			$request = $fields;
			unset($request[$name]);

			$flag = false;
			try {
				RequestValidator::validate($request);
			}
			catch(NackException $e) {
				$flag = true;
				$this->assertEquals(NackException::ERR_MISSING_FIELD, $e->getCode(), $e->getMessage());
			}
			$message = ' ! допущен запрос "' . $type . '" с отсутствующим полем "' . $name . '"';
			$this->assertTrue($flag, $message);
		}
	}


	/**
	 * Генерирует запросы с альтернативными корректными значениями полей и проверяет реакцию валидатора.
	 *
	 * @param array $fields корректные поля запроса {имя => значение}
	 * @param array $valid альтернативные корректные значения полей [[путь, значение]*]
	 */
	private function CheckValidFieldValidation($fields, $valid) {
		$type = $fields['Request'];
		foreach($valid as $entry) {
			$request = $fields;
			$p = &$request;
			foreach(explode('.', $entry[0]) as $n) {
				$p = &$p[$n];
			}
			$p = $entry[1];

			try {
				RequestValidator::validate($request);
			}
			catch(NackException $e) {
				$message = ' ! запрос "' . $type . '": отвергнуто корректное поле : ' . $e->getMessage();
				$this->fail($message);
			}
		}
	}


	/**
	 * Генерирует запросы, в каждом из которых одно из полей имеет некорректный формат, и проверяет реакцию валидатора.
	 *
	 * @param array $fields корректные поля запроса {имя => значение}
	 * @param array $invalid список некорректных значений, [[путь, значение, ожидаемый _код?, путь.к.некорректному.полю?]*]
	 * @param int $defaultCode ожидаемый по умолчанию код исключения, NackException::ERR_*
	 */
	private function CheckInvalidFieldValidation($fields, $invalid, $defaultCode) {
		$type = $fields['Request'];
		foreach($invalid as $entry) {
			$request = $fields;
			$p = &$request;
			foreach(explode('.', $entry[0]) as $n) {
				$p = &$p[$n];
			}
			$p = $entry[1];
			$path = (isset($entry[3]) ? $entry[3] : $entry[0]);

			$flag = false;
			$code = (isset($entry[2]) ? $entry[2] : $defaultCode);

			try {
				RequestValidator::validate($request);
			}
			catch(NackException $e) {
				$flag = true;
				$msg = $e->getMessage();
				$this->assertEquals($code, $e->getCode(), $msg);
				preg_match('/[A-Za-z]+(?:\\.[A-Za-z]+)*/', $msg, $matches);
				if($matches[0] <> $path) {
					$message = ' ! запрос "' . $type . '": выдано некорректное поле "' . $matches[0] . '" вместо "' . $path . '"';
				}
			}
			$value = $entry[1];
			if (is_array($value)) {
				foreach ($value as &$p) {
					if (is_array($p)) {
						$value = array('array[' . count($value) . ']');
						break;
					}
				}

				$value = implode(', ', $value);
			}
			$message = ' ! допущен запрос "' . $type . '" с некорректным полем "' . $path . '" = "' . $value . '"';
			$this->assertTrue($flag, $message);
		}
	}


	/**
	 * Генерирует корректные и некорректные запросы и проверяет реакцию валидатора.
	 *
	 * @param string $type тип запроса (поле Request)
	 * @param array $fields полный набор полей корректного запроса (кроме Request и Sid), {имя => корректное_значение}
	 * @param array $required список обязательных полей (кроме Request), [имя_поля*]
	 * @param array $valid список альтернативных корректных значений, [[путь, значение]*]
	 * @param array $invalid список возможных некорректных значений, [[путь, значение, ожидаемый_код?, путь.к.некорректному.полю?]*]
	 * @param array $unknown список недопустимых полей запроса, [[путь, значение, путь.к.некорректному.полю?]*]
	 */
	private function CheckRequestValidation($type, $fields, $required, $valid = array(), $invalid = array(), $unknown = array()) {
		if(in_array('Sid', $required)) $fields['Sid'] = '1';

		if(!$required) $requiredFields = array();
		else {
			$requiredFields = array_intersect_key($fields, array_combine($required, $required));
		}
		$fields['Request'] = $type;
		$requiredFields['Request'] = $type;


		// все требуемые поля присутствуют
		try {
			RequestValidator::validate($requiredFields);
		}
		catch(NackException $e) {
			$this->fail($e->getMessage());
		}

		// все поля присутствуют
		try {
			RequestValidator::validate($fields);
		}
		catch(NackException $e) {
			$this->fail($e->getMessage());
		}

		// отсутствует одно из требуемых полей
		$this->CheckRequiredFieldValidation($requiredFields);

		if($valid) {
			// альтернативное корректное значение
			$this->CheckValidFieldValidation($fields, $valid);
		}

		if($invalid) {
			// некорректно одно из полей или элементов полей
			$this->CheckInvalidFieldValidation($fields, $invalid, NackException::ERR_WRONG_FORMAT);
		}

		if($unknown) {
			// неизвестное имя поля или элемента поля
			$this->CheckInvalidFieldValidation($fields, $unknown, NackException::ERR_UNKNOWN_FIELD);
		}
	}


//- детальная проверка валидации запросов -----------------------------------

	private static $defaultMap = array(
		'Description' => '',
		'HeadX' => '0',
		'HeadY' => '0',
		'Lines' => array(array('X' => '0', 'Y' => '0', 'Line' => 'A4--c6'))
	);

	private static $defaultProgram = array(
		'Description' => 'ы',
		'Templates' => array('S', 'S', 'S', 'S'),
		'Maps' => array()
	);

//---------------------------------------------------------------------------

	public static function setUpBeforeClass() {
		self::$defaultProgram['Maps'][] = self::$defaultMap;
	}


	public function testInfoValidation() {
		$this->CheckRequestValidation('info', array(), array());
	}

	public function testWhoamiValidation() {
		$this->CheckRequestValidation('whoami', array(), array('Sid'));
	}

	public function testLoginDataValidation() {
		$fields = array('Login' => 'foo');
		$required = array('Login');
		$valid = array();
		$invalid = array(
			array('Login', 'ab'),
			array('Login', 'foobarbazfoobarbaz', NackException::ERR_TOO_LONG),
			array('Login', array('foo' => 'bar')),
		);
		$this->CheckRequestValidation('login data', $fields, $required, $valid, $invalid);
	}

	public function testLoginValidation() {
		$fields = array(
			'Login' => 'foo',
			'Timestamp' => '1234567890',
			'Hash' => 'f0123456789abcdef0123456789abcdef0123456'
		);
		$required = array('Login', 'Timestamp', 'Hash');
		$valid = array();
		$invalid = array(
			// Login проверяется в login data
			array('Timestamp', ''),
			array('Timestamp', '1.2'),
			array('Timestamp', 'abc'),
			array('Hash', '123'),
			array('Hash', '0123456789abcdef0123456789abcdef01234567f', NackException::ERR_TOO_LONG),
			array('Hash', '0123456789ABCDEF0123456789abcdef01234567'),
			array('Hash', '0123456789abcdeg0123456789abcdef01234567'),
		);
		$this->CheckRequestValidation('login', $fields, $required, $valid, $invalid);
	}

	public function testLogoutValidation() {
		$this->CheckRequestValidation('logout', array(), array('Sid'));
	}

	public function testPingValidation() {
		$this->CheckRequestValidation('ping', array(), array('Sid'));
	}

	public function testRatingsValidation() {
		$fields = array(
			'FirstIndex' => '0',
			'Count' => '30',
			'SortBy' => array('>Rating')
		);
		$required = array('Sid');
		$valid = array(
			array('SortBy.0', '<Rating'),
		);
		$invalid = array(
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
		$this->CheckRequestValidation('ratings', $fields, $required, $valid, $invalid);
	}

	public function testPlayerListValidation() {
		$fields = array(
			'FirstIndex' => '0',
			'Count' => '30',
			'SortBy' => array('>Rating')
		);
		$required = array('Sid');
		$valid = array();
		$invalid = array(
			// все поля проверяются в ratings
		);
		$this->CheckRequestValidation('player list', $fields, $required, $valid, $invalid);
	}

	public function testPlayerInfoValidation() {
		$fields = array('PlayerId' => '1');
		$required = array('Sid', 'PlayerId');
		$valid = array();
		$invalid = array(
			array('PlayerId', ''),
		);
		$this->CheckRequestValidation('player info', $fields, $required, $valid, $invalid);
	}

	public function testSnakeListValidation() {
		$fields = array(
			'SnakeTypes' => 'BN',
			'FirstIndex' => '0',
			'Count' => '30',
			'SortBy' => array('<SnakeName')
		);
		$required = array('Sid');
		$valid = array(
			array('SnakeTypes', 'B'),
			array('SnakeTypes', 'N'),
		);
		$invalid = array(
			array('SnakeTypes', ''),
			array('SnakeTypes', 'BNB', NackException::ERR_TOO_LONG),
			array('SnakeTypes', 'bn'),
			array('SnakeTypes', 'A'),
			// FirstIndex, Count и SortBy проверяются в ratings
		);
		$this->CheckRequestValidation('snake list', $fields, $required, $valid, $invalid);
	}

	public function testSkinListValidation() {
		$this->CheckRequestValidation('skin list', array(), array('Sid'));
	}

	public function testSnakeInfoValidation() {
		$fields = array('SnakeId' => '1');
		$required = array('Sid', 'SnakeId');
		$valid = array();
		$invalid = array(
			array('SnakeId', ''),
		);
		$this->CheckRequestValidation('snake info', $fields, $required, $valid, $invalid);
	}

	public function testSnakeNewValidation() {
		$fields = array(
			'SnakeName' => 'ы',
			'SnakeType' => 'B',
			'SkinId' => '1',
			'ProgramDescription' => '',
			'Templates' => array('S', 'S', 'S', 'S'),
			'Maps' => array(self::$defaultMap)
		);
		$required = array('Sid', 'SnakeName', 'SnakeType', 'SkinId', 'ProgramDescription', 'Templates', 'Maps');

		$valid = array(
			array('SnakeType', 'N'),
			array('Templates.0', 'STVWXY'),
			array('Maps', array_fill(0, 9, self::$defaultMap)),
			array('Maps.0.HeadX', '6'),
			array('Maps.0.HeadY', '6'),
			array('Maps.0.Lines', array()),
		);
		$invalid = array(
			array('SnakeType', ''),
			array('SnakeType', 'BN'),
			array('SnakeType', 'n'),
			array('Templates.0', ''),
			array('Templates.0', 's'),
			array('Templates.0', 'STVWXYZ', NackException::ERR_TOO_LONG),
			array('Maps', array()),
			array('Maps', array_fill(0, 10, self::$defaultMap)),
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
		$this->CheckRequestValidation('snake new', $fields, $required, $valid, $invalid);
	}

	public function testSnakeDeleteValidation() {
		$fields = array('SnakeId' => '1');
		$required = array('Sid', 'SnakeId');
		$valid = array();
		$invalid = array(
			// SnakeId проверяется в snake info
		);
		$this->CheckRequestValidation('snake delete', $fields, $required, $valid, $invalid);
	}

	public function testSnakeEditValidation() {
		$fields = array(
			'SnakeId' => '1',
			'SnakeName' => 'ы',
			'SnakeType' => 'B',
			'SkinId' => '1',
			'ProgramDescription' => '',
			'Templates' => array('S', 'S', 'S', 'S'),
			'Maps' => array(self::$defaultMap)
		);
		$required = array('Sid', 'SnakeId');
		$valid = array();
		$invalid = array(
			// SnakeId проверяется в snake info
			// SnakeName, SnakeType, SkinId, ProgramDescription, Templates, Maps
			// проверяются в snake new
		);
		$this->CheckRequestValidation('snake edit', $fields, $required, $valid, $invalid);
	}

	public function testSnakeAssignValidation() {
		$fields = array('SnakeId' => '1');
		$required = array('Sid', 'SnakeId');
		$valid = array();
		$invalid = array(
			// SnakeId проверяется в snake info
		);
		$this->CheckRequestValidation('snake assign', $fields, $required, $valid, $invalid);
	}

	public function testFightListValidation() {
		$fields = array('FightListType' => 'ordered');
		$required = array('Sid', 'FightListType');
		$valid = array(
			array('FightListType', 'challenged'),
		);
		$invalid = array(
			array('FightListType', ''),
			array('FightListType', 'foo'),
			array('FightListType', 'ORDERED'),
		);
		$this->CheckRequestValidation('fight list', $fields, $required, $valid, $invalid);
	}

	public function testFightInfoValidation() {
		$fields = array('FightId' => '1');
		$required = array('Sid', 'FightId');
		$valid = array();
		$invalid = array(
			array('FightId', ''),
		);
		$this->CheckRequestValidation('fight info', $fields, $required, $valid, $invalid);
	}

	public function testFightTestValidation() {
		$fields = array(
			'TurnLimit' => '1000',
			'SnakeName' => 'ы',
			'SkinId' => '1',
			'ProgramDescription' => '',
			'Templates' => array('S', 'S', 'S', 'S'),
			'Maps' => array(self::$defaultMap),
			'OtherSnakeIds' => array('', '', '')
		);
		$required = array('Sid', 'SnakeName', 'SkinId', 'ProgramDescription', 'Templates', 'Maps', 'OtherSnakeIds');
		$valid = array(
			array('TurnLimit', '1'),
			array('OtherSnakeIds', array('1', '2', '2')),
		);
		$invalid = array(
			array('TurnLimit', ''),
			array('TurnLimit', '0'),
			array('TurnLimit', '1001'),
			array('OtherSnakeIds', array()),
			array('OtherSnakeIds', array('', '')),
			array('OtherSnakeIds', array('', '', '', '')),
			// SnakeName, SnakeType, SkinId, ProgramDescription, Templates, Maps
			// проверяются в snake new
		);
		$this->CheckRequestValidation('fight test', $fields, $required, $valid, $invalid);
	}

	public function testFightTrainValidation() {
		$fields = array(
			'TurnLimit' => '1000',
			'SnakeIds' => array('1', '', '', '')
		);
		$required = array('Sid', 'SnakeIds');
		$valid = array(
			array('SnakeIds', array('1', '1', '1', '1')),
		);
		$invalid = array(
			array('SnakeIds', array('', '', '')),
			array('SnakeIds', array('', '', '', '', '')),
			// TurnLimit проверяется в fight test
		);
		$this->CheckRequestValidation('fight train', $fields, $required, $valid, $invalid);
	}

	public function testFightChallengeValidation() {
		$fields = array('PlayerIds' => array('1', '2', '3'));
		$required = array('Sid', 'PlayerIds');
		$valid = array();
		$invalid = array(
			array('PlayerIds.1', ''),
			array('PlayerIds', array('1', '2')),
			array('PlayerIds', array('1', '2', '3', '4')),
		);
		$this->CheckRequestValidation('fight challenge', $fields, $required, $valid, $invalid);
	}

	public function testSlotListValidation() {
		$this->CheckRequestValidation('slot list', array(), array('Sid'));
	}

	public function testSlotViewValidation() {
		$fields = array('SlotIndex' => '0');
		$required = array('Sid', 'SlotIndex');
		$valid = array(
			array('SlotIndex', '9'),
		);
		$invalid = array(
			array('SlotIndex', ''),
			array('SlotIndex', '10'),
			array('SlotIndex', '-1'),
			array('SlotIndex', 'a'),
		);
		$this->CheckRequestValidation('slot view', $fields, $required, $valid, $invalid);
	}

	public function testSlotSaveValidation() {
		$fields = array(
			'SlotIndex' => '0',
			'SlotName' => '',
			'FightId' => '1',
		);
		$required = array('Sid', 'SlotIndex', 'SlotName', 'FightId');
		$valid = array();
		$invalid = array(
			array('FightId', ''),
		);
		$this->CheckRequestValidation('slot save', $fields, $required, $valid, $invalid);
	}

	public function testSlotRenameValidation() {
		$fields = array(
			'SlotIndex' => '0',
			'SlotName' => '',
		);
		$required = array('Sid', 'SlotIndex', 'SlotName');
		$valid = array();
		$invalid = array(
		);
		$this->CheckRequestValidation('slot rename', $fields, $required, $valid, $invalid);
	}

	public function testSlotDeleteValidation() {
		$fields = array('SlotIndex' => '0');
		$required = array('Sid', 'SlotIndex');
		$valid = array();
		$invalid = array(
		);
		$this->CheckRequestValidation('slot delete', $fields, $required, $valid, $invalid);
	}

/*
	public function testValidation() {
		$fields = array();
		$required = array();
		$valid = array();
		$invalid = array(
			array('', ''),
			array('', ''),
		);
		$this->CheckRequestValidation('', $fields, $required, $valid, $invalid);
	}

*/
}