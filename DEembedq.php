<?php
//IMathAS:  Embed a Question via iFrame
//(c) 2010 David Lippman

require("./init_without_validate.php");
require("i18n/i18n.php");
require("includes/DEutil.php");
header('P3P: CP="ALL CUR ADM OUR"');

if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }

require("./assessment/displayq2.php");
$GLOBALS['assessver'] = 1;

$sessiondata = array();
$sessiondata['graphdisp'] = 1;
$sessiondata['mathdisp'] = 3;
$showtips = 2;
$useeqnhelper = 4;

if (isset($_GET['jssubmit']) && $_GET['jssubmit']==1) {
	$jssubmit = true;
} else {
	$jssubmit = false;
}

if (empty($_GET['id'])) {
	echo 'Need to supply an id';
	exit;
}

$qsetid=intval($_GET['id']);
$sessiondata['coursetheme'] = $coursetheme;

$page_formAction = "DEembedq.php?id=$qsetid";

$showans = false;

$flexwidth = true; //tells header to use non _fw stylesheet
$placeinhead .= '<style type="text/css">div.question {width: auto;} div.review {width: auto; margin-top: 5px;} body {height:auto;}</style>';
$useeditor = 1;
require("./assessment/header.php");

if (isset($_GET['showscored'])) {
	//DE is requesting that the question be redisplayed with right/wrong markers
	list($params, $auth, $sig) = parse_params($_SERVER['QUERY_STRING']);
	/*
	if ($auth=='') {
		echo 'Error - need to provide auth= for redisplay';
		exit;
	}
	if ($sig=='') {
		echo 'Error - need to sign query string for redisplay';
		exit;
	}
	$query = "SELECT password FROM imas_users WHERE SID='".stripslashes($auth)."'";
	$result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
	$row = mysql_fetch_row($result);
	if (!check_signature($params, $row[0], $sig)) {
		echo 'Error - invalid signature';
		exit;
	}
	*/
	$showans = (!isset($params['showans']) || $params['showans']=='true');

	$lastanswers = array();
	list($seed, $rawscores, $lastanswers[0]) = explode(';', $params['showscored'], 3);
	$rawscores = explode('~',$rawscores);
	$seed = intval($seed);

	displayq(0, $qsetid, $seed, $showans?2:0, true, 0,false,false,false,$rawscores);
	echo '<script type="text/javascript">
		$(function() {
			var height = $("body").outerHeight();
			window.parent.postMessage("action=resize&id='.$qsetid.'&height="+height,"*");
		});
		</script>';

} else if (isset($_POST['seed'])) {
	//time to score the question
	$seed = intval($_POST['seed']);
	$lastanswers = array();

	list($score,$rawscores) = scoreq(0,$qsetid,$_POST['seed'],$_POST['qn0'],1);
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
	if (strpos($rawscores,'~')===false) {
		$rawafter = round($scores,1);
		if ($rawafter < 0) { $rawafter = 0;}
	} else {
		$fparts = explode('~',$rawscores);
		$rawafter = array();
		foreach ($fparts as $k=>$fpart) {
			$rawafter[$k] = round($fpart,2);
			if ($rawafter[$k]<0) {$rawafter[$k]=0;}
		}
		$rawafter = implode('~',$rawafter);
	}
	//DB $lastanswers[0] = stripslashes($lastanswers[0]);

	$pts = getpts($after);

	$params = array('action'=>'updatescore', 'id'=>$qsetid, 'score'=>$pts, 'redisplay'=>"$seed;$rawafter;{$lastanswers[0]}");
	$postAuth = Sanitize::stripHtmlTags($_POST['auth']);
	if (isset($postAuth)) {
		//DB $query = "SELECT password FROM imas_users WHERE SID='".$_POST['auth']."'";
		//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID");
		$stm->execute(array(':SID'=>$postAuth));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$sig = $row[0];
	} else {
		$sig = '';
	}
	$signed = build_signed_querystring($params, $sig);

	echo '<script type="text/javascript">
	$(function() {
		window.parent.postMessage("'.$signed.'","*");
	});
	</script>';

	echo '<p>Saving score... <img src="img/updating.gif" alt="Updating"/></p>';

} else {
	$lastanswers = array();
	if (isset($_GET['redisplay'])) {
		//DE is requesting that the question be redisplayed
		list($params, $auth, $sig) = parse_params($_SERVER['QUERY_STRING']);
		list($seed, $rawscores, $lastanswers[0]) = explode(';', $params['redisplay'],3);
		$rawscores = array();
	} else {
		$seed = rand(1,9999);
	}
	$doshowans = 0;
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit(this)\">\n";
	echo "<input type=\"hidden\" name=\"seed\" value=\"$seed\" />";
	if (isset($_GET['auth'])) {
	}
	if (isset($_GET['showhints']) && $_GET['showhints']==0) {
		$showhints = false;
	} else {
		$showhints = true;
	}

	displayq(0, $qsetid, $seed, $doshowans, $showhints, 0);
	if ($jssubmit) {
		echo '<input type="submit" id="submitbutton" style="display:none;"/>';
		echo '<script type="text/javascript">
		$(function() {
			$(window).on("message", function(e) {
				var data = e.originalEvent.data;
				if (data=="submit") {
					$("#submitbutton").click();
				}});
		});
		</script>';
	} else {
		echo "<input type=submit name=\"check\" value=\"" . _('Submit') . "\">\n";
	}
	echo "</form>\n";
	echo '<script type="text/javascript">
		$(function() {
			var height = $("body").outerHeight();
			window.parent.postMessage("action=resize&id='.$qsetid.'&height="+height,"*");
		});
		</script>';
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
