<?php

class AdminController extends Controller {
	protected $allowedGroups = Player::GROUP_ADMIN;

//---------------------------------------------------------------------------
	public function actionIndex() {
		$this->render('index');
	}

//---------------------------------------------------------------------------
	public function actionPlayer($id = NULL) {
		if (!$id) {
			$provider = new CActiveDataProvider(Player::model(), array(
				'criteria' => array(
					'order' => '`id` ASC',
				),
				'pagination' => array(
					'currentPage' => (isset($_GET['page']) ? $_GET['page'] - 1 : 0),
					'pageSize' => 20,
				),
			));
			$this->render('players', array('provider' => $provider));
			return;
		}

		$player = Player::model()->findByPk($id);
		$this->render('player', array('admin' => $this->player, 'player' => $player));
	}

//---------------------------------------------------------------------------
	public function actionChpass() {
		$fields = array(
			'Login' => false,
			'Hash' => '/^[0-9a-f]{40}$/',
			'Timestamp' => '/^[0-9]+$/',
			'HashDiff' => '/^[0-9a-f]{40}$/',
		);
		foreach ($fields as $name => $regex) {
			if (!isset($_POST[$name]) or ($regex and !preg_match($regex, $_POST[$name]))) {
				throw new CHttpException(400);
			}
		}

		/** @var Player $player */
		$player = Player::model()->findByAttributes(array('login' => $_POST['Login']));
		if (!$player) throw new CHttpException(404);

		$player->hash = Util::hashXor($this->player->hash, $_POST['HashDiff']);
		if (!$player->checkLoginHash($_POST['Hash'], $_POST['Timestamp'])) {
			$this->renderJson(array('Response' => 'nack', 'Error' => 'некорректная смена пароля'));
			return;
		}

		$player->authChanged();
		if ($player->save(false)) {
			$this->renderJson(array('Response' => 'ack'));
		} else {
			$this->renderJson(array('Response' => 'error', 'Error' => 'не удалось обновить игрока'));
		}
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}