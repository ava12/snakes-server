<?php

abstract class Model extends CModel {
	protected $names = array();
	protected $writableNames = array();


	public function attributeNames() {
		return $this->names;
	}

	public function canGetProperty($name) {
		return isset($this->names[$name]);
	}

	public function canSetProperty($name) {
		return isset($this->writableNames[$name]);
	}

	public function asArray() {
		$result = array();
		foreach ($this->names as $name => $t) {
			if (isset($t)) $result[$name] = $this->$name;
		}
		return $result;
	}

	public function __construct($data = array()) {
		foreach ($data as $name => $value) {
			if ($this->canGetProperty($name)) $this->$name = $value;
		}
	}

	public function __get($name) {
		if (!$this->canGetProperty($name)) {
			throw new Exception('property ' . __CLASS__ . '::' . $name . ' does not exist');
		}

		if ($this->names[$name]) {
			$name = 'get' . ucfirst($name);
			return $this->$name();
		} else return $this->$name;
	}

	public function __set($name, $value) {
		if (!$this->canSetProperty($name)) {
			throw new Exception('cannot set property ' . __CLASS__ . '::' . $name);
		}

		if ($this->writableNames[$name]) {
			$name = 'set' . ucfirst($name);
			$this->$name($value);
		} else $this->$name = $value;
	}
}