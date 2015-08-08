<?php

class UserIdentity implements IUserIdentity {
	protected $login;
	protected $timestamp;
	protected $hash;

	protected $player;

//---------------------------------------------------------------------------
	public function __construct($login, $timestamp, $hash) {
		if (!$login or !$hash or
			abs(time() - $timestamp) > Yii::app()->params['MaxTimestampDiff'])
		{
			return;
		}

		$this->login = $login;
		$this->timestamp = $timestamp;
		$this->hash = $hash;
	}

//---------------------------------------------------------------------------
	public function authenticate() {
		if (!$this->login) return false;

		/** @var Player $player */
		$player = Player::model()->findByAttributes(array('login' => $this->login));
		if (!$player or
			!$player->checkLoginHash($this->hash, $this->timestamp)) return false;

		$this->player = $player;
		return true;
	}

//---------------------------------------------------------------------------
	public function getId() {
		return @$this->player->id;
	}

//---------------------------------------------------------------------------
	public function getIsAuthenticated() {
		return isset($this->player);
	}

//---------------------------------------------------------------------------
	public function getName() {
		return @$this->player->name;
	}

//---------------------------------------------------------------------------
	public function getPersistentStates() {
		return array();
	}

//---------------------------------------------------------------------------
	public function getPlayer() {
		return $this->player;
	}
//---------------------------------------------------------------------------
}