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

require("./config.php");
require("i18n/i18n.php");
header('P3P: CP="ALL CUR ADM OUR"');
$sessiondata = array();
$sessiondata['graphdisp'] = 1;
$sessiondata['mathdisp'] = 3;
$showtips = 2;
$useeqnhelper = 4;
$useeditor = 1;

if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
} else {
 	 $urlmode = 'http://';
}

session_start();
 
function saveAssessData() {
	global $qids, $seeds, $rawscores, $attempts, $lastanswers, $sameseed, $theme, $targetid;
	$_SESSION['qids'] = $qids;
	$_SESSION['seeds'] = $seeds;
	$_SESSION['rawscores'] = $rawscores;
	$_SESSION['attempts'] = $attempts;
	$_SESSION['lastanswers'] = $lastanswers;
	$_SESSION['sameseed'] = $sameseed;
	$_SESSION['theme'] = $theme;
	$_SESSION['targetid'] = $targetid;
	
}

if (isset($_SESSION['qids']) && (!isset($_GET['id']) || $_GET['id']==implode('-',$_SESSION['qids'])) && !isset($_GET['regen'])) {
	$qids = $_SESSION['qids'];
	$seeds = $_SESSION['seeds'];
	$rawscores = $_SESSION['rawscores'];
	$attempts = $_SESSION['attempts'];
	$lastanswers = $_SESSION['lastanswers'];
	$sameseed = $_SESSION['sameseed'];
	$theme = $_SESSION['theme'];
	$targetid = $_SESSION['targetid'];
} else {
	$qids = explode("-",$_GET['id']);
	foreach ($qids as $i=>$v) {
		$qids[$i] = intval($v);
	}
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
	}
	saveAssessData();
}
	
require("./assessment/displayq2.php");                 

if (isset($_GET['action']) && $_GET['action']=='scoreembed') {
	//load filter
	$loadgraphfilter = true;
	require_once("./filter/filter.php");
	
	//need question ids, attempts, seeds.  Put in query string, or??
	$qn = $_POST['toscore'];
	$colors = array();
	$GLOBALS['scoremessages'] = '';
	$GLOBALS['questionmanualgrade'] = false;
	
	list($unitrawscore,$rawscores[$qn]) = scoreq($qn,$qids[$qn],$seeds[$qn],$_POST["qn$qn"],$attempts[$qn],1);
	$attempts[$qn]++;
	saveAssessData();
	
	if (strpos($rawscores[$qn],'~')!==false) {
		$colors = explode('~',$rawscores[$qn]);
	} else {
		$colors = array($rawscores[$qn]);
	}
	$quesout = '';
	ob_start();
	displayq($qn,$qids[$qn],$seeds[$qn],false,$showhints,$attempts[$qn],false,false,false,$colors);
	$quesout .= ob_get_clean();
	$quesout = substr($quesout,0,-7).'<br/><input type="button" class="btn" value="'. _('Submit'). '" onclick="assessbackgsubmit('.$qn.',\'submitnotice'.$qn.'\')" /><span id="submitnotice'.$qn.'"></span></div>';
	echo '<input type="hidden" id="verattempts'.$qn.'" value="'.$attempts[$qn].'"/>';
	echo $quesout;
	exit;
}
	

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
$placeinhead .= '<style type="text/css"> html,body {margin:0px;} div.question input.btn { margin-left: 10px; } </style>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqntips.js?v=032810\"></script>";
                        
$flexwidth = true; //tells header to use non _fw stylesheet
$placeinhead .= '<style type="text/css">div.question {width: auto;} div.review {width: auto; margin-top: 5px;} body {height:auto;}</style>';

if ($theme != '') {
	$sessiondata['coursetheme'] = $theme.'.css';
}
require("./assessment/header.php");

echo '<script type="text/javascript">var assesspostbackurl="' .$urlmode. $_SERVER['HTTP_HOST'] . $imasroot . '/multiembedq.php?embedpostback=true&action=scoreembed";</script>';
			
echo '<input type="hidden" id="asidverify" value="0"/>';
echo '<input type="hidden" id="disptime" value="'.time().'"/>';
echo '<input type="hidden" id="isreview" value="0"/>';
echo '<p><a href="multiembedq.php?id='.$_GET['id'].'&amp;regen=1&amp;sameseed='.$sameseed.'&amp;theme='.$theme.'&amp;iframe_resize_id='.$targetid.'">Try Another Version of ';
if (count($qids)>1) { 
	echo 'These Questions</a></p>';
} else {
	echo 'This Question</a></p>';
}
$showhints = true;

foreach ($qids as $i=>$qid) {
	echo '<div id="embedqwrapper'.$i.'" class="embedqwrapper">';
	$quesout = '';
	ob_start();
	displayq($i,$qid,$seeds[$i],false,$showhints,$attempts[$i]);
	$quesout .= ob_get_clean();
	$quesout = substr($quesout,0,-7).'<br/><input type="button" class="btn" value="'. _('Submit'). '" onclick="assessbackgsubmit('.$i.',\'submitnotice'.$i.'\')" /><span id="submitnotice'.$i.'"></span></div>';
	echo $quesout;
	echo '<input type="hidden" id="verattempts'.$i.'" value="'.$attempts[$i].'"/>';
	echo '</div>';			
}
if ($targetid != '') {
echo '<script type="text/javascript">
	function sendresizemsg() {
	  window.parent.postMessage( JSON.stringify({
	      subject: "lti.frameResize",
	      height: default_height,
	      iframe_resize_id: "'.$targetid.'"
	  }), "*");
	}
	if (MathJax) {
		MathJax.Hub.Queue(function () {
			sendresizemsg();
		});
	} else {
		$(function() {
			sendresizemsg();
		});
	}
</script>';
}

require("./footer.php");

?>
