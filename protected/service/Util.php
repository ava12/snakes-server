<?php

final class Util {

//---------------------------------------------------------------------------
	public static function unescapeTableName($name, $db = NULL) {
		if (!$db) $db = Yii::app()->db;
		$prefix = $db->tablePrefix;
		return preg_replace('/\\{\\{([a-zA-Z0-9_]+)\\}\\}/', $prefix . '$1', $name);
	}

//---------------------------------------------------------------------------
	public static function makeMultiInsert($db, $table, $columns, $values) {
		$table = $db->quoteTableName($db->tablePrefix . $table);

		foreach ($columns as &$p) {
			$p = $db->quoteColumnName($p);
		}
		$columns = implode(', ', $columns);

		foreach ($values as &$row) {
			foreach ($row as &$p) {
				if (!isset($p)) $p = 'DEFAULT';
				else $p = $db->quoteValue($p);
			}
			$row = '(' . implode(', ', $row) . ')';
		}
		$values = implode(",\r\n", $values);

		return "INSERT INTO $table ($columns) VALUES $values";
	}

//---------------------------------------------------------------------------
	public static function compareArrays($expect, $got, $path = '') {
		if (count($expect) <> count($got)) {
			$message = $path . ': длины массивов (' . $path . ') не совпадают, ' . count($got) . ' вместо ' . count($expect);
			throw new RuntimeException($message);
		}

		foreach ($expect as $k => $v) {
			if (!array_key_exists($k, $got)) {
				$message = 'отсутствует путь ' . $path . $k;
				throw new RuntimeException($message);
			}

			if (is_array($v)) {
				if (!is_array($got[$k])) {
					$message = 'вместо массива (' .$path . $k . ') получено "' . $got[$k] . '"';
					throw new RuntimeException($message);
				}

				static::compareArrays($v, $got[$k], $path . $k . '.');
			} else {
				if ($v !== $got[$k]) {
					$te = gettype($v);
					$tg = gettype($got[$k]);
					$gotString = $got[$k];
					if (is_array($gotString)) $gotString = implode(', ', $gotString);
					$message = "вместо {$path}{$k}=($te)\"$v\" получено ($tg)\"$gotString\"";
					throw new RuntimeException($message);
				}
			}
		}
	}

//---------------------------------------------------------------------------
	public static function saveModel($model, $attr) {
		$model->setAttributes($attr);
		if ($model->save()) return NULL;

		$result = array();
		foreach ($model->errors as $name => $errors) {
			if ($errors) $result[$name] = $errors[0];
		}

		return $result;
	}

//---------------------------------------------------------------------------
	public static function makeValidationException($model, $message) {
		$errors = $model->getErrors();
		if (!$errors) return new RuntimeException($message);

		foreach ($errors as $name => &$p) {
			$p = $name . ': ' . $p[0];
		}
		$errors = implode(', ', $errors);

		return new NackException(NackException::ERR_INVALID_INPUT, $errors);
	}

//---------------------------------------------------------------------------
	public static function hashXor($a, $b) {
		$a = pack('H*', $a);
		$b = pack('H*', $b);
		$result = '';
		$len = max(strlen($a), strlen($b));
		for ($i = 0; $i < $len; $i++) {
			$result .= chr(ord(substr($a, $i, 1)) ^ ord(substr($b, $i, 1)));
		}

		return bin2hex($result);
	}

//---------------------------------------------------------------------------
}