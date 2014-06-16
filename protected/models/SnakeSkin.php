<?php

class SnakeSkin extends CActiveRecord {

//---------------------------------------------------------------------------
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{skin}}';
	}

//---------------------------------------------------------------------------
	public function rules() {
		return array(
			array('name', 'safe'),
			array('id', 'safe', 'on' => 'insert'),
			array('id, name', 'required', 'on' => 'insert'),
			array('id', 'numerical', 'integerOnly' => true, 'min' => 1),
			array('name', 'length', 'min' => 1, 'max' => 40),
			array('id', 'unique', 'className' => 'SnakeSkin'),
		);
	}

//---------------------------------------------------------------------------
	public function defaultScope() {
		return array(
			'order' => 'id ASC',
		);
	}

//---------------------------------------------------------------------------
}