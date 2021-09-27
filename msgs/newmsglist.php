<?php
	//Displays New Message list
	//(c) 2009 David Lippman

/*	viewed: 0 unread, 1 read
     deleted: 0 not deleted, 1 deleted by sender, 2 deleted by reader  (ordered this way so we can use < 2)
     tagged: 0 no, 1 yes
*/

	require("../init.php");


	if ($cid!=0 && !isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
	}
	if (isset($teacherid)) {
		$isteacher = true;
	} else {
		$isteacher = false;
	}
	$cansendmsgs = false;
	$threadsperpage = $listperpage;

	$cid = Sanitize::courseId($_GET['cid']);

	if (isset($_POST['read']) && !empty($_POST['checked'])) {
		$checklist = implode(',', array_map('intval', $_POST['checked']));
		$stm = $DBH->prepare("UPDATE imas_msgs SET viewed=1 WHERE id IN ($checklist) AND viewed=0 AND msgto=?");
		$stm->execute(array($userid));
	}
	if (isset($_POST['remove']) && !empty($_POST['checked'])) {
		$checklist = implode(',', array_map('intval', $_POST['checked']));
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id IN ($checklist) AND deleted=1 AND msgto=?");
		$stm->execute(array($userid));
		$stm = $DBH->prepare("UPDATE imas_msgs SET deleted=2 WHERE id IN ($checklist) AND msgto=?");
		$stm->execute(array($userid));
	}
	if (isset($_GET['removeid'])) {
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id=:id AND deleted=1 AND msgto=:msgto");
		$stm->execute(array(':id'=>$_GET['removeid'], ':msgto'=>$userid));
		$stm = $DBH->prepare("UPDATE imas_msgs SET deleted=2 WHERE id=:id AND msgto=:msgto");
		$stm->execute(array(':id'=>$_GET['removeid'], ':msgto'=>$userid));
	}

	$pagetitle = "New Messages";
	require("../header.php");

	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> ";
	if ($cid>0) {
		echo "&gt; <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	}
	echo "&gt; New Message List</div>";
	echo '<div id="headernewmsglist" class="pagetitle"><h1>New Messages</h1></div>';

?>

	<form id="qform" method="post" action="newmsglist.php?cid=<?php echo $cid;?>">

	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
	With Selected: <input type=submit name="read" value="Mark as Read">
	<input type=submit name="remove" value="Delete">

<?php
	$query = "SELECT imas_msgs.id,imas_msgs.msgfrom,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_users.LastName,imas_users.FirstName,imas_msgs.viewed,imas_courses.name,imas_courses.id AS cid ";
	$query .= "FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom LEFT JOIN imas_courses ON imas_courses.id=imas_msgs.courseid WHERE ";
	$query .= "imas_msgs.msgto=:msgto AND viewed=0 AND deleted<2 ";
	$query .= "ORDER BY imas_courses.name, senddate DESC ";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':msgto'=>$userid));
	if ($stm->rowCount()==0) {
		echo "<p>No new messages</p>";
	} else {
		$lastcourse = '';
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		    $line['title'] = Sanitize::encodeStringForDisplay($line['title']);
			if ($line['name']!=$lastcourse) {
				if($lastcourse!='') {
					echo '</tbody></table>';
				}
				echo '<h3>Course: '.Sanitize::encodeStringForDisplay($line['name']).'</h3>';
				echo '<table class="gb"><thead><tr><th></th><th>Message</th><th>Replied</th><th>From</th><th>Course</th><th>Sent</th></tr></thead><tbody>';
				$lastcourse = $line['name'];
			}
			if (trim($line['title'])=='') {
				$line['title'] = '[No Subject]';
			}
			$n = 0;
			while (strpos($line['title'],'Re: ')===0) {
				$line['title'] = substr($line['title'],4);
				$n++;
			}
			if ($n==1) {
				$line['title'] = 'Re: '.$line['title'];
			} else if ($n>1) {
				$line['title'] = "Re<sup>$n</sup>: ".$line['title'];
			}
			printf("<tr><td><input type=checkbox name=\"checked[]\" value=\"%d\"/></td><td>",
                Sanitize::onlyInt($line['id']));
			printf("<a href=\"viewmsg.php?cid=%s&type=new&msgid=%d\">", Sanitize::courseId($line['cid']),
                Sanitize::onlyInt($line['id']));
			if ($line['viewed']==0) {
				echo "<b>{$line['title']}</b>";
			} else {
				echo $line['title'];
			}
			echo "</a></td><td>";
			if ($line['replied']==1) {
				echo "Yes";
			}
			if ($line['LastName']==null) {
				if ($line['msgfrom']==0) {
					$line['fullname'] = _("[System Message]");
				} else {
					$line['fullname'] = _("[Deleted]");
				}
			} else {
				$line['fullname'] = sprintf('%s, %s', $line['LastName'], $line['FirstName']);
			}
			printf("</td><td><span class='pii-full-name'>%s</span></td>",
                Sanitize::encodeStringForDisplay($line['fullname']));
			if ($line['name']==null) {
				$line['name'] = "[Deleted]";
			}
			printf("<td>%s</td>", Sanitize::encodeStringForDisplay($line['name']));
			$senddate = tzdate("F j, Y, g:i a",$line['senddate']);
			echo "<td>$senddate</td></tr>";
		}
		echo '</tbody></table>';
	}
?>
	</form>
<?php

	echo "<p><a href=\"sentlist.php?cid=$cid\">Sent Messages</a></p>";

	require("../footer.php");
?>
