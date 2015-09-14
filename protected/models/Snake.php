<?php

/**
 * Змея.
 *
 * @property int $id
 * @property int $player_id
 * @property string $name
 * @property string $type
 * @property int $skin_id
 * @property string $description
 *
 * @property array $templates
 * @property SnakeMap[] $maps
 * @property Player $player
 */
class Snake extends ActiveRecord {
	const TYPE_BOT = 'B';
	const TYPE_NORMAL = 'N';

	const MAX_SNAKES = 10;
	const MAX_MAPS = 9;

	protected $magicGetters = array('templates' => false, 'maps' => false);
	protected $magicSetters = array('templates' => false, 'maps' => false, 'type' => false);
	protected $blobNames = array('templates', 'maps' => array('SnakeMap'));

	protected $templates = array('S', 'S', 'S', 'S');
	protected $maps = array();

	protected $typeChanged = false;

//---------------------------------------------------------------------------
	/**
	 * @param string $className
	 * @return Snake
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{snake}}';
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'player' => array(self::BELONGS_TO, 'Player', 'player_id'),
		);
	}

//---------------------------------------------------------------------------
	public function rules() {
		return array(
			array('player_id, name, type, skin_id, description, templates, maps, data', 'safe', 'on' => 'insert'),
			array('name, type, skin_id, description, templates, maps, data', 'safe', 'on' => 'update'),
			array('player_id, name, type, skin_id, templates, maps', 'required', 'on' => 'insert'),
			array('player_id', 'exists', 'className' => 'Player', 'attributeName' => 'id'),
			array('skin_id', 'exists', 'className' => 'SnakeSkin', 'attributeName' => 'id'),
			array('name', 'length', 'min' => 1, 'max' => 40),
			array('name', 'unique', 'message' => 'змея с таким именем уже есть',
				'criteria' => array('condition' => 'player_id = :pid', 'params' => array(':pid' => $this->player_id))),
			array('type', 'in', 'range' => array(self::TYPE_BOT, self::TYPE_NORMAL)),
			array('description', 'length', 'min' => 0, 'max' => 1024),
			array('maps', 'ArrayValidator', 'min' => 1, 'max' => static::MAX_MAPS, 'class' => 'SnakeMap'),
			array('maps', 'validateComponent'),
		);
	}

//---------------------------------------------------------------------------
	public function getTemplates() {
		return $this->templates;
	}

//---------------------------------------------------------------------------
	public function getMaps() {
		return $this->maps;
	}

//---------------------------------------------------------------------------
	public function setTemplates($templates) {
		if (!is_array($templates)) {
			$templates = explode(',', $templates);
		}
		if (count($templates) <> 4) return false;

		foreach ($templates as &$p) {
			if (strspn($p, 'ABCDSTVWXYZ') <> strlen($p)) return false;

			$p = array_unique(str_split($p));
			sort($p);
			$p = implode('', $p);
		}
		unset($p);

		if ($templates == $this->templates) return NULL;

		$this->templates = $templates;
		return true;
	}

//---------------------------------------------------------------------------
	public function types($types) {
		if (is_string($types)) $types = str_split($types);
		$types = array_unique($types);
		$this->getDbCriteria()->addInCondition('type', $types);
		return $this;
	}

//---------------------------------------------------------------------------
	public function forPlayer($playerId) {
		if (is_object($playerId)) $playerId = $playerId->id;
		$this->getDbCriteria()->addColumnCondition(array('player_id' => $playerId));
		return $this;
	}

//---------------------------------------------------------------------------
	public function setMaps($maps) {
		$this->maps = array_values($maps);
	}

//---------------------------------------------------------------------------
	public function setType($type) {
		if ($type == $this->getAttribute('type')) return;

		$this->setAttribute('type', $type);
		$this->typeChanged = true;
	}

//---------------------------------------------------------------------------
	protected function getTransaction() {
		$db = $this->getDbConnection();
		$transaction = $db->currentTransaction;
		if ($transaction and $transaction->active) return false;
		else return $db->beginTransaction();
	}

//---------------------------------------------------------------------------
	protected function checkCanChangeType() {
		if (!$this->typeChanged or $this->type == self::TYPE_BOT) return;

		if (Player::model()->findByPk($this->player_id)->fighter_id == $this->id) {
			throw new NackException(NackException::ERR_CANNOT_REMOVE_FIGHTER, $this->id);
		}
	}

//---------------------------------------------------------------------------
	public function checkCanCreate($playerId = NULL) {
		if (!$playerId) $playerId = $this->player_id;
		if (Snake::model()->forPlayer($playerId)->count() >= static::MAX_SNAKES) {
			throw new NackException(NackException::ERR_TOO_MANY_SNAKES, static::MAX_SNAKES);
		}
	}

//---------------------------------------------------------------------------
	protected function beforeValidate() {
		if ($this->getIsNewRecord()) $this->checkCanCreate();
		else $this->checkCanChangeType();

		return true;
	}

//---------------------------------------------------------------------------
	public function asArray() {
		$maps = array();
		foreach ($this->maps as $map) $maps[] = $map->asArray();
		$result = $this->getAttributes();
		$result['data'] = $this->serialize(array(
			'templates' => $this->templates,
			'maps' => $maps,
		));
		return $result;
	}

//---------------------------------------------------------------------------
}