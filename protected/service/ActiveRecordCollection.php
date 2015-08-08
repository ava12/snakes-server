<?php

class ActiveRecordCollection {

	protected $items = array();
	/** @var CDbConnection */
	protected $dbConnection;
	protected $table = '';
	protected $defaults = array();
	protected $columns = array();

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
		$this->items = $items;
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

		/** @var Model $item */
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
		if ($this->columns) return $this->columns;

		$columns = array();
		/** @var Model $item */
		foreach ($this->items as $item) {
			if (is_object($item)) $item = $item->getAttributes();
			foreach ($item as $name => $value) {
				if (isset($value)) $columns[$name] = true;
			}
		}
		$this->columns = array_keys($columns);
		return $this->columns;
	}

//---------------------------------------------------------------------------
	public function setColumns($columns) {
		$this->columns = $columns;
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
		$columns = array_keys(array_flip($this->getColumns()) + $this->defaults);
		$values = array();

		/** @var Model|array $item */
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
		$this->defaults = $defaults + $this->defaults;
	}

//---------------------------------------------------------------------------
	public function number($column, $base = 0) {
		foreach ($this->items as $index => $item) {
			if (is_array($item)) $item[$column] = $index + $base;
			else $item->$column = $index + $base;
		}
	}

//---------------------------------------------------------------------------
	public function getItems() {
		return $this->items;
	}

//---------------------------------------------------------------------------
}