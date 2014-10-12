<?php

class Controller extends CController {
	protected $allowedGroups = Player::GROUP_ANY;

	protected $accessRights = array();

	protected $player = null;

//---------------------------------------------------------------------------
	protected function accessAllowed($actionName) {
		$groups = (
			isset($this->accessRights[$actionName])
			? $this->accessRights[$actionName]
			: $this->allowedGroups
		);
		if ($this->player) {
			$groups &= $this->player->getGroups();
		} else {
			$groups &= Player::GROUP_ANON;
		}
		return (bool)$groups;
	}

//---------------------------------------------------------------------------
	protected function beforeAction() {
		$this->player = Yii::app()->user->getPlayer();

		if (!defined('BASE_URL')) {
			$baseUrl = Yii::app()->getBaseUrl();
			if (substr($baseUrl, -1) <> '/') {
				$baseUrl .= '/';
			}
			define('BASE_URL', $baseUrl);
		}

		$route = explode('/', $this->route);
		$action = array_pop($route);
		if (!$this->accessAllowed($action)) {
			throw new CHttpException('403');
		}

		return true;
	}

//---------------------------------------------------------------------------
	public function renderJson($data) {
		header('Content-Type: text/json', true);
		echo json_encode($data);
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}