<?php
//A library of base changing functions.  Version 1.0, Nov 13, 2007

global $allowedmacros;
array_push($allowedmacros,"baseconvert","asciitodec","dectoascii");

//baseconvert(num,from,to)
//converts the number num from base-"from" to base-"to"
//For example, baseconvert(23,5,7) converts 23-base5 to base7
//if a base is >10, then each place should be given (or will be returned)
// separated by commas, eg. 5,12,3
function baseconvert($n,$f,$t) {
	if ($f>10) {
		$p = explode(',',$n);
		foreach ($p as $k=>$v) {
			if ($v>9) {
				$p[$k] = chr($v+87);	
			}
		}
		$n = implode('',$p);
	}
	$o = base_convert($n,$f,$t);
	if ($t>10) {
		$p = stringtoarray($o);
		foreach($p as $k=>$v) {
			if (!is_numeric($v)) {
				$p[$k] = ord($v)-87;
			}
		}
		$o = implode(',',$p);
	}
	return $o;
}

//asciitodec(char)
//returns the ASCII decimal code for a character
function asciitodec($c) {
	return ord($c);
}

//dectoascii(num)
//returns the ASCII character for a decimal value
function dectoascii($c) {
	return chr($c);
}


?>
