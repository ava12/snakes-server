<?php

/**
 * Игрок.
 *
 * @property int $id
 * @property string $name
 * @property string $login
 * @property string $hash SHA1(SHA1(логин ":" пароль) соль)
 * @property string $salt
 * @property int $sequence ++ при смене пароля или принудительном закрытии сессии
 * @property int|NULL $fighter_id ид змеи-бойца
 * @property int|NULL $rating
 * @property int|NULL $delayed_id ид текущего рассчитываемого боя
 * @property int|NULL $viewed_id ид последнего просмотренного боя
 */
class Player extends CActiveRecord {
	const SALT_LENGTH = 8;

//---------------------------------------------------------------------------
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
	public function rules() {
		return array(
			array('login, name, hash, salt', 'safe', 'on' => 'insert'),
			array('name, rating, fighter_id, delayed_id, viewed_id', 'safe', 'on' => 'insert'),
			array('login, name, hash, salt', 'required', 'on' => 'insert'),
			array('login', 'unique'),
		);
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
		return substr(md5(mt_rand(0, 0x7fffffff)), 0, self::SALT_LENGTH);
	}

//---------------------------------------------------------------------------
}