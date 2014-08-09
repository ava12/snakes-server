<?php

/**
 * Бой.
 *
 * @property int $id
 * @property int $refs
 * @property string $type
 * @property int $time
 * @property int $player_id
 * @property int $turn_limit
 * @property int $turn_count
 * @property string $turns
 * @property string $result
 */
class Fight extends CActiveRecord {

	const TYPE_TRAIN = 'train';
	const TYPE_CHALLENGE = 'challenge';

	const RESULT_NONE = '';
	const RESULT_LIMIT = 'limit';
	const RESULT_EATEN = 'eaten';
	const RESULT_BLOCK = 'block';


//---------------------------------------------------------------------------
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{fight}}';
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'player' => array(self::BELONGS_TO, 'Player', 'player_id'),
			'snake_stats' => array(self::HAS_MANY, 'SnakeStat', 'fight_id',
				'order' => 'snake_stats.index'),
		);
	}

//---------------------------------------------------------------------------
	public function defaultScope() {
		return array(
			'condition' => 't.refs > 0',
		);
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}