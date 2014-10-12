<?php

class IndexController extends Controller {
	protected $accessRights = array(
		'login' => Player::GROUP_ANON,
	);

//---------------------------------------------------------------------------
	public function actionIndex() {
		$model = Player::model()->hasRating();//->with('fighter');
		$provider = new CActiveDataProvider($model, array(
			'criteria' => array(
				'order' => '`rating` DESC, `t`.`id` ASC',
			),
			'pagination' => array(
				'currentPage' => (isset($_GET['page']) ? $_GET['page'] - 1 : 0),
				'pageSize' => 20,
			),
		));

		$this->render('index', array('provider' => $provider));
	}

//---------------------------------------------------------------------------
	public function actionLogindata() {
		$player = $this->player;
		if (!$player and (!isset($_POST['Login']) or !preg_match('/^[a-z0-9_.-]{2,}$/', $_POST['Login']))) {
			$this->renderJson(array(
				'Response' => 'nack',
				'Error' => 'требуется логин',
			));
			return;
		}

		$login = (isset($_POST['Login']) ? $_POST['Login'] : $player->login);
		$this->renderJson(array(
			'Response' => 'login data',
			'Login' => $login,
			'Salt' => Player::model()->getLoginSalt($login),
			'Timestamp' => time(),
		));
	}

//---------------------------------------------------------------------------
	public function actionLogin() {
		try {
			$fields = array(
				'Login' => 'требуется логин',
				'Hash' => 'требуется хэш',
				'Timestamp' => 'требуется метка времени',
			);

			foreach ($fields as $name => &$p) {
				if (!isset($_POST[$name])) {
					$this->renderJson(array(
						'Response' => 'nack',
						'Error' => $p,
					));
					return;
				}

				$p = $_POST[$name];
			}

			$identity = new UserIdentity($fields['Login'], $fields['Timestamp'], $fields['Hash']);
			if ($identity->authenticate() and Yii::app()->user->login($identity)) {
				$this->renderJson(array(
					'Response' => 'ack',
				));
			} else {
					$this->renderJson(array(
						'Response' => 'nack',
						'Error' => 'неверный логин или пароль',
					));
			}
		} catch (Exception $e) {
			$this->renderJson(array(
				'Response' => 'error',
				'Error' => $e->getMessage(),
				'ErrorCode' => $e->getCode(),
			));
		}
	}

//---------------------------------------------------------------------------
	public function actionLogout() {
		Yii::app()->user->logout();
		$this->redirect(BASE_URL);
	}

//---------------------------------------------------------------------------
	public function actionCaptcha() {
		Yii::app()->session->open(3600);
		$captcha = new KCAPTCHA;
		Yii::app()->session['captcha'] = $captcha->getKeyString();
	}

//---------------------------------------------------------------------------
	public function actionRegister() {
		$player = $this->player;
		if ($player and !$player->hasGroup(Player::GROUP_ANON)) {
			$this->redirect(BASE_URL);
		}

		if (!$_POST) {
			Yii::app()->session->open();
			$this->render('register');
			return;
		}

		Yii::app()->session->open();

		try {
			$player = new Player;
			$player->setAttributes($_POST);
			if (!$player->save()) {
				$this->renderJson(array(
					'Response' => 'nack',
					'Errors' => $player->getErrors(),
				));
				return;
			}

			Yii::app()->session->close();
			Yii::app()->session->destroy();
			$this->renderJson(array(
				'Response' => 'register',
				'Login' => $player->login,
				'Salt' => $player->salt,
				'Timestamp' => time(),
			));
		} catch (Exception $e) {
			$this->renderJson(array(
				'Response' => 'error',
				'Error' => $e->getMessage(),
				'ErrorCode' => $e->getCode(),
			));
		}
	}

//---------------------------------------------------------------------------
}