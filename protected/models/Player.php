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
				'scopes' => 'current'),
			'snakes' => array(self::HAS_MANY, 'Snake', 'player_id',
				'order' => 'name ASC', 'scopes' => 'current'),
		);
	}

//---------------------------------------------------------------------------
	public function scopes() {
		return array(
			'hasRating' => 'rating IS NOT NULL',
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
//---------------------------------------------------------------------------
}