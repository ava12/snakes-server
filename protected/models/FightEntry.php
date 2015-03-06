<?php

/**
 * Элемент списка боев.
 *
 * @property int $player_id
 * @property int $time
 * @property string $type
 * @property int $fight_id
 */
class FightEntry extends CActiveRecord {
	const TYPE_ORDERED = 'ordered';
	const TYPE_CHALLENGED = 'challenged';

	const LIST_SIZE_ORDERED = 10;
	const LIST_SIZE_CHALLENGED = 10;

	protected static $listSize = array(
		self::TYPE_ORDERED => self::LIST_SIZE_ORDERED,
		self::TYPE_CHALLENGED => self::LIST_SIZE_CHALLENGED,
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
			'order' => 't.time DESC, t.fight_id DESC',
		);
	}

//---------------------------------------------------------------------------
	public function byType($type) {
		if (!isset(self::$listSize[$type])) {
			throw new UnexpectedValueException('unknown list type: "' . $type . '"');
		}

		$limit = self::$listSize[$type];
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 't.type = \'' . $type . '\'',
			'limit' => $limit,
		));
		return $this;
	}

//---------------------------------------------------------------------------
	public function forPlayer($playerId) {
		if (is_object($playerId)) $playerId = $playerId->id;
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 't.player_id = :pid',
			'params' => array(':pid' => $playerId),
		));
		return $this;
	}

//---------------------------------------------------------------------------
	public function addFight($fightId, $listType, $playerIds) {
		$time = new CDbExpression('NOW()');
		$list = array();

		foreach ((array)$playerIds as $playerId) {
			$list[] = array(
				'type' => $listType,
				'player_id' => $playerId,
				'time' => $time,
				'fight_id' => $fightId,
			);
		}

		$collection = new ActiveRecordCollection($list, $this);
		return $collection->save(false);
	}

//---------------------------------------------------------------------------
}