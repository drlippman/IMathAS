<?php
//Cryptography functions, Nov 2, 2012

//IMPORTANT NOTE to anyone who might be looking at this file outside the context of IMathAS:
// These functions were designed as learning demo functions, and are NOT cryptographically secure.
// Do NOT use these functions in any application needing secure cryptography

global $allowedmacros;
array_push($allowedmacros,"randfiveletterword","shiftcipher","transcipher","randmilphrase","chunktext","modularexponent","cryptorsakeys","randsubmap","subcipher");


//chunktext(text, size)
//adds spaces between chunks of text of the given size
function chunktext($m, $s=4) {
	return chunk_split($m, $s, ' ');
}


//randfiveletterword()
//returns a random five-letter word
function randfiveletterword() {
	global $RND;
	$fiveletterwords = explode(',','first,thing,those,woman,child,there,after,world,still,three,state,never,leave,while,great,group,begin,where,every,start,might,about,place,again,where,right,small,night,point,today,bring,large,under,water,write,money,story,young,month,right,study,issue,black,house,after,since,until,power,often,among,stand,later,white,least,learn,right,watch,speak,level,allow,spend,party,early,force,offer,maybe,music,human,serve,sense,build,death,reach,local,class,raise,field,major,along,heart,light,voice,whole,price,carry,drive,break,thank,value,model,early,agree,paper,space,event,whose,table,court,teach,image,phone,cover,quite,clear,piece,movie,north,third,catch,cause,point,plant,short,place,south,floor,close,wrong,sport,board,fight,throw,order,focus,blood,color,store,sound,enter,share,other,shoot,seven,scene,stock,eight,happy,occur,media,ready,argue,staff,trade,glass,skill,crime,stage,state,force,truth,check,laugh,guess,study,prove,since,claim,close,sound,enjoy,legal,final,green,above,trial,radio,visit,avoid,close,peace,apply,shake,chair,treat,style,adult,worry,range,dream,stuff,hotel,heavy,cause,tough,exist,agent,owner,ahead,coach,total,civil,mouth,smile,score,break,front,admit,alone,fresh,video,judge');
	return $fiveletterwords[$RND->rand(0,count($fiveletterwords)-1)];
}

//randmilphrase([removespaces],[pad])
//returns a random military-ish phrase like "at noon march towards target"
//if removespaces is set to true, all spaces will be removed
//if pad is set to a number N, then the phrase will be padded with random characters
//for a transposition cipher with N columns.
function randmilphrase($removespaces=false,$pad=false) {
	global $RND;
	$milphrase1 = explode(',','at noon,at one,at two,at three,at four,at five,at six,at seven,at eight,at nine,at ten,at eleven,at midnight,tomorrow,in two days');
	$milphrase2 = explode(',','air strike on,attack,march towards,surveillance on,regroup at,spy on,head east to,head west to,head north to,head south to');
	$milphrase3 = explode(',','headquarters,base camp,enemy camp,front lines,trenches,target');
	$phrase = $milphrase1[$RND->rand(0,count($milphrase1)-1)] .' '. $milphrase2[$RND->rand(0,count($milphrase2)-1)] .' '. $milphrase3[$RND->rand(0,count($milphrase3)-1)];
	$n = strlen(str_replace(' ','',$phrase));
	$charset = 'BCDFGHJKLMNPQRSTVWXYZ';
	if ($pad!==false) {
		if ($n%$pad != 0) {
			$phrase .= ' ';
			for ($i=$n%$pad;$i<$pad;$i++) {
				$phrase .= $charset[$RND->rand(0,20)];
			}
		}
	}
	if ($removespaces) { $phrase = str_replace(' ','',$phrase);}
	return strtoupper($phrase);
}

//shiftcipher(message, shift, [alpha] [rotate])
//encrypts the message using a basic Caesar shift substitution cipher.
//shift can be numeric value (e.g., 3 means A encrypts as D), or can be a letter
//  indicating what A encrypts as
//alpha should be "alpha" for A-Z or "alphanum" for A-Z0-9.  Defaults to "alpha"
//if rotate is set to true, then the shift will be increased after each letter
//  is encrypted.  Can set to a number to shift by more 1 after each letter.
function shiftcipher($m, $s, $alpha='alpha', $rotate=false) {
	$m = strtoupper($m);
	if ($alpha=='alphanum') {
		$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$m = preg_replace('/[^A-Z0-9]/','',$m);
	} else {
		$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$m = preg_replace('/[^A-Z]/','',$m);
	}
	$L = strlen($charset);
	if (!is_numeric($s)) {
		$c = ord(strtoupper($s));
		if ($c >= 65 && $c <=  90) {
			$s = $c-65;
		} else if ($c >= 48 && $c <= 57 && $L>26 ) {
			$s = $c-48+26;
		} else {
			echo 'Invalid shift';
		}
	}
	if ($rotate===false) {
		$rotate = 0;
	} else if ($rotate===true) {
		$rotate = 1;
	}


	$output = '';
	for ($i=0; $i<strlen($m);$i++) {
		$c = ord($m[$i]);
		if ($c >= 65 && $c <=  90) { // Uppercase letter
			$output .= $charset[($c-65+$s)%$L];
		} else if ($c >= 48 && $c <= 57 && $L>26 ) { // Number
			$output .= $charset[($c-48+26+$s)%$L];
		} else {
		        $output .= $m[$i];  // Copy
		}
		$s += $rotate;
	}
	return $output;
}

//randsubmap([charset])
//generates a random substitution mapping
//by default uses A-Z.  Set charset="alphanum" or A-Z0-9
function randsubmap($alpha = "alpha") {
	global $RND;
	if ($alpha=='alphanum') {
		$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	} else {
		$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}
	return $RND->str_shuffle($charset);
}

//subcipher(message, submap, [alpha] [rotate])
//encrypts the message using a general substitution cipher.
//submap is a string consisting of all characters of the character set in
// desired mapping order
//alpha should be "alpha" for A-Z or "alphanum" for A-Z0-9.  Defaults to "alpha"
//if rotate is set to true, then the shift will be increased after each letter
//  is encrypted.  Can set to a number to shift by more 1 after each letter.
function subcipher($m, $map, $alpha='alpha', $rotate=false) {
	$m = strtoupper($m);
	if ($alpha=='alphanum') {
		$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$m = preg_replace('/[^A-Z0-9]/','',$m);
	} else {
		$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$m = preg_replace('/[^A-Z]/','',$m);
	}
	$L = strlen($map);

	if ($rotate===false) {
		$rotate = 0;
	} else if ($rotate===true) {
		$rotate = 1;
	}

	$s = 0;
	$output = '';
	for ($i=0; $i<strlen($m);$i++) {
		$c = ord($m[$i]);
		if ($c >= 65 && $c <=  90) { // Uppercase letter
			$output .= $map[($c-65+$s)%$L];
		} else if ($c >= 48 && $c <= 57 && $L>26 ) { // Number
			$output .= $map[($c-48+26+$s)%$L];
		} else {
		        $output .= $m[$i];  // Copy
		}
		$s += $rotate;
	}
	return $output;
}

//transcipher(message, columns, [order])
//encrypts a message using a basic transposition cipher, writing the message
//in rows, each with the given number of columns.  The encrypted message is
//then formed by reading down the columns.
//message is padded if needed with A's.
//by default, the columns are read from left to right.  If an array of numbers (0 to cols-1)
//are provided for order, they will specify the order to read.  If a word is
//fed in for order, it will serve as the the code-word, and columsn will be read
//in the alphabetic order of the letters in the word (e.g. day would mean 2 1 3
//since a is the first of the letters in the alphabet, d is 2nd, etc)
//
function transcipher($m, $cols, $order=false) {
	$m = strtoupper($m);
	$m = preg_replace('/[^A-Z0-9]/','',$m);
	$n = strlen($m);
	$rows = ceil($n/$cols);
	if ($n%$cols != 0) {
		for ($i=$n%$cols;$i<$cols;$i++) {
			$m .= 'A';
		}
	}
	$n = strlen($m);

	$mat = array();
	$output = '';
	if ($order===false) {
		$order = range(0,$cols-1);
	} else if (!is_array($order)) {
		if (strlen($order)!=$cols) {
			echo 'Length of codeword should match number of columns';
		}
		$order = str_split($order);  //ie (0=>d, 1=>a 2=>y)
		asort($order);  //ie (1=>a, 0=>d, 2=>y)
		$order = array_keys($order);  // gets (1, 0, 2)
	}
	$col = -1;
	for ($i=0;$i<$n;$i++) {
		if ($i%$rows==0) { $col++;} //current column we're reading
		$r = $i%$rows;  //row we want to read in current column
		$output .= $m[$cols*$r + $order[$col]];
	}
	return $output;
}

//modularexponent(base, exponent, modulus)
//calculates base^exponent mod modulus
function modularexponent($base,$exponent,$modulus) {
	$result = 1;
	while ($exponent > 0 ) {
    	    if ($exponent % 2 == 1) {
    	    	   $result = ($result * $base) % $modulus;
            }
            $exponent = $exponent >> 1;
            $base = ($base * $base) % $modulus;
        }
        return $result;
}

//cryptorsakeys(p, q)
//given two primes, p and q, returns array(n, e, d)
//where n and e form the public key, and d is the private key
function cryptorsakeys($p,$q) {
	$n = $p*$q;
	$phi = ($p-1)*($q-1);
	$e = 3;  //this is obviously an insecure value for e, but makes the numbers more reasonable for learning
	while (gcd($e,$phi)!=1 && $e<$phi) {$e++;}
	if ($e>=$phi) {echo 'e bigger than phi - fail'; return;}

	list($d,$j,$k) = extended_gcd($e,$phi);
	if ($d<0) {
		$d += $phi;
	}

	return array($n, $e, $d);
}


function extended_gcd($u, $v) {
	//solve ux + vy = gcd(u,v)
	//returns (x, y, gcd(u,v))
    	$u1 = 1;
	$u2 = 0;
	$u3 = $u;
	$v1 = 0;
	$v2 = 1;
	$v3 = $v;
	while ($v3 != 0) {
		$q = floor($u3 / $v3);
		$t1 = $u1 - $q * $v1;
		$t2 = $u2 - $q * $v2;
		$t3 = $u3 - $q * $v3;
		$u1 = $v1;
		$u2 = $v2;
		$u3 = $v3;
		$v1 = $t1;
		$v2 = $t2;
		$v3 = $t3;
	}
	return array($u1, $u2, $u3);
}

?>
