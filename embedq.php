<?php
//IMathAS:  Embed a Question via iFrame
//(c) 2010 David Lippman
$init_skip_csrfp = true;
require("./init_without_validate.php");
unset($init_skip_csrfp);
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
$prefdefaults = array(
	'mathdisp'=>1,
	'graphdisp'=>1,
	'drawentry'=>1,
	'useed'=>1,
	'livepreview'=>1);
$prefcookie = json_decode($_COOKIE["embedquserprefs"], true);
$sessiondata['userprefs'] = array();
foreach($prefdefaults as $key=>$def) {
	if ($prefcookie!==null && isset($prefcookie[$key])) {
		$sessiondata['userprefs'][$key] = filter_var($prefcookie[$key], FILTER_SANITIZE_NUMBER_INT);
	} else {
		$sessiondata['userprefs'][$key] = $def;
	}
}
foreach(array('graphdisp','mathdisp','useed') as $key) {
	$sessiondata[$key] = $sessiondata['userprefs'][$key];
}

$showtips = 2;
$useeqnhelper = 4;
$sessiondata['drill']['cid'] = 0;
$sessiondata['drill']['sa'] = 0;
$sessiondata['secsalt'] = "12345";
$cid = "embedq";
if (empty($_GET['id'])) {
	echo 'Need to supply an id';
	exit;
}
$qsetid=Sanitize::onlyInt($_GET['id']);

$page_formAction = "embedq.php?id=$qsetid";

if (isset($_GET['theme'])) {
	$theme = preg_replace('/\W/','',$_GET['theme']);
	$sessiondata['coursetheme'] = $theme . '.css';
	$page_formAction .= "&theme=$theme";
} else {
	$sessiondata['coursetheme'] = $coursetheme;
}

if (isset($_GET['noscores'])) {
	$page_formAction .= '&noscores=true';
}
if (isset($_GET['showans'])) {
	//options:
	//  0: never
	//  1: after wrong attempts
	//  2: after all attempts    *default
	//  3: always
	$page_formAction .= '&showans='.Sanitize::onlyInt($_GET['showans']);
} else {
	$_GET['showans'] = 2;
}
if (isset($_GET['noresults'])) {
	$page_formAction .= '&noresults=true';
}
if (isset($_GET['noregen'])) {
	$page_formAction .= '&noregen=true';
}
if (isset($_GET['resizer'])) {
	$page_formAction .= '&resizer=true';
}

if ($_GET['showans']==3) {//show always
	$showans = 1;
} else {
	$showans = 0;
}
$qcol = array();
if (isset($_POST['seed']) && isset($_POST['check'])) {
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
	if (empty($_GET['noresults'])) {
		$qcol = explode('~',$rawscores);
	}
	$lastanswers[0] = $lastanswers[0];
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
	$seed = $_POST['seed'];
	if ($_GET['showans']==2) {
		$showans = 1;
	} else if ($_GET['showans']==1 && getpts($after)<1) {
		$showans = 1;
	}
} else {
	$page_scoreMsg = '';
	$seed = rand(1,9999);
	$lastanswers = array();
}

$flexwidth = true; //tells header to use non _fw stylesheet
$useeditor = 1;
if (isset($_GET['resizer'])) {
	$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/iframeSizer_contentWindow_min.js"></script>';
}
if (isset($_GET['frame_id'])) {
	$frameid = preg_replace('/[^\w:.-]/','',$_GET['frame_id']);
	$placeinhead .= '<script type="text/javascript">
		function sendresizemsg() {
		 if(self != top){
		  var default_height = Math.max(
	              document.body.scrollHeight, document.body.offsetHeight,
	              document.documentElement.clientHeight, document.documentElement.scrollHeight,
	              document.documentElement.offsetHeight);
		  window.parent.postMessage( JSON.stringify({
		      subject: "lti.frameResize",
		      height: default_height,
		      frame_id: "'.$frameid.'"
		  }), "*");
		 }
		}

		if (mathRenderer == "Katex") {
			window.katexDoneCallback = sendresizemsg;
		} else if (typeof MathJax != "undefined") {
			MathJax.Hub.Queue(function () {
				sendresizemsg();
			});
		} else {
			$(function() {
				sendresizemsg();
			});
		}
		</script>';
	if ($sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==3) {
		//in case MathJax isn't loaded yet
		$placeinhead .= '<script type="text/x-mathjax-config">
			MathJax.Hub.Queue(function () {
				sendresizemsg();
			});
			</script>';
	}
}


require("./assessment/header.php");

if ($page_scoreMsg != '' && !isset($_GET['noscores'])) {
	echo '<div class="review">' . _('Score on last question:') . $page_scoreMsg;
	echo '</div>';
}

echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"" . Sanitize::encodeStringForDisplay($page_formAction) . "\" onsubmit=\"doonsubmit()\">\n";
echo "<input type=\"hidden\" name=\"seed\" value=\"" . Sanitize::encodeStringForDisplay($seed) . "\" />";
displayq(0,$qsetid,$seed,$showans,true,0,false,false,false,$qcol);
echo "<p><input type=submit name=\"check\" value=\"" . _('Check Answer') . "\">\n";
if (empty($_GET['noregen'])) {
	echo " <input type=submit name=\"next\" value=\"" . _('New Question') . "\"/>\n";
}
echo '</p>';
/*
if ($showans) {
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"" . Sanitize::encodeStringForDisplay($page_formAction) . "\" onsubmit=\"doonsubmit()\">\n";
	echo "<p>" . _('Displaying last question with solution') . " <input type=submit name=\"next\" value=\"" . _('New Question') . "\"/></p>\n";
	displayq(0,$qsetid,$seed,2,true,0,false,false,false,$qcol);
	echo "</form>\n";
} else {
	$doshowans = 0;
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"" . Sanitize::encodeStringForDisplay($page_formAction) . "\" onsubmit=\"doonsubmit()\">\n";
	echo "<input type=\"hidden\" name=\"seed\" value=\"" . Sanitize::encodeStringForDisplay($seed) . "\" />";
	$lastanswers = array();
	displayq(0,$qsetid,$seed,$doshowans,true,0,false,false,false,$qcol);
	echo "<input type=submit name=\"check\" value=\"" . _('Check Answer') . "\">\n";
	echo "</form>\n";
}
*/

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
	global $DBH,$imasroot;
	$poss = 1;
	if (strpos($sc,'~')===false) {
		$sc = str_replace('-1','N/A',$sc);
		$out = sprintf(_('%1$s out of %2$s'), $sc, $poss);
		$pts = $sc;
		if (!is_numeric($pts)) { $pts = 0;}
	} else {
		//DB $query = "SELECT control FROM imas_questionset WHERE id='$qsetid'";
		//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		//DB $control = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT control FROM imas_questionset WHERE id=:id");
		$stm->execute(array(':id'=>$qsetid));
		$control = $stm->fetchColumn(0);
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
				$pm = 'gchk'; $alt=_('Correct');
			} else if (!is_numeric($v) || $v==0) {
				$pm = 'redx'; $alt=_('Incorrect');
			} else if (abs($v-$ptposs[$k])<.011) {
				$pm = 'gchk'; $alt=_('Correct');
			} else {
				$pm = 'ychk'; $alt=_('Partially correct');
			}
			$bar = "<img src=\"$imasroot/img/$pm.gif\" alt=\"$alt\"/>";
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
