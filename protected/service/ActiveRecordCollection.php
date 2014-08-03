<?php

class ActiveRecordCollection {

	protected $items = array();
	protected $dbConnection;
	protected $table = '';
	protected $defaults = array();

//---------------------------------------------------------------------------
	public function __construct($items, $model = NULL) {
		if (!$items or !is_array($items)) {
			throw new CException('array of active records expected, ' . gettype($items) . ' given');
		}

		if (!$model) {
			$model = $this->getModel($items);
		}
		if (!is_object($model)) $model = call_user_func(array($model, 'model'));

		$this->dbConnection = $model->getDbConnection();
		$this->table = Util::unescapeTableName($model->tableName());
	}

//---------------------------------------------------------------------------
	protected function getModel($items) {
		foreach($items as $item) {
			if (is_a($item, 'CActiveRecord')) return $item;
		}

		throw new CException('active record required');
	}

//---------------------------------------------------------------------------
	protected function validate() {
		$this->applyDefaults();

		foreach($this->items as $item) {
			if (!is_object($item)) continue;

			if (!$item->validate()) return false;
		}

		return true;
	}

//---------------------------------------------------------------------------
	protected function applyDefaults() {
		$defaults = $this->defaults;
		if (!$defaults) return;

		foreach ($this->items as $item) {
			if (is_array($item)) $item += $defaults;
			else {
				foreach ($defaults as $name => $value) {
					if (!isset($item->$name)) $item->$name = $value;
				}
			}
		}

		$this->defaults = array();
	}

//---------------------------------------------------------------------------
	public function getColumns() {
		$columns = array();
		foreach ($this->items as $item) {
			if (is_object($item)) $item = $item->getAttributes();
			foreach ($item as $name => $value) {
				if (isset($value)) $columns[$name] = true;
			}
		}
		return array_keys($columns);
	}

//---------------------------------------------------------------------------
	public function save($validate = true) {
		if (!$this->items) return false;

		if ($validate) {
			if (!$this->validate()) return false;
		} else {
			$this->applyDefaults();
		}

		$db = $this->dbConnection;
		$columns = $this->getColumns();
		$values = array();

		foreach ($this->items as $item) {
			if (is_object($item)) $item = $item->getAttributes();
			$row = array();
			foreach ($columns as $name) {
				if (array_key_exists($name, $item)) $value = $item[$name];
				else $value = NULL;

				if (is_bool($value)) $value = ($value ? '1' : '0');
				elseif (is_object($value)) $value = (string)$value;
				else $value = $db->quoteValue($value);

				$row[] = $value;
			}

			$values[] = '(' . implode(', ', $row) . ')';
		}

		$set = array();
		foreach ($columns as &$p) {
			$p = $db->quoteColumnName($p);
			$set[] = $p . ' = VALUES(' . $p . ')';
		}
		unset($p);


		$request = 'INSERT INTO `' . $this->table . '` (' .
			implode(', ', $columns) .
			") VALUES\r\n" . implode(",\r\n", $values) .
			"\r\nON DUPLICATE KEY UPDATE\r\n" .
			implode(', ', $set);

		return $db->createCommand($request)->execute();
	}

//---------------------------------------------------------------------------
	public function setDefaults($defaults) {
		foreach ($defaults as $name => $value) {
			if (!in_array($name, $this->columns)) {
				$this->columns[] = $name;
			}
			$this->defaults[$name] = $value;
		}
	}

//---------------------------------------------------------------------------
	public function number($column, $base = 0) {
		foreach ($this->items as $index => $item) {
			$item[$column] = $index + $base;
		}
	}

//---------------------------------------------------------------------------
}