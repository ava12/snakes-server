<?php

class Snake extends CActiveRecord {

//---------------------------------------------------------------------------
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{snake}}';
	}

//---------------------------------------------------------------------------
	public function defaultScope() {
		return array(
			'condition' => 't.refs > 0',
		);
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'maps' => array(self::HAS_MANY, 'SnakeMap', 'snake_id',
				'order' => 'maps.index', 'index' => 'maps.index'),
			'player' => array(self::BELONGS_TO, 'Player', 'player_id'),
		);
	}

//---------------------------------------------------------------------------
	public function scopes() {
		return array(
			'current' => 'current > 0',
		);
	}
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}