<?php

class User extends CApplicationComponent implements IWebUser {

	public $serverLifetime;
	public $clientLifetime;
	public $loginUrl = '/login';
	public $serverSessionName = 's';
	public $sessionTable = '{{session}}';

	const SID_LENGTH = 32;
	const FLAG_IS_CLIENT = 1;

	protected $player;
	protected $sid;
	protected $isClient = true;

//---------------------------------------------------------------------------
	public function checkAccess($operation, $params = array()) {
		return true;
	}

//---------------------------------------------------------------------------
	public function getId() {
		return @$this->player->id;
	}

//---------------------------------------------------------------------------
	public function getIsGuest() {
		return (bool)$this->player;
	}

//---------------------------------------------------------------------------
	public function getName() {
		return @$this->player->name;
	}

//---------------------------------------------------------------------------
	public function loginRequired() {
		Yii::app()->getRequest()->redirect($this->loginUrl);
	}

//---------------------------------------------------------------------------
	public function getIsClient() {
		return $this->isClient;
	}

//---------------------------------------------------------------------------
	protected function refreshCookie() {
		$sid = $this->sid;
		$timestamp = $time() + $this->serverLifetime;
		$name = $this->serverSessionName;
		Yii::app()->request->cookies->add(
			new CHttpCookie($name, $sid, array(
				'expire' => $timestamp, 'httpOnly' => true,
			))
		);
	}

//---------------------------------------------------------------------------
	public function init() {
		parent::init();

		$this->sessionTable = preg_replace(
			'/^\\{\\{([a-z0-9_]+)\\}\\}$/',
			Yii::app()->db->tablePrefix . '$1',
			$this->sessionTable
		);

		$request = Yii::app()->getRequest();
		$sid = $request->getParam($this->serverSessionName);
		if ($sid) {
			$this->sid = $sid;
			$this->player = $this->open($this->sid, false);
			$this->isClient = false;
		} elseif ($request->getParam('Sid')) {
			$this->sid = $request->getParam('Sid');
			$this->player = $this->open($this->sid, true);
		}
	}

//---------------------------------------------------------------------------
	public function open($sid, $isClientSid = false) {
		if (strlen($sid) <> self::SID_LENGTH or
			!preg_match('/^[0-9a-z]+$/', $sid)) return NULL;

		$fieldName = ($isClientSid ? 'cid' : 'sid');

		$db = Yii::app()->getDb();
		$row = $db->createCommand()
			->from($this->sessionTable)
			->where('and', "$fieldName = '$sid'", 'expires <= ' . time())
			->queryRow();
		if (!$row) return NULL;

		$player = Player::model()
			->findByAttributes(array('id' => $row['player_id'], 'sequence' => $row['sequence']));
		if (!$player) {
			$db->createCommand()
				->delete($this->sessionTable, "$fieldName = '$sid'");
			return NULL;
		}

		$seconds = ($row['flags'] & self::FLAG_IS_CLIENT ? $this->clientLifetime : $this->serverLifetime);
		$db->createCommand()
			->update($this->sessionTable,
				array('expires' => time() + $seconds),
				"$fieldName = '$sid'");

		if (!$isClientSid) $this->refreshCookie();
		return $player;
	}

//---------------------------------------------------------------------------
	public function login($identity, $isClient = false) {
		$player = $identity->getPlayer();
		if (!$player) return false;

		$seconds = ($isClient ? $this->clientLifetime : $this->serverLifetime);
		$cmd = Yii::app()->db->createCommand();
		$cols = array(
			'player_id' => $player->id,
			'sequence' => $player->sequence,
			'flags' => ($isClient ? self::FLAG_IS_CLIENT : 0),
			'expires' => time() + (int)$seconds,
			'sid' => '', 'cid' => ''
		);
//		$sm = Yii::app()->getSecurityManager();

		for($retry = 5; $retry > 0; $retry++) {
			try {
				foreach(array('sid', 'cid') as $name) {
//					$cols[$name] = md5($cols[$name] . $sm->generateRandomBytes(16));
					$cols[$name] = md5($cols[$name] . microtime(true) . mt_rand());
				}
				$cmd->insert($this->sessionTable, $cols);

				$this->player = $player;
				$this->isClient = $isClient;
				$this->sid = $cols['sid'];
				if (!$isClient) $this->refreshCookie();
				return $cols['cid'];
			}
			catch(CDbException $e) {}
		}

		return false;
	}

//---------------------------------------------------------------------------
	public function logout($sid = NULL, $isClient = false) {
		if (!isset($sid)) {
			if ($this->sid) $sid = $this->sid;
			else return;
		}

		$fieldName = ($isClient ? 'cid' : 'sid');
		Yii::app()->db->createCommand()
			->delete($this->sessionTable, array($fieldName => $sid));

		if (!$isClient and $sid == $this->sid) {
			$this->sid = NULL;
			$this->player = NULL;
		}
	}

//---------------------------------------------------------------------------
}