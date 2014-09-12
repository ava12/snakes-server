<?php

/**
 * Сохраненный бой.
 *
 * @property int $player_id
 * @property int $index
 * @property int $fight_id
 * @property string $name
 */
class FightSlot extends CActiveRecord {

//---------------------------------------------------------------------------
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{fightslot}}';
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'fight' => array(self::BELONGS_TO, 'Fight', 'fight_id'),
			'player' => array(self::BELONGS_TO, 'Player', 'player_id'),
		);
	}

//---------------------------------------------------------------------------
	public function defaultScope() {
		return array(
			'order' => 't.index',
			'index' => 'index',
		);
	}

//---------------------------------------------------------------------------
	public function forPlayer($playerId) {
		if (is_object($playerId)) $playerId = $playerId->id;
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 't.player_id = ' . (int)$playerId,
		));

		return $this;
	}

//---------------------------------------------------------------------------
	public function byIndex($index) {
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 't.index = ' . (int)$index,
		));

		return $this;
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}