<?php
/**
 * Pseudo random number generator, intentionally designed to always
 * produce the same sequence of values from the same seed.
 */

class Rand {
	private $seed;
	private $randmax;

	function __construct() {
		$this->seed = rand();
		$this->randmax = getrandmax();
	}

	public function srand($n=0) {
		if ($n==0) {
			srand();
			if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
				$this->seed = rand();
			}
		} else {
			$n = (int)$n;
			if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
				$this->seed = $n;
			} else {
				srand($n);
			}
		}
	}

	public function rand($min=0,$max=null) {  //simple xorshift
		if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
			if ($max===null) {
				$max = $this->randmax;
			}
			$min = (int)$min;
			$max = (int)$max;
			if ($min < $max) {
				if ($GLOBALS['assessver']>1) {
					$this->seed = ($this->seed^($this->seed << 13)) & 0xffffffff;
					if ($this->seed >  0x7fffffff) { $this->seed -= 0x100000000;}
					$this->seed = ($this->seed^($this->seed >> 17)) & 0xffffffff;
					if ($this->seed >  0x7fffffff) { $this->seed -= 0x100000000;}
					$this->seed = ($this->seed^($this->seed << 5)) & 0x7fffffff;
				} else { //broken; assessver=1 only
					$this->seed ^= ($this->seed << 13);
					$this->seed ^= ($this->seed >> 17);
					$this->seed ^= ($this->seed << 5);
					$this->seed &= 0x7fffffff;
				}
				return ($this->seed % ($max + 1 - $min)) + $min;
			} else if($min > $max){
				return $this->rand($max,$min);
			} else if ($min == $max) {
				return $min;
			}
		} else {
			if ($max===null) {
				return rand();
			} else {
				return rand($min,$max);
			}
		}
	}

	public function shuffle(&$arr) {
		if (!is_array($arr)) {
			if ($GLOBALS['myrights']>10) {
				echo _('Input to shuffle must be an array');
			}
			return;
		}
        $arr = array_values($arr);
		if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
			for ($i=count($arr)-1;$i>0;$i--) {
				if ($GLOBALS['assessver']>1) {
					$this->seed = ($this->seed^($this->seed << 13)) & 0xffffffff;
					if ($this->seed >  0x7fffffff) { $this->seed -= 0x100000000;}
					$this->seed = ($this->seed^($this->seed >> 17)) & 0xffffffff;
					if ($this->seed >  0x7fffffff) { $this->seed -= 0x100000000;}
					$this->seed = ($this->seed^($this->seed << 5)) & 0x7fffffff;
				} else { //broken; assessver=1 only
					$this->seed ^= ($this->seed << 13);
					$this->seed ^= ($this->seed >> 17);
					$this->seed ^= ($this->seed << 5);
					$this->seed &= 0x7fffffff;
				}
				$j = $this->seed % ($i+1); //$this->rand(0,$i);
				if ($i!=$j) {
					$tmp = $arr[$j];
					$arr[$j] = $arr[$i];
					$arr[$i] = $tmp;
				}
			}
		} else {
			shuffle($arr);
		}
	}

	public function str_shuffle($str) {
		if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
			$arr = str_split($str);
			$this->shuffle($arr);
			return implode('', $arr);
		} else {
			return str_shuffle($str);
		}
	}

	public function array_rand($arr, $n=1) {
		if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
			$keys = array_keys($arr);
			if ($n==1) {
				$n = $this->rand(0,count($keys)-1);
				return $keys[$n];
			} else if ($n==count($arr)) { //no point in shuffling since php's internal function doesn't shuffle
				return $keys;
			} else {
				$n = (int)$n;
				$this->shuffle($keys);
				return array_slice($keys,0,$n);
			}
		} else {
			return array_rand($arr,$n);
		}
	}

}
