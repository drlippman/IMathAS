<?php
//IMathAS:  IMathAS question interpreter.  Defines how the IMathAS question
//language works.
//(c) 2006 David Lippman
require_once("mathphp.php");
array_push($allowedmacros,"loadlibrary","array","where");
$disallowedwords = array("exit","die");
$disallowedvar = array('$link','$qidx','$qnidx','$seed','$qdata','$toevalqtxt','$la','$GLOBALS','$laparts','$anstype','$kidx','$iidx','$tips','$options','$partla','$partnum','$score');
		
function interpret($blockname,$anstype,$str)
{
	if ($blockname=="qtext") {
		$str = str_replace('"','\"',$str);
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("\n\n","<br/><br/>\n",$str);
		return $str;
	} else {
		global $allowedmacros;
		global $mathfuncs;
		global $disallowedwords,$disallowedvar;
		$str = str_replace(array('\\frac','\\tan','\\root','\\vec'),array('\\\\frac','\\\\tan','\\\\root','\\\\vec'),$str);
		$str .= ' ';
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("&&\n","<br/>",$str);
		$str = str_replace("&\n"," ",$str);
		$commands = explode("\n",$str);
		foreach($commands as $com) {
			if (substr($com,0,2)=="//") {continue;}
			do {
				$origcom = $com;
				$com = preg_replace('/(\'[^\']*)\/\/([^\']*\')/',"$1~~$2",$com);
				$com = preg_replace('/("[^"]*)\/\/([^"]*")/',"$1~~$2",$com);
			} while ($com != $origcom);
			$com = preg_replace('/^(.+)\/\/.+$/',"$1",$com);
			$com = str_replace('~~','//',$com);
			//causing problems
			//$com = str_replace($disallowedwords,"",$com);
			//$com = preg_replace('/&(\w+);/',"&$1 exit",$com);
			//causing problems in showasciisvg, so removed for now
			//$com = str_replace(';','',$com);
			//$com = str_replace(' exit',';',$com);
			if (trim($com) == "") { continue;}
			//check for "for" loop
			$forcond = array();
			if (preg_match('/^for\s*\(\s*(\$\w+)\s*\=\s*(\d+|\$\w+)\s*\.\.\s*(\d+|\$\w+)\s*\)\s*\{(.+)\}\s*$/',$com,$matches)) {
				$forcond = array_slice($matches,1,3);
				$com = $matches[4];
			}
			//check for "if" conditional
			$ifcond = '';
			if (($l=strpos($com," if "))!==false) {
				$ifpos = 0;
				$poscnt = 0;
				foreach(token_get_all('<?php '.$com) as $c) { //check "if" is really legit
					if (is_array($c)) {
						if ($c[1]=='if') {
							$ifpos = $poscnt-6; 
							break;
						} else {
							$poscnt += strlen($c[1]);
						}
					} else {
						$poscnt += strlen($c);
					}
				}
				if ($ifpos>0) {
					$ifcond = rtrim(substr($com,$ifpos+3));
					$com = substr($com,0,$ifpos);
				}
			}
			if (preg_match('/^\s*([$\w\,$\[\]]+)\s*=(.*)/',$com,$matches)) {

				//if var side has comma, treat as list(vars)=arraymacro
				if (strpos($matches[1],",")!==false) {
					$lsvars = explode(",",str_replace(" ","",$matches[1]));
					foreach ($lsvars as $v) {
						if (strpos($v,"$")!==0) {
							echo "left side $v is not a variable<BR>\n";
							return false;
						}
						if (in_array($v,$disallowedvar)) {
							echo "$v is not an allowed variable name<BR>\n";
							return false;
						}
					}
					//$com = str_replace($matches[1],"list({$matches[1]})",$com);
					$com = substr_replace($com,"list({$matches[1]}) ",0,strpos($com,'='));
				} else	{
					if (strpos($matches[1],"$")!==0) {
						echo "Left side {$matches[1]} is not a variable<BR>\n";
						return false;
					}
					if (in_array($matches[1],$disallowedvar)) {
						echo "{$matches[1]} is not an allowed variable name<BR>\n";
						return false;
					}
				}
				
				//check that macros used are allowed macros
				//only want to check stuff not in quotes
				$rsnoquote = preg_replace('/"[^"]*"/','""',$matches[2]);
				$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
				if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
					$ismath = true;
					for ($i=0;$i<count($funcs[1]);$i++) {
						if (strpos($funcs[1][$i],"$")===false) {
							if (!in_array($funcs[1][$i],$allowedmacros)) {
								echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
								return false;
							}
							if (!in_array($funcs[1][$i],$mathfuncs) && !preg_match('/[\*\/\^\!]/',$rsnoquote)) {
								$ismath = false;
							}
						}
					}
					//check for "where" conditional
					if (($l=strpos($com,"where"))!==false) {
						$wherepos = 0;
						$poscnt = 0;
						foreach(token_get_all('<?php '.$com) as $c) {
							if (is_array($c)) {
								if ($c[1]=='where') {
									$wherepos = $poscnt-6; 
									break;
								} else {
									$poscnt += strlen($c[1]);
								}
							} else {
								$poscnt += strlen($c);
							}
						}
						if ($wherepos>0) {
							$left = substr($com,0,$wherepos);
							if ($ismath) {
								//$com = str_replace($matches[2],mathphp($matches[2],null),$com);
								$eqpos = strpos($left,'=');
								$lhs = substr($left,0,$eqpos);
								$rhs = substr($left,$eqpos);
								$rhs = mathphp($rhs,null);	
								$left = $lhs . $rhs;
							}
							$cond = substr($com,$wherepos+5);
							$cond = str_replace('!=','#=',$cond);
							$cond = mathphp($cond,null,true);
							$cond = str_replace('#=','!=',$cond);
							$com = '$count=0; do{'.$left.'; $count++;} while (!('.$cond.')&&($count<200)); if ($count==200) {echo "where not met in 200 iterations";}';
						}
					} else if ($ismath) {
						//$com = str_replace($matches[2],mathphp($matches[2],null),$com);
						$eqpos = strpos($com,'=');
						$lhs = substr($com,0,$eqpos);
						$rhs = substr($com,$eqpos);
						$rhs = mathphp($rhs,null);	
						$com = $lhs . $rhs;
					}
				} else{ //if (strpos($matches[2],'"')===false && strpos($matches[2],"'")===false) { //if right side is not quoted and no macros, mathphp it 
					//do mathphp on right side
					$eqpos = strpos($com,'=');
					$lhs = substr($com,0,$eqpos);
					$rhs = substr($com,$eqpos);
					$rhs = mathphp($rhs,null);	
					$com = $lhs . $rhs;
				//} else { //right side is quoted
					
					//all answer cleaners moved to displayq
				}
				
			} else if (($libs = preg_replace('/.*loadlibrary\(([^\)]*)\).*/',"$1",$com)) != $com) {
				loadlibrary($libs);
				$com = '';
			} else if (($ns = preg_replace('/.*setseed\(([^\)]*)\).*/',"$1",$com)) != $com) {
				if ($ns=="userid") {
					if (isset($GLOBALS['teacherid']) && isset($GLOBALS['teacherreview'])) { //reviewing in gradebook
						srand($GLOBALS['teacherreview']);	
					} else { //in assessment
						srand($GLOBALS['userid']); 
					}
				} else {
					srand($ns);
				}
				$com = '';
			} else {
				echo "Line {$matches[0]} invalid: $com<BR>\n";
				return false;
			}
			if ($ifcond!='') {
				$rsnoquote = preg_replace('/"[^"]*"/','""',$ifcond);
				$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
				if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
					$ismath = true;
					for ($i=0;$i<count($funcs[1]);$i++) {
						if (strpos($funcs[1][$i],"$")===false) {
							if (!in_array($funcs[1][$i],$allowedmacros)) {
								echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
								return false;
							}
						}
					}
				}
				if (strpos($ifcond,'"')===false && strpos($ifcond,"'")===false) { //if not quoted, mathphp it 
					$ifcond = str_replace('!=','#=',$ifcond);
					$ifcond = mathphp($ifcond,null,true);
					$ifcond = str_replace('#=','!=',$ifcond);
				}
								
				$com = "if ($ifcond) { $com; }";
			} 
			if (count($forcond)==3) {
				$com = "for ({$forcond[0]}=intval({$forcond[1]});{$forcond[0]}<=round(floatval({$forcond[2]}),0);{$forcond[0]}++) { $com; }";
			}
			$out .= "$com;\n";
		}
		return $out;
	}

}
function preg_mathphp_callback($matches) {
	//need to eval now, because this will be set as a string
	return (eval('return ('.mathphp($matches[1],null).');'));
}
function loadlibrary($str) {
	$str = str_replace(array("/",".",'"'),"",$str);
	$libs = explode(",",$str);
	$libdir = rtrim(dirname(__FILE__), '/\\') .'/libs/';
	foreach ($libs as $lib) {
		if (is_file($libdir . $lib.".php")) {
			include_once($libdir.$lib.".php");
		} else {
			echo "Error loading library $lib\n";	
		}
	}
}

?>
