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
class Snake extends ActiveRecord {
	const TYPE_BOT = 'B';
	const TYPE_NORMAL = 'N';

	const MAX_SNAKES = 10;

	protected $magicGetters = array('templates' => false, 'needsRespawn' => false);
	protected $magicSetters = array('templates' => false, 'maps' => false, 'type' => false);

	protected $newMaps = array();
	protected $needsRespawn = false;
	protected $typeChanged = false;
	protected $isTemp = false;

//---------------------------------------------------------------------------
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
			'maps' => array(self::HAS_MANY, 'SnakeMap', 'snake_id', 'order' => 'maps.index'),
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
			array('name', 'unique', 'message' => 'змея с таким именем уже есть',
				'criteria' => array(
					'condition' => 'player_id = :pid AND current AND base_id <> :baseId',
					'params' => array(':pid' => $this->player_id, ':baseId' => (int)$this->base_id),
				)
			),
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
		if (is_array($templates)) {
			if (count($templates) <> 4) return false;

			foreach ($templates as &$p) {
				if (strspn($p, 'ABCDSTVWXYZ') <> strlen($p)) return false;

				$p = array_unique(str_split($p));
				sort($p);
				$p = implode('', $p);
			}
			unset($p);

			$templates = implode(',', $templates);
		}

		if ($templates == $this->attributes['templates']) return;

		$this->setAttribute('templates', $templates);
		$this->needsRespawn = true;
	}

//---------------------------------------------------------------------------
	public function scopes() {
		return array(
			'live' => array('condition' => 't.refs > 0'),
			'current' => array('condition' => 't.current > 0'),
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
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 't.base_id = ' . (int)$baseId . ' AND t.current',
		));
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
		$id = $this->id;
		$maps = array_values($maps);
		foreach ($maps as $map) $map->snake_id = NULL;
		$this->newMaps = $maps;
		$oldMaps = array_values($this->maps);
		if (count($maps) <> count($oldMaps)) $this->needsRespawn = true;
		if (!$this->needsRespawn) {
			foreach ($oldMaps as $index => $oldMap) {
				if (!$oldMap->sameAs($maps[$index])) {
					$this->needsRespawn = true;
					return;
				}
			}
		}
	}

//---------------------------------------------------------------------------
	public function setType($type) {
		if (!in_array($type, array(self::TYPE_BOT, self::TYPE_NORMAL))) {
			throw new UnexpectedValueException('неверный тип змеи');
		}

		if ($type == $this->type) return;

		$this->setAttribute('type', $type);
		$this->typeChanged = true;
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
	public function getNeedsRespawn() {
		return ($this->needsRespawn and !$this->getIsNewRecord());
	}

//---------------------------------------------------------------------------
	public function respawn() {
		if (!$this->needsRespawn or $this->getIsNewRecord()) return $this;

		$snake = new Snake();

		$attr = $this->getAttributes();
		unset($attr['id']);
		unset($attr['refs']);
		$snake->setAttributes($attr, false);
		$snake->typeChanged = $this->typeChanged;

		$sourceMaps = ($this->newMaps ? $this->newMaps : $this->maps);
		$maps = array();
		foreach ($sourceMaps as $map) {
			$maps[] = $map->copy();
		}
		$snake->setMaps($maps);

		return $snake;
	}

//---------------------------------------------------------------------------
	public function setIsTemp($isTemp = true) {
		$this->isTemp = $isTemp;
	}

//---------------------------------------------------------------------------
	protected function checkCanChangeType() {
		if (!$this->typeChanged or $this->type == self::TYPE_BOT) return;

		if (Player::model()->findByPk($this->player_id)->fighter_id == $this->base_id) {
			throw new NackException(NackException::ERR_CANNOT_REMOVE_FIGHTER, $this->base_id);
		}
	}

//---------------------------------------------------------------------------
	public function insert($attributes = NULL) {
		if (!$this->newMaps) {
			throw new RuntimeException('требуется хотя бы одна карта');
		}

		$mapCollection = new ActiveRecordCollection($this->newMaps);
		$mapCollection->number('index');
		$this->current = ($this->isTemp ? 0 : 1);
		$this->refs = 1;
		$baseId = $this->base_id;

		$transaction = $this->getTransaction();

		try {
			if ($baseId) {
				$this->checkCanChangeType();

				$this->updateAll(
					array('current' => 0, 'refs' => new CDbExpression('refs - 1')),
					'base_id = :id AND current',
					array(':id' => $this->base_id)
				);
			}

			$this->checkCanCreate();

//			if ($this->isTemp) $this->refs = 0; // release manually
			if (!parent::insert()) {
				throw new RuntimeException('не могу создать змею');
			}

			$snakeId = $this->id;
			$mapCollection->setDefaults(array('snake_id' => $snakeId));
			if (!$mapCollection->save()) {
				foreach ($mapCollection->getItems() as $index => $map) {
					$errors = $map->getErrors();
					if (!$errors) continue;

					$name = key($errors);
					$message = $errors[$name][0];
					throw new NackException(NackException::ERR_INVALID_MAP, array($index, $message));
				}

				throw new RuntimeException('не могу сохранить карты');
			}

			if (!$this->base_id) {
				if (!$this->updateByPk($snakeId, array('base_id' => $snakeId))) {
					throw new RuntimeException('не могу создать змею');
				}

				$this->base_id = $snakeId;
			}
		} catch (Exception $e) {
			if ($transaction) $transaction->rollback();
			throw $e;
		}

		if ($transaction) $transaction->commit();

		$this->newMaps = array();
		$this->needsRespawn = false;
		$this->typeChanged = false;

		return true;
	}

//---------------------------------------------------------------------------
	public function update($attributes = NULL) {
		if ($this->needsRespawn) return false;

		if ($this->newMaps) {
			$mapCollection = new ActiveRecordCollection($this->newMaps);
			$mapCollection->setColumns(array('snake_id', 'index', 'description'));
		}

		$transaction = $this->getTransaction();

		try {
			if (!parent::update()) {
				throw new RuntimeException('не могу обновить змею');
			}

			if ($this->newMaps and !$mapCollection->save()) {
				foreach ($this->newMaps as $index => $map) {
					if ($map->hasErrors()) {
						throw Util::makeValidationException($map, 'не могу обновить карту ' . $index);
					}
				}
				throw new RuntimeException('не могу обновить карты');
			}

		} catch (Exception $e) {
			if ($transaction) $transaction->rollback();
			throw $e;
		}

		if ($transaction) $transaction->commit();

		$this->newMaps = array();
		$this->needsRespawn = false;
		$this->typeChanged = false;

		return true;
	}

//---------------------------------------------------------------------------
	public function release($id = NULL) {
		if (!$id) $id = $this->id;
		$this->updateCounters(array('refs' => -1), 'id = ' . (int)$id);
	}

//---------------------------------------------------------------------------
	public function checkCanCreate($playerId = NULL) {
		if (!$playerId) $playerId = $this->player_id;
		if (Snake::model()->forPlayer($playerId)->current()->count() >= static::MAX_SNAKES) {
			throw new NackException(NackException::ERR_TOO_MANY_SNAKES, static::MAX_SNAKES);
		}
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}