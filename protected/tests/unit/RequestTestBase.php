<?php

class RequestTestBase extends CTestCase {

	protected static $playerId = false;

//---------------------------------------------------------------------------
	protected static function clearDb() {
		/** @var CDbConnection $db */
		$db = Yii::app()->db;
		$prefix = $db->tablePrefix;

		$request = "SET FOREIGN_KEY_CHECKS = 0;\r\n";
		$tables = array(
			'player', 'snake', 'fight', 'delayedfight', 'fightlist',
			'fightslot', 'session',
		);
		foreach($tables as $name) {
			$request .= "TRUNCATE TABLE `$prefix$name`;\r\n";
		}
		$request .= 'SET FOREIGN_KEY_CHECKS = 1';
		$db->createCommand($request)->execute();
	}

//---------------------------------------------------------------------------
	protected static function setupDb($dbData) {
		/** @var CDbConnection $db */
		$db = Yii::app()->db;

		$db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();

		foreach($dbData as $table => $def) {
			$columns = $def[0];
			$rows = $def[1];
			foreach ($columns as $index => $name) {
				if (is_string($name)) continue;

				foreach ($rows as &$row) {
					$data = array();
					foreach ($name as $i => $k) {
						$data[$k] = $row[$index][$i];
					}
					$row[$index] = json_encode($data, JSON_UNESCAPED_UNICODE);
				}

				$columns[$index] = 'data';
			}
			$db->createCommand(Util::makeMultiInsert($db, $table, $columns, $rows))->execute();
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
	protected function tearDown() {
		flush();
	}

//---------------------------------------------------------------------------
}
