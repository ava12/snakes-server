<?php

/**
 * Змея.
 *
 * @property int $id
 * @property int $base_id
 * @property int $refs
 * @property int $current
 * @property int $player_id
 * @property string $name
 * @property string $type
 * @property int $skin_id
 * @property string $description
 * @property array $templates
 */
class Snake extends CActiveRecord {
	const TYPE_BOT = 'B';
	const TYPE_NORMAL = 'N';

	protected $newMaps = array();
	protected $needsRespawn = false;

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
	public function rules() {
		return array(
			array('player_id, name, type, skin_id, description, templates', 'safe', 'on' => 'insert'),
			array('name, type, skin_id, description, templates', 'safe', 'on' => 'update'),
			array('player_id, name, type, skin_id, templates', 'required', 'on' => 'insert'),
			array('player_id', 'exists', 'className' => 'Player', 'attributeName' => 'id'),
			array('skin_id', 'exists', 'className' => 'SnakeSkin', 'attributeName' => 'id'),
			array('name', 'length', 'min' => 1, 'max' => 40),
			array('name', 'unique', 'criteria' => array(
				'condition' => 'player_id = :pid', 'params' => array(':pid' => $this->player_id)
			)),
			array('type', 'in', 'range' => array(self::TYPE_BOT, self::TYPE_NORMAL)),
			array('description', 'length', 'min' => 0, 'max' => 1024),
		);
	}

//---------------------------------------------------------------------------
	public function getTemplates() {
		return explode(',', $this->attributes['templates']);
	}

//---------------------------------------------------------------------------
	public function setTemplates($templates) {
		if (!is_array($templates) or count($templates) <> 4) return false;

		foreach ($templates as &$p) {
			if (strspn($p, 'ABCDSTVWXYZ') <> strlen($p)) return false;

			$p = implode('', array_unique(str_split($p)));
		}
		unset($p);
		$this->attributes['templates'] = implode(',', $templates);
	}

//---------------------------------------------------------------------------
	public function scopes() {
		return array(
			'current' => 'current > 0',
		);
	}

//---------------------------------------------------------------------------
	public function types($types) {
		if (is_string($types)) $types = str_split($types);
		$types = array_unique($types);
		$this->getDbCriteria()->addInCondition('type', $types);
		return $this;
	}

//---------------------------------------------------------------------------
	public function byBaseId($baseId) {
		$this->getDbCriteria()->addColumnCondition('base_id', $baseId);
		return $this;
	}

//---------------------------------------------------------------------------
	public function forPlayer($playerId) {
		if (is_object($playerId)) $playerId = $playerId->id;
		$this->getDbCriteria()->addColumnCondition('player_id', $playerId);
		return $this;
	}

//---------------------------------------------------------------------------
	public function setMaps($maps) {
		$this->newMaps = $maps;
		$this->needsRespawn = true;
	}

//---------------------------------------------------------------------------
	public function setType($type) {
		if (!in_array($type, array(self::TYPE_BOT, self::TYPE_NORMAL))) {
			throw new UnexpectedValueException('неверный тип змеи');
		}

		if ($type == $this->type) return true;

		$this->type = $type;
		$this->needsRespawn = true;
	}

//---------------------------------------------------------------------------
	protected function getTransaction() {
		$db = $this->getDbConnection();
		$transaction = $db->currentTransaction;
		if ($transaction and $transaction->active) return false;
		else return $db->beginTransaction();
	}

//---------------------------------------------------------------------------
	public function insert() {
		if (!$this->newMaps) {
			throw new RuntimeException('требуется хотя бы одна карта');
		}

		$mapCollection = new ActiveRecordCollection($this->newMaps);
		$mapCollection->number('index');

		$transaction = $this->getTransaction();

		try {
			if (!parent::insert()) {
				throw new RuntimeException('не могу создать змею');
			}

			$snakeId = $this->id;
			$mapCollection->setDefaults(array('snake_id' => $snakeId));
			if (!$mapCollection->save()) {
				throw new RuntimeException('не могу сохранить карты');
			}

			if (!$this->updateByPk($snakeId, array('base_id' => $snakeId))) {
				throw new RuntimeException('не могу создать змею');
			}
		} catch (Exception $e) {
			if ($transaction) $transaction->rollback();
			throw $e;
		}

		if ($transaction) $transaction->commit();
		return true;
	}

//---------------------------------------------------------------------------
	public function update() {

	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}