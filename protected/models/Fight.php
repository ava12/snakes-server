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
 * @property string $result
 * @property array $turns
 * @property Snake[] $snakes
 * @property SnakeStat[] $stats
 *
 * @method Fight live()
 */
class Fight extends ActiveRecord {
	const TYPE_TRAIN = 'train';
	const TYPE_CHALLENGE = 'challenge';

	const RESULT_NONE = '';
	const RESULT_LIMIT = 'limit';
	const RESULT_EATEN = 'eaten';
	const RESULT_BLOCKED = 'blocked';

	const DEFAULT_TURN_LIMIT = 1000;
	const MAX_TURN_LIMIT = 1000;

	protected $blobNames = array('snakes' => array('Snake'), 'stats' => array('SnakeStat'), 'turns');

	public $snakes = array();
	public $stats = array();
	public $turns = array();


//---------------------------------------------------------------------------
	/**
	 * @param string $className
	 * @return Fight
	 */
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
		);
	}

//---------------------------------------------------------------------------
	public function scopes() {
		return array(
			'live' => array('condition' => 't.refs > 0'),
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
	public function rules() {
		return array(
			array('type, player_id, turn_limit', 'safe', 'on' => 'insert'),
			array('type, player_id', 'required', 'on' => 'insert'),

			array('type', 'in', 'range' => array(self::TYPE_TRAIN, self::TYPE_CHALLENGE)),
			array('turn_limit', 'default', 'value' => self::DEFAULT_TURN_LIMIT),
			array('refs', 'default', 'value' => 1),
		);
	}

//---------------------------------------------------------------------------
	public function isListed($playerId = NULL, $id = NULL) {
		if (!$id) $id = $this->id;
		if (!$playerId) $playerId = $this->player_id;
		return (bool)$this->getDbConnection()
			->createCommand('SELECT `can_view_fight`(:pid, :fid)')
			->queryScalar(array(':pid' => $playerId, ':fid' => $id));
	}

//---------------------------------------------------------------------------
	public function setSnakes($snakes) {
		foreach ($snakes as $index => $snake) {
			if (!is_object($snake)) {
				$snake = Snake::model()->findByPk($snake);
			}
			if ($snake) {
				$this->snakes[$index] = $snake;
				$this->stats[$index] = new SnakeStat;
			}
		}
	}
//---------------------------------------------------------------------------
}