<?php

class Game {

	const VERSION = 1;
	const COMPATIBLE = 1;

	protected $request;
	protected $player;
	protected $ack = array('Response' => 'ack');

	protected $snakeFields = array(
		'SnakeName' => 'name', 'SnakeType' => 'type', 'SkinId' => 'skin_id',
		'ProgramDescription' => 'description', 'Templates' => 'templates',
	);
	protected $mapNames = array(
		'Description' => 'description', 'HeadX' => 'head_x', 'HeadY' => 'head_y',
	);

//---------------------------------------------------------------------------
	public function __construct($request = NULL) {
		$this->request = ($request ? $request : $_POST);
	}

//---------------------------------------------------------------------------
	protected function process() {
		try {
			RequestValidator::validate($this->request);

			$request = $this->request;
			if (isset($request['Sid'])) {
				$this->player = Yii::app()->user->open($request['Sid'], true);
				if (!$this->player) return array('Response' => 'relogin');
			}

			$funcName = 'request' . str_replace(' ', '', ucwords($request['Request']));
			return $this->$funcName();
		}
		catch (NackException $e) {
			return $e->asArray();
		}
		catch (Exception $e) {
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
	protected function makeDataProvider(
		$model, $defaultSort = '<id', $sortedNames = array('id' => 'id')
	) {
		$request = $this->request;
		$count = (isset($request['Count']) ? $request['Count'] : 30);
		$firstIndex = (isset($request['FirstIndex']) ? $request['FirstIndex'] : 0);

		$sortBy = (isset($request['SortBy']) ? $request['SortBy'] : (array)$defaultSort);
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
			'TotalCount' => $totalCount,
			'FirstIndex' => $provider->pagination->offset,
			'SortBy' => $this->request['SortBy'],
		);
	}

//---------------------------------------------------------------------------
	protected function requestRatings() {
		$model = Player::model()->hasRating()->with('fighter');
		$provider = $this->makeDataProvider($model, '>Rating',
			array('Rating' => 'rating', 'PlayerName' => 'name'));
		$data = $provider->getData();
		$response = array('ratings', $provider);

		$ratings = array();
		foreach($data as $player) {
			$fighter = $player->fighter;
			$ratings[] = array(
				'PlayerId' => $player->id,
				'PlayerName' => $player->name,
				'Rating' => $player->rating,
				'SnakeId' => $fighter->base_id,
				'SnakeName' => $fighter->name,
				'SkinId' => $fighter->skin_id,
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
				'Id' => $player->id,
				'Name' => $player->name,
				'Rating' => (int)$player->rating,
			);
		}

		$response['PlayerList'] = $list;
		return $response;
	}

//---------------------------------------------------------------------------
	protected function requestPlayerInfo() {
		$playerId = $this->request['PlayerId'];
		$player = Player::model()->with('snakes')->findByPk($playerId);
		if (!$player) {
			throw new NackException(NackException::ERR_UNKNOWN_PLAYER, $playerId);
		}

		$snakes = array();
		foreach($player->snakes as $snake) {
			$snakes[] = array(
				'SnakeId' => $snake->base_id,
				'SnakeName' => $snake->name,
				'SnakeType' => $snake->type,
				'SkinId' => (int)$snake->skin_id,
			);
		}

		return array(
			'Response' => 'player info',
			'PlayerId' => $player->id,
			'PlayerName' => $player->name,
			'Rating' => (int)$player->rating,
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
		$model = Snake::model()
			->current()
			->types($this->request['SnakeTypes'])
			->with('player');

		$provider = $this->makeDataProvider($model, '<SnakeName',
			array('SnakeName' => 'name'));

		$data = $provider->getData();
		$response = $this->makeListResponse('snake list', $provider);
		$list = array();

		foreach($data as $snake) {
			$list[] = array(
				'Id' => $snake->base_id,
				'Name' => $snake->name,
				'Type' => $snake->type,
				'Skin' => (int)$snake->skin_id,
				'PlayerId' => $snake->player_id,
				'PlayerName' => $snake->player->name,
			);
		}

		$response['SnakeList'] = $list;
		return $response;
	}

//---------------------------------------------------------------------------
	protected function makeResponseMap($map) {
		$lines = $map->lines;
		$offset = strspn($lines, '-') >> 1;

		return array(
			'Description' => $map->description,
			'HeadX' => (int)$map->head_x,
			'HeadY' => (int)$map->head_y,
			'Lines' => array(
				array(
					'X' => $offset % 7,
					'Y' => (int)($offset / 7),
					'Line' => str_replace('--', '', $lines),
				),
			),
		);
	}

//---------------------------------------------------------------------------
	protected function requestSnakeInfo() {
		$snakeId = $this->request['SnakeId'];
		$snake = Snake::model()->current()->byBaseId($snakeId)->with('player')->find();
		if (!$snake) {
			throw new NackException(NackException::ERR_UNKNOWN_SNAKE);
		}

		$response = array(
			'Response' => 'snake info',
			'SnakeId' => $snake->base_id,
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
			'Templates' => $snake->getTemplates(),
			'Maps' => $maps,
		);

		return $response;
	}

//---------------------------------------------------------------------------
	protected function requestSnakeAssign() {
		$snakeId = $this->request['SnakeId'];
		$transaction = Yii::app()->db->beginTransaction();

		try {
			$snake = Snake::model()->current()->byBaseId($snakeId)->find();
			if (!$snake) {
				throw new NackException(NackException::ERR_UNKNOWN_SNAKE, $snakeId);
			}

			if ($snake->player_id <> $this->player->id) {
				throw new NackException(NackException::ERR_NOT_MY_SNAKE, $snakeId);
			}

			if ($snake->type <> Snake::TYPE_NORMAL) {
				throw new NackException(NackException::ERR_CANNOT_ASSIGN_BOT, $snakeId);
			}

			$this->player->update(array('fighter_id' => $snakeId));
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
		$transaction = Yii::app()->db->beginTransaction();

		try {
			$snake = Snake::model()->current()->byBaseId($snakeId)->find();
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

			$snake->update(array('current' => 0, 'refs' => new CDbExpression('refs - 1')));
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		$transaction->commit();
		return $this->ack;
	}

//---------------------------------------------------------------------------
	protected function requestSnakeAdd() {
		$request = $this->request;
		$snake = new Snake;
		$snake->player_id = $this->player->id;

		foreach ($this->snakeFields as $requestName => $dbName) {
			$snake->$dbName = $request[$requestName];
		}

		$maps = array();
		foreach ($request['Maps'] as $mapFields) {
			$map = new SnakeMap();
			foreach ($this->mapNames as $requestName => $dbName) {
				$map->$dbName = $mapFields[$requestName];
			}
			foreach ($mapFields['Lines'] as $line) {
				$map->addLine($line['X'], $line['Y'], $line['Line']);
			}
			$maps[] = $map;
		}

		$snake->setMaps($maps);
		if (!$snake->save()) {
			throw new RuntimeException('не могу создать змею');
		}

		return array(
			'Response' => 'snake new',
			'SnakeId' => $snake->base_id,
		);
	}

//---------------------------------------------------------------------------
	protected function requestSnakeEdit() {
		$request = $this->request;
		$snakeId = $request['SnakeId'];
		$snake = Snake::model()->current()->byBaseId($snakeId)->find();
		if (!$snake) {
			throw new NackException(NackException::ERR_UNKNOWN_SNAKE, $snakeId);
		}

		if ($snake->player_id <> $this->player->id) {
			throw new NackException(NackException::ERR_NOT_MY_SNAKE, $snakeId);
		}

		foreach ($this->snakeFields as $requestName => $dbName) {
			if (array_key_exists($requestName, $request) {
				$snake->$dbName = $request[$requestName];
			}
		}

		if (isset($request['Maps'])) {
			$maps = array();
			foreach ($request['Maps'] as $mapFields) {
				$map = new SnakeMap();
				foreach ($this->mapNames as $requestName => $dbName) {
					$map->$dbName = $mapFields[$requestName];
				}
				foreach ($mapFields['Lines'] as $line) {
					$map->addLine($line['X'], $line['Y'], $line['Line']);
				}
				$maps[] = $map;
			}

			$snake->setMaps($maps);
		}

		if ($snake->needsRespawn) {
			$snake = $snake->respawn();
		}

		if (!$snake->save()) {
			throw new RuntimeException('не могу отредактировать змею');
		}

		return $this->ack;
	}

//---------------------------------------------------------------------------
	protected function requestFightList() {
		$listType = $this->request['FightListType'];

		$fightList = FightList::model()->forPlayer($this->player->id)->byType($listType)
			->with(
				'fight', 'fight.player', 'snake_stats', 'snake_stats.snake',
				'snake_stats.snake.player'
			)->findAll();

		$list = array();
		foreach ($fightList as $item) {
			$fight = $item->fight;

			$snakes = array(NULL, NULL, NULL, NULL);
			foreach ($item->snake_stats as $index => $stat) {
				$snake = $stat->snake;
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
	protected function requestFightInfo() {
		$fightId = $this->request['FightId'];

		if ($this->player->delayed_id == $fightId) {
			return array(
				'Response' => 'fight delayed',
				'FightId' => $fightId,
			);
		}

		$playerId = $this->player->id;
		$fight = Fight::model()->forPlayer($playerId)
			->with('snake_stats', 'snake_stats.snake', 'snake_stats.snake.player', 'snake_stats.maps')
			->findByPk($fightId);

		if (!$fight or !$fight->isListed($playerId)) {
			throw new NackException(NackException::ERR_UNKNOWN_FIGHT, $fightId);
		}

		$fightType = $fight->type;
		$stats = array(NULL, NULL, NULL, NULL);
		$snakes = $stats;
		foreach ($fight->snake_stats as $index => $stat) {
			$snake = $stat->snake;

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
				'FinalLength' => (int)$length,
			);

			if ($fightType == Fight::TYPE_CHALLENGE) $entry += array(
				'InitialRating' => (int)$stat->pre_rating,
				'FinalRating' => (int)$stat->post_rating,
			);

			if ($snake->player_id == $playerId or $snake->type == Snake::TYPE_BOT) {
				$maps = array();
				foreach($stat->maps as $map) {
					$maps[] = $this->makeResponseMap($map);
				}

				$entry += array(
					'ProgramDescription' => $snake->description,
					'Templates' => $snake->templates,
					'Maps' => $maps,
					'DebugData' => $stat->debug,
				);

				$stats[$index] = $entry;
			}
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


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}