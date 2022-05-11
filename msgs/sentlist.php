<?php
	//Displays Message list
	//(c) 2006 David Lippman

	require("../init.php");
	require('../includes/getcourseopts.php');

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

	$threadsperpage = intval($listperpage);
    
	$cid = Sanitize::courseId($_GET['cid'] ?? 0);
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = Sanitize::onlyInt($_GET['page']);
	}
	if (isset($_GET['filtercid'])) {
		$filtercid = Sanitize::onlyInt($_GET['filtercid']);
	} else if ($cid!='admin' && $cid>0) {
		$filtercid = $cid;
	} else {
		$filtercid = 0;
	}
	if (isset($_GET['filteruid'])) {
		$filteruid = intval($_GET['filteruid']);
	} else {
		$filteruid = 0;
	}
	/*
	viewed: 0 unread, 1 read
	deleted: 0 not deleted, 1 deleted by sender, 2 deleted by reader  (ordered this way so we can use < 2)
	tagged: 0 no, 1 yes
	*/
	if (isset($_POST['remove']) && count($_POST['checked'])>0) {
		$checklist = implode(',', array_map('intval', $_POST['checked']));
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id IN ($checklist) AND deleted=2 AND msgfrom=?");
		$stm->execute(array($userid));
		$stm = $DBH->prepare("UPDATE imas_msgs SET deleted=1 WHERE id IN ($checklist) AND msgfrom=?");
		$stm->execute(array($userid));
	}
	if (isset($_POST['unsend']) && count($_POST['checked'])>0 && $isteacher) {
		$checklist = implode(',', array_map('intval', $_POST['checked']));
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id IN ($checklist) AND msgfrom=?");
		$stm->execute(array($userid));
	}
	if (isset($_GET['removeid'])) {
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id=:id AND deleted=2 AND msgfrom=:msgfrom");
		$stm->execute(array(':id'=>$_GET['removeid'], ':msgfrom'=>$userid));
		$stm = $DBH->prepare("UPDATE imas_msgs SET deleted=1 WHERE id=:id AND msgfrom=:msgfrom");
		$stm->execute(array(':id'=>$_GET['removeid'], ':msgfrom'=>$userid));
	}

	$pagetitle = "Messages";
	require("../header.php");

	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($cid>0 && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
		echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	}
	echo " Sent Message List</div>";
	echo '<div id="headersentlist" class="pagetitle"><h1>Sent Messages</h1></div>';

	echo "<div class=\"cpmid\"><a href=\"msglist.php?cid=$cid\">Received Messages</a></div>";
	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom=:msgfrom AND deleted<>1 ";
	$qarr = array(':msgfrom'=>$userid);
	if ($filtercid>0) {
		$query .= " AND courseid=:courseid";
		$qarr[':courseid'] = $filtercid;
	}
	if ($filteruid>0) {
		$query .= " AND msgto=:msgto";
		$qarr[':msgto'] = $filteruid;
	}
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);
	if ($numpages==0 && $filteruid>0) {
		//might have changed filtercid w/o changing user.
		//we'll open up to all users then
		$filteruid = 0;
		$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom=:msgfrom AND deleted<>1 ";
		if ($filtercid>0) {
			$query .= " AND courseid=:courseid";
		}
		$stm = $DBH->prepare($query);
		if ($filtercid>0) {
			$stm->execute(array(':msgfrom'=>$userid, ':courseid'=>$filtercid));
		} else {
			$stm->execute(array(':msgfrom'=>$userid));
		}
		$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);
	}
	$prevnext = '';
	if ($numpages > 1) {
		$prevnext .= "Page: ";
		if ($page < $numpages/2) {
			$min = max(2,$page-4);
			$max = min($numpages-1,$page+8+$min-$page);
		} else {
			$max = min($numpages-1,$page+4);
			$min = max(2,$page-8+$max-$page);
		}
		if ($page==1) {
			$prevnext .= "<b>1</b> ";
		} else {
			$prevnext .= "<a href=\"sentlist.php?page=1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">1</a> ";
		}
		if ($min!=2) { $prevnext .= " ... ";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				$prevnext .= "<b>$i</b> ";
			} else {
				$prevnext .= "<a href=\"sentlist.php?page=$i&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">$i</a> ";
			}
		}
		if ($max!=$numpages-1) {$prevnext .= " ... ";}
		if ($page == $numpages) {
			$prevnext .= "<b>$numpages</b> ";
		} else {
			$prevnext .= "<a href=\"sentlist.php?page=$numpages&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">$numpages</a> ";
		}
		$prevnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		if ($page>1) {
			$prevnext .= "<a href=\"sentlist.php?page=".($page-1)."&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Previous</a> ";
		} else {
			$prevnext .= "Previous ";
		}
		if ($page < $numpages) {
			$prevnext .= "| <a href=\"sentlist.php?page=".($page+1)."&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Next</a> ";
		} else {
			$prevnext .= "| Next ";
		}
		echo "<div>$prevnext</div>\n";
	}
	$address = $GLOBALS['basesiteurl'] . "/msgs/sentlist.php?cid=$cid&filtercid=";


?>
<script type="text/javascript">
function chgfilter() {
	var filtercid = document.getElementById("filtercid").value;
	var filteruid = document.getElementById("filteruid").value;
	window.location = "<?php echo $address;?>"+filtercid+"&filteruid="+filteruid;
}
</script>
	<form id="qform" method="post" action="sentlist.php?page=<?php echo $page;?>&cid=<?php echo $cid;?>">
	<p><label for="filtercid">Filter by course</label>: <select id="filtercid" onchange="chgfilter()">
<?php
	echo "<option value=\"0\" ";
	if ($filtercid==0) {
		echo "selected=1 ";
	}
	echo ">All courses</option>";
	echo getCourseOpts(false, $filtercid);
	echo "</select> ";
	echo '<label for="filteruid">By recipient</label>: <select id="filteruid" class="pii-full-name" onchange="chgfilter()"><option value="0" ';
	if ($filteruid==0) {
		echo 'selected="selected" ';
	}
	echo '>All</option>';
	$query = "SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users ";
	$query .= "JOIN imas_msgs ON imas_msgs.msgto=imas_users.id WHERE imas_msgs.msgfrom=:msgfrom";
	if ($filtercid>0) {
		$query .= " AND imas_msgs.courseid=:courseid";
	}
	$stm = $DBH->prepare($query);
	if ($filtercid>0) {
		$stm->execute(array(':msgfrom'=>$userid, ':courseid'=>$filtercid));
	} else {
		$stm->execute(array(':msgfrom'=>$userid));
	}
	$senders = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$senders[$row[0]] = $row[1] . ', ' . $row[2];
	}
	asort($senders);
	foreach ($senders as $sid=>$sname) {
		echo "<option value=\"".Sanitize::onlyInt($sid)."\" ";
		if ($filteruid==$sid) {
			echo 'selected=1';
		}
		echo '>' . Sanitize::encodeStringForDisplay($sname) . '</option>';
	}
	echo "</select></p>";
?>
	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
	With Selected: 	<input type=submit name="remove" value="Remove from Sent Message List">
	<?php
	if ($isteacher) {
		echo ' <input type=submit name="unsend" value="Unsend" onclick="return confirm(\'Are you sure? This will delete the message from the receiver\\\'s inbox and your send list.\');">';
	}
	?>

	<table class=gb>
	<thead>
	<tr><th></th><th>Message</th><th>To</th><th>Read</th><th>Sent</th></tr>
	</thead>
	<tbody>
<?php
	$offset = max(0, ($page-1)*$threadsperpage);
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.viewed FROM imas_msgs,imas_users ";
	$query .= "WHERE imas_users.id=imas_msgs.msgto AND imas_msgs.msgfrom=:msgfrom AND imas_msgs.deleted<>1 ";
	$qarr = array(':msgfrom'=>$userid);
	if ($filtercid>0) {
		$query .= "AND imas_msgs.courseid=:courseid ";
		$qarr[':courseid']=$filtercid;
	}
	if ($filteruid>0) {
		$query .= "AND imas_msgs.msgto=:msgto ";
		$qarr[':msgto']=$filteruid;
	}
	$query .= " ORDER BY senddate DESC ";
	$query .= "LIMIT $offset,$threadsperpage";// known INTs
	$stm = $DBH->prepare($query);
  $stm->execute($qarr);
	if ($stm->rowCount()==0) {
		echo "<tr><td></td><td>No messages</td><td></td><td></td></tr>";
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
		$line['title'] = Sanitize::encodeStringForDisplay($line['title']);
		if ($n==1) {
			$line['title'] = 'Re: '.$line['title'];
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".$line['title'];
		}
		echo "<tr><td><input type=checkbox name=\"checked[]\" value=\"".Sanitize::onlyInt($line['id'])."\"/></td><td>";
		echo "<a href=\"viewmsg.php?page$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&type=sent&msgid=".Sanitize::onlyInt($line['id'])."\">";
		echo $line['title'];
		echo "</a></td>";
		printf("<td><span class='pii-full-name'>%s, %s</span></td>", Sanitize::encodeStringForDisplay($line['LastName']),
            Sanitize::encodeStringForDisplay($line['FirstName']));
		if ($line['viewed']==1) {
			echo "<td>Yes</td>";
		} else {
			echo "<td>No</td>";
		}
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

	require("../footer.php");
?>
