<?php

class Game {

	const VERSION = 1;
	const COMPATIBLE = 1;

	protected $request;
	/** @var Player $player */
	protected $player;
	protected $ack = array('Response' => 'ack');

	protected $snakeFields = array(
		'SnakeName' => 'name', 'SnakeType' => 'type', 'SkinId' => 'skin_id',
		'ProgramDescription' => 'description', 'Templates' => 'templates',
	);
	protected $mapNames = array(
		'Description' => 'description', 'HeadX' => 'head_x', 'HeadY' => 'head_y',
	);

	public $debug = false;

//---------------------------------------------------------------------------
	public function __construct($request = NULL, $debug = false) {
		$this->request = ($request ? $request : $_POST);
		$this->debug = $debug;
	}

//---------------------------------------------------------------------------
	public function setPlayer($player) {
		if (!$player) return NULL;

		if (is_scalar($player)) {
			$playerId = $player;
			$player = Player::model()->findByPk($player);
			if (!$player) throw new RuntimeException('unknown player id: ' . $playerId);
		}

		$this->player = $player;
		return $this;
	}

//---------------------------------------------------------------------------
	public function setRequest($request) {
		$this->request = $request;
		return $this;
	}

//---------------------------------------------------------------------------
	protected function process() {
		try {
			$request = RequestValidator::validate($this->request);
			$this->request = $request;

			if (isset($request['Sid']) and !$this->player) {
				$this->player = Yii::app()->user->open($request['Sid'], true);
				if (!$this->player) return array('Response' => 'relogin');
			}

			$funcName = 'request' . str_replace(' ', '', ucwords($request['Request']));
			return $this->$funcName();
		}
		catch (NackException $e) {
			if ($this->debug) throw $e;

			return $e->asArray();
		}
		catch (Exception $e) {
			if ($this->debug) throw $e;

			return array(
				'Response' => 'error',
				'ErrorCode' => $e->getCode(),
				'Error' => $e->getMessage(),
			);
		}
	}

//---------------------------------------------------------------------------
	public function run() {
		$result = $this->process();
		if (isset($this->request['RequestId'])) {
			$result['RequestId'] = $this->request['RequestId'];
		}
		return $result;
	}

//---------------------------------------------------------------------------
	protected function requestPing() {
		return $this->ack;
	}

//---------------------------------------------------------------------------
	protected function requestInfo() {
		/** @var User $user */
		$user = Yii::app()->user;
		return array(
			'Response' => 'info',
			'Version' => self::VERSION,
			'Compatible' => self::COMPATIBLE,
			'Lifetime' => ($user->getIsClient() ? $user->clientLifetime : $user->serverLifetime),
		);
	}

//---------------------------------------------------------------------------
	protected function requestWhoami() {
		$player = $this->player;
		return array(
			'Response' => 'whoami',
			'PlayerId' => $player->id,
			'PlayerName' => $player->name,
			'FightId' => $player->delayed_id,
		);
	}

//---------------------------------------------------------------------------
	protected function requestLoginData() {
		$login = $this->request['Login'];
		return array(
			'Response' => 'login data',
			'Login' => $login,
			'Salt' => Player::model()->getLoginSalt($login),
			'Timestamp' => time(),
		);
	}

//---------------------------------------------------------------------------
	protected function requestLogin() {
		$request = $this->request;
		$identity = new UserIdentity($request['Login'], $request['Timestamp'], $request['Hash']);
		if (!$identity->authenticate()) {
			throw new NackException(NackException::ERR_WRONG_LOGIN);
		}

		$sid = Yii::app()->user->login($identity, true);
		if (!$sid) {
			throw new RuntimeException('невозможно открыть сеанс, попробуйте позже');
		}

		return array(
			'Response' => 'login',
			'Sid' => $sid,
			'PlayerId' => $identity->getId(),
			'PlayerName' => $identity->getName(),
		);
	}

//---------------------------------------------------------------------------
	protected function requestLogout() {
		Yii::app()->user->logout($this->request['Sid'], true);
		return $this->ack;
	}

//---------------------------------------------------------------------------
	/**
	 * @param CActiveRecord $model
	 * @param string $defaultSort
	 * @param array $sortedNames
	 * @return CActiveDataProvider
	 */
	protected function makeDataProvider(
		$model, $defaultSort = '<id', $sortedNames = array('id' => 'id')
	) {
		$request = $this->request;
		$count = (isset($request['Count']) ? $request['Count'] : 30);
		$firstIndex = (isset($request['FirstIndex']) ? $request['FirstIndex'] : 0);

		if (isset($request['SortBy'])) {
			$sortBy = $request['SortBy'];
		} else {
			$sortBy = (array)$defaultSort;
			$this->request['SortBy'] = $sortBy;
		}
		$sort = array();

		foreach($sortBy as $field) {
			$order = (substr($field, 0, 1) == '<' ? ' ASC' : ' DESC');
			$name = substr($field, 1);
			if (!isset($sortedNames[$name])) continue;

			$sort[] = $sortedNames[$name] . $order;
		}

		if ($sort) {
			$model->getDbCriteria()->mergeWith(array('order' => implode(', ', $sort)));
		}

		return new CActiveDataProvider($model, array('pagination' => array(
			'currentPage' => (int)($firstIndex / $count),
			'pageSize' => $count,
		)));
	}

//---------------------------------------------------------------------------
	protected function makeListResponse($response, $provider) {
		$result = (is_array($response) ? $response : array('Response' => $response));
		$totalCount = $provider->totalItemCount;
		return $result + array(
			'TotalCount' => (int)$totalCount,
			'FirstIndex' => (int)$provider->pagination->offset,
			'SortBy' => $this->request['SortBy'],
		);
	}

//---------------------------------------------------------------------------
	protected function requestRatings() {
		$model = Player::model()->hasRating()->with('fighter');
		$provider = $this->makeDataProvider($model, '>Rating',
			array('Rating' => 'rating', 'PlayerName' => 't.name'));
		$data = $provider->getData();
		$response = $this->makeListResponse('ratings', $provider);

		$ratings = array();
		foreach($data as $player) {
			$fighter = $player->fighter;
			$rating = $player->rating;
			$ratings[] = array(
				'PlayerId' => $player->id,
				'PlayerName' => $player->name,
				'Rating' => (isset($rating) ? (int)$rating : NULL),
				'SnakeId' => $fighter->id,
				'SnakeName' => $fighter->name,
				'SkinId' => (int)$fighter->skin_id,
			);
		}

		$response['Ratings'] = $ratings;
		return $response;
	}

//---------------------------------------------------------------------------
	protected function requestPlayerList() {
		$model = Player::model();
		$provider = $this->makeDataProvider($model, '<PlayerName',
			array('PlayerName' => 'name'));
		$data = $provider->getData();
		$response = $this->makeListResponse('player list', $provider);
		$list = array();

		foreach($data as $player) {
			$list[] = array(
				'PlayerId' => $player->id,
				'PlayerName' => $player->name,
				'Rating' => (int)$player->rating,
			);
		}

		$response['PlayerList'] = $list;
		return $response;
	}

//---------------------------------------------------------------------------
	protected function requestPlayerInfo() {
		$playerId = $this->request['PlayerId'];
		$player = Player::model()->findByPk($playerId);
		if (!$player) {
			throw new NackException(NackException::ERR_UNKNOWN_PLAYER, $playerId);
		}

		$snakes = array();
		foreach($player->snakes as $snake) {
			$snakes[] = array(
				'SnakeId' => $snake->id,
				'SnakeName' => $snake->name,
				'SnakeType' => $snake->type,
				'SkinId' => (int)$snake->skin_id,
			);
		}

		$rating = $player->rating;
		return array(
			'Response' => 'player info',
			'PlayerId' => $player->id,
			'PlayerName' => $player->name,
			'Rating' => (isset($rating) ? (int)$rating : NULL),
			'FighterId' => (isset($rating) ? $player->fighter_id : NULL),
			'PlayerSnakes' => $snakes,
		);
	}

//---------------------------------------------------------------------------
	protected function requestSkinList() {
		$list = array();
		foreach(SnakeSkin::model()->findAll() as $skin) {
			$list[] = array(
				'SkinId' => (int)$skin->id,
				'SkinName' => $skin->name,
			);
		}
		return array(
			'Response' => 'skin list',
			'SkinList' => $list,
		);
	}

//---------------------------------------------------------------------------
	protected function requestSnakeList() {
		$types = array_unique(str_split($this->request['SnakeTypes']));
		$model = Snake::model()->types($types)->with('player');

		$provider = $this->makeDataProvider($model, array('<SnakeName', '<PlayerName'),
			array('SnakeName' => 't.name', 'PlayerName' => 'player.name'));

		$data = $provider->getData();
		$response = $this->makeListResponse('snake list', $provider);
		$list = array();

		foreach($data as $snake) {
			$list[] = array(
				'SnakeId' => $snake->id,
				'SnakeName' => $snake->name,
				'SnakeType' => $snake->type,
				'SkinId' => (int)$snake->skin_id,
				'PlayerId' => $snake->player_id,
				'PlayerName' => $snake->player->name,
			);
		}

		$response['SnakeList'] = $list;
		$response['SnakeTypes'] = implode('', $types);
		return $response;
	}

//---------------------------------------------------------------------------
	protected function makeResponseMap($map) {
		$lines = $map->lines;
		$offset = strspn($lines, '-') >> 1;

		if ($offset >= 49) $lines = array();
		else $lines = array(
			array(
				'X' => $offset % 7,
				'Y' => (int)($offset / 7),
				'Line' => str_replace('--', '', $lines),
			),
		);

		return array(
			'Description' => $map->description,
			'HeadX' => (int)$map->head_x,
			'HeadY' => (int)$map->head_y,
			'Lines' => $lines,
		);
	}

//---------------------------------------------------------------------------
	protected function requestSnakeInfo() {
		$snakeId = $this->request['SnakeId'];
		$snake = Snake::model()->with('player')->findByPk($snakeId);
		if (!$snake) {
			throw new NackException(NackException::ERR_UNKNOWN_SNAKE);
		}

		$response = array(
			'Response' => 'snake info',
			'SnakeId' => $snake->id,
			'SnakeName' => $snake->name,
			'SnakeType' => $snake->type,
			'SkinId' => (int)$snake->skin_id,
			'PlayerId' => $snake->player_id,
			'PlayerName' => $snake->player->name,
		);

		if (
			$snake->type == Snake::TYPE_NORMAL and
			(!$this->player or $snake->player_id <> $this->player->id)
		) {
			return $response;
		}

		$maps = array();
		foreach ($snake->maps as $map) {
			$maps[] = $this->makeResponseMap($map);
		}

		$response += array(
			'ProgramDescription' => $snake->description,
			'Templates' => $snake->templates,
			'Maps' => $maps,
		);

		return $response;
	}

//---------------------------------------------------------------------------
	protected function requestSnakeAssign() {
		$snakeId = $this->request['SnakeId'];
		/** @var CDbTransaction $transaction */
		$transaction = Yii::app()->db->beginTransaction();

		try {
			$snake = Snake::model()->findByPk($snakeId);
			if (!$snake) {
				throw new NackException(NackException::ERR_UNKNOWN_SNAKE, $snakeId);
			}

			if ($snake->player_id <> $this->player->id) {
				throw new NackException(NackException::ERR_NOT_MY_SNAKE, $snakeId);
			}

			if ($snake->type <> Snake::TYPE_NORMAL) {
				throw new NackException(NackException::ERR_CANNOT_ASSIGN_BOT, $snakeId);
			}

			$this->player->fighter_id = $snakeId;
			if (!$this->player->save()) {
				throw new RuntimeException('не могу назначить бойца');
			}
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		$transaction->commit();
		return $this->ack;
	}

//---------------------------------------------------------------------------
	protected function requestSnakeDelete() {
		$snakeId = $this->request['SnakeId'];
		/** @var CDbTransaction $transaction */
		$transaction = Yii::app()->db->beginTransaction();

		try {
			$snake = Snake::model()->findByPk($snakeId);
			if (!$snake) {
				throw new NackException(NackException::ERR_UNKNOWN_SNAKE, $snakeId);
			}

			if ($snake->player_id <> $this->player->id) {
				throw new NackException(NackException::ERR_NOT_MY_SNAKE, $snakeId);
			}

			if ($snake->type == Snake::TYPE_NORMAL) {
				if (Player::model()->countByAttributes(array('fighter_id' => $snakeId))) {
					throw new NackException(NackException::ERR_CANNOT_REMOVE_FIGHTER, $snakeId);
				}
			}

			$snake->delete();
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		$transaction->commit();
		return $this->ack;
	}

//---------------------------------------------------------------------------
	/**
	 * @param Snake $snake
	 * @param array $request
	 */
	protected function editSnake($snake, $request) {
		foreach ($this->snakeFields as $requestName => $dbName) {
			if (array_key_exists($requestName, $request)) {
				$snake->$dbName = $request[$requestName];
			}
		}

		if (isset($request['Maps'])) {
			$maps = array();
			foreach ($request['Maps'] as $mapIndex => $mapFields) {
				$map = new SnakeMap;
				foreach ($this->mapNames as $requestName => $dbName) {
					$map->$dbName = $mapFields[$requestName];
				}
				foreach ($mapFields['Lines'] as $index => $line) {
					if (!$map->addLine($line['X'], $line['Y'], $line['Line'])) {
						$params = array($mapIndex, $index, $line['Line']);
						throw new NackException(NackException::ERR_INVALID_MAP_LINE, $params);
					}
				}
				$maps[] = $map;
			}
			$snake->setMaps($maps);
		}
	}

//---------------------------------------------------------------------------
	protected function requestSnakeNew() {
		$snake = new Snake;
		$snake->player_id = $this->player->id;
		$snake->checkCanCreate();
		$this->editSnake($snake, $this->request);

		if (!$snake->save()) {
			throw Util::makeValidationException($snake, 'не могу создать змею');
		}

		return array(
			'Response' => 'snake new',
			'SnakeId' => $snake->id,
		);
	}

//---------------------------------------------------------------------------
	protected function requestSnakeEdit() {
		$request = $this->request;
		$snakeId = $request['SnakeId'];
		$snake = Snake::model()->findByPk($snakeId);
		if (!$snake) {
			throw new NackException(NackException::ERR_UNKNOWN_SNAKE, $snakeId);
		}

		if ($snake->player_id <> $this->player->id) {
			throw new NackException(NackException::ERR_NOT_MY_SNAKE, $snakeId);
		}

		$this->editSnake($snake, $request);

		if (!$snake->save()) {
			throw Util::makeValidationException($snake, 'не могу отредактировать змею');
		}

		return $this->ack;
	}

//---------------------------------------------------------------------------
	protected function requestFightList() {
		$listType = $this->request['FightListType'];

		$fightList = FightEntry::model()->forPlayer($this->player->id)->byType($listType)
			->with('fight', 'fight.player')->findAll();

		$list = array();
		foreach ($fightList as $item) {
			$fight = $item->fight;

			$snakes = array(NULL, NULL, NULL, NULL);
			foreach ($item->fight->stats as $index => $stat) {
				$snake = $fight->snakes[$index];
				$snakes[$index] = array(
					'SnakeId' => $snake->id,
					'SnakeName' => $snake->name,
					'SnakeType' => $snake->type,
					'SkinId' => (int)$snake->skin_id,
					'PlayerId' => $snake->player_id,
					'PlayerName' => $snake->player->name,
				);
			}

			$list[] = array(
				'FightId' => $item->fight_id,
				'FightType' => $fight->type,
				'FightTime' => (int)$fight->time,
				'PlayerId' => $fight->player_id,
				'PlayerName' => $fight->player->name,
				'Snakes' => $snakes,
			);
		}

		return array(
			'Response' => 'fight list',
			'FightListType' => $listType,
			'FightList' => $list,
		);
	}

//---------------------------------------------------------------------------
	protected function updateRatings($fight) {
		foreach ($fight->stats as $index => $stat) {
			$rating = (int)$fight->snakes[$index]->player->rating;
			$stat->pre_rating = $rating;
			$stat->post_rating = $rating;
			$fight->snakes[$index]->player->rating = $rating;
		}
	}

//---------------------------------------------------------------------------
	/**
	 * @param Fight $fight
	 * @param DelayedFight $delayed
	 * @throws Exception
	 */
	protected function updateFight($fight, $delayed) {
		$fight->result = $delayed->result;
		$fight->turns = $delayed->turns;
		$snakes = $delayed->snakes;

		$stats = $fight->stats;

		foreach ($stats as $index => $stat) {
			$snake = $snakes[$index];
			$stat->result = $snake['Result'];
			$stat->length = count($snake['Coords']);
			$stat->debug = implode('', $snake['Debug']);
		}

		$isChallenge = ($fight->type == Fight::TYPE_CHALLENGE);

		if ($isChallenge) {
			$this->updateRatings($fight);
		}

		$player = $this->player;

		/** @var CDbTransaction $transaction */
		$transaction = Yii::app()->db->beginTransaction();

		try {
			if (!$fight->save()) {
				throw new RuntimeException('не могу сохранить бой');
			}

			$fightId = $fight->id;
			$entry = FightEntry::model();
			$entry->addFight($fightId, FightEntry::TYPE_ORDERED, $player->id);

			if ($isChallenge) {
				$ids = array();
				for ($index = 0; $index < 3; $index++) {
					$ids[] = $stats[$index]->snake->player_id;
				}
				$entry->addFight($fightId, FightEntry::TYPE_CHALLENGED, $ids);
			}

			$player->delayed_id = NULL;

			if ($isChallenge) {
				foreach ($fight->snakes as $index => $snake) {
					if (!$snake->player->save()) {
						throw new RuntimeException('не могу сохранить рейтинг для змеи ' . $index);
					}
				}
			} else {
				$player->save();
			}

			DelayedFight::model()->deleteByPk($fight->id);


		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		$transaction->commit();
	}

//---------------------------------------------------------------------------
	protected function requestFightInfo() {
		$fightId = $this->request['FightId'];
		$playerId = $this->player->id;

		if ($this->player->delayed_id == $fightId) {
			$delayed = DelayedFight::model()->findByPk($fightId);
			if (!$delayed->process()) {
				$delayed->save();

				return array(
					'Response' => 'fight delayed',
					'FightId' => $fightId,
				);
			}

			$fight = Fight::model()->forPlayer($playerId)->findByPk($fightId);
			$this->updateFight($fight, $delayed);
		} else {
			$fight = Fight::model()->live()->findByPk($fightId);
			if (!$fight or !$fight->isListed($playerId)) {
				throw new NackException(NackException::ERR_UNKNOWN_FIGHT, $fightId);
			}
		}

		$this->player->viewed_id = $fightId;
		$this->player->save();

		return $this->getFightInfo($fight);
	}

//---------------------------------------------------------------------------
	protected function getFightInfo($fight) {
		$fightId = $fight->id;
		$playerId = $this->player->id;
		$fightType = $fight->type;
		$stats = array(NULL, NULL, NULL, NULL);
		$snakes = $stats;

		foreach ($fight->stats as $index => $stat) {
			$snake = $fight->snakes[$index];

			$snakes[$index] = array(
				'SnakeId' => $snake->id,
				'SnakeName' => $snake->name,
				'SnakeType' => $snake->type,
				'SkinId' => (int)$snake->skin_id,
				'PlayerId' => $snake->player_id,
				'PlayerName' => $snake->player->name,
			);

			$entry = array(
				'Status' => $stat->result,
				'FinalLength' => (int)$stat->length,
			);

			if ($fightType == Fight::TYPE_CHALLENGE) $entry += array(
				'InitialRating' => (int)$stat->pre_rating,
				'FinalRating' => (int)$stat->post_rating,
			);

			if ($snake->player_id == $playerId or $snake->type == Snake::TYPE_BOT) {
				$maps = array();
				foreach($snake->maps as $map) {
					$maps[] = $this->makeResponseMap($map);
				}

				$entry += array(
					'ProgramDescription' => $snake->description,
					'Templates' => $snake->templates,
					'Maps' => $maps,
					'DebugData' => $stat->debug,
				);

			}

			$stats[$index] = $entry;
		} // foreach stats

		return array(
			'Response' => 'fight info',
			'FightId' => $fightId,
			'FightType' => $fightType,
			'FightTime' => (int)$fight->time,
			'FightResult' => $fight->result,
			'TurnLimit' => (int)$fight->turn_limit,
			'Turns' => $fight->turns,
			'Snakes' => $snakes,
			'SnakeStats' => $stats,
		);
	}

//---------------------------------------------------------------------------
	protected function createFight($snakes, $type, $turnLimit = NULL) {
		$playerId = $this->player->id;
		$fight = new Fight;
		$delayed = new DelayedFight;

		/** @var CDbTransaction $transaction */
		$transaction = Yii::app()->db->beginTransaction();

		try {
			$player = Player::model()->findByPk($playerId);
			if ($player->delayed_id) {
				throw new NackException(NackException::ERR_HAS_DELAYED, $player->delayed_id);
			}

			$fight->player_id = $this->player->id;
			$fight->type = $type;
			$fight->setSnakes($snakes);
			if ($turnLimit) $fight->turn_limit = $turnLimit;
			if (!$fight->save()) {
				throw new RuntimeException('не могу создать бой');
			}

			$delayed->fight_id = $fight->id;
			if (!$delayed->save()) {
				throw new RuntimeException('не могу создать расчет боя');
			}

			$this->player->delayed_id = $fight->id;
			if (!$this->player->save()) {
				throw new RuntimeException('не могу выполнить расчет боя');
			}

		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		$transaction->commit();
		return $delayed;
	}

//---------------------------------------------------------------------------
	protected function requestFightTest() {
		$request = $this->request;
		$tempSnake = new Snake;
		$tempSnake->player_id = $this->player->id;
		$tempSnake->type = Snake::TYPE_BOT;
		$this->editSnake($tempSnake, $request);
		if (!$tempSnake->validate()) {
			throw Util::makeValidationException($tempSnake, 'некорректное описание временной змеи');
		}

		$stats = $request['OtherSnakeIds'];
		array_unshift($stats, $tempSnake);

		$delayedFight = $this->createFight($stats, Fight::TYPE_TRAIN, $request['TurnLimit']);

		return array(
			'Response' => 'fight delayed',
			'FightId' => $delayedFight->fight_id,
		);
	}

//---------------------------------------------------------------------------
	protected function requestFightTrain() {
		$request = $this->request;
		$snakeIds = $request['SnakeIds'];
		$turnLimit = $request['TurnLimit'];

		$delayedFight = $this->createFight($snakeIds, Fight::TYPE_TRAIN, $turnLimit);

		return array(
			'Response' => 'fight delayed',
			'FightId' => $delayedFight->fight_id,
		);
	}

//---------------------------------------------------------------------------
	protected function requestFightChallenge() {
		if (!$this->player->fighter) {
			throw new NackException(NackException::ERR_CANNOT_CHALLENGE, $this->player->id);
		}

		$playerIds = $this->request['PlayerIds'];
		$players = Player::model()->with('fighter')->findAllByPk($playerIds);
		$playerIds = array_flip($playerIds);
		$fighters = array($this->player->fighter);

		foreach ($players as $player) {
			$playerId = $player->id;
			$fighters[$playerIds[$playerId] + 1] = $player->fighter;
			unset($playerIds[$playerId]);
		}

		if ($playerIds) {
			$playerIds = array_keys($playerIds);
			throw new NackException(NackException::ERR_CANNOT_CHALLENGE, $playerIds[0]);
		}

		$delayedFight = $this->createFight($fighters, Fight::TYPE_CHALLENGE);

		return array(
			'Response' => 'fight delayed',
			'FightId' => $delayedFight->fight_id,
		);
	}

//---------------------------------------------------------------------------
	protected function requestFightCancel() {
		$fightId = $this->player->delayed_id;
		if (!$fightId) return $this->ack;

		$this->player->delayed_id = NULL;

		/** @var CDbTransaction $transaction */
		$transaction = Yii::app()->db->beginTransaction();
		try {
			DelayedFight::model()->deleteByPk($fightId);
			$this->player->save();
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}
		$transaction->commit();

		return $this->ack;
	}

//---------------------------------------------------------------------------
	protected function requestSlotList() {
		$slots = FightSlot::model()->forPlayer($this->player->id)->with('fight')->findAll();
		$list = array_fill(0, 10, NULL);
		foreach ($slots as $index => $slot) {
			$fight = $slot->fight;
			$list[$index] = array(
				'SlotName' => $slot->name,
				'FightId' => $slot->fight_id,
				'FightType' => $fight->type,
				'FightTime' => (int)$fight->time,
			);
		}

		return array(
			'Response' => 'slot list',
			'SlotList' => $list,
		);
	}

//---------------------------------------------------------------------------
	protected function requestSlotView() {
		$slotIndex = (int)$this->request['SlotIndex'];
		$slot = FightSlot::model()->forPlayer($this->player->id)->byIndex($slotIndex)
			->with('fight')->find();
		if (!$slot) {
			throw new NackException(NackException::ERR_UNKNOWN_SLOT, $slotIndex);
		}

		$response = $this->getFightInfo($slot->fight);
		$response['Response'] = 'slot view';
		$response['SlotIndex'] = $slotIndex;
		$response['SlotName'] = $slot->name;
		unset($response['FightId']);

		return $response;
	}

//---------------------------------------------------------------------------
	protected function requestSlotRename() {
		$slotIndex = (int)$this->request['SlotIndex'];
		$slot = FightSlot::model()->forPlayer($this->player->id)->byIndex($slotIndex)->find();
		if (!$slot) {
			throw new NackException(NackException::ERR_UNKNOWN_SLOT, $slotIndex);
		}

		$slot->name = $this->request['SlotName'];
		$slot->save();
		return $this->ack;
	}

//---------------------------------------------------------------------------
	protected function requestSlotDelete() {
		$slotIndex = (int)$this->request['SlotIndex'];
		FightSlot::model()->forPlayer($this->player->id)->byIndex($slotIndex)->deleteAll();
		return $this->ack;
	}

//---------------------------------------------------------------------------
	protected function requestSlotSave() {
		$request = $this->request;
		$index = (int)$request['SlotIndex'];
		$name = $request['SlotName'];
		$fightId = $request['FightId'];
		$playerId = $this->player->id;

		/** @var CDbTransaction $transaction */
		$transaction = Yii::app()->db->beginTransaction();

		try {
			if (!Fight::model()->isListed($playerId, $fightId)) {
				throw new NackException(NackException::ERR_UNKNOWN_FIGHT, $fightId);
			}

			FightSlot::model()->forPlayer($playerId)->byIndex($index)->deleteAll();

			$slot = new FightSlot;
			$slot->player_id = $playerId;
			$slot->index = $index;
			$slot->name = $name;
			$slot->fight_id = $fightId;
			$slot->save();

		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		$transaction->commit();
		return $this->ack;
	}

//---------------------------------------------------------------------------
}