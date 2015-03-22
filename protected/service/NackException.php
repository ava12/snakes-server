<?php

class NackException extends RuntimeException {
	protected $data;

	const ERR_SERVER_DOWN = 0;
	const ERR_UNKNOWN_REQUEST = 1;
	const ERR_UNKNOWN_FIELD = 2;
	const ERR_MISSING_FIELD = 3;
	const ERR_WRONG_VALUE = 4;
	const ERR_TOO_LONG = 5;
	const ERR_WRONG_LOGIN = 6;
	const ERR_UNKNOWN_PLAYER = 7;
	const ERR_UNKNOWN_SNAKE = 8;
	const ERR_UNKNOWN_FIGHT = 9;
	const ERR_CANNOT_CHALLENGE = 10;
	const ERR_WRONG_FORMAT = 11;
	const ERR_CANNOT_REMOVE_FIGHTER = 12;
	const ERR_UNKNOWN_SKIN = 13;
	const ERR_CANNOT_ASSIGN_BOT = 14;
	const ERR_UNKNOWN_SLOT = 15;
	const ERR_NOT_MY_SNAKE = 16;
	const ERR_HAS_DELAYED = 17;
	const ERR_NO_FOES = 18;
	const ERR_INVALID_INPUT = 19;
	const ERR_INVALID_MAP_LINE = 20;
	const ERR_TOO_MANY_SNAKES = 21;


	protected static $messages = array(
		'сервер временно недоступен: %s',

		'неизвестное имя запроса: "%s"',
		'неизвестное имя поля: "%s"',
		'отсутствует необходимое поле: [%s]',
		'некорректное значение поля [%1$s]: "%2$s"',
		'слишком длинная строка в поле [%1$s] - %2$s символов (допустимо не более %3$s)',

		'неизвестный логин или неверный хэш',
		'неизвестный идентификатор игрока: "%s"',
		'неизвестный идентификатор змеи: "%s"',
		'неизвестный идентификатор боя: "%s"',
		'игрок с идентификатором "%s" не участвует в рейтинге',

		'некорректный формат поля [%1$s]: (%3$s)"%2$s"',
		'невозможно удалить бойца "%s" или сменить его тип',
		'некорректная окраска (%1$s) змеи "%2$s"',
		'невозможно назначить бойцом бота "%s"',
		'сохраненный бой № %s отсутствует',

		'змея "%s" принадлежит другому игроку',
		'имеется незавершенный бой: "%s"',
		'в бою должны участвовать не менее двух змей',
		'некорректные данные: %s',
		'некорректная линия %2$d (%3$s) для карты %1$d',

		'у вас уже максимальное количество змей (%d)',

	);


//---------------------------------------------------------------------------
	public static function formatMessage($code, $data = NULL) {
		$message = static::$messages[$code];
		if(isset($data)) {
			$data = (array)$data;
			foreach($data as &$p) {
				if(is_array($p)) $p = implode('.', $p);
			}
			unset($p);
			$message = vsprintf($message, $data);
		}
		return $message;
	}

//---------------------------------------------------------------------------
	public function getData() {
		return $this->data;
	}

//---------------------------------------------------------------------------
	public function __construct($code, $data = NULL) {
		$this->code = $code;
		$this->data = (array)$data;
		$this->message = static::formatMessage($code, $data);
	}

//---------------------------------------------------------------------------
	public function asArray() {
		return array(
			'Response' => 'nack',
			'ErrorCode' => $this->code,
			'Error' => $this->message,
		);
	}

//---------------------------------------------------------------------------
}
