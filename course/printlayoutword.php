<?php
//IMathAS:  Pandoc-based print to Word
//(c) 2014 David Lippman

/*** master php includes *******/
require("../init.php");


 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Print Layout";


	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else if (!isset($CFG['GEN']['pandocserver'])) {
	$overwriteBody = 1;
	$body = 'No pandoc server specified in config';
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

}

/******* begin html output ********/

$aid = Sanitize::onlyInt($_GET['aid']);
if (!empty($_GET['from']) && $_GET['from'] == 'addq2') {
    $addq = 'addquestions2';
    $from = 'addq2';
} else {
    $addq = 'addquestions';
    $from = 'addq';
}
$stm = $DBH->prepare("SELECT itemorder,shuffle,defpoints,name,intro FROM imas_assessments WHERE id=:id");
$stm->execute(array(':id'=>$aid));
$line = $stm->fetch(PDO::FETCH_ASSOC);


if ($overwriteBody==1) {
	require("../header.php");
	echo $body;
} if (!isset($_REQUEST['versions'])) {

	require("../header.php");
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
	echo "<a href=\"$addq.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> ";
	echo "&gt; Print Test</div>\n";

    echo '<div class="cpmid">';
    if ($courseUIver == 1) {
        echo '<a href="printtest.php?cid='.$cid.'&amp;aid='.$aid.'&amp;from='.$from.'">Generate for in-browser printing</a> | ';
    }
    echo '<a href="printlayoutbare.php?cid='.$cid.'&amp;aid='.$aid.'&amp;from='.$from.'">Generate for cut-and-paste</a></div>';

	echo "<h1>"._('Generate Word Version')."</h1>";

  echo '<h2>'.Sanitize::encodeStringForDisplay($line['name']).'</h2>';

	echo '<p>This page will help you create a copy of this assessment as a Word 2007+ file that you can then edit for printing.</p>';

	echo "<form method=\"post\" action=\"printlayoutword.php?cid=$cid&aid=$aid&from=$from\" class=\"nolimit\">\n";
	echo '<span class="form">Number of different versions to generate:</span><span class="formright"><input type=text name=versions value="1" size="3"></span><br class="form"/>';
	echo '<span class="form">Format?</span><span class="formright"><input type="radio" name="format" value="trad" checked="checked" /> Multiple forms of the whole assessment - Form A: 1 2 3, Form B: 1 2 3<br/><input type="radio" name="format" value="inter"/> Multiple forms grouped by question - 1a 1b 2a 2b</span><br class="form"/>';
	echo '<span class="form">Generate answer keys?</span><span class="formright"> <input type=radio name=keys value=1 checked=1>Yes <input type=radio name=keys value=0>No</span><br class="form"/>';
	echo '<span class="form">Question separator:</span><span class="formright"><input type=text name="qsep" value="" /></span><br class="form"/>';
	echo '<span class="form">Version separator:</span><span class="formright"><input type=text name="vsep" value="+++++++++++++++" /> Use PAGEBREAK for a page break</span><br class="form"/>';
	echo '<span class="form">Include question numbers and point values:</span><span class="formright"><input type="checkbox" name="showqn" checked="checked" /> </span><br class="form"/>';
	echo '<span class="form">Hide text entry lines?</span><span class="formright"><input type=checkbox name=hidetxtboxes checked="checked" ></span><br class="form"/>';
	echo '<span class="form">Include between-question text?</span><span class="formright"><input type=checkbox name=showtexts ></span><br class="form"/>';

	echo '<p>NOTE: In some versions of Word, variables in equations may appear incorrectly at first.  To fix this, ';
	echo 'select everything (Control-A), then under the Equation Tools menu, click Linear then Professional.</p>';

	echo '<div class="submit"><input type="submit" value="Continue"/></div></form>';


} else {
  $GLOBALS['texdisp'] = true;
  $GLOBALS['texdoubleescape'] = true;
  $GLOBALS['hide-sronly'] = true;
  $texusealignsformatrix = true;

  $origmathdisp = $_SESSION['mathdisp'];
  $origgraphdisp = $_SESSION['graphdisp'];
  $_SESSION['graphdisp'] = 1;
  $_SESSION['mathdisp'] = 2;
  $loadgraphfilter = true;
  $hidedrawcontrols = true;
  $assessver = 2;

	//load filter
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require_once("$curdir/../filter/filter.php");

	$out = '<!DOCTYPE html><html><body>';

    $texts = [];
	if (($introjson=json_decode($line['intro'], true))!==null) { //is json intro
        $line['intro'] = $introjson[0];
        $texts = array_slice($introjson, 1);
	}

	$ioquestions = explode(",",$line['itemorder']);
	$aname = $line['name'];
	$questions = array();
	foreach($ioquestions as $k=>$q) {
		if (strpos($q,'~')!==false) {
			$sub = explode('~',$q);
			if (strpos($sub[0],'|')===false) { //backwards compat
				$questions[] = $sub[array_rand($sub,1)];
			} else {
				$grpqs = array();
				$grpparts = explode('|',$sub[0]);
				array_shift($sub);
				if ($grpparts[1]==1) { // With replacement
					for ($i=0; $i<$grpparts[0]; $i++) {
						$questions[] = $sub[array_rand($sub,1)];
					}
				} else if ($grpparts[1]==0) { //Without replacement
					shuffle($sub);
					for ($i=0; $i<min($grpparts[0],count($sub)); $i++) {
						$questions[] = $sub[$i];
					}
					//$grpqs = array_slice($sub,0,min($grpparts[0],count($sub)));
					if ($grpparts[0]>count($sub)) { //fix stupid inputs
						for ($i=count($sub); $i<$grpparts[0]; $i++) {
							$questions[] = $sub[array_rand($sub,1)];
						}
					}
				}
			}
		} else {
			$questions[] = $q;
		}
	}

	$points = array();
	$qn = array();
	$fixedseeds = array();
	$qlist = array_map('Sanitize::onlyInt', $questions);
	$query_placeholders = Sanitize::generateQueryPlaceholders($qlist);
	$stm = $DBH->prepare("SELECT id,points,questionsetid,fixedseeds FROM imas_questions WHERE id IN ($query_placeholders)");
	$stm->execute($qlist);
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1]==9999) {
			$points[$row[0]] = $line['defpoints'];
		} else {
			$points[$row[0]] = $row[1];
		}
		$qn[$row[0]] = $row[2];
		if ($row[3]!==null && $row[3]!='') {
			$fixedseeds[$row[0]] = explode(',',$row[3]);
	}
	}


	$numq = count($questions);

	if ($courseUIver > 1) {
		include('../assess2/AssessStandalone.php');
		$a2 = new AssessStandalone($DBH);
		$stm = $DBH->prepare("SELECT iqs.* FROM imas_questionset AS iqs JOIN imas_questions ON imas_questions.questionsetid=iqs.id WHERE imas_questions.assessmentid=:id");
		$stm->execute(array(':id'=>$aid));
		while ($qdata = $stm->fetch(PDO::FETCH_ASSOC)) {
			$a2->setQuestionData($qdata['id'], $qdata);
		}
	} else {
	include("../assessment/displayq2.php");
	}

	if (is_numeric($_REQUEST['versions'])) {
		$copies = $_REQUEST['versions'];
	} else {
		$copies = 1;
	}
	//add interlace output
	//add prettyprint along with text-based output option
	$seeds = array();
	$fixedn = array();
	for ($j=0; $j<$copies; $j++) {
		$seeds[$j] = array();
		if ($line['shuffle']&2) {  //all questions same random seed
			if ($shuffle&4) { //all students same seed
				$seeds[$j] = array_fill(0,count($questions),$aid+$j);
			} else {
				$seeds[$j] = array_fill(0,count($questions),rand(1,9999));
			}
		} else {
			if ($shuffle&4) { //all students same seed
				for ($i = 0; $i<count($questions);$i++) {
					if (isset($fixedseeds[$questions[$i]])) {
						$seeds[$j][] = $fixedseeds[$questions[$i]][$j%count($fixedseeds[$questions[$i]])];
					} else {
					$seeds[$j][] = $aid + $i + $j;
				}
				}
			} else {
				for ($i = 0; $i<count($questions);$i++) {
					if (isset($fixedseeds[$questions[$i]])) {
						$n = count($fixedseeds[$questions[$i]]);
						if (isset($fixedn[$i])) {
							$x = $fixedn[$i];
						} else {
							$x = rand(0,$n-1);
							$fixedn[$i] = $x;
						}
						$seeds[$j][] = $fixedseeds[$questions[$i]][($x+$j)%$n];
					} else {
					$seeds[$j][] = rand(1,9999);
				}
			}
		}
	}
	}


	if ($_REQUEST['format']=='trad') {
		for ($j=0; $j<$copies; $j++) {
			if ($j>0) { $out .= '<p>'.$_REQUEST['vsep'].'</p>';}

			$headerleft = '';
			$headerleft .= $line['name'];
			if ($copies>1) {
				$headerleft .= ' - Form ' . ($j+1);
			}
			if ((isset($_REQUEST['iname']) || isset($_REQUEST['cname'])) && isset($_REQUEST['aname'])) {
				$headerleft .= "<br/>";
			}
			$headerright = '';
			$out .= "<div class=q>\n";
			$out .= "<div class=hdrm>\n";

			$out .= "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
			$out .= "<div id=intro>{$line['intro']}</div>\n";
			$out .= "</div>\n";
			$out .= "</div>\n";


			for ($i=0; $i<$numq; $i++) {
                if ($i>0) { $out .= '<p>'.$_REQUEST['qsep'].'</p>';}
                if (!empty($_REQUEST['showtexts'])) {
                    foreach ($texts as $k=>$v) {
                        if ($v['displayBefore'] == $i) {
                            if (!empty($v['ispage']) && !empty($v['pagetitle'])) {
                                $out .= '<p><b>'.printfilter(filter(Sanitize::encodeStringForDisplay(html_entity_decode($v['pagetitle'])))).'</b></p>';
                            }
                            $out .= '<div>'.printfilter(filter($v['text'])).'</div>';
                        }
                    }
                }
				if ($courseUIver > 1) {
					list($newout,$sa[$j][$i]) = printq2($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_REQUEST['showqn']));
				} else {
				list($newout,$sa[$j][$i]) = printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_REQUEST['showqn']));
				}
				$out .= $newout;
			}

		}

		if ($_REQUEST['keys']>0) { //print answer keys
			for ($j=0; $j<$copies; $j++) {
				$out .= '<p>'.$_REQUEST['vsep'].'</p>';
				$out .= '<p><b>Key - Form ' . ($j+1) . "</b></p>\n";
				$out .= "<ol>\n";
				for ($i=0; $i<$numq; $i++) {
					$out .= '<li>';
					if (is_array($sa[$j][$i])) {
						$out .= printfilter(filter(implode(' ~ ',$sa[$j][$i])));
					} else {
						$out .= printfilter(filter($sa[$j][$i]));
					}
					$out .= "</li>\n";
				}
				$out .= "</ol>\n";
				//if ($_REQUEST['keys']==2) {
				//	$out .= "<p class=pageb>&nbsp;</p>\n";
				//}
			}
		}
	} else if ($_REQUEST['format']=='inter') {

		$headerleft = '';
		$headerleft .= $line['name'];
		if ((isset($_REQUEST['iname']) || isset($_REQUEST['cname'])) && isset($_REQUEST['aname'])) {
			$headerleft .= "<br/>";
		}
		$headerright = '';
		$out .= "<div class=q>\n";
		$out .= "<div class=hdrm>\n";

		$out .= "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
		$out .= "<div id=intro>{$line['intro']}</div>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		for ($i=0; $i<$numq; $i++) {
            if ($i>0) { $out .= '<p>'.$_REQUEST['qsep'].'</p>';}
            if (!empty($_REQUEST['showtexts'])) {
                foreach ($texts as $k=>$v) {
                    if ($v['displayBefore'] == $i) {
                        if (!empty($v['ispage']) && !empty($v['pagetitle'])) {
                            $out .= '<p><b>'.printfilter(filter(Sanitize::encodeStringForDisplay(html_entity_decode($v['pagetitle'])))).'</b></p>';
                        }
                        $out .= '<div>'.printfilter(filter($v['text'])).'</div>';
                    }
                }
            }
			for ($j=0; $j<$copies;$j++) {
				if ($j>0) { $out .= '<p>'.$_REQUEST['qsep'].'</p>';}
				if ($courseUIver > 1) {
					list($newout,$sa[]) = printq2($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_REQUEST['showqn']));
				} else {
				list($newout,$sa[]) = printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_REQUEST['showqn']));
				}
				$out .= $newout;
			}
		}
		if ($_REQUEST['keys']>0) { //print answer keys
			$out .= '<p>'.$_REQUEST['vsep'].'</p>';
			$out .= "<p><b>Key</b></p>\n";
			$out .= "<ol>\n";
			for ($i=0; $i<count($sa); $i++) {
				$out .= '<li>';
				if (is_array($sa[$i])) {
					$out .= printfilter(filter(implode(' ~ ',$sa[$i])));
				} else {
					$out .= printfilter(filter($sa[$i]));
				}
				$out .= "</li>\n";
			}
			$out .= "</ol>\n";
		}
	}
	$licurl = $GLOBALS['basesiteurl'] . '/course/showlicense.php?id=' . implode('-',$qn);
	$out .= '<hr/><p style="font-size:70%">License info at: <a href="'.$licurl.'">'.$licurl.'</a></p>';
	$out .= '</body></html>';

	$out = preg_replace('|(<img[^>]*?)src="/|', '$1 src="'.$urlmode.Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']).'/', $out);

	if (substr($CFG['GEN']['pandocserver'],0,4)=='http') {
		$pandocurl = $CFG['GEN']['pandocserver'];
	} else {
		$pandocurl = 'http://'.$CFG['GEN']['pandocserver'];
	}

	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; Print Test</div>\n";

	echo '<div class="cpmid"><a href="printtest.php?cid='.$cid.'&amp;aid='.$aid.'&amp;from='.$from.'">Generate for in-browser printing</a> | <a href="printlayoutbare.php?cid='.$cid.'&amp;aid='.$aid.'">Generate for cut-and-paste</a></div>';

	echo "<h1>"._('Generate Word Version')."</h1>";
	echo '<p>'._('Assessment is prepared, and ready for conversion').'.</p>';
	echo '<p>NOTE: In some versions of Word, variables in equations may appear incorrectly at first.  To fix this, ';
	echo 'select everything (Control-A), then under the Equation Tools menu, click Linear then Professional.</p>';
	echo '<form id="theform" method="post" action="'.$pandocurl.'/html2docx.php">';
	echo '<p><label><input type="checkbox" name="darkgrid"> '._('Darken graph grid lines').'</label><br/>';
	echo '<label><input type="checkbox" name="doubleimgs"> '._('Double image sizes').'</label></p>';

	echo '<p><input type="submit" value="'._("Convert to Word").'"/> ';
	echo '<a href="printlayoutword.php?cid='.$cid.'&amp;aid='.$aid.'&amp;from='.$from.'">'._('Change print settings').'</a></p>';
	echo '<textarea name="html" style="visibility:hidden">'.Sanitize::encodeStringForDisplay($out).'</textarea>';
	echo '</form>';

	/*

	$data = 'html='.Sanitize::encodeUrlParam($out);

	$params = array (
            'http' => array (
                    'method' => 'POST',
                    'content' => $data,
                    'timeout' => 4.0,
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                    "Content-Length: " . strlen ( $data ) . "\r\n"
            )
        );
        $ctx = stream_context_create ( $params );
        $fp = fopen ( 'http://'.$CFG['GEN']['pandocserver'].'/html2docx.php', 'rb', false, $ctx );

        header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
	header('Content-Disposition: attachment; filename="print'.$aid.'.docx"');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

        fpassthru( $fp );
     */
	/*$filename = sys_get_temp_dir().'/print'.$aid;

	file_put_contents($filename.'.html', $out);
	exec($CFG['pandocpath'].' '.$filename.'.html -f html+tex_math_double_backslash -o '.$filename.'.docx');

	header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
	header('Content-Disposition: attachment; filename="print'.$aid.'.docx"');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: '.filesize($filename));
	readfile($filename.'.docx');
	*/
  $_SESSION['mathdisp'] = $origmathdisp;
  $_SESSION['graphdisp'] = $origgraphdisp;
  require("../footer.php");
	exit;
}

function printq2($qn,$qsetid,$seed,$pts,$showpts) {
	global $a2,$isfinal,$imasroot,$urlmode;
	$state = array(
		'seeds' => array($qn => $seed),
		'qsid' => array($qn => $qsetid)
	);
	$a2->setState($state);
	// TODO: Some way to override or rewrite matrix answersize, and choices list numbering
	$res = $a2->displayQuestion($qn, ['includeans'=>true, 'printformat'=>true]);

	$retstrout = "<div class=q>";
	if ($isfinal) {
		$retstrout .= "<div class=\"trq$qn\">\n";
	} else {
		$retstrout .= "<div class=m id=\"trq$qn\">\n";
	}
	if ($showpts) {
		$retstrout .= ($qn+1).'. ('.$pts.' pts) ';
	}
	$retstrout .= "<div>\n";
	$retstrout .= printfilter($res['html']) . '</div>';
	$retstrout .= '</div></div>';

	return array($retstrout, $res['jsparams']['ans']);
}

function printq($qn,$qsetid,$seed,$pts,$showpts) {
	global $RND,$DBH,$isfinal,$imasroot,$urlmode;
	$RND->srand($seed);
	$stm = $DBH->prepare("SELECT qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id=:id");
	$stm->execute(array(':id'=>$qsetid));
	$qdata = $stm->fetch(PDO::FETCH_ASSOC);

	if ($qdata['hasimg']>0) {
		$stm = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");
		$stm->execute(array(':qsetid'=>$qsetid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (substr($row[1],0,4)=='http') {
				${$row[0]} = "<img src=\"{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			} else if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				${$row[0]} = "<img src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			} else {
				${$row[0]} = "<img src=\"$imasroot/assessment/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			}
		}
	}
	try {
		eval(interpret('control',$qdata['qtype'],$qdata['control']));
		eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
		$toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
		$toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
		$toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);
		$RND->srand($seed+1);
		eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
	} catch (Throwable $thrownerror) {
		if ($GLOBALS['myrights']>10) {
			echo '<p>Caught error in evaluating a function in a question: ';
			echo Sanitize::encodeStringForDisplay($thrownerror->getMessage());
			echo '</p>';
		}
	}
	$RND->srand($seed+2);
	$la = '';

	if (isset($choices) && !isset($questions)) {
		$questions =& $choices;
	}
	if (isset($variable) && !isset($variables)) {
		$variables =& $variable;
	}
	if ($displayformat=="select") {
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
			if (($anstype=='matrix' || $anstype=='calcmatrix') && isset($answersize)) {
				$oldoptionsanswersize = $options['answersize'];
				unset($options['answersize']);
			}
			list($answerbox[$kidx],$tips[$kidx],$shans[$kidx]) = makeanswerbox($anstype,$kidx,$laparts[$kidx],$options,$qn+1);
			if (($anstype=='matrix' || $anstype=='calcmatrix') && isset($answersize)) {
				$options['answersize'] = $oldoptionsanswersize;
			}
      if ($anstype=='choices' || $anstype=='multans') {
        $answerbox[$kidx] = str_replace(['<ul class=nomark>','</ul>'],
          ['<ol style="list-style-type:upper-alpha">', '</ol>'],
          $answerbox[$kidx]);
      }
      if (!isset($showanswer)) {
        $showanswer = array();
      }
      if (is_array($showanswer) && !isset($showanswer[$kidx])) {
        $showanswer[$kidx] = $shans[$kidx];
      }
		}
	} else {
		if ($qdata['qtype']=='matrix' || $qdata['qtype']=='calcmatrix') {
			unset($options['answersize']); //pandoc doesn't like nested tables
		}
		list($answerbox,$tips[0],$shans[0]) = makeanswerbox($qdata['qtype'],$qn,$la,$options,0);
    if ($qdata['qtype']=='choices' || $qdata['qtype']=='multans') {
      $answerbox = str_replace(['<ul class=nomark>','</ul>'],
        ['<ol style="list-style-type:upper-alpha">', '</ol>'],
        $answerbox);
    }
    if (!isset($showanswer)) {
      $showanswer = $shans[0];
    }
	}

	$retstrout .= "<div class=q>";
	if ($isfinal) {
		$retstrout .= "<div class=\"trq$qn\">\n";
	} else {
		$retstrout .= "<div class=m id=\"trq$qn\">\n";
	}
	if ($showpts) {
		$retstrout .= ($qn+1).'. ('.$pts.' pts) ';
	}
	$retstrout .= "<div>\n";
	//$retstrout .= $toevalqtext;
	eval("\$evaledqtext = \"$toevalqtxt\";");
  // fix [AB] fields
  if (strpos($evaledqtext,'[AB')!==false) {
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
				if (strpos($evaledqtext,'[AB'.$iidx.']')!==false) {
					$evaledqtext = str_replace('[AB'.$iidx.']', $abox, $evaledqtext);
					$toevalqtxt .= '$answerbox['.$iidx.']';  //to prevent autoadd
				}
			}
		} else {
			$evaledqtext = str_replace('[AB]', $answerbox, $evaledqtext);
			$toevalqtxt .= '$answerbox';
		}
	}
  // remove [SAB] fields
  $evaledqtext = preg_replace('/\[SAB\d*\]/','',$evaledqtext);

	$retstrout .= printfilter(filter($evaledqtext));
	$retstrout .= "</div>\n"; //end question div

	if (strpos($toevalqtxt,'$answerbox')===false) {
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
				$retstrout .= printfilter(filter("<div>$abox</div>\n"));
				$retstrout .= "<div class=spacer>&nbsp;</div>\n";
			}
		} else {  //one question only
			$retstrout .= printfilter(filter("<div>$answerbox</div>\n"));
		}


	}


	$retstrout .= "</div>";//end m div

	$retstrout .= "&nbsp;";
	$retstrout .= "</div>\n"; //end q div
	if (!isset($showanswer)) {
		return array($retstrout,$shans);
	} else {
    if (is_array($showanswer)) {
      ksort($showanswer);
    }
		return array($retstrout,$showanswer);
	}
}
