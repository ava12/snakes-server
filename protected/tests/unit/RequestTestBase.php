<?php

class RequestTestBase extends CTestCase {

	protected static $playerId = false;

//---------------------------------------------------------------------------
	protected static function clearDb() {
		$db = Yii::app()->db;
		$prefix = $db->tablePrefix;

		$request = "SET FOREIGN_KEY_CHECKS = 0;\r\n";
		$tables = array(
			'player', 'snake', 'map', 'fight', 'snakestat', 'delayedfight',
			'fightlist', 'fightslot', 'session',
		);
		foreach($tables as $name) {
			$request .= "TRUNCATE TABLE `$prefix$name`;\r\n";
		}
		$request .= 'SET FOREIGN_KEY_CHECKS = 1';
		$db->createCommand($request)->execute();
	}

//---------------------------------------------------------------------------
	protected static function setupDb($dbData) {
		$db = Yii::app()->db;
		$prefix = $db->tablePrefix;

		$db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();

		foreach($dbData as $table => $def) {
			$db->createCommand(Util::makeMultiInsert($db, $table, $def[0], $def[1]))->execute();
		}

		$db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
	}

//---------------------------------------------------------------------------
	public static function setUpBeforeClass() {
		self::clearDb();
		self::setupDb(require 'db-data.php');
	}

//---------------------------------------------------------------------------
	public static function checkValidRequest($request, $response = NULL, $playerId = NULL) {
		if (!isset($playerId)) $playerId = static::$playerId;
		if ($playerId) {
			$request['Sid'] = '1';
		}
		$game = new Game($request, true);
		$game->setPlayer($playerId);

		if (!$response) $response = array('Response' => 'ack');

		$result = NULL;
		try {
			$result = $game->run();
			Util::compareArrays($response, $result);
		} catch (Exception $e) {
			echo PHP_EOL;
//			var_dump($response);
			var_dump($result);
			throw $e;
		}
	}

//---------------------------------------------------------------------------
	public static function checkInvalidRequest($request, $code, $playerId = NULL) {
		if (!isset($playerId)) $playerId = static::$playerId;
		if ($playerId) {
			$request['Sid'] = '1';
		}

		$game = new Game($request, true);
		$game->setPlayer($playerId);

		try {
			$game->run();
		} catch (NackException $e) {
			if ($code <> $e->getCode()) {
				$msg = 'NackException: код ошибки ' . $e->getCode() . ' не совпадает с ожидаемым ' . $code;
				$msg .= ', сообщение: ' . $e->getMessage();
				echo $msg, PHP_EOL;
				throw $e;
			}
		}
	}

//---------------------------------------------------------------------------
}
