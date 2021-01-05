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

if ($typeid==0 || !in_array($stype,array('I','L','A','W','F','D'))) {
	$overwritebody = true;
	$body = 'Invalid request';
} else {
	$data = array();
	$descrips = array();
	$qarr = array(':courseid'=>$cid, ':typeid'=>$typeid);
	if ($stype=='I') {
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type='inlinetext' AND typeid=:typeid");
		$stm2 = $DBH->prepare("SELECT title FROM imas_inlinetext WHERE id=:id");
	} else if ($stype=='L') {
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type IN ('linkedsum','linkedlink','linkedintext','linkedviacal') AND typeid=:typeid");
		$stm2 = $DBH->prepare("SELECT title FROM imas_linkedtext WHERE id=:id");
	} else if ($stype=='A') {
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type IN ('assessintro','assesssum','assess') AND typeid=:typeid");
		$stm2 = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
	} else if ($stype=='W') {
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type IN ('wiki','wikiintext') AND typeid=:typeid");
		$stm2 = $DBH->prepare("SELECT name FROM imas_wikis WHERE id=:id");
	} else if ($stype=='F') {
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND ((type='forumpost' AND info=:typeid) OR (type='forumreply' AND info LIKE :likeid))");
		$qarr[':likeid'] = "$typeid;%";
		$stm2 = $DBH->prepare("SELECT name FROM imas_forums WHERE id=:id");
	} else if ($stype=='D') {
		$stm = $DBH->prepare("SELECT userid,type,info FROM imas_content_track WHERE courseid=:courseid AND type='drill' AND typeid=:typeid");
		$stm2 = $DBH->prepare("SELECT name FROM imas_drillassess WHERE id=:id");
	}
	$stm->execute($qarr);
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
	$itemname = Sanitize::encodeStringForDisplay($stm2->fetchColumn(0));

	$stus = array();
	$query = "SELECT iu.id,iu.LastName,iu.FirstName FROM imas_users AS iu ";
	$query .= "JOIN imas_students AS istu ON iu.id=istu.userid AND istu.courseid=:courseid ";
	$query .= "WHERE istu.locked=0 ORDER BY iu.LastName,iu.FirstName";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$stus[$row['id']] = $row['LastName'].', '.$row['FirstName'];
	}
}

//*** begin HTML output***/
$placeinhead = '<script type="text/javascript">
function sendMsg(tolist) {
	GB_show("Send Message", "masssend.php?embed=true&nolimit=true&cid="+cid+"&to="+tolist, 760,"auto");
}
</script>';
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
		$didview = array();
		$didviewIDs = array();
		$notview = array();
		$notviewIDs = array();
		foreach ($stus as $stu=>$name) {
			if (isset($data[$ident][$stu])) {
				$didview[] = array($name,$data[$ident][$stu]);
				$didviewIDs[] = $stu;
			} else {
				$notview[] = $name;
				$notviewIDs[] = $stu;
			}
		}
		$viewedBtn = '<a class=small href="#" onclick="sendMsg(\''.implode('-', $didviewIDs).'\');return false;">'._('Send Message').'</a>';
		$notviewedBtn = '<a class=small href="#" onclick="sendMsg(\''.implode('-', $notviewIDs).'\');return false;">'._('Send Message').'</a>';

		echo '<h3>'.$descrips[$ident].'</h3>';
		echo '<table class="gb"><thead>';
		if ($stype=='F') {
			if ($descrips[$ident] == 'Forum posts') {
				echo '<tr><th colspan="2">Posted<br/>'.$viewedBtn.'</th><th>Not Posted<br/>'.$notviewedBtn.'</th></tr>';
				echo '<tr><th>Name</th><th style="padding-right:1em">Posts</th>';
			} else if ($descrips[$ident] == 'Forum replies') {
				echo '<tr><th colspan="2">Replied<br/>'.$viewedBtn.'</th><th>Not Replied<br/>'.$notviewedBtn.'</th></tr>';
				echo '<tr><th>Name</th><th style="padding-right:1em">Replies</th>';
			}
		} else {
			echo '<tr><th colspan="2">Viewed<br/>'.$viewedBtn.'</th><th>Not Viewed<br/>'.$notviewedBtn.'</th></tr>';
			echo '<tr><th>Name</th><th style="padding-right:1em">Views</th>';
		}
		echo '<th>Name</th></tr></thead><tbody>';

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
