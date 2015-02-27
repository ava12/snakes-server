<?php

/**
 * Информация о змее, участвующей в бою.
 */
class SnakeStat extends Model {
	const RESULT_NONE = '';
	const RESULT_FREE = 'free';
	const RESULT_EATEN = 'eaten';
	const RESULT_BLOCKED = 'blocked';

	protected $names = array('fight_id', 'result', 'length', 'pre_rating', 'post_rating', 'debug');

	public $fight_id;
	public $result = self::RESULT_NONE;
	public $length = 10;
	public $pre_rating;
	public $post_rating;
	public $debug = '';

//---------------------------------------------------------------------------
}