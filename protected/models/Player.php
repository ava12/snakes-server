<?php

/**
 * Игрок.
 *
 * @property int $id
 * @property string $name
 * @property string $login
 * @property string $forum_login
 * @property string $hash SHA1(SHA1(логин ":" пароль) соль)
 * @property string $salt
 * @property int $sequence ++ при смене пароля или принудительном закрытии сессии
 * @property int $groups
 * @property int|NULL $fighter_id ид змеи-бойца
 * @property int|NULL $rating
 * @property int|NULL $delayed_id ид текущего рассчитываемого боя
 * @property int|NULL $viewed_id ид последнего просмотренного боя
 */
class Player extends CActiveRecord {
	const SALT_LENGTH = 8;

	const GROUP_PLAYER = 1;
	const GROUP_ADMIN = 2;
	const GROUP_ANON = 0x80000000;

	const GROUP_ANY = -1;

	protected $captcha;

//---------------------------------------------------------------------------
	/**
	 * @param string $className
	 * @return Player
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{player}}';
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'fighter' => array(self::BELONGS_TO, 'Snake', array('fighter_id' => 'base_id'),
				'condition' => 'fighter.current'),
			'snakes' => array(self::HAS_MANY, 'Snake', 'player_id',
				'order' => 'snakes.name ASC', 'condition' => 'snakes.current'),
		);
	}

//---------------------------------------------------------------------------
	public function scopes() {
		return array(
			'hasRating' => array('condition' => 'rating IS NOT NULL'),
		);
	}

//---------------------------------------------------------------------------
	public function attributeLabels() {
		return array(
			'login' => 'логин',
			'name' => 'имя',
			'hash' => 'хэш пароля',
			'salt' => 'соль хэша',
			'captcha' => 'проверочный код',
		);
	}

//---------------------------------------------------------------------------
	public function rules() {
		return array(
			array('login, name, forum_login, hash, salt, captcha', 'safe', 'on' => 'insert'),
			array('name, rating, fighter_id, delayed_id, viewed_id', 'safe', 'on' => 'update'),

			array('login, name, hash, salt', 'required', 'on' => 'insert',
				'message' => 'требуется поле "{attribute}"'),
			array('login', 'match', 'pattern' => '/^[a-z0-9_.-]{2,30}$/',
				'message' => 'логин может содержать только символы a-z0-9_.-'),
			array('login', 'unique',
				'message' => 'пользователь с таким логином уже зарегистрирован'),
			array('name', 'unique',
				'message' => 'пользователь с таким именем уже зарегистрирован'),
			array('name', 'length', 'min' => 2, 'max' => 40,
				'message' => 'допустимая длина имени - от 2 до 40 символов'),
			array('hash', 'match', 'pattern' => '/^[0-9a-f]{40}$/',
				'message' => 'некорректный хэш пароля'),
			array('salt', 'match', 'pattern' => '/^[0-9a-f]{8}$/',
				'message' => 'некорректная соль хэша'),
			array('captcha', 'validateCaptcha'),
		);
	}

//---------------------------------------------------------------------------
	public function validateCaptcha() {
		$session = Yii::app()->session;
		if (empty($session['captcha']) or $session['captcha'] <> $this->captcha) {
			$this->addError('captcha', 'неверный проверочный код');
		}
	}

//---------------------------------------------------------------------------
	public function checkLoginHash($hash, $timestamp) {
		if (!$this->hash) return false;

		return (abs(time() - $timestamp) <= Yii::app()->params['MaxTimestampDiff'] and
			$hash == sha1($this->hash . $timestamp));
	}

//---------------------------------------------------------------------------
	public function getLoginSalt($login) {
		$player = $this->findByAttributes(array('login' => $login));
		if ($player) return $player->salt;

		$secret = __FILE__;
		return substr(md5($secret . $login), 0, self::SALT_LENGTH);
	}

//---------------------------------------------------------------------------
	public function getRandomSalt() {
		$chars = '0123456789abcdef';
		$len = strlen($chars) - 1;
		$result = '';
		for ($i = self::SALT_LENGTH; $i > 0; $i--) {
			$result .= substr($chars, mt_rand(0, $len), 1);
		}
		return $result;
	}

//---------------------------------------------------------------------------
	public function getGroups() {
		$result = $this->groups;
		if (!$this->id) $result |= self::GROUP_ANON;
		return $result;
	}

//---------------------------------------------------------------------------
	public function isConfirmed() {
		return $this->hasGroup(self::GROUP_PLAYER);
	}

//---------------------------------------------------------------------------
	public function confirm() {
		$this->groups |= self::GROUP_PLAYER;
	}

//---------------------------------------------------------------------------
	public function hasGroup($group) {
		return (bool)($this->groups & $group);
	}

//---------------------------------------------------------------------------
}