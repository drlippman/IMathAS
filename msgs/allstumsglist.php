<?php
	//Displays Message list of students
	//(c) 2008 David Lippman

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
	   require("../header.php");
	   echo "You must be a teacher, and access this page from the course page Messages link.\n";
	   require("../footer.php");
	   exit;
	}

	$threadsperpage = intval($listperpage);

	$cid = Sanitize::courseId($_GET['cid']);
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = Sanitize::onlyInt($_GET['page']);
	}
	if (isset($_GET['filterstu'])) {
		$filterstu = Sanitize::onlyInt($_GET['filterstu']);
	} else {
		$filterstu = 0;
	}

	if (isset($_POST['remove'])) {
		$goodmsgs = array();
		foreach ($_POST['checked'] as $msgid) {
			if (is_numeric($msgid) && $msgid!=0) {
				$goodmsgs[] = intval($msgid);
			}
		}
		$checklist = implode(',', $goodmsgs);
		//TODO: check courseid on these msgs against teacher courses
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist)";
		$DBH->query($query);
	}
	if (isset($_GET['removeid'])) {
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['removeid']));
	}

	$pagetitle = "Student Messages";
	require("../header.php");

	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> ";
	if ($cid>0) {
		echo "&gt; <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	}
	echo "&gt; <a href=\"msglist.php?cid=$cid\">Message List</a> &gt; Student Messages</div>";
	echo '<div id="headerallstumsglist" class="pagetitle"><h1>All Student Messages</h1></div>';
	if ($filterstu>0) {
		$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_msgs WHERE courseid=:courseid AND deleted<2 AND msgto=:msgto");
		$stm->execute(array(':courseid'=>$cid, ':msgto'=>$filterstu));
	} else {
		$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_msgs WHERE courseid=:courseid AND deleted<2");
		$stm->execute(array(':courseid'=>$cid));
	}
	$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);

	$prevnext = '';
	if ($numpages > 1) {
		echo "<span class=\"right\" style=\"padding: 5px;\">Page: ";
		if ($page < $numpages/2) {
			$min = max(2,$page-4);
			$max = min($numpages-1,$page+8+$min-$page);
		} else {
			$max = min($numpages-1,$page+4);
			$min = max(2,$page-8+$max-$page);
		}
		if ($page==1) {
			echo "<b>1</b> ";
		} else {
			echo "<a href=\"allstumsglist.php?page=1&cid=$cid&filterstu=$filterstu\">1</a> ";
		}
		if ($min!=2) { echo " ... ";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				echo "<b>$i</b> ";
			} else {
				echo "<a href=\"allstumsglist.php?page=$i&cid=$cid&filterstu=$filterstu\">$i</a> ";
			}
		}
		if ($max!=$numpages-1) { echo " ... ";}
		if ($page == $numpages) {
			echo "<b>$numpages</b> ";
		} else {
			echo "<a href=\"allstumsglist.php?page=$numpages&cid=$cid&filterstu=$filterstu\">$numpages</a> ";
		}
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		if ($page>1) {
			$prevnext .= "<a href=\"allstumsglist.php?page=".($page-1)."&cid=$cid&filterstu=$filterstu\">Previous</a> ";
		} else {
			$prevnext .= "Previous ";
		}
		if ($page < $numpages) {
			$prevnext .= "<a href=\"allstumsglist.php?page=".($page+1)."&cid=$cid&filterstu=$filterstu\">Next</a> ";
		} else {
			$prevnext .= "Next ";
		}
		echo $prevnext;
		echo "</span>\n";
	}
	$address = $GLOBALS['basesiteurl'] . "/msgs/allstumsglist.php?cid=$cid&filterstu=";

?>
<script type="text/javascript">
function chgfilter() {
	var filterstu = document.getElementById("filterstu").value;
	window.location = "<?php echo $address;?>"+filterstu;
}
</script>
	<form id="qform" method=post action="allstumsglist.php?page=<?php echo $page;?>&cid=<?php echo $cid;?>">
	<p>Filter by student: <select id="filterstu" class='pii-full-name' onchange="chgfilter()">
<?php
	echo "<option value=\"0\" ";
	if ($filterstu==0) {
		echo "selected=1 ";
	}
	echo ">All students</option>";
	$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName FROM imas_users,imas_students WHERE ";
	$query .= "imas_students.userid=imas_users.id AND imas_students.courseid=:courseid ORDER BY imas_users.LastName,imas_users.FirstName";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	$stulist = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stulist[$row[0]] = "{$row[1]}, {$row[2]}";
		echo "<option value=\"" . Sanitize::encodeStringForDisplay($row[0]) . "\" ";
		if ($filterstu==$row[0]) {
			echo 'selected=1';
		}
		echo " >" . Sanitize::encodeStringForDisplay($row[1]) . ", " . Sanitize::encodeStringForDisplay($row[2]) . "</option>";
	}
	echo "</select></p>";
	$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName FROM imas_users,imas_teachers WHERE ";
	$query .= "imas_teachers.userid=imas_users.id AND imas_teachers.courseid=:courseid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stulist[$row[0]] = "{$row[1]}, {$row[2]}";
	}

?>
	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
	With Selected: 	<input type=submit name="remove" value="Delete">

	<table class=gb>
	<thead>
	<tr><th></th><th>Message</th><th>From</th><th>To</th><th>Sent</th></tr>
	</thead>
	<tbody>
<?php
	$offset = ($page-1)*$threadsperpage;
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_msgs.msgto,imas_msgs.msgfrom,imas_courses.name ";
	$query .= "FROM imas_msgs,imas_courses WHERE imas_courses.id=imas_msgs.courseid AND ";
	$query .= "imas_msgs.deleted<2 AND imas_msgs.courseid=:courseid ";
	if ($filterstu>0) {
		$query .= "AND imas_msgs.msgto=:msgto ";
	}
	$query .= "ORDER BY senddate DESC LIMIT $offset,$threadsperpage";// these are INT vals
	$stm = $DBH->prepare($query);
	if ($filterstu>0) {
		$stm->execute(array(':courseid'=>$cid, ':msgto'=>$filterstu));
	} else {
		$stm->execute(array(':courseid'=>$cid));
	}
	if ($stm->rowCount()==0) {
		echo "<tr><td></td><td>No messages</td><td></td></tr>";
	}
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (trim($line['title'])=='') {
			$line['title'] = '[No Subject]';
		}
		$n = 0;
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		if ($n==1) {
			$line['title'] = 'Re: '.Sanitize::encodeStringForDisplay($line['title']);
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".Sanitize::encodeStringForDisplay($line['title']);
		}
		echo "<tr><td><input type=checkbox name=\"checked[]\" value=\"" . Sanitize::onlyInt($line['id']) . "\"/></td><td>";
		echo "<a href=\"viewmsg.php?page" . Sanitize::onlyInt($page) . "&cid=$cid&filterstu=" . Sanitize::encodeStringForDisplay($filterstu) . "&type=msg&msgid=" . Sanitize::onlyInt($line['id']) . "&type=allstu\">";
		echo $line['title']; // sanitized above
        echo "</a></td><td>";
        if (isset($stulist[$line['msgfrom']])) {
            echo '<span class="pii-full-name">';
            echo Sanitize::encodeStringForDisplay($stulist[$line['msgfrom']]);
            echo '</span>';
        }
        echo "</td><td>";
        if (isset($stulist[$line['msgto']])) {
            echo '<span class="pii-full-name">';
            echo Sanitize::encodeStringForDisplay($stulist[$line['msgto']]);
            echo '</span>';
        }
        echo "</td>";
		$senddate = tzdate("F j, Y, g:i a",$line['senddate']);
		echo "<td>$senddate</td></tr>";
	}
?>
	</tbody>
	</table>
	</form>
<?php
	if ($prevnext != '') {
		echo "<p>$prevnext</p>";
	}
	echo "<p><a href=\"msglist.php?cid=$cid\">Back to My Messages</a></p>";

	require("../footer.php");
?>
