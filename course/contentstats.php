<?php
//IMathAS: Content view statistics for an item
//(c) 2013 David Lippman for Lumen Learning

require("../init.php");


$overwritebody = false;

if (!isset($teacherid) && !isset($tutorid)) {
	$overwritebody = true;
	$body = 'You do not have authorization to view this page';
}
$stype = $_GET['type'];
$typeid = Sanitize::onlyInt($_GET['id']);

if ($typeid==0 || !in_array($stype,array('I','L','A','W','F'))) {
	$overwritebody = true;
	$body = 'Invalid request';
} else {
	$data = array();
	$descrips = array();
	$qarr = array(':courseid'=>$cid, ':typeid'=>$typeid);
	if ($stype=='I') {
		//DB $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type='inlinetext' AND typeid='$typeid'";
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type='inlinetext' AND typeid=:typeid");
		//DB $query = "SELECT title FROM imas_inlinetext WHERE id='$typeid'";
		$stm2 = $DBH->prepare("SELECT title FROM imas_inlinetext WHERE id=:id");
	} else if ($stype=='L') {
		//DB $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type IN ('linkedsum','linkedlink','linkedintext','linkedvviacal') AND typeid='$typeid'";
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type IN ('linkedsum','linkedlink','linkedintext','linkedvviacal') AND typeid=:typeid");
		//DB $q2 = "SELECT title FROM imas_linkedtext WHERE id='$typeid'";
		$stm2 = $DBH->prepare("SELECT title FROM imas_linkedtext WHERE id=:id");
	} else if ($stype=='A') {
		//DB $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type IN ('assessintro','assessum','assess') AND typeid='$typeid'";
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type IN ('assessintro','assessum','assess') AND typeid=:typeid");
		//DB $q2 = "SELECT name FROM imas_assessments WHERE id='$typeid'";
		$stm2 = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
	} else if ($stype=='W') {
		//DB $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type IN ('wiki','wikiintext') AND typeid='$typeid'";
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type IN ('wiki','wikiintext') AND typeid=:typeid");
		//DB $q2 = "SELECT name FROM imas_wikis WHERE id='$typeid'";
		$stm2 = $DBH->prepare("SELECT name FROM imas_wikis WHERE id=:id");
	} else if ($stype=='F') {
		//DB $query = "SELECT userid,type,info FROM imas_content_track WHERE courseid='$cid' AND type IN ('forumpost','forumreply') AND info='$typeid'";
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND ((type='forumpost' AND info=:typeid) OR (type='forumreply' AND info LIKE :likeid))");
		$qarr[':likeid'] = "$typeid;%";
		//DB $q2 = "SELECT name FROM imas_forums WHERE id='$typeid'";
		$stm2 = $DBH->prepare("SELECT name FROM imas_forums WHERE id=:id");
	}
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm->execute($qarr);
	//DB while ($row = mysql_fetch_assoc($result)) {
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$type = $row['type'];
		if ($type=='linkedviacal') {
			$type = 'linkedlink';
		}
		if ($stype=='F' ||in_array($type,array('inlinetext','linkedsum','linkedintext','assessintro','assesssum','wikiintext'))) {
			if ($type=='forumreply') {
				$parts = explode(';',$row['info']);
				$ident = $type.'::'.$parts[0];
			} else {
				$ident = $type.'::'.$row['info'];
			}
		} else {
			$ident = $type;
		}
		if (!isset($descrips[$ident])) {
			if (in_array($type,array('inlinetext','linkedsum','linkedintext','assessintro','assesssum','wikiintext'))) {
				$parts = explode('::',$row['info']);
				if (count($parts)>1) {
					$desc = 'In-item link to <a href="'.$parts[0].'">'.$parts[1].'</a>';
				} else {
					$desc = 'In-item link to '.$row['info'];
				}
			} else if (in_array($type,array('linkedlink','linkedviacal','wiki','assess'))) {
				$desc = 'Link to item';
			} else if ($type=='forumpost') {
				$desc = 'Forum posts';
			} else if ($type=='forumreply') {
				$desc = 'Forum replies';
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
	$stm2->execute(array(':id'=>$typeid));
	//DB $result = mysql_query($q2) or die("Query failed : " . mysql_error());
	//DB $row = mysql_fetch_row($result);
	$itemname = Sanitize::encodeStringForDisplay($stm2->fetchColumn(0));

	$stus = array();
	//DB $query = "SELECT iu.id,iu.LastName,iu.FirstName FROM imas_users AS iu ";
	//DB $query .= "JOIN imas_students AS istu ON iu.id=istu.userid AND istu.courseid='$cid' ";
	//DB $query .= "ORDER BY iu.LastName,iu.FirstName";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_assoc($result)) {
	$query = "SELECT iu.id,iu.LastName,iu.FirstName FROM imas_users AS iu ";
	$query .= "JOIN imas_students AS istu ON iu.id=istu.userid AND istu.courseid=:courseid ";
	$query .= "ORDER BY iu.LastName,iu.FirstName";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$stus[$row['id']] = $row['LastName'].', '.$row['FirstName'];
	}
}

//*** begin HTML output***/
require("../header.php");

if ($overwritebody) {
	echo $body;
} else {
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo " &gt; Content Stats</div>";

	echo '<div id="headermoddataset" class="pagetitle">';
	echo "<h1>Stats: $itemname</h1>\n";
	echo '</div>';

	$idents = array_keys($descrips);

	if (count($idents)==0) {
		if ($stype=='I') {
			echo '<p>'._('No clicks on links in this item yet').'</p>';
		} else if ($stype=='F') {
			echo '<p>'._('No student posts or replies in this forum yet').'</p>';
		} else {
			echo '<p>No views on this item yet</p>';
		}
	}
	if ($stype=='F') {
		echo '<p>'._('This page only shows who has made posts and replies. For forum viewing data, look in the forum thread list and click the number in the Views column').'</p>';
	}

	foreach ($idents as $ident) {
		echo '<h3>'.$descrips[$ident].'</h3>';
		echo '<table class="gb"><thead>';
		if ($stype=='F') {
			if ($descrips[$ident] == 'Forum posts') {
				echo '<tr><th colspan="2">Posted</th><th>Not Posted</th></tr>';
				echo '<tr><th>Name</th><th style="padding-right:1em">Posts</th>';
			} else if ($descrips[$ident] == 'Forum replies') {
				echo '<tr><th colspan="2">Replied</th><th>Not Replied</th></tr>';
				echo '<tr><th>Name</th><th style="padding-right:1em">Replies</th>';
			}
		} else {
			echo '<tr><th colspan="2">Viewed</th><th>Not Viewed</th></tr>';
			echo '<tr><th>Name</th><th style="padding-right:1em">Views</th>';
		}
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
