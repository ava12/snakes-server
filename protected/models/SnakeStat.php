<?php

/**
 * Информация о змее, участвующей в бою.
 */
class SnakeStat extends Model {
	const RESULT_NONE = '';
	const RESULT_FREE = 'free';
	const RESULT_EATEN = 'eaten';
	const RESULT_BLOCKED = 'blocked';

	protected $names = array('result' => false, 'length' => false, 'pre_rating' => false, 'post_rating' => false, 'debug' => false);

	public $result = self::RESULT_NONE;
	public $length = 10;
	public $pre_rating;
	public $post_rating;
	public $debug = '';

//---------------------------------------------------------------------------
}