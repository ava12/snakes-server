<?php

/**
 * Элемент списка боев.
 *
 * @property int $player_id
 * @property int $time
 * @property string $type
 * @property int $fight_id
 */
class FightList extends CActiveRecord {
	const TYPE_ORDERED = 'ordered';
	const TYPE_CHALLENGED = 'challenged';

	const LIST_SIZE = array(
		self::TYPE_ORDERED => 10,
		self::TYPE_CHALLENGED => 10,
	);

//---------------------------------------------------------------------------
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{fightlist}}';
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'player' => array(self::BELONGS_TO, 'Player', 'player_id'),
			'fight' => array(self::BELONGS_TO, 'Fight', 'fight_id'),
		);
	}

//---------------------------------------------------------------------------
	public function defaultScope() {
		return array(
			'order' => 't.time DESC',
		);
	}

//---------------------------------------------------------------------------
	public function byType($type) {
		$limit = @self::LIST_SIZE[$type];
		if (!$limit) {
			throw new UnexpectedValueException('unknown list type: "' . $type . '"');
		}

		$this->getDbCriteria()->mergeWith(array(
			'condition' => 't.type = ' . $type,
			'limit' => $limit,
		));
		return $this;
	}

//---------------------------------------------------------------------------
	public function forPlayer($playerId) {
		if (is_object($playerId)) $playerId = $playerId->id;
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 'player_id = :pid',
			'params' => array(':pid' => $playerId),
		));
		return $this;
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}