<?php

final class Util {

//---------------------------------------------------------------------------
	public static function unescapeTableName($name, $db = NULL) {
		if (!$db) $db = Yii::app()->db;
		$prefix = $db->tablePrefix;
		return preg_replace('/\\{\\{([a-zA-Z0-9_]+)\\}\\}/', $prefix . '$1', $name);
	}

//---------------------------------------------------------------------------
}