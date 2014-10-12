<?php

class ProfileController extends Controller {
	protected $allowedGroups = Player::GROUP_PLAYER;

//---------------------------------------------------------------------------
	public function actionIndex() {
		$this->render('index');
	}

//---------------------------------------------------------------------------
	public function actionChname() {
		if (!isset($_POST['name'])) throw new CHttpException(400);

		try {
			$this->player->name = trim($_POST['name']);
			if ($this->player->save(true)) {
				$result = array('Response' => 'ack');
			} else {
				$errors = $this->player->getErrors();
				$errors = array_shift($errors);
				$result = array(
					'Response' => 'nack',
					'Error' => ($errors ? $errors[0] : 'неизвестная ошибка'),
				);
			}
			$this->renderJson($result);

		} catch (Exception $e) {
			$this->renderJson(array(
				'Response' => 'error',
				'Error' => $e->getMessage(),
				'ErrorCode' => $e->getCode(),
			));
		}
	}

//---------------------------------------------------------------------------
	public function actionChpass() {
		foreach (array('Hash', 'Timestamp', 'HashDiff') as $name) {
			if (!isset($_POST[$name])) throw new CHttpException(400);
		}

		try {
			$player = $this->player;
			$player->hash = Util::hashXor($player->hash, $_POST['HashDiff']);
			if (!$player->checkLoginHash($_POST['Hash'], $_POST['Timestamp'])){
				$this->renderJson(array('Response' => 'nack', 'Error' => 'ошибка смены пароля'));
				return;
			}

			$player->authChanged();
			if (!$player->save()) {
				throw new Exception('не могу сохранить новый пароль');
			}

			Yii::app()->user->reopen();
			$this->renderJson(array('Response' => 'ack'));

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