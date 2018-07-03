<?php
//IMathAS:  Embed a Question via iFrame
//(c) 2010 David Lippman

require("./init_without_validate.php");
require("i18n/i18n.php");
require("includes/JWT.php");
header('P3P: CP="ALL CUR ADM OUR"');

if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }

require("./assessment/displayq2.php");
$GLOBALS['assessver'] = 2;

$sessiondata = array();

$prefdefaults = array(
	'mathdisp'=>6,
	'graphdisp'=>1,
	'drawentry'=>1,
	'useed'=>1,
	'livepreview'=>1);

$prefcookie = json_decode($_COOKIE["OEAembeduserprefs"], true);
$sessiondata['userprefs'] = array();
foreach($prefdefaults as $key=>$def) {
	if ($prefcookie!==null && isset($prefcookie[$key])) {
		$sessiondata['userprefs'][$key] = filter_var($prefcookie[$key], FILTER_SANITIZE_NUMBER_INT);
	} else {
		$sessiondata['userprefs'][$key] = $def;
	}
}
if (isset($_GET['graphdisp'])) { //currently same is used for graphdisp and drawentry
	$sessiondata['userprefs']['graphdisp'] = filter_var($_GET['graphdisp'], FILTER_SANITIZE_NUMBER_INT);
	$sessiondata['userprefs']['drawentry'] = filter_var($_GET['graphdisp'], FILTER_SANITIZE_NUMBER_INT);
	setcookie("OEAembeduserprefs", json_encode(array(
		'graphdisp'=>$sessiondata['userprefs']['graphdisp'],
		'drawentry'=>$sessiondata['userprefs']['drawentry']
		)),0,'','',false,true);
}
foreach(array('graphdisp','mathdisp','useed') as $key) {
	$sessiondata[$key] = $sessiondata['userprefs'][$key];
}

$sessiondata['secsalt'] = "12345";
$cid = "embedq";
$showtips = 2;
$useeqnhelper = 4;

$placeinhead = '<style type="text/css"> html,body {margin:0px;} </style>';

$issigned = false;
if (isset($_GET['jwt'])) {
	try {
		//decode JWT.  Stupid hack to convert it into an assocc array
		$QS = json_decode(json_encode(JWT::decode($_GET['jwt'])), true);
	} catch (Exception $e) {
	         echo "JWT Error: ".$e->getMessage();
	         exit;
	}

	if (isset($QS['auth'])) {
		$issigned = true;
	}
} else {
	$QS = $_GET;
}
if (isset($QS['jssubmit']) && $QS['jssubmit']==0) {
	$jssubmit = false;
} else {
	$jssubmit = true;
}

if (empty($QS['id'])) {
	echo 'Need to supply an id';
	exit;
}

$qsetid=intval($QS['id']);

$page_formAction = "OEAembedq.php?id=$qsetid";

if (isset($_GET['frame_id'])) {
	$frameid = preg_replace('/[^\w:.-]/','',$_GET['frame_id']);
	$page_formAction .= '&frame_id='.$frameid;
} else {
	$frameid = "OEAembedq-$qsetid";
}
if (isset($_REQUEST['theme'])) {
	$theme = preg_replace('/\W/','',$_REQUEST['theme']);
	$page_formAction .= '&theme='.$theme;
	$sessiondata['coursetheme'] = $theme.'.css';
}


$showans = false;

$flexwidth = true; //tells header to use non _fw stylesheet
$placeinhead .= '<style type="text/css">div.question {width: auto;} div.review {width: auto; margin-top: 5px;} body {height:auto;}</style>';
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
$useeditor = 1;
require("./assessment/header.php");

if ($sessiondata['graphdisp'] == 1) {
	echo '<div style="position:absolute;width:1px;height:1px;left:0px:top:-1px;overflow:hidden;"><a href="OEAembedq.php?'.Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING']).'&graphdisp=0">Enable text based alternatives for graph display and drawing entry</a></div>';
} else {
	echo '<div style="float:right;"><a href="OEAembedq.php?'.Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING']).'&graphdisp=1">Enable visual graph display and drawing entry</a></div>';
}

//seeds 1-4999 are for summative requests that are signed
//seeds 5000-9999 are for formative requests (unsigned)

if (isset($QS['showscored'])) {
	//DE is requesting that the question be redisplayed with right/wrong markers

	$lastanswers = array();
	list($seed, $rawscores, $lastanswers[0]) = explode(';', $QS['showscored'], 3);
	$rawscores = explode('~',$rawscores);
	$seed = intval($seed);

	$showans = (($issigned || $seed>4999)  && (!isset($QS['showans']) || $QS['showans']=='true'));


	displayq(0, $qsetid, $seed, $showans?2:0, true, 0,false,false,false,$rawscores);

} else if (isset($_POST['seed'])) {
	//time to score the question
	$seed = intval($_POST['seed']);
	$scoredonsubmit = false;
	if (isset($_POST['auth'])) {
		//DB $query = "SELECT password FROM imas_users WHERE SID='{$_POST['auth']}'";
		//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID");
		$stm->execute(array(':SID'=>Sanitize::stripHtmlTags($_POST['auth'])));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$key = $row[0];
		$jwtcheck = json_decode(json_encode(JWT::decode($_POST['jwtchk'], $key)), true);
		if ($jwtcheck['id'] != $qsetid || $jwtcheck['seed'] != $seed) {
			echo "ID/Seed did not check";
			exit;
		} else if ($seed>4999) {
			echo "Seed invalid";
			exit;
		}
		$scoredonsubmit = $jwtcheck['scoredonsubmit'];
    $showans = $jwtcheck['showans'];
	} else {
		$key = '';
		if ($seed<5000) {
			echo "Seed invalid";
			exit;
		}
		$scoredonsubmit = isset($_POST['showscoredonsubmit']);
    $showans = $_POST['showans'];
	}

	$lastanswers = array();

	list($score,$rawscores) = scoreq(0,$qsetid,$seed,$_POST['qn0'],1);
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
		$rawafter = round($rawscores,1);
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
	$lastanswers[0] = $lastanswers[0];

	$pts = getpts($after);

	$params = array('id'=>$qsetid, 'score'=>$pts, 'redisplay'=>"$seed;$rawafter;{$lastanswers[0]}");
	if (isset($_POST['auth'])) {
		$params["auth"] = $_POST['auth'];
	}

	$signed = JWT::encode($params, $key);

	echo '<script type="text/javascript">
	$(function() {
		window.parent.postMessage(JSON.stringify({subject: "lti.ext.mom.updateScore", id: '.$qsetid.', score: '.$pts.', redisplay: "'.str_replace('"','\\"',$params["redisplay"]).'", jwt: "'.$signed.'", frame_id: "' . $frameid . '"}), "*");
	});
	</script>';
	if ($scoredonsubmit) {
		$rawscores = explode('~',$rawafter);

		// set above
    // $showans = (($issigned || $seed>4999)  && (!isset($QS['showans']) || $QS['showans']=='true'));

		displayq(0, $qsetid, $seed, $showans?2:0, true, 0,false,false,false,$rawscores);
	} else {
		echo '<p>Saving score... <img src="img/updating.gif" alt="Saving"/></p>';
	}

} else {
	$lastanswers = array();
	if (isset($QS['redisplay']) && trim($QS['redisplay'])!='') {
		//DE is requesting that the question be redisplayed
		list($seed, $rawscores, $lastanswers[0]) = explode(';', $QS['redisplay'],3);
		$rawscores = array();
	} else {
		if (isset($QS['auth'])) { //is signed
			if (isset($QS['seed']) && intval($QS['seed'])<5000) {
				$seed = intval($QS['seed']);
			} else {
				$seed = rand(1,4999);
			}
		} else {
			if (isset($QS['seed']) && intval($QS['seed'])>4999) {
				$seed = intval($QS['seed']);
			} else {
				$seed = rand(5000,9999);
			}
		}
	}
	$doshowans = 0;
	echo "<form id=\"qform\" method=\"post\" enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit(this)\">\n";
	echo "<input type=\"hidden\" name=\"seed\" value=\"".Sanitize::encodeStringForDisplay($seed)."\" />";
	$scoredonsubmit = false;
	if (isset($QS['showscoredonsubmit']) && ($QS['showscoredonsubmit']=='1' || $QS['showscoredonsubmit']=='true')) {
		echo '<input type="hidden" name="showscoredonsubmit" value="1"/>';
		$scoredonsubmit = true;
	}
  if (($issigned || $seed>4999)  && (!isset($QS['showans']) || $QS['showans']=='true')) {
    echo '<input type="hidden" name="showans" value="1"/>';
		$showansonsubmit = true;
  } else {
    echo '<input type="hidden" name="showans" value="0"/>';
		$showansonsubmit = false;
  }
	if (isset($QS['auth'])) {
		$verarr = array("id"=>$qsetid, "seed"=>$seed, 'scoredonsubmit'=>$scoredonsubmit, 'showans'=>$showansonsubmit);
		//DB $query = "SELECT password FROM imas_users WHERE SID='".addslashes(stripslashes($QS['auth']))."'";
		//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID");
		$stm->execute(array(':SID'=>Sanitize::stripHtmlTags($QS['auth'])));
		$key = $stm->fetchColumn(0);

		echo '<input type="hidden" name="jwtchk" value="'.JWT::encode($verarr,$key).'"/>';
		echo '<input type="hidden" name="auth" value="'.Sanitize::encodeStringForDisplay($QS['auth']).'"/>';
	}
	if (isset($QS['showhints']) && $QS['showhints']==0) {
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
