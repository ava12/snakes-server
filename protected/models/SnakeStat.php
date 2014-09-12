<?php

/**
 * Информация о змее, участвующей в бою.
 *
 * @property int $fight_id
 * @property int $index
 * @property int $snake_id
 * @property string $result
 * @property int $length
 * @property int $pre_rating
 * @property int $post_rating
 * @property string $debug
 */
class SnakeStat extends CActiveRecord {
	const RESULT_NONE = '';
	const RESULT_FREE = 'free';
	const RESULT_EATEN = 'eaten';
	const RESULT_BLOCKED = 'blocked';

//---------------------------------------------------------------------------
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{snakestat}}';
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'snake' => array(self::BELONGS_TO, 'Snake', 'snake_id'),
			'fight' => array(self::BELONGS_TO, 'Fight', 'fight_id'),
		);
	}

//---------------------------------------------------------------------------
	public function rules() {
		return array(
			array('snake_id', 'exists', 'className' => 'Snake', 'attributeName' => 'id'),
		);
	}

//---------------------------------------------------------------------------
	public function setFightSnake($fightId, $snakeId, $index) {
		$this->fight_id = (is_object($fightId) ? $fightId->id : $fightId);
		$this->snake_id = (is_object($snakeId) ? $snakeId->id : $snakeId);
		$this->index = $index;
		$this->length = 10;
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}