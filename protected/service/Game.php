<?php

class Game {

	const VERSION = 1;
	const COMPATIBLE = 1;

	protected $request;
	protected $player;
	protected $ack = array('Response' => 'ack');

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
				'PlayerId' => $player->id,
				'PlayerName' => $player->name,
				'Rating' => $player->rating,
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
				'SkinId' => $snake->skin_id,
			);
		}

		return array(
			'Response' => 'player info',
			'PlayerId' => $player->id,
			'PlayerName' => $player->name,
			'Rating' => $player->rating,
			'PlayerSnakes' => $snakes,
		);
	}

//---------------------------------------------------------------------------
	protected function requestSkinList() {
		$list = array();
		foreach(SnakeSkin::model()->findAll() as $skin) {
			$list[] = array(
				'SkinId' => $skin->id,
				'SkinName' => $skin->name,
			);
		}
		return array(
			'Response' => 'skin list',
			'SkinList' => $list,
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
}