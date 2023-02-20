<?php
//(c) 2013 David Lippman.  Part of IMathAS
//Display outcome alignment table

require("../init.php");


if (!isset($teacherid)) { echo "You are not validated to view this page"; exit;}

//load outcomes
$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$row = $stm->fetch(PDO::FETCH_NUM);
if ($row[0]=='') {
	$outcomes = array();
} else {
	$outcomes = unserialize($row[0]);
}

$outcomeinfo = array();
$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$outcomeinfo[$row[0]] = $row[1];
}

$outcomelinks = 0;
$outcomeassoc = array();
//load assessment usage count
$assessgbcat = array();  //will record gb category for assessment
$assessnames = array();
$assessqcnt = array();
$query = "SELECT ia.name,ia.gbcategory,ia.defoutcome,ia.id,iq.category FROM ";
$query .= "imas_assessments AS ia JOIN imas_questions AS iq ON ia.id=iq.assessmentid ";
$query .= "WHERE ia.courseid=:courseid AND (ia.defoutcome>0 OR iq.category<>'0')";
$stm = $DBH->prepare($query);
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {

	if (!is_numeric($row['category']) && $row['defoutcome']==0) {continue;}
	if (!is_numeric($row['category']) || $row['category']==0) {
		$outc = $row['defoutcome'];
	} else {
		$outc = $row['category'];
	}
	if (!isset($assessqcnt[$row['id']])) {
		$assessqcnt[$row['id']] = array();
	}
	if (!isset($assessqcnt[$row['id']][$outc])) {
		$assessqcnt[$row['id']][$outc] = 1;
	} else {
		$assessqcnt[$row['id']][$outc]++;
	}
	$assessgbcat[$row['id']] = $row['gbcategory'];
	$assessnames[$row['id']] = $row['name'];
	$outcomelinks++;
}
foreach ($assessqcnt as $id=>$os) {
	foreach ($os as $o=>$cnt) {
		if (!isset($outcomeassoc[$o])) {
			$outcomeassoc[$o] = array();
		}
		if (!isset($outcomeassoc[$o][$assessgbcat[$id]])) {
			$outcomeassoc[$o][$assessgbcat[$id]] = array();
		}
		$outcomeassoc[$o][$assessgbcat[$id]][] = array('assess',$id);
	}
}

//load offline grade usage
$offgbcat = array();
$offnames = array();
$stm = $DBH->prepare("SELECT id,name,gbcategory,outcomes FROM imas_gbitems WHERE courseid=:courseid AND outcomes<>''");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$oc = explode(',',$row['outcomes']);
	foreach ($oc as $o) {
		if (!isset($outcomeassoc[$o])) {
			$outcomeassoc[$o] = array();
		}
		if (!isset($outcomeassoc[$o][$row['gbcategory']])) {
			$outcomeassoc[$o][$row['gbcategory']] = array();
		}

		$outcomeassoc[$o][$row['gbcategory']][] = array('offline',$row['id']);
		$outcomelinks++;
	}
	$offgbcat[$row['id']] = $row['gbcategory'];
	$offnames[$row['id']] = $row['name'];
}

//load forum grade usage
$forumgbcat = array();
$forumnames = array();
$stm = $DBH->prepare("SELECT id,cntingb,name,gbcategory,outcomes FROM imas_forums WHERE courseid=:courseid AND outcomes<>''");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$oc = explode(',',$row['outcomes']);
	if ($row['cntingb']!=0) {
		$forumgbcat[$row['id']] = $row['gbcategory'];
	} else {
		$row['gbcategory'] = 'UG';
	}
	foreach ($oc as $o) {
		if (!isset($outcomeassoc[$o])) {
			$outcomeassoc[$o] = array();
		}
		if (!isset($outcomeassoc[$o][$row['gbcategory']])) {
			$outcomeassoc[$o][$row['gbcategory']] = array();
		}

		$outcomeassoc[$o][$row['gbcategory']][] = array('forum',$row['id']);
		$outcomelinks++;
	}

	$forumnames[$row['id']] = $row['name'];
}

//load linkedtext items usage
$linknames = array();
$stm = $DBH->prepare("SELECT id,title,outcomes FROM imas_linkedtext WHERE courseid=:courseid AND outcomes<>''");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$oc = explode(',',$row['outcomes']);
	foreach ($oc as $o) {
		if (!isset($outcomeassoc[$o])) {
			$outcomeassoc[$o] = array();
		}
		if (!isset($outcomeassoc[$o]['UG'])) {
			$outcomeassoc[$o]['UG'] = array();
		}

		$outcomeassoc[$o]['UG'][] = array('link',$row['id']);
		$outcomelinks++;
	}
	$linknames[$row['id']] = $row['title'];
}

//load inlinetext items usage
$inlinenames = array();
$stm = $DBH->prepare("SELECT id,title,outcomes FROM imas_inlinetext WHERE courseid=:courseid AND outcomes<>''");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$oc = explode(',',$row['outcomes']);
	foreach ($oc as $o) {
		if (!isset($outcomeassoc[$o])) {
			$outcomeassoc[$o] = array();
		}
		if (!isset($outcomeassoc[$o]['UG'])) {
			$outcomeassoc[$o]['UG'] = array();
		}

		$outcomeassoc[$o]['UG'][] = array('inline',$row['id']);
		$outcomelinks++;
	}
	$inlinenames[$row['id']] = $row['title'];
}

$cats = array_unique(array_merge($offgbcat,$forumgbcat,$assessgbcat));
$catnames = array();
if (in_array(0, $cats)) {
	$catnames[0] = _('Default');
}
if (count($cats)>0) {
	$catlist = implode(',', array_map('intval', $cats));
	$stm = $DBH->query("SELECT id,name FROM imas_gbcats WHERE id IN ($catlist)");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$catnames[$row[0]] = $row[1];
	}
}
natsort($catnames);
$placeinhead = '<style type="text/css"> td { line-height: 150%;} .icon {background-color: #0f0;}</style>';
require("../header.php");
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
$curBreadcrumb .= "<a href=\"addoutcomes.php?cid=$cid\">"._("Course Outcomes")."</a>\n";

echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; '._("Outcomes Map").'</div>';

echo "<div id=\"headercourse\" class=\"pagetitle\"><h1>"._("Outcomes Map")."</h1></div>\n";

if ($outcomelinks==0) {
	echo '<p>No items have been associated with outcomes yet</p>';
	require("../footer.php");
	exit;
}

echo '<table class="gb"><thead><tr><th>'._('Outcome').'</th><th>'._('Not Graded').'</th>';
foreach ($catnames as $cn) {
	echo '<th>'.Sanitize::encodeStringForDisplay($cn).'</th>';
}
echo '</tr></thead><tbody>';
$n = count($catnames)+2;

function printitems($items) {
	global $assessnames, $forumnames, $offnames, $linknames, $inlinenames;
	foreach ($items as $i=>$item) {
		if ($i!=0) { echo '<br/>';}
		if ($item[0]=='link') {
			echo '<span class="icon iconlink" >L</span> '.$linknames[$item[1]];
		} else if ($item[0]=='inline') {
			echo '<span class="icon iconinline" >I</span> '.$inlinenames[$item[1]];
		}else if ($item[0]=='assess') {
			echo '<span class="icon iconassess" >A</span> '.$assessnames[$item[1]];
		} else if ($item[0]=='forum') {
			echo '<span class="icon iconforum" >F</span> '.$forumnames[$item[1]];
		} else if ($item[0]=='offline') {
			echo '<span class="icon iconoffline" >O</span> '.$offnames[$item[1]];
		}
	}
}
$cnt = 0;
function printoutcome($arr,$ind) {
	global $outcomeinfo, $outcomeassoc, $n, $catnames, $cnt;
	foreach ($arr as $oi) {
		if ($cnt%2==0) {
			$class = "even";
		} else {
			$class = "odd";
		}
		$cnt++;
		if (is_array($oi)) { //is outcome group
			echo '<tr class="'.Sanitize::encodeStringForDisplay($class).'" colspan="'.$n.'"><td><span class="ind'.$ind.'"><b>'.Sanitize::encodeStringForDisplay($oi['name']).'</b></span></td></tr>';
			printoutcome($oi['outcomes'],$ind+1);
		} else {
			echo '<tr class="'.$class.'">';
			echo '<td><span class="ind'.$ind.'">'.Sanitize::encodeStringForDisplay($outcomeinfo[$oi]).'</span></td><td>';
			if (isset($outcomeassoc[$oi]['UG'])) {
				printitems($outcomeassoc[$oi]['UG']);
			}
			echo '</td>';
			foreach ($catnames as $id=>$cn) {
				echo '<td>';
				if (isset($outcomeassoc[$oi][$id])) {
					printitems($outcomeassoc[$oi][$id]);
				}
				echo '</td>';
			}
			echo '</tr>';
		}
	}
}
printoutcome($outcomes,0);
echo '</tbody></table>';
echo '<p>'._('Key:  L: Links, I: Inline Text, A: Assessments, F: Forums, O: Offline Grades').'</p>';
require("../footer.php");
?>
