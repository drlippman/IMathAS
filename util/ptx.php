<?php
//IMathAS:  Generate MathBookXML (pretext?) for a question
//(c) 2017 David Lippman

header("Content-Type: text/plain");

require("../init_without_validate.php");
require("../i18n/i18n.php");
require("mbxfilter.php");
require("../assessment/displayq2.php");

 if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }
$basesiteurl = $urlmode  . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot;

$sessiondata = array();
$prefdefaults = array(
	'mathdisp'=>1,
	'graphdisp'=>2,
	'drawentry'=>1,
	'useed'=>0,
	'livepreview'=>0);
$sessiondata['userprefs'] = array();
foreach($prefdefaults as $key=>$def) {
	$sessiondata['userprefs'][$key] = $def;
}
foreach(array('graphdisp','mathdisp','useed') as $key) {
	$sessiondata[$key] = $sessiondata['userprefs'][$key];
}
$sessiondata['texdisp'] = true;

$showtips = 0;
$useeqnhelper = 0;
$sessiondata['drill']['cid'] = 0;
$sessiondata['drill']['sa'] = 0;
$sessiondata['secsalt'] = "12345";
$cid = "mbx";
if (empty($_GET['id'])) {
	echo 'Need to supply an id';
	exit;
}
$qsetlist = array_map('Sanitize::onlyInt',explode('-',$_GET['id']));

if (isset($_GET['seed'])) {
  $seed = Sanitize::onlyInt($_GET['seed']);
} else {
	$seed = rand(1,9999);
}

foreach ($qsetlist as $qn=>$qsetid) {
	mbxproc($qn,$qsetid,$seed);	
}

function mbxproc($qn,$qsetid,$seed) {
  global $DBH,$RND,$imasroot,$basesiteurl,$urlmode;
  $isbareprint = true;
  $RND->srand($seed);
  $stm = $DBH->prepare("SELECT qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id=:id");
  $stm->execute(array(':id'=>$qsetid));
  $qdata = $stm->fetch(PDO::FETCH_ASSOC);
  if ($qdata['hasimg']>0) {
		$stm = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");
		$stm->execute(array(':qsetid'=>$qsetid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				${$row[0]} = "<img src=\"https://{$GLOBALS['AWSbucket']}.s3.amazonaws.com/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			} else {
				${$row[0]} = "<img src=\"$basesiteurl/assessment/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			}
		}
	}
	eval(interpret('control',$qdata['qtype'],$qdata['control']));
	eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
  $toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
  $toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
  $toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);
  $RND->srand($seed+1);
  eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
  $RND->srand($seed+2);
  $la = '';

  if (isset($choices) && !isset($questions)) {
    $questions =& $choices;
  }
  if (isset($variable) && !isset($variables)) {
    $variables =& $variable;
  }
  if (is_array($displayformat)) {
  	 foreach ($displayformat as $k=>$v) {
  	 	 if ($v=="select" || $v=="horiz" || $v=="column" || $v=="2column" || $v=="inline") {
  	 	 	 unset($displayformat[$k]);
  	 	 }
  	 }
  } else if ($displayformat=="select" || $displayformat=="horiz" || $displayformat=="column" || $displayformat=="2column" || $displayformat=="inline") {
  	 unset($displayformat);
  }
  
  //pack options
  if (isset($ansprompt)) {$options['ansprompt'] = $ansprompt;}
  if (isset($displayformat)) {$options['displayformat'] = $displayformat;}
  if (isset($answerformat)) {$options['answerformat'] = $answerformat;}
  if (isset($questions)) {$options['questions'] = $questions;}
  if (isset($answers)) {$options['answers'] = $answers;}
  if (isset($answer)) {$options['answer'] = $answer;}
  if (isset($questiontitle)) {$options['questiontitle'] = $questiontitle;}
  if (isset($answertitle)) {$options['answertitle'] = $answertitle;}
  if (isset($answersize)) {$options['answersize'] = $answersize;}
  if (isset($variables)) {$options['variables'] = $variables;}
  if (isset($domain)) {$options['domain'] = $domain;}
  if (isset($answerboxsize)) {$options['answerboxsize'] = $answerboxsize;}
  if (isset($hidepreview)) {$options['hidepreview'] = $hidepreview;}
  if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
  if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
  if (isset($reqdecimals)) {$options['reqdecimals'] = $reqdecimals;}
  if (isset($grid)) {$options['grid'] = $grid;}
  if (isset($background)) {$options['background'] = $background;}
  if ($qdata['qtype']=="multipart") {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$laparts = explode("&",$la);
		foreach ($anstypes as $kidx=>$anstype) {
			list($answerbox[$kidx],$tips[$kidx],$shans[$kidx]) = makeanswerbox($anstype,$kidx,$laparts[$kidx],$options,$qn+1);
		}
	} else {
		list($answerbox,$tips[0],$shans[0]) = makeanswerbox($qdata['qtype'],$qn,$la,$options,0);
	}
  eval("\$evaledqtext = \"$toevalqtxt\";");
  $statement = mbxfilter($evaledqtext);

  if (strpos($toevalqtxt,'$answerbox')===false) {
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
				$statement .= mbxfilter("<p>$abox</p>\n");
			}
		} else {  //one question only
			$statement .= mbxfilter("<p>$answerbox</p>\n");
		}
	}
  $statement = rtrim($statement);

  $shansout = array();
  if (isset($showanswer) && !is_array($showanswer)) {
  	  $soln = mbxfilter('<p>'.$showanswer.'</p>');
  } else {
	  foreach ($shans as $kidx=>$shansval) {
		if (isset($showanswer) && is_array($showanswer) && isset($showanswer[$kidx])) {
			$shansout[] = $showanswer[$kidx];
		} else {
			$shansout[] = $shansval;
		}
	  }
	  if (count($shansout)==1) {
	  	  $soln = mbxfilter('<p>'.$shansout[0].'</p>');
	  } else {
	  	  $soln = mbxfilter('<p><ul><li>'.implode('</li><li>',$shansout).'</li></ul></p>');
	  }
  }



echo "<myopenmath id=\"" . Sanitize::onlyInt($qsetid) . "\">
<statement>
$statement
</statement>
<solution>
$soln
</solution>
</myopenmath>

";
}
?>
