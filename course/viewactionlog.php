<?php
//IMathAS:  View content tracking logs
//(c) 2013 David Lippman

require("../init.php");


$cid = intval($_GET['cid']);
if (!isset($teacherid) && !isset($tutorid)) {
	$uid = $userid;
} else {
	$uid = intval($_GET['uid']);
}

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a>\n";
if (isset($teacherid)) {
	if (isset($_GET['from']) && $_GET['from']=='gb') {
		$curBreadcrumb .= " &gt; <a href=\"gradebook.php?cid=$cid&stu=0\">Gradebook</a> ";
		$curBreadcrumb .= " &gt; <a href=\"gradebook.php?cid=$cid&stu=$uid\">Student Detail</a> ";
	} else {
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> ";
	}
}
$curBreadcrumb .= "&gt; View Activity Log\n";
$pagetitle = "View Activity Log";
require("../header.php");
echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";

echo '<div id="headerloginlog" class="pagetitle"><h1>'.$pagetitle. '</h1></div>';

echo '<div class="cpmid"><a href="viewloginlog.php?cid='.$cid.'&uid='.$uid.'">View Login Log</a></div>';


//DB $query = "SELECT LastName,FirstName FROM imas_users WHERE id='$uid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT LastName,FirstName FROM imas_users WHERE id=:id");
$stm->execute(array(':id'=>$uid));
$row = $stm->fetch(PDO::FETCH_NUM);
echo '<h2>Activity Log for '.Sanitize::encodeStringForDisplay($row[0]).', '.Sanitize::encodeStringForDisplay($row[1]).'</h2>';


$actions = array();
$lookups = array('as'=>array(), 'in'=>array(), 'li'=>array(), 'ex'=>array(), 'wi'=>array(), 'fo'=>array(), 'forums'=>array());

//DB $query = "SELECT type,typeid,viewtime,info FROM imas_content_track WHERE userid='$uid' AND courseid='$cid' ORDER BY viewtime DESC";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT type,typeid,viewtime,info FROM imas_content_track WHERE userid=:userid AND courseid=:courseid ORDER BY viewtime DESC");
$stm->execute(array(':userid'=>$uid, ':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$actions[] = $row;
	$t = substr($row[0],0,2);
	$lookups[$t][] = intval($row[1]);
	if ($t=='fo') {
		$ip = explode(';',$row[3]);
		$lookups['forums'][] = $ip[0];
	}
}
$asnames = array();
if (count($lookups['as'])>0) {
	//DB $query = 'SELECT id,name FROM imas_assessments WHERE id IN ('..')';
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$lookuplist = array_map('intval', array_unique($lookups['as']));
	$query_placeholders = Sanitize::generateQueryPlaceholders($lookuplist);
	$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE id IN ($query_placeholders)");
	$stm->execute(array_values($lookuplist));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$asnames[$row[0]] = $row[1];
	}
}
$innames = array();
if (count($lookups['in'])>0) {
	$lookuplist = array_map('intval', array_unique($lookups['in']));
	//DB $query = 'SELECT id,title FROM imas_inlinetext WHERE id IN ('.implode(',',array_unique($lookups['in'])).')';
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query_placeholders = Sanitize::generateQueryPlaceholders($lookuplist);
	$stm = $DBH->prepare("SELECT id,title FROM imas_inlinetext WHERE id IN ($query_placeholders)");
	$stm->execute(array_values($lookuplist));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$innames[$row[0]] = $row[1];
	}
}
$linames = array();
if (count($lookups['li'])>0) {
	$lookuplist = array_map('intval', array_unique($lookups['li']));
	//DB $query = 'SELECT id,title FROM imas_linkedtext WHERE id IN ('.implode(',',array_unique($lookups['li'])).')';
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query_placeholders = Sanitize::generateQueryPlaceholders($lookuplist);
	$stm = $DBH->prepare("SELECT id,title FROM imas_linkedtext WHERE id IN ($query_placeholders)");
	$stm->execute(array_values($lookuplist));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$linames[$row[0]] = $row[1];
	}
}
$winames = array();
if (count($lookups['wi'])>0) {
	$lookuplist = array_map('intval', array_unique($lookups['wi']));
	//DB $query = 'SELECT id,name FROM imas_wikis WHERE id IN ('.implode(',',array_unique($lookups['wi'])).')';
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query_placeholders = Sanitize::generateQueryPlaceholders($lookuplist);
	$stm = $DBH->prepare("SELECT id,name FROM imas_wikis WHERE id IN ($query_placeholders)");
	$stm->execute(array_values($lookuplist));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$winames[$row[0]] = $row[1];
	}
}
$exnames = array();
if (count($lookups['ex'])>0) {
	$lookuplist = array_map('intval', array_unique($lookups['ex']));
	//DB $query = 'SELECT id,assessmentid FROM imas_questions WHERE id IN ('.implode(',',array_unique($lookups['ex'])).')';
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query_placeholders = Sanitize::generateQueryPlaceholders($lookuplist);
	$stm = $DBH->prepare("SELECT id,assessmentid FROM imas_questions WHERE id IN ($query_placeholders)");
	$stm->execute(array_values($lookuplist));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$exnames[$row[0]] = $asnames[$row[1]];
	}
}
$fpnames = array();
if (count($lookups['fo'])>0) {
	$lookuplist = array_map('intval', array_unique($lookups['fo']));
	//DB $query = 'SELECT id,subject FROM imas_forum_posts WHERE id IN ('.implode(',',array_unique($lookups['fo'])).')';
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query_placeholders = Sanitize::generateQueryPlaceholders($lookuplist);
	$stm = $DBH->prepare("SELECT id,subject FROM imas_forum_posts WHERE id IN ($query_placeholders)");
	$stm->execute(array_values($lookuplist));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$fpnames[$row[0]] = $row[1];
	}
}
$forumnames = array();
if (count($lookups['forums'])>0) {
	$lookuplist = array_map('intval', array_unique($lookups['forums']));
	//DB $query = 'SELECT id,name FROM imas_forums WHERE id IN ('.implode(',',array_unique($lookups['forums'])).')';
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query_placeholders = Sanitize::generateQueryPlaceholders($lookuplist);
	$stm = $DBH->prepare("SELECT id,name FROM imas_forums WHERE id IN ($query_placeholders)");
	$stm->execute(array_values($lookuplist));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$forumnames[$row[0]] = $row[1];
	}
}

echo '<table><thead><tr><th>Date</th><th>Action</th></tr></thead><tbody>';
foreach ($actions as $r) {
	if (isset($r[3])) {
		$r3pts = explode('::',$r[3]);
		if (count($r3pts)==2) {
			$thelink = '<a href="'.Sanitize::url($r3pts[0]).'" target="_blank">'.Sanitize::encodeStringForDisplay($r3pts[1]).'</a>';
			$href = Sanitize::url($r3pts[0]);
		} else {
			$thelink = Sanitize::url($r[3]);
			$href = Sanitize::url($r[3]);
		}
	}
	$actionmsg = '';
	switch ($r[0]) {
	case 'inlinetext':
		$actionmsg =  'In inline text item '.Sanitize::encodeStringForDisplay($innames[$r[1]]).', clicked link to '.$thelink;
		break;
	case 'linkedsum':
		$actionmsg =  'From linked item '.Sanitize::encodeStringForDisplay($linames[$r[1]]).' summary, clicked link to '.$thelink;
		break;
	case 'linkedlink':
		if ($r[3]==$r[1] || (strpos($href,'showlinkedtext')!==false && strpos($href,'id='.$r[1])!==false)) {
			$actionmsg =  'Opened linked text item '.Sanitize::encodeStringForDisplay($linames[$r[1]]);
		} else {
			$actionmsg =  'Clicked linked item <a target="_blank" href="'.$href.'">'.Sanitize::encodeStringForDisplay($linames[$r[1]]).'</a>';
		}
		break;
	case 'linkedintext':
		$actionmsg =  'In linked text '.Sanitize::encodeStringForDisplay($linames[$r[1]]).', clicked link to '.$thelink;
		break;
	case 'linkedviacal':
		if ($r[3]==$r[1] || (strpos($href,'showlinkedtext')!==false && strpos($href,'id='.$r[1])!==false)) {
			$actionmsg =  'Via calendar, opened linked text item '.Sanitize::encodeStringForDisplay($linames[$r[1]]);
		} else {
			$actionmsg =  'Via calendar, clicked linked item <a target="_blank" href="'.$href.'">'.Sanitize::encodeStringForDisplay($linames[$r[1]]).'</a>';
		}
		break;
	case 'extref':
		$p = explode(': ',$r[3]);
		$actionmsg =  'In assessment '.Sanitize::encodeStringForDisplay($exnames[$r[1]]).', clicked help for <a target="_blank" href="'.Sanitize::url($p[1]).'">'.Sanitize::encodeStringForDisplay($p[0]).'</a>';
		break;
	case 'assessintro':
		$actionmsg =  'In assessment '.Sanitize::encodeStringForDisplay($asnames[$r[1]]).' intro, clicked link to '.$thelink;
		break;
	case 'assesssum':
		$actionmsg =  'In assessment '.Sanitize::encodeStringForDisplay($asnames[$r[1]]).' summary, clicked link to '.$thelink;
		break;
	case 'assess':
		$actionmsg =  'Opened assessment '.Sanitize::encodeStringForDisplay($asnames[$r[1]]);
		break;
	case 'assessreview':
		$actionmsg =  'Opened in review mode assessment '.Sanitize::encodeStringForDisplay($asnames[$r[1]]);
		break;
	case 'gbviewasid':
		$actionmsg =  'Viewed in gradebook assessment '.Sanitize::encodeStringForDisplay($asnames[$r[1]]);
		break;
	case 'assessreview':
		echo 'Opened in review mode assessment '.Sanitize::encodeStringForDisplay($asnames[$r[1]]);
		break;
	case 'assesslti':
		$actionmsg =  'Opened assessment '.Sanitize::encodeStringForDisplay($asnames[$r[1]]).' via LTI';
		break;
	case 'assessviacal':
		$actionmsg =  'Via calendar, opened assessment '.Sanitize::encodeStringForDisplay($asnames[$r[1]]);
		break;
	case 'wiki':
		$actionmsg =  'Opened wiki '.Sanitize::encodeStringForDisplay($winames[$r[1]]);
		break;
	case 'wikiintext':
		$actionmsg =  'In wiki '.Sanitize::encodeStringForDisplay($winames[$r[1]]).', clicked link to '.$thelink;
		break;
	case 'forumpost':
		$fp = explode(';',$r[3]);
		$actionmsg =  'New post <a target="_blank" href="../forums/posts.php?cid='.$cid.'&forum='.Sanitize::encodeUrlParam($fp[0]).'&thread='.Sanitize::encodeUrlParam($r[1]).'">'.Sanitize::encodeStringForDisplay($fpnames[$r[1]]).'</a> in forum '.Sanitize::encodeStringForDisplay($forumnames[$fp[0]]);
		break;
	case 'forumreply':
		$fp = explode(';',$r[3]);
		$actionmsg =  'New reply <a target="_blank" href="../forums/posts.php?cid='.$cid.'&forum='.Sanitize::encodeUrlParam($fp[0]).'&thread='.Sanitize::encodeUrlParam($fp[1]).'">'.Sanitize::encodeStringForDisplay($fpnames[$r[1]]).'</a> in forum '.Sanitize::encodeStringForDisplay($forumnames[$fp[0]]);
		break;
	case 'forummod':
		$fp = explode(';',$r[3]);
		$actionmsg =  'Modified post/reply <a target="_blank" href="../forums/posts.php?cid='.$cid.'&forum='.Sanitize::encodeUrlParam($fp[0]).'&thread='.Sanitize::encodeUrlParam($fp[1]).'">'.Sanitize::encodeStringForDisplay($fpnames[$r[1]]).'</a> in forum '.Sanitize::encodeStringForDisplay($forumnames[$fp[0]]);
		break;
	default:
		if (isset($_GET['showall'])) {
			$actionmsg = 'Type: '.Sanitize::encodeStringForDisplay($r[0]).'. Type ID: '.Sanitize::encodeStringForDisplay($r[1]).'. Info: '.Sanitize::encodeStringForDisplay($r[3]);
		}
	}
	if ($actionmsg != '') {
		echo '<tr>';
		echo '<td>'.tzdate("l, F j, Y, g:i a",$r[2]).'</td>';
		echo '<td>', $actionmsg, '</td>';
		echo '</tr>';
	}
}
echo '</tbody></table>';

require("../footer.php");

?>
