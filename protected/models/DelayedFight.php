<?php

class DelayedFight extends CActiveRecord {
	const BATCH_CNT = 50;
	const DEFAULT_TIMEOUT = 30;

	const FREE_CELL = 1;
	const BORDER_CELL = 2;
	const HEAD_CELL = 0x10;
	const BODY_CELL = 0x100;
	const TAIL_CELL = 0x1000;
	const GROUP_SHIFT = 16;
	const RATE_SHIFT = 20;
	const TAIL_SHIFT = 12;
	const HEAD_MASK = 0xF0;
	const BODY_MASK = 0xF00;
	const TAIL_MASK = 0xF000;
	const RATE_MASK = 0x700000;
	const ALLOWED_MASK = self::TAIL_MASK | self::FREE_CELL;
	const ANY_MASK = 0xFFF3;

	protected $dirX = array(0, 1, 0, -1);
	protected $dirY = array(-1, 0, 1, 0);
	protected $startX = array(12, 9, 12, 15);
	protected $startY = array(15, 12, 9, 12);

	protected $isSolitaire = NULL;

	protected $result;
	protected $field;
	protected $turns;
	protected $snakes = array(NULL, NULL, NULL, NULL);

	protected $isLocked = false;

//---------------------------------------------------------------------------
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

//---------------------------------------------------------------------------
	public function tableName() {
		return '{{delayedfight}}';
	}

//---------------------------------------------------------------------------
	public function relations() {
		return array(
			'fight' => array(self::BELONGS_TO, 'Fight', 'fight_id'),
		);
	}

//---------------------------------------------------------------------------
	protected function onBeforeSave() {
		$this->fold();
		return true;
	}

//---------------------------------------------------------------------------
	protected function onAfterSave() {
		$this->unlock();
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
	public function free() {
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 't.delay_till < NOW()',
		));
		return $this;
	}

//---------------------------------------------------------------------------
	protected function lock($timeout) {
		if ($this->isLocked) return true;

		$timeout += 20;
		$table = Util::unescapeTableName($this->tableName());
		$sql = 'UPDATE ' . $table .
			' SET delay_till = DATE_ADD(INTERVAL NOW() PLUS :timeout SECOND)' .
			' WHERE fight_id = :id AND delay_till < NOW()';
		$this->isLocked = (bool)$this->getDbConnection()->createCommand($sql)
			->exec(array(':id' => $this->fight_id, ':timeout' => $timeout));

		return $this->isLocked;
	}

//---------------------------------------------------------------------------
	protected function unlock() {
		if (!$this->isLocked) return false;

		$table = Util::unescapeTableName($this->tableName());
		$sql = 'UPDATE ' . $table .
			' SET delay_till = 0' .
			' WHERE fight_id = :id AND delay_till > NOW()';
		$result = (bool)$this->getDbConnection()->createCommand($sql)
			->exec(array(':id' => $this->fight_id));

		$this->isLocked = false;
		return $result;
	}

//---------------------------------------------------------------------------
	protected function unfold() {
		foreach (unserialize($this->state) as $name => $value) {
			$this->$name = $value;
		}
	}

//---------------------------------------------------------------------------
	protected function fold() {
		$state = array_flip(array('result', 'field', 'snakes', 'turns', 'isSolitaire'));
		foreach ($state as $name => &$p) {
			$p = $this->$name;
		}
		$this->state = serialize($state);
	}

//---------------------------------------------------------------------------
	public function process($timeout = NULL) {
		if ($this->result) return $this->result;

		if (!$timeout) $timeout = static::DEFAULT_TIMEOUT;
		if (!$this->lock($timeout)) return '';

		if ($this->isNewRecord) $this->prepare();
		else $this->unfold();

		$turnLimit = $this->fight->turnLimit;
		$finishTime = time() + $timeout;
		$batchCnt = self::BATCH_CNT;

		for ($turn = 1; $turn < $turnLimit; $turn++) {
			if (!$this->processTurn()) break;

			$batchCnt--;
			if (!$batchCnt) {
				$batchCnt = self::BATCH_CNT;
				if (time() >= $finishTime) break;
			}
		}

		$this->unlock();
		return $this->result;
	}

//---------------------------------------------------------------------------
	protected function prepare() {
		$this->field - array_fill(0, 25, array_fill(0, 25, self::FREE_CELL));
		$this->turns = array();

		$snakes = array(NULL, NULL, NULL, NULL);
		foreach ($this->fight->stats as $index => $stat) {
			$this->isSolitaire = !isset($this->isSolitaire);
			$coords = $this->prepareCoords($index);

			$this->field[$coords[0][1]][$coords[0][0]] = self::HEAD_CELL << $index;
			for ($i = 1; $i < 9; $i++) {
				$this->field[$coords[$i][1]][$coords[$i][0]] = self::BODY_CELL << $index;
			}
			$this->field[$coords[9][1]][$coords[9][0]] = self::TAIL_CELL << $index;

			$snakes[$index] = array(
				'Dir' => $index, 'Coords' => $coords, 'Result' => '', 'Debug' => array(),
				'Maps' => $this->prepareMapVariants($stat->snake, $index),
			);
		}

		$this->snakes = $snakes;
	}

//---------------------------------------------------------------------------
	protected function prepareCoords($index) {
		$result = array();
		$dx = $this->dirX[$index ^ 2];
		$dy = $this->dirY[$index ^ 2];
		$x = $this->startX[$index];
		$y = $this->startY[$index];
		$result[0] = array($x, $y);

		for ($i = 1; $i < 10; $i++) {
			$x += $dx;
			$y += $dy;
			$result[$i] = array($x, $y);
		}

		return $result;
	}

//---------------------------------------------------------------------------
	protected function prepareMapVariants($snake, $index) {
		$result = array();
		$templates = $this->makeTemplateMasks($snake->templates, $index);
		foreach ($snake->maps as $map) {
			$result[] = $this->makeMapVariants($map, $templates, $index);
		}

		return $result;
	}

//---------------------------------------------------------------------------
	protected function makeTemplateMasks($templates, $index) {
		$singleRate = 6 << self::RATE_SHIFT;
		$multiRate = 1 << self::RATE_SHIFT;
		$result = array(
			'S' => $singleRate | (self::BODY_CELL << $index),
			'T' => $singleRate | (self::TAIL_CELL << $index),
			'V' => $singleRate | (self::FREE_CELL),
			'W' => $singleRate | (self::BORDER_CELL),
			'X' => $singleRate | (self::HEAD_MASK & ~(self::HEAD_CELL << $index)),
			'Y' => $singleRate | (self::BODY_MASK & ~(self::BODY_CELL << $index)),
			'Z' => $singleRate | (self::TAIL_MASK & ~(self::TAIL_CELL << $index)),
		);

		foreach (array('A', 'B', 'C', 'D') as $i => $name) {
			$template = str_split($templates[$i]);
			$mask = 0;
			foreach ($template as $t) $mask |= $result[$t];
			$mask = ($mask & ~self::RATE_MASK) | ((7 - count($template) << self::RATE_SHIFT);
			$result[$name] = $mask;
		}

		$mask = self::ANY_MASK | self::RATE_MASK;
		foreach (array_keys($result) as $name) {
			$result[strtolower($name)] = $result[$name] ^ $mask;
		}

		return $result;
	}

//---------------------------------------------------------------------------
	protected function makeMapVariants($map, $templates, $index) {
		$result = array_fill(0, 8, NULL);
		$baseMasks = $this->fillMapVariant($map.lines, $templates);
		$headCoords = array($map.head_x, $map.head_y);
		$result[0] = array($headCoords[0], $headCoords[1], array_chunk($baseMasks, 7));

		// [индекс_X_головы, множитель_X, смещение_X,
		//  индекс_Y_головы, множитель_Y, смещение_Y,
		//  индекс_первой_клетки, смещение_индекса_в_строке, смещение_индекса_новой_строки]
		$params = array(
			NULL,
			array(1, -1, 6,  0, 1, 0,  42, -7, 1),
			array(0, -1, 6,  1, -1, 6,  48, -1, -7),
			array(1, 1, 0,  0, -1, 6,  6, 7, -1),

			array(0, -1, 6,  1, 1, 0,  6, -1, 7),
			array(1, -1, 6,  0, -1, 6,  48, -7, -1),
			array(0, 1, 0,  1, -1, 6,  42, 1, -7),
			array(1, 1, 0,  0, 1, 0,  0, 7, 1),
		);

		for ($i = 1; $i < 8; $i++) {
			$param = $params[$i];
			$masks = array_fill(0, 49, NULL);
			$lineIndex = $param[6];
			$dx = $param[7];
			$dy = $param[8];
			$j = 0;

			for ($y = 0; $y < 7; $y++) {
				$pos = $lineIndex;
				$lineIndex += dy;
				for ($x = 0; $x < 7; $x++) {
					$masks[$j] = $baseMasks[$pos];
					$j++
					$pos += dx;
				}
			}

			$x = $headCoords[$param[0]] * $param[1] + $param[2];
			$y = $headCoords[$param[3]] * $param[4] + $param[5];
			$result[$i] = array($x, $y, array_chunk($masks, 7));
		}

		return $result;
	}

//---------------------------------------------------------------------------
	protected function fillMapVariant($lines, $templates) {
		$lines = str_split($lines, 2);
		$result = array_fill(0, 49, self::ANY_MASK);
		foreach ($lines as $i => $line) {
			$cell = str_split($line);
			if ($cell[0] == '-') continue;

			$result[$i] = $templates[$cell[0]] | (((int)$cell[1]) << self::GROUP_SHIFT);
		}

		return $result;
	}

//---------------------------------------------------------------------------
	protected function processTurn() {
		$stepOrder = array( 0, 1, 2, 3);
		shuffle($stepOrder);

		$snakesMoved = false;
		$turn = 0;

		for ($i = 0; $i < 3; $i++) $turn |= $stepOrder[$i] << ($i << 1);
		foreach ($stepOrder as $step) {
			$stepResult = $this->processStep($step);
			if ($stepResult) $snakesMoved = true;
			$turn |= $stepResult << (($step << 1) + 6);
		}

		if (!$snakesMoved) {
			this->result = Fight::RESULT_BLOCKED;
			return false;
		}

		$this->turns[] = $turn;

		if (!$this->isSolitaire) {
			$snakesAlive = 0;
			foreach ($this->snakes as $snake) {
				if ($snake and $snake['Coords']) {
					$snakesAlive++;
				}
			}
			if ($snakesAlive < 2) {
				$this->result = Fight::RESULT_EATEN;
				return false;
			}
		}

		return true;
	}

//---------------------------------------------------------------------------
	protected function processStep($snakeIndex) {
		$snake = &$this->snakes[$snakeIndex];
		if (!$snake or count($snake['Coords']) < 2) return 0;

		$headx = $snake['Coords'][0][0];
		$headY = $snake['Coords'][0][1];
		$dir = ($snake['Dir'] - 1) & 3;
		$allowedDirs = array();

		for ($i = 2; $i >= 0; $i--) {
			$cell = @$this->field[$headY + $this->dirY[$dir]];
			if ($cell) {
				$cell = @$cell[$headX + $this->dirX[$dir]];
				if ($cell & self::ALLOWED_MASK) $allowedDirs[] = $dir;
			}
			$dir = ($dir + 1) & 3;
		}

		if (count($allowedDirs) < 2) {
			if (!$allowedDirs) {
				$snake['Result'] = SnakeStat::RESULT_EATEN;
				$snake['Debug'][] = $this->encodeDebug(0, 1);
				return 0;
			}

			$dir = $allowedDirs[0]
			$i = ($dir - $snake['Dir'] + 2) & 3;
			$this->moveSnake($snakeIndex, $dir);
			$snake['Debug'][] = $this->encodeDebug(0, 2);
			return $i;
		}

		foreach ($snake['Maps'] as $i => $variants) {
			$bestRate = 0;
			$bestDirs = array();

			foreach ($allowedDirs as $dir) {
				$rates = array(
					$this->rateMapVariant($variants[$dir], $headX, $headY),
					$this->rateMapVariant($variants[$dir | 4], $headX, $headY),
				);
				$rate = max($rates[0], $rates[1]);
				if ($rate > $rates[0]) $dir |= 4;
				if ($rate and $rate >= $bestRate) {
					if ($rate == $bestRate) $bestDirs[] = $dir;
					else {
						$bestRate = $rate;
						$bestDirs = array($dir);
					}
				}
			}

			if (!$bestRate) continue;

			$dir = $bestDirs[array_rand($bestDirs)];
			$j = ($dir - $snake['Dir'] + 2) & 3;
			$this->moveSnake($snakeIndex, $dir & 3);
			$snake['Debug'][] = $this->encodeDebug($i + 1, $dir);
			return $j;
		}

		$dir = $allowedDirs[array_rand($allowedDirs)];
		$i = ($dir - $snake['Dir'] + 2) & 3;
		$this->moveSnake($snakeIndex, $dir);
		$snake['Debug'][] = $this->encodeDebug(0, 3);
		return $i;
	}

//---------------------------------------------------------------------------
	protected function encodeDebug($map, $variant) {
		return chr((($map << 3) | $variant) + 32);
	}

//---------------------------------------------------------------------------
	protected function rateMapVariant($variant, $headX, $headY) {
		$results = array_fill(0, 8, NULL);
		$startX = $headX - $variant[0];
		$startY = $headY - $variant[1];
		$masks = $variant[2];
		$my = $startY - 1;

		for ($y = 0, $my = $startY; $y < 7; $y++, $my++) {
			for ($x = 0, $mx = $startX; $x < 7; $x++, $mx++) {
				if ($mx < 0 or $mx >= 25 or $my < 0 or $my >= 25) $cell = self::BORDER_CELL;
				else $cell = $this->field[$my][$mx];
				$mask = $masks[$y][$x];
				if ($mask == self::ANY_MASK) continue;

				$group = ($mask >> self::GROUP_SHIFT) & 7;
				if (!isset($results[$group])) $results[$group] = 0;
				if (!($cell & $mask)) {
					if ($group < 4) $results[$group] = -1
					continue;
				}

				if ($results[$group] >= 0) {
					$results[$group] += $mask >> self::RATE_SHIFT;
				}
			}
		}

		$result = 1;
		$failed = NULL;
		for ($group = 0; $group < 4; $group++) {
			$groupResult = $results[$group];
			if (!isset($groupResult)) continue;

			if ($groupResult <= 0) {
				if (!isset($failed)) $failed = true;
			} else {
				$failed = false;
				$result += $groupResult;
			}
		}
		if ($failed) return 0;

		for ($group = 4; $group < 8; $group++) {
			$groupResult = $results[$group];
			if (!isset($groupResult)) continue;

			if (!$groupResult) return 0;
			else $result += $groupResult;
		}

		return $result;
	}

//---------------------------------------------------------------------------
	protected function moveSnake($snakeIndex, $dir) {
		$snake = &$this->snakes[$snakeIndex];
		$snakeX = $snake['Coords'][0][0];
		$snakeY = $snake['Coords'][0][1];
		$moveX = $snakeX + $this->dirX[$dir];
		$moveY = $snakeY + $this->dirY[$dir];
		$cell = $this->field[$moveY][$moveX];
		$snake['Dir'] = $dir;
		$snake['Result'] = SnakeStat::RESULT_FREE;

		$this->field[$snakeY][$snakeX] = self::BODY_CELL << $snakeIndex;
		array_unshift($snake['Coords'], array($moveX, $moveY));
		$this->field[$moveY][$moveX] = self::HEAD_CELL << $snakeIndex;

		if ($cell &(self::FREE_CELL | (self::TAIL_CELL << $snakeIndex))) {
			$coords = array_pop($snake['Coords']);
			if ($coords[0] <> $moveX or $coords[1] <> $moveY) {
				$this->field[$coords[1]][$coords[0]] = self::FREE_CELL;
			}
			$coords = $snake['Coords'][count($snake['Coords']) - 1];
			$this->field[$coords[1]][$coords[0]] = self::TAIL_CELL << $snakeIndex;
			return;
		}

		$enemyIndex = array(1 => 0, 2 => 1, 4 => 2, 8 => 3);
		$enemyIndex = $enemyIndex[($cell & self::TAIL_MASK) >> self::TAIL_SHIFT];
		$enemy = &$this->snakes[$enemyIndex];
		array_pop($enemy['Coords']);
		if (count($enemy['Coords']) < 2) $enemy['Result'] = SnakeStat::RESULT_EATEN;
		if ($enemy['Coords']) {
			$coords = $enemy['Coords'][count($enemy['Coords']) - 1];
			$this->field[$coords[1]][$coords[0]] = self::TAIL_CELL << $enemyIndex;
		}
	}

//---------------------------------------------------------------------------
	public function saveResult() {
		$fight = $this->fight;
		foreach ($fight->stats
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}