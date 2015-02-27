<?php

/**
 * Карта
 *
 * @property int $head_x
 * @property int $head_y
 * @property string $lines
 */
class SnakeMap extends Model {
	protected $names = array('description' => false, 'head_x' => false, 'head_y' => false, 'lines' => false);

	public $description;
	protected $head_x;
	protected $head_y;
	protected $lines;

//---------------------------------------------------------------------------
	public function rules() {
		return array(
			array('description', 'length', 'max' => 1024),
			array('head_x, head_y', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 6),
			array('lines', 'validateLines'),
		);
	}

//---------------------------------------------------------------------------
	public function validateLines($attribute) {
		$value = $this->$attribute;
		if (
			!preg_match('/^(?:--|[A-DSTV-Z][0-7]){49}$/i', $value) or
			substr($value, $this->head_y * 7 + $this->head_x, 2) <> '--'
		) {
			$this->addError($attribute, 'некорректное описание карты');
			return false;
		} else return NULL;
	}

//---------------------------------------------------------------------------
	public function addLine($x, $y, $line) {
		if (!preg_match('/^(?:--|[A-DSTV-Z][0-7])+$/i', $line)) return false;

		$offset = ($y * 7 + $x) << 1;
		if ($offset + strlen($line) > 98) return false;

		$lines = $this->lines;
		$len = strlen($line);
		if (substr($lines, $offset, $len) <> str_repeat('-', $len)) return false;

		$this->lines = substr($lines, 0, $offset) . $line . substr($lines, $offset + $len);
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
	public function copy() {
		return new SnakeMap($this->asArray());
	}

//---------------------------------------------------------------------------
}