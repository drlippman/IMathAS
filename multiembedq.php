<?php

//Embed for Econ questions
/*
- Page for econ stuff:
  - one or multiple questions displayed
  - use Embed approach (so submit one question/part at a time)
  - One regen button for the whole page (will regen whole question group)
  - show results on submit, allow reattempts
  - No Show Answers


  - On initial page load:
    - choose seed.  fill $seeds array for group
    - Display "try new version of this/these problem(s)" button
      - Triggers each embed to regen, or reload page
    - Each question is displayed ala embedded showtest
    - Each question gets submit button, does background submit
    - On submit, show scored, but no show answer
    */

$init_skip_csrfp = true;
require("./init_without_validate.php");
unset($init_skip_csrfp);
require("i18n/i18n.php");
require("includes/JWT.php");
header('P3P: CP="ALL CUR ADM OUR"');
$sessiondata = array();
/*
if (isset($_GET['graphdisp'])) {
	$sessiondata['graphdisp'] = intval($_GET['graphdisp']);
	setcookie("multiembedq-graphdisp", $sessiondata['graphdisp']);
} else if (isset($_COOKIE['multiembedq-graphdisp'])) {
	$sessiondata['graphdisp'] = intval($_COOKIE['multiembedq-graphdisp']);
} else {
	$sessiondata['graphdisp'] = 1;
}
$sessiondata['mathdisp'] = 3;
*/
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
if (isset($_GET['graphdisp'])) { //currently same is used for graphdisp and drawentry
	$sessiondata['userprefs']['graphdisp'] = filter_var($_GET['graphdisp'], FILTER_SANITIZE_NUMBER_INT);
	$sessiondata['userprefs']['drawentry'] = filter_var($_GET['graphdisp'], FILTER_SANITIZE_NUMBER_INT);
	setcookie("embedquserprefs", json_encode(array(
		'graphdisp'=>$sessiondata['userprefs']['graphdisp'],
		'drawentry'=>$sessiondata['userprefs']['drawentry']
		)),0,'','',false,true);
}
foreach(array('graphdisp','mathdisp','useed') as $key) {
	$sessiondata[$key] = $sessiondata['userprefs'][$key];
}

$showtips = 2;
$useeqnhelper = 4;
$useeditor = 1;
$sessiondata['secsalt'] = "12345";
$cid = "embedq";

if (isset($CFG['GEN']['JWTsecret'])) {
	$JWTsecret = $CFG['GEN']['JWTsecret'];
} else if (getenv('AWS_SECRET_KEY')) {
	$JWTsecret = getenv('AWS_SECRET_KEY');
} else {
	$JWTsecret = "testing";
}

if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
} else {
 	 $urlmode = 'http://';
}
//settings option:
//  0: never show answer
//  1: show answer button after an attempt
//  2: always show answer button
if (isset($CFG['multiembed-showans'])) {
	$showanstype = $CFG['multiembed-showans'];
} else {
	$showanstype = 1;
}


function saveAssessData() {
	global $qids, $seeds, $rawscores, $attempts, $lastanswers, $sameseed, $theme, $targetid, $JWTsecret;
	$JWTsess['qids'] = $qids;
	$JWTsess['seeds'] = $seeds;
	$JWTsess['rawscores'] = $rawscores;
	$JWTsess['attempts'] = $attempts;
	$JWTsess['lastanswers'] = $lastanswers;
	$JWTsess['sameseed'] = $sameseed;
	$JWTsess['theme'] = $theme;
	$JWTsess['targetid'] = $targetid;
	return JWT::encode($JWTsess, $JWTsecret);
}

$JWTsess = array();
if (isset($_REQUEST['asidverify'])) {
	try {
		$JWTsess = JWT::decode($_REQUEST['asidverify'], $JWTsecret);
	} catch (Exception $e) {
		echo "Invalid session or something";
		exit;
	}
}

if (isset($JWTsess->qids) && (!isset($_GET['id']) || $_GET['id']==implode('-',$JWTsess->qids)) && !isset($_GET['regen'])) {
	$qids = $JWTsess->qids;
	$seeds = $JWTsess->seeds;
	$rawscores = $JWTsess->rawscores;
	$attempts = $JWTsess->attempts;
	$lastanswers = $JWTsess->lastanswers;
	$sameseed = $JWTsess->sameseed;
	$theme = $JWTsess->theme;
	$targetid = $JWTsess->targetid;
} else {
	$qids = explode("-",$_GET['id']);

	if (isset($_GET['sameseed']) && $_GET['sameseed']==1) {
		$seeds = array_fill(0,count($qids), rand(5000,9999));
		$sameseed = 1;
	} else {
		$seeds = array();
		foreach ($qids as $i=>$v) {
			$seeds[$i] = rand(5000,9999);
		}
		$sameseed = 0;
	}
	$rawscores = array_fill(0,count($qids), -1);
	$attempts = array_fill(0,count($qids), 0);
	$lastanswers = array_fill(0,count($qids), '');
	if (isset($_GET['theme'])) {
		$theme = preg_replace('/\W/','',$_GET['theme']);
	}
	if (isset($_GET['iframe_resize_id'])) {
		$targetid = preg_replace('/[^\w:.-]/','',$_GET['iframe_resize_id']);
	} else if (isset($_GET['frame_id'])) {
		$targetid = preg_replace('/[^\w:.-]/','',$_GET['frame_id']);
	}
	$jwtstring = saveAssessData();
}

$qids = array_map('Sanitize::onlyInt',$qids);
$seeds = array_map('Sanitize::onlyInt',$seeds);

require("./assessment/displayq2.php");
$GLOBALS['assessver'] = 2;

if (isset($_GET['action']) && $_GET['action']=='scoreembed') {
	//load filter
	$loadgraphfilter = true;
	require_once("./filter/filter.php");

	//need question ids, attempts, seeds.  Put in query string, or??
	$qn = Sanitize::onlyInt($_POST['toscore']);
	$colors = array();
	$GLOBALS['scoremessages'] = '';
	$GLOBALS['questionmanualgrade'] = false;

	list($unitrawscore,$rawscores[$qn]) = scoreq($qn,$qids[$qn],$seeds[$qn],$_POST["qn$qn"],$attempts[$qn],1);
	$attempts[$qn]++;
	$jwtstring = saveAssessData();

	if (strpos($rawscores[$qn],'~')!==false) {
		$colors = explode('~',$rawscores[$qn]);
	} else {
		$colors = array($rawscores[$qn]);
	}
	$quesout = '';
	ob_start();
	displayq($qn,$qids[$qn],$seeds[$qn],($showanstype>0),$showhints,$attempts[$qn],false,false,false,$colors);
	$quesout .= ob_get_clean();
	$quesout = substr($quesout,0,-7).'<br/><input type="button" class="btn" value="'. _('Submit'). '" onclick="assessbackgsubmit('.$qn.',\'submitnotice'.$qn.'\')" /><span id="submitnotice'.$qn.'"></span></div>';
	echo '<input type="hidden" id="verattempts'.$qn.'" value="'.Sanitize::encodeStringForDisplay($attempts[$qn]).'"/>';
	echo $quesout;

	//"save" session
	echo '<script type="text/javscript">$("#asidverify").val("'.$jwtstring.'");</script>';
	exit;
}



$flexwidth = true; //tells header to use non _fw stylesheet
$placeinhead = '<style type="text/css">html,body {margin:0px;} div.question {width: auto;} div.review {width: auto; margin-top: 5px;} body {height:auto;}</style>';
if ($targetid != '') {
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
	      iframe_resize_id: "'.$targetid.'",
	      element_id: "'.$targetid.'",
	      frame_id: "'.$targetid.'"
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
	$(function() {
		$(window).on("ImathasEmbedReload", sendresizemsg);
	});
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
if ($theme != '') {
	$sessiondata['coursetheme'] = $theme.'.css';
}
require("./assessment/header.php");
if ($sessiondata['graphdisp'] == 1) {
	echo '<div style="position:absolute;width:1px;height:1px;left:0px:top:-1px;overflow:hidden;"><a href="multiembedq.php?'.Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING']).'&graphdisp=0">' . _('Enable text based alternatives for graph display and drawing entry') . '</a></div>';
}
echo '<script type="text/javascript">var assesspostbackurl="' .$urlmode. Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot . '/multiembedq.php?embedpostback=true&action=scoreembed";</script>';

echo '<input type="hidden" id="asidverify" value="'.$jwtstring.'"/>';
echo '<input type="hidden" id="disptime" value="'.time().'"/>';
echo '<input type="hidden" id="isreview" value="0"/>';
echo '<p><a href="multiembedq.php?id='.Sanitize::encodeUrlParam($_GET['id']).'&amp;regen=1&amp;sameseed='.Sanitize::encodeUrlParam($sameseed).'&amp;theme='.Sanitize::encodeUrlParam($theme).'&amp;iframe_resize_id='.Sanitize::encodeUrlParam($targetid).'">';
if (count($qids)>1) {
	echo _('Try Another Version of These Questions').'</a></p>';
} else {
	echo _('Try Another Version of This Question').'</a></p>';
}
$showhints = true;

//preload qsdata
$placeholders = Sanitize::generateQueryPlaceholders($qids);
$stm = $DBH->prepare("SELECT id,qtype,control,qcontrol,qtext,answer,hasimg,extref,solution,solutionopts FROM imas_questionset WHERE id IN ($placeholders)");
$stm->execute($qids);
$qsdata = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$qsdata[$row['id']] = $row;
}

foreach ($qids as $i=>$qid) {
	echo '<div id="embedqwrapper'.$i.'" class="embedqwrapper">';
	$quesout = '';
	ob_start();
	$qdatafordisplayq = $qsdata[$qid];
	displayq($i,$qid,$seeds[$i],($showanstype==2),$showhints,$attempts[$i]);
	$quesout .= ob_get_clean();
	$quesout = substr($quesout,0,-7).'<br/><input type="button" class="btn" value="'. _('Submit'). '" onclick="assessbackgsubmit('.$i.',\'submitnotice'.$i.'\')" /><span id="submitnotice'.$i.'"></span></div>';
	echo $quesout;
	echo '<input type="hidden" id="verattempts'.$i.'" value="'.Sanitize::encodeStringForDisplay($attempts[$i]).'"/>';
	echo '</div>';
}


require("./footer.php");

?>
