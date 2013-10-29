<?php
//IMathAS:  View content tracking logs
//(c) 2013 David Lippman

require("../validate.php");
$cid = intval($_GET['cid']);
if (!isset($teacherid) && !isset($tutorid)) {
	$uid = $userid;
} else {
	$uid = intval($_GET['uid']);
}

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";
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

echo '<div class="cpmid"><a href="viewloginlog.php?cid='.$cid.'&uid='.$uid.'">View Login Log</a></div>';

echo '<div id="headerloginlog" class="pagetitle"><h2>'.$pagetitle. '</h2></div>';



$query = "SELECT LastName,FirstName FROM imas_users WHERE id='$uid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$row = mysql_fetch_row($result);
echo '<h3>Activity Log for '.$row[0].', '.$row[1].'</h3>';


$actions = array();
$lookups = array('as'=>array(), 'in'=>array(), 'li'=>array(), 'ex'=>array(), 'wi'=>array(), 'fo'=>array(), 'forums'=>array());

$query = "SELECT type,typeid,viewtime,info FROM imas_content_track WHERE userid='$uid' AND courseid='$cid' ORDER BY viewtime DESC";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
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
	$query = 'SELECT id,name FROM imas_assessments WHERE id IN ('.implode(',',array_unique($lookups['as'])).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$asnames[$row[0]] = $row[1];
	}
}
$innames = array();
if (count($lookups['in'])>0) {
	$query = 'SELECT id,title FROM imas_inlinetext WHERE id IN ('.implode(',',array_unique($lookups['in'])).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$innames[$row[0]] = $row[1];
	}
}
$linames = array();
if (count($lookups['li'])>0) {
	$query = 'SELECT id,title FROM imas_linkedtext WHERE id IN ('.implode(',',array_unique($lookups['li'])).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$linames[$row[0]] = $row[1];
	}
}
$winames = array();
if (count($lookups['wi'])>0) {
	$query = 'SELECT id,name FROM imas_wikis WHERE id IN ('.implode(',',array_unique($lookups['wi'])).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$winames[$row[0]] = $row[1];
	}
}
$exnames = array();
if (count($lookups['ex'])>0) {
	$query = 'SELECT id,assessmentid FROM imas_questions WHERE id IN ('.implode(',',array_unique($lookups['ex'])).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$exnames[$row[0]] = $asnames[$row[1]];
	}
}
$fpnames = array();
if (count($lookups['fo'])>0) {
	$query = 'SELECT id,subject FROM imas_forum_posts WHERE id IN ('.implode(',',array_unique($lookups['fo'])).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$fpnames[$row[0]] = $row[1];
	}
}
$forumnames = array();
if (count($lookups['forums'])>0) {
	$query = 'SELECT id,name FROM imas_forums WHERE id IN ('.implode(',',array_unique($lookups['forums'])).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$forumnames[$row[0]] = $row[1];
	}
}

echo '<table><thead><tr><th>Date</th><th>Action</th></tr></thead><tbody>';
foreach ($actions as $r) {
	if (isset($r[3])) {
		$r3pts = explode('::',$r[3]);
		if (count($r3pts)==2) {
			$link = '<a href="'.$r3pts[0].'" target="_blank">'.$r3pts[1].'</a>';
			$href = $r3pts[0];
		} else {
			$link = $r[3];
			$href = $r[3];
		}
	}
	echo '<tr>';
	echo '<td>'.tzdate("l, F j, Y, g:i a",$r[2]).'</td>';
	echo '<td>';
	switch ($r[0]) {
	case 'inlinetext':
		echo 'In inline text item '.$innames[$r[1]].', clicked link to '.$link;
		break;
	case 'linkedsum':
		echo 'From linked item '.$linames[$r[1]].' summary, clicked link to '.$link;
		break;
	case 'linkedlink':
		if ($r[3]==$r[1] || (strpos($href,'showlinkedtext')!==false && strpos($href,'id='.$r[1])!==false)) {
			echo 'Opened linked text item '.$linames[$r[1]];
		} else {
			echo 'Clicked linked item <a target="_blank" href="'.$href.'">'.$linames[$r[1]].'</a>';
		}
		break;
	case 'linkedintext':
		echo 'In linked text '.$linames[$r[1]].', clicked link to '.$link;
		break;
	case 'linkedviacal':
		if ($r[3]==$r[1] || (strpos($href,'showlinkedtext')!==false && strpos($href,'id='.$r[1])!==false)) {
			echo 'Via calendar, opened linked text item '.$linames[$r[1]];
		} else {
			echo 'Via calendar, clicked linked item <a target="_blank" href="'.$href.'">'.$linames[$r[1]].'</a>';
		}
		break;
	case 'extref':
		$p = explode(': ',$r[3]);
		echo 'In assessment '.$exnames[$r[1]].', clicked help for <a target="_blank" href="'.$p[1].'">'.$p[0].'</a>';
		break;
	case 'assessintro':
		echo 'In assessment '.$asnames[$r[1]].' intro, clicked link to '.$link;
		break;
	case 'assesssum':
		echo 'In assessment '.$asnames[$r[1]].' summary, clicked link to '.$link;
		break;
	case 'assess':
		echo 'Opened assessment '.$asnames[$r[1]];
		break;
	case 'assesslti':
		echo 'Opened assessment '.$asnames[$r[1]].' via LTI';
		break;
	case 'assessviacal':
		echo 'Via calendar, opened assessment '.$asnames[$r[1]];
		break;
	case 'wiki':
		echo 'Opened wiki '.$winames[$r[1]];
		break;
	case 'wikiintext':
		echo 'In wiki '.$winames[$r[1]].', clicked link to '.$link;
		break;
	case 'forumpost':
		$fp = explode(';',$r[3]);
		echo 'New post <a target="_blank" href="../forums/posts.php?cid='.$cid.'&forum='.$fp[0].'&thread='.$r[1].'">'.$fpnames[$r[1]].'</a> in forum '.$forumnames[$fp[0]];
		break;
	case 'forumreply':
		$fp = explode(';',$r[3]);
		echo 'New reply <a target="_blank" href="../forums/posts.php?cid='.$cid.'&forum='.$fp[0].'&thread='.$fp[1].'">'.$fpnames[$r[1]].'</a> in forum '.$forumnames[$fp[0]];
		break;
	case 'forummod':
		$fp = explode(';',$r[3]);
		echo 'Modified post/reply <a target="_blank" href="../forums/posts.php?cid='.$cid.'&forum='.$fp[0].'&thread='.$fp[1].'">'.$fpnames[$r[1]].'</a> in forum '.$forumnames[$fp[0]];
		break;
	}
	
	
	echo '</td>';
	echo '</tr>';
}
echo '</tbody></table>';
		
require("../footer.php");

?>
