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
	const RESULT_BLOCK = 'blocked';

	const DEFAULT_TURN_LIMIT = 1000;
	const MAX_TURN_LIMIT = 1000;

	protected $newStats = array();


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
			'stats' => array(self::HAS_MANY, 'SnakeStat', 'fight_id',
				'order' => 'stats.index', 'index' => 'stats.index'),
		);
	}

//---------------------------------------------------------------------------
	public function defaultScope() {
		return array(
			'condition' => 't.refs > 0',
		);
	}

//---------------------------------------------------------------------------
	public function rules() {
		return array(
			array('type, player_id, turn_limit', 'safe', 'on' => 'insert'),
			array('type, player_id', 'required', 'on' => 'insert'),

			array('type', 'in', 'range' => array(self::TYPE_TRAIN, self::TYPE_CHALLENGE)),
			array('turn_limit', 'default', 'value' => self::DEFAULT_TURN_LIMIT),
		);
	}

//---------------------------------------------------------------------------
	public function isListed($playerId = NULL, $id = NULL) {
		if (!$id) $id = $this->id;
		if (!$playerId) $playerId = $this->player_id;
		return (bool)$this->getDbConnection()
			->createCommand('SELECT `can_view_fight`(:pid, :fid, :ol, :cl)')
			->queryScalar(array(
				':pid' => $playerId, ':fid' => $id,
				':ol' => FightList::LIST_SIZE_ORDERED, ':cl' => FightList::LIST_SIZE_CHALLENGED,
			));
	}

//---------------------------------------------------------------------------
	public function getTurns() {
		$turns = str_split($this->getAttribute('turns'), 2);
		foreach ($turns as &$p) {
			$p = (ord(substr($p, 0, 1)) << 8) | ord(substr($p, 1, 1));
		}
		return $turns;
	}

//---------------------------------------------------------------------------
	public function setTurns($turns) {
		foreach ($turns as &$p) {
			$p = pack('n', $p);
		}
		$this->setAttribute('turn_count', count($turns);
		$this->setAttribute('turns', implode('', $turns));
	}

//---------------------------------------------------------------------------
	public function setStats($snakes) {
		$this->newStats = array();
		foreach ((array)$snakes as $index => $snake) {
			if (!$snake) continue;

			$stat = new SnakeStat();
			$stat->setFightSnake($this, $snake, $index);
			$this->newStats[] = $stat;
		}
	}

//---------------------------------------------------------------------------
	protected function onAfterInsert() {
		if (!$this->newStats) {
			throw new RuntimeException('требуется хотя бы одна змея');
		}

		$collection = new ActiveRecordCollection($this->newStats);
		if (!$collection->save()) {
			throw new RuntimeException('не могу сохранить змей');
		}
	}

//---------------------------------------------------------------------------
}