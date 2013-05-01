<?php
//IMathAS:  View content tracking logs
//(c) 2013 David Lippman

require("../validate.php");
$cid = intval($_GET['cid']);
if (!isset($teacherid)) {
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
$curBreadcrumb .= "&gt; View Action Log\n";	
$pagetitle = "View Action Log";
require("../header.php");
echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";
echo '<div id="headerloginlog" class="pagetitle"><h2>'.$pagetitle. '</h2></div>';

$query = "SELECT LastName,FirstName FROM imas_users WHERE id='$uid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$row = mysql_fetch_row($result);
echo '<h3>Action Log for '.$row[1].', '.$row[0].'</h3>';


$actions = array();
$lookups = array('as'=>array(), 'in'=>array(), 'li'=>array(), 'ex'=>array(), 'wi'=>array());

$query = "SELECT type,typeid,viewtime,info FROM imas_content_track WHERE userid='$uid' AND courseid='$cid' ORDER BY viewtime DESC";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$actions[] = $row;
	$t = substr($row[0],0,2);
	$lookups[$t][] = intval($row[1]);
}
$asnames = array();
if (count($lookups['as'])>0) {
	$query = 'SELECT id,name FROM imas_assessments WHERE id IN ('.implode(',',$lookups['as']).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$asnames[$row[0]] = $row[1];
	}
}
$innames = array();
if (count($lookups['in'])>0) {
	$query = 'SELECT id,title FROM imas_inlinetext WHERE id IN ('.implode(',',$lookups['in']).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$innames[$row[0]] = $row[1];
	}
}
$linames = array();
if (count($lookups['li'])>0) {
	$query = 'SELECT id,title FROM imas_linkedtext WHERE id IN ('.implode(',',$lookups['li']).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$linames[$row[0]] = $row[1];
	}
}
$winames = array();
if (count($lookups['wi'])>0) {
	$query = 'SELECT id,name FROM imas_wikis WHERE id IN ('.implode(',',$lookups['wi']).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$winames[$row[0]] = $row[1];
	}
}
$exnames = array();
if (count($lookups['ex'])>0) {
	$query = 'SELECT id,assessmentid FROM imas_questions WHERE id IN ('.implode(',',$lookups['ex']).')';
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$exnames[$row[0]] = $asnames[$row[1]];
	}
}

echo '<table><thead><tr><th>Date</th><th>Action</th></tr></thead><tbody>';
foreach ($actions as $r) {
	echo '<tr>';
	echo '<td>'.tzdate("l, F j, Y, g:i a",$r[2]).'</td>';
	echo '<td>';
	switch ($r[0]) {
	case 'inlinetext':
		echo 'In inline text item '.$innames[$r[1]].', clicked link to '.$r[3];
		break;
	case 'linkedsum':
		echo 'From linked item '.$linames[$r[1]].' summary, clicked link to '.$r[3];
		break;
	case 'linkedlink':
		if ($r[3]==$r[1] || (strpos($r[3],'showlinkedtext')!==false && strpos($r[3],'id='.$r[1])!==false)) {
			echo 'Opened linked text item '.$linames[$r[1]];
		} else {
			echo 'Clicked linked item '.$linames[$r[1]].' link to '.$r[3];
		}
		break;
	case 'linkedintext':
		echo 'In linked text '.$linames[$r[1]].', clicked link to '.$r[3];
		break;
	case 'linkedviacal':
		echo 'Via calendar, clicked linked item '.$linames[$r[1]].' link to '.$r[3];
		break;
	case 'extref':
		echo 'In assessment '.$exnames[$r[1]].', clicked help '.$r[3];
		break;
	case 'assessintro':
		echo 'In assessment '.$asnames[$r[1]].' intro, clicked link to '.$r[3];
		break;
	case 'assesssum':
		echo 'In assessment '.$asnames[$r[1]].' summary, clicked link to '.$r[3];
		break;
	case 'assess':
		echo 'Opened assessment '.$asnames[$r[1]];
		break;
	case 'assessviacal':
		echo 'Via calendar, opened assessment '.$asnames[$r[1]];
		break;
	case 'wiki':
		echo 'Opened wiki '.$winames[$r[1]];
		break;
	case 'wikiintext':
		echo 'In wiki '.$winames[$r[1]].', clicked link to '.$r[3];
		break;
	}
	echo '</td>';
	echo '</tr>';
}
echo '</tbody></table>';
		
require("../footer.php");

?>
