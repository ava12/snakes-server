<?php

class ValidatorException extends RuntimeException {
	protected $model;

//---------------------------------------------------------------------------
	public function __construct($message, $code, $model) {
		$this->model = $model;
		parent::__construct($message, $code);
	}

//---------------------------------------------------------------------------
	public function getModel() {
		return $this->model;
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}