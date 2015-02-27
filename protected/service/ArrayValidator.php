<?php

class ArrayValidator extends CValidator {
	public $type = NULL;
	public $class = NULL;
	public $min = 0;
	public $max = NULL;
	public $message = 'некорректный массив {attribute}';

	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if (!is_array($value) or count($value) < $this->min or ($this->max and count($value) > $this->max)) {
			$this->addError($object, $attribute, $this->message);
			return;
		}

		if ($this->type) {
			foreach ($value as $v) {
				if (gettype($v) <> $this->type) {
					$this->addError($object, $attribute, $this->message);
					return;
				}
			}
		}

		if ($this->class) {
			foreach ($value as $v) {
				if (!is_object($v) or !is_a($v, $this->class)) {
					$this->addError($object, $attribute, $this->message);
					return;
				}
			}
		}
	}
}