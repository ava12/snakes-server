<?php

abstract class ActiveRecord extends CActiveRecord {
	protected $magicGetters = array();
	protected $magicSetters = array();
	protected $blobNames = array();

	public function __get($name) {
		if (!isset($this->magicGetters[$name])) return parent::__get($name);

		$method = $this->magicGetters[$name];
		if (!$method) $method = 'get' . ucfirst($name);
		return $this->$method();
	}

	public function __set($name, $value) {
		if (!isset($this->magicSetters[$name])) {
			parent::__set($name, $value);
			return;
		}

		$method = $this->magicSetters[$name];
		if (!$method) $method = 'set' . ucfirst($name);
		$this->$method($value);
	}

	protected function afterFind() {
		if (!$this->hasAttribute('data')) return;

		$data = json_decode($this->getAttribute('data'), true);
		foreach ($this->blobNames as $name => $class) {
			if (is_numeric($name)) {
				$name = $class;
				$class = false;
			}
			if (isset($data[$name])) {
				if ($class) {
					if (is_array($class)) {
						$class = $class[0];
						$list = array();
						$isAR = is_subclass_of($class, 'CActiveRecord');
						foreach ((array)$data[$name] as $index => $value) {
							if ($isAR) {
								$list[$index] = call_user_func(array($class, 'model'))->populateRecord($value);
							} else {
								$list[$index] = new $class($value);
							}
						}
						$this->$name = $list;
					} else {
						$isAR = is_subclass_of($class, 'CActiveRecord');
						if ($isAR) {
							$this->$name = call_user_func(array($class, 'model'))->populateRecord($data[$name]);
						} else {
							$this->$name = new $class($data[$name]);
						}
					}
				}
				else $this->$name = $data[$name];
			}
		}
	}

	protected function beforeSave() {
		if (!$this->hasAttribute('data')) return true;

		$data = array();
		foreach ($this->blobNames as $name => $class) {
			if (is_numeric($name)) $name = $class;
			$value = $this->$name;
			if (is_object($value)) {
				$value = $value->asArray();
			} elseif (is_array($value)) {
				/** @var Model $p */
				foreach ($value as &$p) {
					if (is_object($p)) $p = $p->asArray();
				}
			}
			$data[$name] = $value;
		}
		$flags = (defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0);
		$this->setAttribute('data', json_encode($data, $flags));
		return true;
	}

	/**
	 * @param CModel $object
	 * @param string $name
	 */
	protected function addComponentErrors($object, $name) {
		$errors = $object->getErrors();
		$name .= '.';
		foreach ($errors as $attr => $list) {
			foreach ($list as $message) {
				$this->addError($name . $attr, $message);
			}
		}
	}

	public function validateComponent($attribute) {
		$value = $this->$attribute;
		if (is_object($value)) {
			if ($value->hasErrors()) {
				$this->addComponentErrors($value, $attribute);
			}
		} elseif (is_array($value)) {
			/** @var CModel|array $v */
			foreach ($value as $name => $v) {
				if (is_object($v)) {
					$v->validate();
					if ($v->hasErrors()) {
						$this->addComponentErrors($v, $attribute . '.' . $name);
					}
				}
			}
		}
	}
}