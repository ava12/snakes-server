<?php

/**
 * Базовая валидация AJAX-запросов.
 *
 * Проверяется тип запроса, наличие необходимых и отсутствие лишних полей,
 * формат полей (в т. ч. диапазоны чисел).
 */
class RequestValidator {
	protected static $arrayFieldTypes = array('_array', '_narray', '_list', '_object');

	/* {имя => [имя_типа, значение_по_умолчанию*, параметр*]|имя_типа}
	если значение по умолчанию не указано или NULL, то поле обязательно
	для типа _array: true - минимальный массив с записями указанного типа
	для типа _object: true - объект с полями со значениями по умолчанию
	стандартные типы (имя типа: параметр*):
	_null:
	_bool:
	_int: мин_значение, макс_значение
	_string: мин_длина, макс_длина, регвыр (длина в символах, а не в байтах)
	_select: [возможное_значение+]
	_array: тип_элемента, мин_длина, макс_длина
	_narray: тип_элемента, мин_длина, макс_длина
	_list: тип_элемента, мин_длина, макс_длина
	_object: {имя => [имя_типа, значение_по_умолчанию?, параметр*]}
	*/
	protected static $fieldTypes = array(
		'.DebugString' => array('_string', '', 0, 1000, '/^[!"#(-o]*$/'),
		'.Map' => array('_object', NULL, array(
			'Description' => array('_string', '', 0, 1024),
			'HeadX' => array('_int', NULL, 0, 6),
			'HeadY' => array('_int', NULL, 0, 6),
			'Lines' => array('_array', NULL, '.MapLine'),
		)),
		'.MapLine' => array('_object', NULL, array(
			'X' => array('_int', NULL, 0, 6),
			'Y' => array('_int', NULL, 0, 6),
			'Line' => array('_string', NULL, 2, 98, '/^(?:--|[A-DSTV-Za-dstv-z][0-7])+$/'),
		)),
		'.Snake' => array('_object', NULL, array(
			'Id' => 'SnakeId',
			'Name' => 'SnakeName',
			'Type' => 'SnakeType',
			'SkinId' => 'SkinId',
			'PlayerId' => 'PlayerId',
			'PlayerName' => 'PlayerName',
		)),
		'.SnakeStat' => array('_object', NULL, array(
			'Status' => array('_select', NULL, array('free', 'blocked', 'eaten')),
			'FinalLength'  => array('_int', NULL, 0, 40),
			'InitialRating' => '_int',
			'FinalRating' => '_int',
			'ProgramDescription' => 'ProgramDescription',
			'Templates' => array('_array', false, '.Template', 4, 4),
			'DebugData' => '.DebugString'
		)),
		'.SortBy' => array('_string', NULL, 2, NULL, '/^[<>][A-Za-z]+$/'),
		'.Template' => array('_string', NULL, 1, 6, '/^[STV-Z]+$/'),
		'.Turn' => array('_int', NULL, 0, 0x3FFF),

		'Count' => array('_int', 30, 1, 50),
//		'Error' => '_string',
//		'ErrorCode' => '_int',
		'FightId' => array('_string', NULL, 1),
		'FightListType' => array('_select', NULL, array('ordered', 'challenged')),
		'FightResult' => array('_select', NULL, array('limit', 'eaten', 'blocked')),
		'FightTime' => '_int',
		'FightType' => array('_select', NULL, array('train', 'challenge')),
		'FirstIndex' => array('_int', 0, 0),
		'Hash' => array('_string', NULL, 40, 40, '/^[0-9a-f]+$/'),
		'Login' => array('_string', NULL, 3, 16, '/^[a-z_][a-z0-9_]+$/'),
		'Maps' => array('_array', NULL, '.Map', 1, 9),
		'OtherSnakeIds' => array('_narray', true, 'SnakeId', 3, 3),
		'PlayerId' => array('_string', NULL, 1),
		'PlayerIds' => array('_array', NULL, 'PlayerId', 3, 3),
		'PlayerName' => array('_string', NULL, 1, 30),
		'ProgramDescription' => array('_string', NULL, 0, 1024),
		'Request' => '_string',
		'Sid' => array('_string', NULL),
		'SkinId' => array('_int', NULL, 1),
		'SlotIndex' => array('_int', NULL, 0, 9),
		'SlotName' => array('_string', '', 0, 255),
		'SnakeId' => array('_string', NULL, 1),
		'SnakeIds' => array('_narray', NULL, 'SnakeId', 4, 4),
		'SnakeName' => array('_string', NULL, 1, 40),
		'Snakes' => array('_narray', NULL, '.Snake', 4, 4),
		'SnakeStats' => array('_narray', NULL, '.SnakeStat', 1, 4),
		'SnakeType' => array('_select', NULL, array('B', 'N')),
		'SnakeTypes' => array('_string', 'BN', 1, 2, '/^[BN]+$/'),
		'SortBy' => array('_array', NULL, '.SortBy', 1),
		'Templates' => array('_array', NULL, '.Template', 4, 4),
		'Timestamp' => '_int',
		'TurnLimit' => array('_int', 1000, 1, 1000),
		'Turns' => array('_array', NULL, '.Turn', 1, 1000),
	);

	// поля, общие для всех запросов
	// {имя_поля => требуется?}
	protected static $defaultFields = array(
		'Request' => true,
		'Sid' => true,
		'RequestId' => false,
	);

	// поля, специфичные для запросов
	// {запрос => [[требуемое_поле*]?, [дополнительное_поле*]?]}
	// поле, помеченное и как обязательное, и как дополнительное,
	// считается дополнительным
	protected static $requestTypes = array(
		'info' => array(NULL, 'Sid'),
		'ping' => array(),
		'whoami' => array(),
		'login data' => array('Login', 'Sid'),
		'login' => array(array('Login', 'Timestamp', 'Hash'), 'Sid'),
		'logout' => array(),

		'ratings' => array(NULL, array('FirstIndex', 'Count', 'SortBy')),
		'player list' => array(NULL, array('FirstIndex', 'Count', 'SortBy')),
		'player info' => array('PlayerId'),

		'snake list' => array(NULL, array('SnakeTypes', 'FirstIndex', 'Count', 'SortBy')),
		'skin list' => array(),
		'snake info' => array('SnakeId'),
		'snake new' => array(array(
			'SnakeName', 'SnakeType', 'SkinId', 'ProgramDescription', 'Templates', 'Maps'
		)),
		'snake delete' => array('SnakeId'),
		'snake edit' => array('SnakeId', array(
			'SnakeName', 'SnakeType', 'SkinId', 'ProgramDescription', 'Templates', 'Maps'
		)),
		'snake assign' => array('SnakeId'),

		'fight list' => array('FightListType'),
		'fight info' => array('FightId'),
		'fight test' => array(array(
			'SnakeName', 'SkinId', 'ProgramDescription', 'Templates', 'Maps', 'OtherSnakeIds'
		), 'TurnLimit'),
		'fight train' => array('SnakeIds', 'TurnLimit'),
		'fight challenge' => array('PlayerIds'),
		'fight cancel' => array(),

		'slot list' => array(),
		'slot view' => array('SlotIndex'),
		'slot save' => array(array('SlotIndex', 'SlotName', 'FightId')),
		'slot rename' => array(array('SlotIndex', 'SlotName')),
		'slot delete' => array('SlotIndex'),
	);


//---------------------------------------------------------------------------
	protected static function getDefaultValue($type) {
		$typeDef = (array)static::$fieldTypes[$type];
		if(isset($typeDef[1])) {
			$result = $typeDef[1];
			if(in_array($type, static::$arrayFieldTypes) and !is_array($typeDef[1])) {
				$value = static::GetDefaultValue($typeDef[0]);
				if(!isset($typeDef[3]) or !$typeDef[3]) $result = array();
				else $result = array_fill(0, $typeDef[3], $value);
			}
			return $result;
		}

		while(substr($typeDef[0], 0, 1) <> '_') {
			$typeDef = (array)static::$fieldTypes[$typeDef[0]];
			if(isset($typeDef[1])) return $typeDef[1];
		}

		return NULL;
	}


//---------------------------------------------------------------------------
	protected static function validateField($value, $typeDef, $name, $path = array()) {
		$path[] = $name;
		$typeDef = (array)$typeDef;
		$type = $typeDef[0];
		while(substr($type, 0, 1) <> '_') {
			$typeDef = (array)static::$fieldTypes[$type];
			$type = $typeDef[0];
		}

		if(is_array($value) <> in_array($type, static::$arrayFieldTypes)) {
			$params = array($path, (string)$value, gettype($value));
			throw new NackException(NackException::ERR_WRONG_FORMAT, $params);
		}

		switch($type) {
			case '_bool': {
				if($value == '0' or $value == '1') return (bool)$value;
			break; }

			case '_int': {
				if(preg_match('/^-?[0-9]+$/', $value)) {
					$t = (int)$value;
					if(
						(!isset($typeDef[2]) or $t >= $typeDef[2]) and
						(!isset($typeDef[3]) or $t <= $typeDef[3])
					) return $t;
				}
			break; }

			case '_string': {
				if(is_string($value)) {
					if(isset($typeDef[3]) and mb_strlen($value) > $typeDef[3]) {
						$params = array($path, mb_strlen($value), $typeDef[3]);
						throw new NackException(NackException::ERR_TOO_LONG, $params);
					}
					if(
						(!isset($typeDef[2]) or mb_strlen($value) >= $typeDef[2]) and
						(!isset($typeDef[4]) or preg_match($typeDef[4], $value))
					) return $value;
				}
			break; }

			case '_select': {
				if(in_array($value, $typeDef[2])) return $value;
			break; }

			case '_array': case '_narray': case '_list': {
				$count = count($value);
				if(
					(isset($typeDef[3]) and $count < $typeDef[3]) or
					(isset($typeDef[4]) and $count > $typeDef[4])
				) break;

				$nullAllowed = ($type == '_narray');
				$isIndex = ($type <> '_list');
				$elementType = $typeDef[2];
				$index = 0;
				foreach($value as $k => &$v) {
					if($isIndex and $k <> $index) {
						$path[] = $k;
						throw new NackException(NackException::ERR_UNKNOWN_FIELD, array($path));
					}

					$index++;
					if($v == '' and $nullAllowed) {
						$v = NULL;
						continue;
					}

					$v = static::ValidateField($v, $elementType, $k, $path);
				}
				return $value;
			break; }

			case '_object': {
				$fields = $typeDef[2];
				foreach($value as $name => $v) {
					if(!isset($fields[$name])) {
						$path[] = $name;
						throw new NackException(NackException::ERR_UNKNOWN_FIELD, array($path));
					}
				}
				foreach($fields as $name => $typeDef) {
					$typeDef = (array)$typeDef;
					if(isset($value[$name])) {
						$value[$name] = static::validateField($value[$name], $typeDef, $name, $path);
					}
					else {
						$v = GetDefaultValue($typeDef[0]);
						if(isset($v)) $value[$name] = $v;
						else {
							$path[] = $name;
							throw new NackException(NackException::ERR_MISSING_FIELD, array($path));
						}
					}
				}
				return $value;
			break; }
		}

		$params = array($path, (string)$value, gettype($value));
		throw new NackException(NackException::ERR_WRONG_FORMAT, $params);
	}


//---------------------------------------------------------------------------
	public static function validate($request) {
		if(empty($request['Request'])) {
			throw new NackException(NackException::ERR_MISSING_FIELD, 'Request');
		}

		$requestType = $request['Request'];
		if(!is_string($requestType)) {
			$type = gettype($requestType);
			if (is_array($requestType)) $requestType = implode(', ', $requestType);
			$params = array('Request', (string)$requestType, $type);
			throw new NackException(NackException::ERR_WRONG_FORMAT, $params);
		}

		if(!isset(static::$requestTypes[$requestType])) {
			throw new NackException(NackException::ERR_UNKNOWN_REQUEST, $requestType);
		}

		$t = static::$requestTypes[$requestType];
		$requestFields = array();
		if(!empty($t[0])) {
			$requestFields += array_combine((array)$t[0], array_fill(0, count((array)$t[0]), true));
		}
		if(!empty($t[1])) {
			$requestFields += array_combine((array)$t[1], array_fill(0, count((array)$t[1]), false));
		}
		$requestFields += static::$defaultFields;

		foreach(array_keys($request) as $name) {
			if(!isset($requestFields[$name])) {
				throw new NackException(NackException::ERR_UNKNOWN_FIELD, $name);
			}
		}

		$fieldTypes = static::$fieldTypes;
		foreach($requestFields as $name => $isRequired) {
			if($name == 'RequestId') continue;

			if(!isset($request[$name])) {
				if($isRequired) {
					throw new NackException(NackException::ERR_MISSING_FIELD, $name);
				}

				else {
					$value = static::GetDefaultValue($name);
					if(isset($value)) $request[$name] = $value;
				}
			}

			else {
				$request[$name] = static::ValidateField($request[$name], $name, $name);
			}
		}

		return $request;
	}

//---------------------------------------------------------------------------
}