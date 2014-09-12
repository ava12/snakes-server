<?php

class ActiveRecord extends CActiveRecord {
	protected $magicGetters = array();
	protected $magicSetters = array();

	public function __get($name) {
		if (!isset($this->magicGetters[$name])) return parent::__get($name);

		$method = $this->magicGetters[$name];
		if (!$method) $method = 'get' . ucfirst($name);
		return $this->$method();
	}

	public function __set($name, $value) {
		if (!isset($this->magicSetters[$name])) return parent::__set($name, $value);

		$method = $this->magicSetters[$name];
		if (!$method) $method = 'set' . ucfirst($name);
		$this->$method($value);
	}
}