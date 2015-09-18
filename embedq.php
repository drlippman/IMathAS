<?php
//IMathAS:  Embed a Question via iFrame
//(c) 2010 David Lippman

require("./config.php");
require("i18n/i18n.php");
header('P3P: CP="ALL CUR ADM OUR"');
$public = '?public=true';
$publica = '&public=true';
if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }

require("./assessment/displayq2.php");

$sessiondata = array();
$sessiondata['graphdisp'] = 1;
$sessiondata['mathdisp'] = 1;
$showtips = 2;
$useeqnhelper = 4;
$sessiondata['drill']['cid'] = 0;
$sessiondata['drill']['sa'] = 0;
if (empty($_GET['id'])) {
	echo 'Need to supply an id';
	exit;
}
$qsetid=$_GET['id'];
$sessiondata['coursetheme'] = $coursetheme;

$page_formAction = "embedq.php?id=$qsetid";
if (isset($_GET['noscores'])) {
	$page_formAction .= '&noscores=true';
}
if (isset($_GET['noregen'])) {
	$page_formAction .= '&noregen=true';
}

$showans = false;
if (isset($_POST['seed'])) {
	list($score,$rawscores) = scoreq(0,$qsetid,$_POST['seed'],$_POST['qn0']);
	if (strpos($score,'~')===false) {
		$after = round($score,1);
		if ($after < 0) { $after = 0;}
	} else {
		$fparts = explode('~',$score);
		$after = array();
		foreach ($fparts as $k=>$fpart) {
			$after[$k] = round($fpart,2);
			if ($after[$k]<0) {$after[$k]=0;}
		}
		$after = implode('~',$after);
	}
	$lastanswers[0] = stripslashes($lastanswers[0]);
	$page_scoreMsg =  printscore($after,$qsetid,$_POST['seed']);
	$pts = getpts($after);
	$page_scoreMsg .= '<script type="text/javascript">
	function inIframe() {
	 try {
        	return window.self !== window.top;
         } catch (e) {
        	return true;
         }
	}
	if (inIframe()) {
		window.parent.postMessage('.$pts.',"*");
	}
	</script>';
	if (isset($_GET['noregen'])) {
		$seed = $_POST['seed'];
	} else if (getpts($score)<1) {
		$showans = true;
		$seed = $_POST['seed'];
	} else {
		unset($lastanswers);
		$seed = rand(1,9999);
	}
} else {
	$page_scoreMsg = '';
	$seed = rand(1,9999);
}

$flexwidth = true; //tells header to use non _fw stylesheet
$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/assessment/mathquill.css?v=102113\" type=\"text/css\" />";
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')!==false) {
	$placeinhead .= '<!--[if lte IE 7]><style style="text/css">
		.mathquill-editable.empty { width: 0.5em; }
		.mathquill-rendered-math .numerator.empty, .mathquill-rendered-math .empty { padding: 0 0.25em;}
		.mathquill-rendered-math sup { line-height: .8em; }
		.mathquill-rendered-math .numerator {float: left; padding: 0;}
		.mathquill-rendered-math .denominator { clear: both;width: auto;float: left;}
		</style><![endif]-->';
}
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/mathquill_min.js?v=102113\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/mathquilled.js?v=070214\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/AMtoMQ.js?v=102113\"></script>";
$placeinhead .= '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqntips.js?v=032810\"></script>";

$useeditor = 1;
require("./assessment/header.php");

if ($page_scoreMsg != '' && !isset($_GET['noscores'])) {
	echo '<div class="review">' . _('Score on last question:') . $page_scoreMsg;
	echo '</div>';
}

if ($showans) {
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit()\">\n";
	echo "<p>" . _('Displaying last question with solution') . " <input type=submit name=\"next\" value=\"" . _('New Question') . "\"/></p>\n";
	displayq(0,$qsetid,$seed,2,true,0);
	echo "</form>\n";
} else {
	$doshowans = 0;
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit()\">\n";
	echo "<input type=\"hidden\" name=\"seed\" value=\"$seed\" />";
	$lastanswers = array();
	displayq(0,$qsetid,$seed,$doshowans,true,0);
	echo "<input type=submit name=\"check\" value=\"" . _('Check Answer') . "\">\n";
	echo "</form>\n";
}

require("./footer.php");


function getansweights($code,$seed) {
	$foundweights = false;
	if (($p = strpos($code,'answeights'))!==false || strpos($code,'anstypes')===false) {
		$p = strpos($code,"\n",$p);
		$weights = sandboxgetweights($code,$seed);
		if (is_array($weights)) {
			return $weights;
		}
		
	} 
	if (!$foundweights) {
		preg_match('/anstypes\s*=(.*)/',$code,$match);
		$n = substr_count($match[1],',')+1;
		if ($n>1) {
			$weights = array_fill(0,$n-1,round(1/$n,3));
			$weights[] = 1-array_sum($weights);
			return $weights;
		} else {
			return array(1);
		}
	}
}

function sandboxgetweights($code,$seed) {
	srand($seed);
	eval(interpret('control','multipart',$code));
	if (!isset($answeights)) {
		return false;
	} else if (is_array($answeights)) {
		return $answeights;
	} else {
		return explode(',',$answeights);
	}
}

function printscore($sc,$qsetid,$seed) {
	global $imasroot;
	$poss = 1;
	if (strpos($sc,'~')===false) {
		$sc = str_replace('-1','N/A',$sc);
		$out = sprintf(_('%1$s out of %2$s'), $sc, $poss);
		$pts = $sc;
		if (!is_numeric($pts)) { $pts = 0;}
	} else {
		$query = "SELECT control FROM imas_questionset WHERE id='$qsetid'";
		$result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		$control = mysql_result($result,0,0);
		$ptposs = getansweights($control,$seed);
		for ($i=0; $i<count($ptposs)-1; $i++) {
			$ptposs[$i] = round($ptposs[$i]*$poss,2);
		}
		//adjust for rounding
		$diff = $poss - array_sum($ptposs);
		$ptposs[count($ptposs)-1] += $diff;
		
		
		$pts = getpts($sc);
		$sc = str_replace('-1','N/A',$sc);
		//$sc = str_replace('~',', ',$sc);
		$scarr = explode('~',$sc);
		foreach ($scarr as $k=>$v) {
			if ($ptposs[$k]==0) {
				$pm = 'gchk';
			} else if (!is_numeric($v) || $v==0) { 
				$pm = 'redx';
			} else if (abs($v-$ptposs[$k])<.011) {
				$pm = 'gchk';
			} else {
				$pm = 'ychk';
			}
			$bar = "<img src=\"$imasroot/img/$pm.gif\" />";
			$scarr[$k] = "$bar $v/{$ptposs[$k]}";
		}
		$sc = implode(', ',$scarr);
		//$ptposs = implode(', ',$ptposs); 
		$out = sprintf(_('%1$s out of %2$s (parts: %3$s)'), $pts, $poss, $sc);
	}	
	$bar = '<span class="scorebarholder">';
	if ($poss==0) {
		$w = 30;
	} else {
		$w = round(30*$pts/$poss);
	}
	if ($w==0) {$w=1;}
	if ($w < 15) { 
	     $color = "#f".dechex(floor(16*($w)/15))."0";
	} else if ($w==15) {
	     $color = '#ff0';
	} else { 
	     $color = "#". dechex(floor(16*(2-$w/15))) . "f0";
	}
	
	$bar .= '<span class="scorebarinner" style="background-color:'.$color.';width:'.$w.'px;">&nbsp;</span></span> ';
	return $bar . $out;	
}

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) { 
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}


?>
