<?php
//IMathAS:  Converts ASCIIMath format to php math format for eval
//(c) 2006 David Lippman
//This code is a php conversion/mod of mathjs from Peter Jipsen's ASCIIsvg.js
//script, (c) Peter Jipsen.  See /javascript/ASCIIsvg.js

function mathphppre($st) {
  if (strpos($st,"^-1") || strpos($st,"^(-1)")) {
		$st = str_replace(array("sin^-1","cos^-1","tan^-1","sin^(-1)","cos^(-1)","tan^(-1)"),array("asin","acos","atan","asin","acos","atan"),$st);
		$st = str_replace(array("sinh^-1","cosh^-1","tanh^-1","sinh^(-1)","cosh^(-1)","tanh^(-1)"),array("asinh","acosh","atanh","asinh","acosh","atanh"),$st);
  }
  return $st;
}
function parenvarifneeded($matches) {
	  if ($matches[2]=='[') {
		  return $matches[0];
	  } else {
		  return ('('.$matches[1].')'.$matches[2]);
	  }
  }
//varlist should be | separated, like "x|y"
function mathphp($st,$varlist,$skipfactorial=false,$ignorestrings=true) {
  //translate a math formula to php function notation
  // a^b --> pow(a,b)
  // na --> n*a
  // (...)d --> (...)*d
  // n! --> factorial(n)
  // sin^-1 --> asin etc.
  //while ^ in string, find term on left and right
  //slice and concat new formula string
  
  //parenthesize variables with number endings, ie $c2^3 => ($c2)^3
  //$st = preg_replace('/(\$[a-zA-Z\d]+)$/',"($1)",$st);
  $st .= ' ';
  
  if ($ignorestrings) {
	   //'/(\'|").*?[^\\\\](\\1)/'
	  preg_match_all('/(["\'])(?:\\\\?.)*?\\1/',$st,$strmatches,PREG_SET_ORDER);
	  foreach ($strmatches as $k=>$match) {
		 $st =  str_replace($match[0],"(#$k#)",$st);
	  }
  }
  //$st = preg_replace('/(\$[a-zA-Z\d_]+)([^\[])/',"($1)$2",$st);
  $st = preg_replace_callback('/(\$[a-zA-Z\d_]+)(.)/','parenvarifneeded',$st);
  
  //parenthesizes the function variables
  if ($varlist != null) {
	  $reg = "/([^a-zA-Z])(" . $varlist . ")([^a-zA-Z])/";
	  $st= preg_replace($reg,"$1($2)$3",$st);	
	  //need second run through to catch x*x
	  $st= preg_replace($reg,"$1($2)$3",$st);	
	  $reg = "/^(" . $varlist . ")([^a-zA-Z])/";
	  $st= preg_replace($reg,"($1)$2",$st);
	  $reg = "/([^a-zA-Z])(" . $varlist . ")$/";
	  $st= preg_replace($reg,"$1($2)",$st);
	  $reg = "/^(" . $varlist . ")$/";
	  $st= preg_replace($reg,"($1)",$st);
  }

  $st = preg_replace("/\s/","",$st);
  $st = str_replace(array("arcsin","arccos","arctan","arcsinh","arccosh","arctanh"),array("asin","acos","atan","asinh","acosh","atanh"),$st);
  if (strpos($st,"^-1") || strpos($st,"^(-1)")) {
		$st = str_replace(array("sin^-1","cos^-1","tan^-1","sin^(-1)","cos^(-1)","tan^(-1)"),array("asin","acos","atan","asin","acos","atan"),$st);
		$st = str_replace(array("sinh^-1","cosh^-1","tanh^-1","sinh^(-1)","cosh^(-1)","tanh^(-1)"),array("asinh","acosh","atanh","asinh","acosh","atanh"),$st);
  }
  if ($st=='pi') { $st = "(M_PI)";}
  if ($st=='e') { $st = "(exp(1))";}
  $st= preg_replace('/^e$/',"(exp(1))",$st);
  $st= preg_replace('/^pi([^a-zA-Z])/',"(M_PI)$1",$st);
  $st= preg_replace('/([^a-zA-Z$])pi$/',"$1(M_PI)",$st);
  $st= preg_replace('/([^a-zA-Z$])pi([^a-zA-Z])/',"$1(M_PI)$2",$st);
  $st= preg_replace('/^e([^a-zA-Z])/',"(exp(1))$1",$st);
  $st= preg_replace('/([^a-zA-Z$])e$/',"$1(exp(1))",$st);
  
  $st= preg_replace('/([^a-zA-Z$])e([^a-zA-Z])/',"$1(exp(1))$2",$st);
  //$st= preg_replace("/([0-9])([\(a-zA-Z])/","$1*$2",$st);
  $st= preg_replace("/([0-9])([\(])/","$1*$2",$st);
  if ($varlist != null) {
	  $st= preg_replace("/([0-9])(".$varlist.")/","$1*$2",$st);
  }
  $st = preg_replace("/([0-9])(sin|cos|tan|sec|csc|cot|ln|log|exp|asin|acos|atan|sqrt|abs)/","$1*$2",$st);
  //want 4E2 to be scientific notation

  $st= preg_replace('/([0-9])\*\(exp\(1\)\)([0-9])/',"\\1E\\2",$st);

  $st= preg_replace('/([0-9])\*E([\-0-9])/',"\\1E\\2",$st);
  
  $st= preg_replace("/\)([\(0-9a-zA-Z])/",")*$1",$st);
  
  //fix PHP's 1/-2*5 order of ops bug
  $st = preg_replace('/\/(\-[\d\.E]+)(\*|\/)/',"/($1)$2",$st);
   
  while ($i=strpos($st,"^")) {
    //find left argument
    if ($i==0) return "Error: missing argument";
    $j = $i-1;
    $ch = substr($st,$j,1);
    if ($ch>="0" && $ch<="9") {// look for (decimal) number
      $j--;
      while ($j>=0 && ($ch=substr($st,$j,1))>="0" && $ch<="9") $j--;
      if ($ch==".") {
        $j--;
        while ($j>=0 && ($ch=substr($st,$j,1))>="0" && $ch<="9") $j--;
      }
    } else if ($ch==")") {// look for matching opening bracket and function name
      $nested = 1;
      $j--;
      while ($j>=0 && $nested>0) {
        $ch = substr($st,$j,1);
        if ($ch=="(") $nested--;
        else if ($ch==")") $nested++;
        $j--;
      }
      while ($j>=0 && ($ch=substr($st,$j,1))>="a" && $ch<="z" || $ch>="A" && $ch<="Z")
        $j--;
    } else if ($ch>="a" && $ch<="z" || $ch>="A" && $ch<="Z" || $ch=='$') {// look for variable
      $j--;
      while ($j>=0 && (($ch=substr($st,$j,1))>="a" && $ch<="z" || $ch>="A" && $ch<="Z" || $ch=='$'))
        $j--;
    } else { 
      return "Error: incorrect syntax in " .$st." at position ".$j;
    }
    //find right argument
    if ($i==strlen($st)-1) return "Error: missing argument";
    $k = $i+1;
    $ch = substr($st,$k,1);
    if ($ch>="0" && $ch<="9" || $ch=="-" || $ch==".") {// look for signed (decimal) number
      $k++;
      while ($k<strlen($st) && ($ch=substr($st,$k,1))>="0" && $ch<="9") $k++;
      if ($ch==".") {
        $k++;
        while ($k<strlen($st) && ($ch=substr($st,$k,1))>="0" && $ch<="9") $k++;
      }
    } else if ($ch=="(") {// look for matching closing bracket and function name
      $nested = 1;
      $k++;
      while ($k<strlen($st) && $nested>0) {
        $ch = substr($st,$k,1);
        if ($ch=="(") $nested++;
        else if ($ch==")") $nested--;
        $k++;
      }
    } else if ($ch>="a" && $ch<="z" || $ch>="A" && $ch<="Z" || $ch=='$') {// look for variable
      $k++;
      while ($k<strlen($st) && (($ch=substr($st,$k,1))>="a" && $ch<="z" ||
               $ch>="A" && $ch<="Z") || $ch=='$') $k++;
	       if ($ch=='(' && preg_match('/^(sin|cos|tan|sec|csc|cot|sinh|cosh|tanh|sech|csch|coth|ln|log|exp|asin|acos|atan|asinh|acosh|atanh|sqrt|abs)$/',substr($st,$i+1,($k-$i-1)))) {
		       $nested = 1;
		       $k++;
		      while ($k<strlen($st) && $nested>0) {
			$ch = substr($st,$k,1);
			if ($ch=="(") $nested++;
			else if ($ch==")") $nested--;
			$k++;
		      }
	       }
    } else { 
      return "Error: incorrect syntax in ".$st." at position "+$k;
    }
    $st = substr($st,0,$j+1) . "safepow(" . substr($st,$j+1,($i-$j-1)) . "," . substr($st,$i+1,($k-$i-1)) . ")" . substr($st,$k);
    //$st= st.slice(0,$j+1)+"pow("+st.slice($j+1,i)+","+st.slice(i+1,$k)+")"+ st.slice($k);
  }
  
  while (!$skipfactorial && ($i=strpos($st,"!"))) {
    //find left argument
    if ($i==0) return "Error: missing argument";
    $j = $i-1;
    $ch = substr($st,$j,1);
    if ($ch>="0" && $ch<="9") {// look for (decimal) number
      $j--;
      while ($j>=0 && ($ch=substr($st,$j,1))>="0" && $ch<="9") $j--;
      if ($ch==".") {
        $j--;
        while ($j>=0 && ($ch=substr($st,$j,1))>="0" && $ch<="9") $j--;
      }
    } else if ($ch==")") {// look for matching opening bracket and function name
      $nested = 1;
      $j--;
      while ($j>=0 && $nested>0) {
        $ch = substr($st,$j,1);
        if ($ch=="(") $nested--;
        else if ($ch==")") $nested++;
        $j--;
      }
      while ($j>=0 && ($ch=substr($st,$j,1))>="a" && $ch<="z" || $ch>="A" && $ch<="Z")
        $j--;
    } else if ($ch>="a" && $ch<="z" || $ch>="A" && $ch<="Z" || $ch=='$') {// look for variable
      $j--;
      while ($j>=0 && (($ch=substr($st,$j,1))>="a" && $ch<="z" || $ch>="A" && $ch<="Z" || $ch=='$'))
        $j--;
    } else { 
      return "Error: incorrect syntax in ".$st." at position "+$j;
    }
    $st= substr($st,0,$j+1)."factorial(".substr($st,$j+1,($i-$j-1)).")".substr($st,$i+1);
  }
  //down here so log10 doesn't get changed to log*10
  //$st = str_replace("log","log10",$st);
  $st= preg_replace("/^log([^a-zA-Z])/","log10$1",$st);
  $st= preg_replace('/([^a-zA-Z$])log$/',"\\1log10",$st);
  $st= preg_replace('/([^a-zA-Z$])log([^a-zA-Z])/',"\\1log10$2",$st);
  
  //$st = str_replace("ln","log",$st);
  $st= preg_replace("/^ln([^a-zA-Z])/","log$1",$st);
  $st= preg_replace('/([^a-zA-Z$])ln$/',"\\1log",$st);
  $st= preg_replace('/([^a-zA-Z$])ln([^a-zA-Z])/',"\\1log$2",$st);
  $st= preg_replace('/^\s*\((\$[a-zA-Z\d_]+)\)\s*=/',"$1 =",$st);
  if ($ignorestrings) {
	  foreach ($strmatches as $k=>$match) {
		 $st =  str_replace("(#$k#)",$match[0],$st);
	  }
  }
//echo "st: $st<br/>";
  return $st;
}

function safepow($base,$power) {
	if ($base==0) {if($power==0) {return sqrt(-1);} else {return 0;}}
	if ($base<0 && floor($power)!=$power) {
		for ($j=3; $j<50; $j+=2) {
			if (abs(round($j*$power)-($j*$power))<.000001) {
				if (round($j*$power)%2==0) {
					return exp($power*log(abs($base)));
				} else {
					return -1*exp($power*log(abs($base)));
				}
			}
		}
		return sqrt(-1);
	}
	if (floor($base)==$base && floor($power)==$power && $power>0) { //whole # exponents
		$result = pow(abs($base),$power);
	} else { //fractional & negative exponents (pow can't handle?)
		$result = exp($power*log(abs($base)));
	}
	if (($base < 0) && ($power % 2 != 0)) {
		$result = -($result);
	}
	return $result;
}

function factorial($x) {
	for ($i=$x-1;$i>0;$i--) {
		$x *= $i;	
	}
	return ($x<0?false:($x==0?1:$x));
}
//basic trig cofunctions
function sec($x) {
	return (1/cos($x));
}
function csc($x) {
	return (1/sin($x));
}
function cot($x) {
	return (1/tan($x));
}
function sech($x) {
	return (1/cosh($x));
}
function csch($x) {
	return (1/sinh($x));
}
function coth($x) {
	return (1/tanh($x));
}

function decimaltofraction($d,$format="fraction",$maxden = 5000) {
	if (floor($d)==$d) {
		return floor($d);
	}
	$numerators = array(0, 1);
	$denominators = array(1, 0);
	
	$d2 = $d;
	$calcD = -1;
	$prevCalcD = -1;
	for ($i = 2; $i < 1000; $i++)  {
		$L2 = floor($d2);
		$numerators[$i] = $L2 * $numerators[$i-1] + $numerators[$i-2];
		//if (Math.abs(numerators[i]) > maxNumerator) return;
		$denominators[$i] = $L2 * $denominators[$i-1] + $denominators[$i-2];
		if (abs($denominators[$i])>$maxden) {
			break;
		}
		$calcD = $numerators[$i] / $denominators[$i];
		if ($calcD == $prevCalcD) { break; }
	
		//appendFractionsOutput(numerators[i], denominators[i]);
	
		if ($calcD == $d) { break;}
	
		$prevCalcD = $calcD;
	
		$d2 = 1/($d2-$L2);
	}
	if (abs($numerators[$i]/$denominators[$i] - $d)>1e-9) {
		return $d;
	}
	if ($format=="mixednumber") {
		$w = floor($numerators[$i]/$denominators[$i]);
		if ($w>0) {
			$n = $numerators[$i] - $w*$denominators[$i];
			return "$w $n/".$denominators[$i];
		} else {
			return $numerators[$i].'/'.$denominators[$i];
		}
	} else {
		return $numerators[$i].'/'.$denominators[$i];
	}
}

?>
