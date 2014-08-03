<?php

/**
 * Карта
 *
 * @property int snake_id
 * @property int index
 * @property string description
 * @property int head_x
 * @property int head_y
 * @property string lines
 */
class SnakeMap extends CActiveRecord {

//---------------------------------------------------------------------------
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{map}}';
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'snake' => aray(self::BELONGS_TO, 'Snake', 'snake_id'),
		);
	}

//---------------------------------------------------------------------------
	public function rules() {
		return array(
			array('snake_id, index, description, head_x, head_y, lines', 'safe', 'on' => 'insert'),
			array('snake_id, index, head_x, head_y, lines', 'required', 'on' => 'insert'),
			array('snake_id', 'exists', 'className' => 'Snake', 'attributeName' => 'id'),
			array('index', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 9),
			array('description', 'length', 'max' => 1024),
			array('head_x, head_y', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 6),
			array('lines', 'line'),
		);
	}

//---------------------------------------------------------------------------
	public function validateLine($attribute) {
		$value = $this->$attribute;
		if (
			!preg_match('/^(?:--|[A-DSTV-Z][0-7]){49}$/i', $value) or
			substr($value, $this->head_y * 7 + $this->head_x, 2) <> '--'
		) {
			$this->addError($attribute, 'некорректное описание карты');
			return false;
		}
	}

//---------------------------------------------------------------------------
	public function addLine($x, $y, $line) {
		if (!preg_match('/^(?:--|[A-DSTV-Z][0-7])+$/i', $line)) return false;

		$offset = ($y * 7 + $x) << 1;
		if ($offset + strlen($line) > 78) return false;

		$lines = $this->lines;
		if (!$lines) $lines = str_repeat('-', 78);
		$this->lines = substr($lines, 0, $offset) . $line . substr($lines, $offset + strlen($line));
		return true;
	}

//---------------------------------------------------------------------------
	public function sameAs($that) {
		return (
			$this->lines == $that->lines and
			$this->head_x == $that->head_x and
			$this->head_y == $that->head_y
		);
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}