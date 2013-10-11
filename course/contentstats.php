<?php
//IMathAS: Content view statistics for an item
//(c) 2013 David Lippman for Lumen Learning

require("../validate.php");

$overwritebody = false;

if (!isset($teacherid) && !isset($tutorid)) {
	$overwritebody = true;
	$body = 'You do not have authorization to view this page'; 
}
$type = $_GET['type'];
$typeid = intval($_GET['id']);

if ($typeid==0 || !in_array($type,array('I','L','A','W','F'))) {
	$overwritebody = true;
	$body = 'Invalid request';
} else {
	$data = array();
	$descrips = array();
	if ($type=='I') {
		$query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type='inlinetext' AND typeid='$typeid'";
		$q2 = "SELECT title FROM imas_inlinetext WHERE id='$typeid'";
	} else if ($type=='L') {
		$query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type IN ('linkedsum','linkedlink','linkedintext','linkedvviacal') AND typeid='$typeid'";
		$q2 = "SELECT title FROM imas_linkedtext WHERE id='$typeid'";
	} else if ($type=='A') {
		$query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type IN ('assessintro','assessum','assess') AND typeid='$typeid'";
		$q2 = "SELECT name FROM imas_assessments WHERE id='$typeid'";
	} else if ($type=='W') {
		$query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type IN ('wiki','wikiintext') AND typeid='$typeid'";
		$q2 = "SELECT name FROM imas_wikis WHERE id='$typeid'";
	} else if ($type=='F') {
		$query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type IN ('forumpost','forumreply') AND info='$typeid'";
		$q2 = "SELECT name FROM imas_forums WHERE id='$typeid'";
	} 
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_assoc($result)) {
		$ident = $row['type'].'::'.$row['info'];
		$type = $row['type'];
		if (!isset($descrips[$ident])) {
			if (in_array($type,array('inlinetext','linkedsum','linkedintext','assessintro','assesssum','wikiintext'))) {
				$desc = 'In-item link to '.$row['info'];
			} else if (in_array($type,array('linkedlink','linkedviacal','wiki','assess'))) {
				$desc = 'Link to item';
			} else if ($type=='forumpost') {
				$desc = 'Forum post';
			} else if ($type=='forumreply') {
				$desc = 'Forum reply';
			}
			$descrips[$ident] = $desc;
		}
			
		if (!isset($data[$ident])) {
			$data[$ident] = array();
		}
		if (!isset($data[$ident][$row['userid']])) {
			$data[$ident][$row['userid']] = 1;
		} else {
			$data[$ident][$row['userid']]++;
		}
	}
	
	$result = mysql_query($q2) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	$itemname = $row[0];
	
	$stus = array();
	$query = "SELECT iu.id,iu.LastName,iu.FirstName FROM imas_users AS iu ";
	$query .= "JOIN imas_students AS istu ON iu.id=istu.userid AND istu.courseid='$cid' ";
	$query .= "ORDER BY iu.LastName,iu.FirstName";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_assoc($result)) {
		$stus[$row['id']] = $row['LastName'].', '.$row['FirstName'];
	}
}

//*** begin HTML output***/
require("../header.php");

if ($overwritebody) {
	echo $body;
} else {
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo " &gt; Content Stats</div>";
	
	echo '<div id="headermoddataset" class="pagetitle">';
	echo "<h2>Stats: $itemname</h2>\n";
	echo '</div>';
	
	$idents = array_keys($descrips);
	
	if (count($idents)==0) {
		echo '<p>No views on this item yet</p>';
	}
	
	foreach ($idents as $ident) {
		echo '<h4>'.$descrips[$ident].'</h4>';
		echo '<table class="gb"><thead>';
		echo '<tr><th colspan="2">Viewed</th><th>Not Viewed</th></tr>';
		echo '<tr><th>Name</th><th style="padding-right:1em">Views</th>';
		echo '<th>Name</th></tr></thead><tbody>';
		
		$didview = array();
		$notview = array();
		foreach ($stus as $stu=>$name) {
			if (isset($data[$ident][$stu])) {
				$didview[] = array($name,$data[$ident][$stu]);
			} else {
				$notview[] = $name;
			}
		}
		$n = max(count($didview),count($notview));
		for ($i=0;$i<$n;$i++) {
			echo '<tr>';
			if (!isset($didview[$i])) {
				echo '<td></td><td style="border-right:1px solid"></td>';
			} else {
				echo '<td>'.$didview[$i][0].'</td>';
				echo '<td style="border-right:1px solid">'.$didview[$i][1].'</td>';
			}
			if (!isset($notview[$i])) {
				echo '<td></td>';
			} else {
				echo '<td>'.$notview[$i].'</td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}
require("../footer.php");
		
?>
