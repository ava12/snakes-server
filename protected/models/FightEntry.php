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

	protected $sqlSave = 'CALL update_fight_list(\'%s\', %d, %d, %d)';
	protected $list = array();

//---------------------------------------------------------------------------
	/**
	 * @param string $className
	 * @return FightEntry
	 */
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
		foreach ((array)$playerIds as $playerId) {
			$this->list[] = array(
				'type' => $listType,
				'player_id' => (int)$playerId,
				'fight_id' => (int)$fightId,
				'time' => 0,
			);
		}
	}

//---------------------------------------------------------------------------
	public function saveList() {
		if (!$this->list) return;

		$sql = array();
		$time = time();
		foreach ($this->list as $entry) {
			$entry['time'] = $time;
			$sql[] = vsprintf($this->sqlSave, $entry);
		}
		$sql = implode('; ', $sql);
		$this->getDbConnection()->createCommand($sql)->execute();
		$this->list = array();
	}

//---------------------------------------------------------------------------
}