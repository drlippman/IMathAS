<?php
//IMathAS:  Course Map view
//(c) 2017 David Lippman

require("../init.php");
require('../includes/loaditemshowdata.php');

$placeinhead = '<style type="text/css">
ul.qview ul { border-left: 1px dashed #ccc; padding-left: 10px;}
</style>';

require("../header.php");

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) { // loaded by an unauthorized person
	echo _("You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n");
	require("../footer.php");
	exit;
}

$viewall = (isset($teacherid) || isset($tutorid));

$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$items = unserialize($stm->fetchColumn(0));

if (!$viewall) {
	$exceptions = loadExceptions($cid, $userid);
	require_once("../includes/exceptionfuncs.php");
	$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
}
//update block start/end dates to show blocks containing items with exceptions
if (count($exceptions)>0) {
	upsendexceptions($items);
}

$itemshowdata = loadItemShowData($items, -1, $viewall, false, false);

//echo '<pre>';
//print_r($itemshowdata);
//echo '</pre>';

$now = time();

function showicon($type,$alt='') {
	global $CFG,$imasroot;
	if ($alt=='') {$alt = $type;}
	if (isset($CFG['CPS']['miniicons'][$type])) {
		echo '<img alt="'.$alt.'" src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons'][$type].'" class="mida icon" /> ';
	}
}

function showitemtree($items,$parent) {
	 global $DBH, $CFG, $itemshowdata, $typelookups, $imasroot, $cid, $userid, $exceptions, $exceptionfuncs, $now, $viewall, $studentinfo;

	 foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if (isset($item['grouplimit']) && count($item['grouplimit'])>0 && !$viewall) {
				 if (!in_array('s-'.$studentinfo['section'],$item['grouplimit'])) {
					 continue;
				 }
			}
			if (($item['avail']==2 || ($item['avail']==1 && $item['startdate']<$now && $item['enddate']>$now)) ||
						($viewall || ($item['SH'][0]=='S' && $item['avail']>0))) {
				if ($item['SH'][1]=='T') { //just link to treereader item
					echo '<li><a href="course.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($parent).'#B'.Sanitize::encodeUrlParam($item['id']).'">';
					showicon('tree', 'treereader');
					echo Sanitize::encodeStringForDisplay($item['name']);
					echo '</a></li>';
				} else { //show block contents
					echo '<li><a href="course.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($parent.'-'.($k+1)).'">';
					showicon('folder');
					echo Sanitize::encodeStringForDisplay($item['name']);
					echo '</a><ul class="qview">';
					showitemtree($item['items'], $parent .'-'.($k+1));
					echo '</ul></li>';
				}
			 }
		} else {
			if ($itemshowdata[$item]['itemtype']=='Calendar') {
				continue; //no need to show calendars in map
			}
			echo '<li>';;
			$line = $itemshowdata[$item];
			if ($line['itemtype']=='Assessment') {
				if (!$viewall && isset($exceptions[$item])) {
					$useexception = $exceptionfuncs->getCanUseAssessException($exceptions[$item], $line, true);
					if ($useexception) {
						$line['startdate'] = $exceptions[$item][0];
						$line['enddate'] = $exceptions[$item][1];
					}
			   	}
			   	$nothidden = true;  $showgreyedout = false;
				if (abs($line['reqscore'])>0 && $line['reqscoreaid']>0 && !$viewall && $line['enddate']>$now
				   && (!isset($exceptions[$item]) || $exceptions[$item][3]==0)) {
				   if ($line['reqscore']<0 || $line['reqscoretype']&1) {
					   $showgreyedout = true;
				   }
				   $stm = $DBH->prepare("SELECT bestscores FROM imas_assessment_sessions WHERE assessmentid=:assessmentid AND userid=:userid");
				   $stm->execute(array(':assessmentid'=>$line['reqscoreaid'], ':userid'=>$userid));
				   if ($stm->rowCount()==0) {
					   $nothidden = false;
				   } else {
					   //DB $scores = explode(';',mysql_result($result,0,0));
					   $scores = explode(';',$stm->fetchColumn(0));
					   if ($line['reqscoretype']&2) { //using percent-based
					   	   if (round(100*getpts($scores[0])/$line['reqscoreptsposs'],1)+.02<abs($line['reqscore'])) {
							   $nothidden = false;
						   }
					   } else { //points based
						   if (round(getpts($scores[0]),1)+.02<abs($line['reqscore'])) {
							   $nothidden = false;
						   }
					   }
				   }
				}
				if (($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now && ($nothidden || $showgreyedout)) ||
					($line['avail']==1 && $line['enddate']<$now && $line['reviewdate']>$now) || $viewall) {

					echo '<li><a href="course.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($parent).'#'.Sanitize::encodeUrlParam($item).'">';
					showicon('assess', 'Assessment');
					echo Sanitize::encodeStringForDisplay($line['name']);
					echo '</a></li>';
				}

			} else if ($line['itemtype']=='InlineText') {
				if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					echo '<li><a href="course.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($parent).'#inline'.Sanitize::encodeUrlParam($line['id']).'">';
					showicon('inline', 'Inline Text');
					if ($line['title']!='##hidden##') {
						echo Sanitize::encodeStringForDisplay($line['title']);
					} else {
						echo _('Inline text item');
					}
					echo '</a></li>';
				}
			} else if ($line['itemtype']=='LinkedText') {
				if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					echo '<li><a href="course.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($parent).'#'.Sanitize::encodeUrlParam($item).'">';
					showicon('linked', 'Link');
					echo Sanitize::encodeStringForDisplay($line['title']);
					echo '</a></li>';
				}
			} else if ($line['itemtype']=='Drill') {
				if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					echo '<li><a href="course.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($parent).'#'.Sanitize::encodeUrlParam($item).'">';
					showicon('drill', 'Drill');
					echo Sanitize::encodeStringForDisplay($line['name']);
					echo '</a></li>';
				}
			} else if ($line['itemtype']=='Forum') {
				if (!$viewall && isset($exceptions[$item])) {
					list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $line['postby'], $line['replyby'], $line['enddate']) = $exceptionfuncs->getCanUseLatePassForums($exceptions[$item], $line);
				}

				if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					echo '<li><a href="course.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($parent).'#'.Sanitize::encodeUrlParam($item).'">';
					showicon('forum', 'Forum');
					echo Sanitize::encodeStringForDisplay($line['name']);
					echo '</a></li>';
				}
			} else if ($line['itemtype']=='Wiki') {
				if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					echo '<li><a href="course.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($parent).'#'.Sanitize::encodeUrlParam($item).'">';
					showicon('wiki', 'Wiki');
					echo Sanitize::encodeStringForDisplay($line['name']);
					echo '</a></li>';
				}
			}
			echo '</li>';
		}
	}
}

echo '<div class="breadcrumb">';
echo $breadcrumbbase;
echo "<a href=\"course.php?cid=$cid&folder=0\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
echo _('Course Map');
echo '</div>';

echo '<div id="headercoursemap" class="pagetitle"><h2>'._('Course Map').'</h2></div>';
echo '<p>'._('Select an item to jump to its location in the course, or a folder to view the contents').'</p>';
echo '<ul class="qview coursemap">';
showitemtree($items,'0');
echo '</ul>';

require("../footer.php");

?>
