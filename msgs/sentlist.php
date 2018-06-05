<?php
	//Displays Message list
	//(c) 2006 David Lippman

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

	$threadsperpage = intval($listperpage);

	$cid = Sanitize::courseId($_GET['cid']);
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
isread:
# to  frm
0 NR  --
1 R   --
2 DR  --
3 DNR --
4 NR  D
5 R   D

0 - not read
1 - read
2 - deleted not read
3 - deleted and read
4 - deleted by sender
5 - deleted by sender,read

isread is bitwise:
1      2         4                   8
Read   Deleted   Deleted by Sender   Tagged
	*/
	if (isset($_POST['remove']) && count($_POST['checked'])>0) {
		//DB $checklist = "'".implode("','",$_POST['checked'])."'";
		$checklist = implode(',', array_map('intval', $_POST['checked']));
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist) AND (isread&2)=2";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$DBH->query($query);
		$query = "UPDATE imas_msgs SET isread=(isread|4) WHERE id IN ($checklist)";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$DBH->query($query);
	}
	if (isset($_POST['unsend']) && count($_POST['checked'])>0) {
		//DB $checklist = "'".implode("','",$_POST['checked'])."'";
		$checklist = implode(',', array_map('intval', $_POST['checked']));
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist)";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$DBH->query($query);
	}
	if (isset($_GET['removeid'])) {
		//DB $query = "DELETE FROM imas_msgs WHERE id='{$_GET['removeid']}' AND (isread&2)=2";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id=:id AND (isread&2)=2");
		$stm->execute(array(':id'=>$_GET['removeid']));
		//DB $query = "UPDATE imas_msgs SET isread=(isread|4) WHERE id='{$_GET['removeid']}'";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_msgs SET isread=(isread|4) WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['removeid']));
	}

	$pagetitle = "Messages";
	require("../header.php");

	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
		echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	}
	echo " Sent Message List</div>";
	echo '<div id="headersentlist" class="pagetitle"><h2>Sent Messages</h2></div>';

	echo "<div class=\"cpmid\"><a href=\"msglist.php?cid=$cid\">Received Messages</a></div>";

	//DB $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom='$userid' AND (isread&4)=0";
	//DB if ($filtercid>0) {
	//DB 	$query .= " AND courseid='$filtercid'";
	//DB }
	//DB if ($filteruid>0) {
	//DB 	$query .= " AND msgto='$filteruid'";
	//DB }
	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom=:msgfrom AND (isread&4)=0";
	$qarr = array(':msgfrom'=>$userid);
	if ($filtercid>0) {
		$query .= " AND courseid=:courseid";
		$qarr[':courseid'] = $filtercid;
	}
	if ($filteruid>0) {
		$query .= " AND msgto=:msgto";
		$qarr[':msgto'] = $filteruid;
	}
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	//DB $numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
	$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);
	if ($numpages==0 && $filteruid>0) {
		//might have changed filtercid w/o changing user.
		//we'll open up to all users then
		$filteruid = 0;
		//DB $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom='$userid' AND (isread&4)=0";
		//DB if ($filtercid>0) {
			//DB $query .= " AND courseid='$filtercid'";
		//DB }
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
		$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom=:msgfrom AND (isread&4)=0";
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
	/*$query = "SELECT DISTINCT imas_courses.id,imas_courses.name FROM imas_courses,imas_msgs WHERE imas_courses.id=imas_msgs.courseid AND imas_msgs.msgfrom=:msgfrom";
	$query .= " ORDER BY imas_courses.name";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':msgfrom'=>$userid));
	*/
	$query = "SELECT DISTINCT imas_courses.id,imas_courses.name,";
	$query .= "IF(UNIX_TIMESTAMP()<imas_courses.startdate OR UNIX_TIMESTAMP()>imas_courses.enddate,0,1) as active,";
	$query .= "IF(istu.hidefromcourselist=1 OR itut.hidefromcourselist=1 OR iteach.hidefromcourselist=1,1,0) as hidden ";
	$query .= "FROM imas_courses JOIN imas_msgs ON imas_courses.id=imas_msgs.courseid AND imas_msgs.msgfrom=:msgfrom AND imas_msgs.isread&4=0 ";
	$query .= "LEFT JOIN imas_students AS istu ON imas_msgs.courseid=istu.courseid AND istu.userid=:uid ";
	$query .= "LEFT JOIN imas_tutors AS itut ON imas_msgs.courseid=itut.courseid AND itut.userid=:uid2 ";
	$query .= "LEFT JOIN imas_teachers AS iteach ON imas_msgs.courseid=iteach.courseid AND iteach.userid=:uid3 ";
	$query .= "ORDER BY hidden,active DESC,imas_courses.name";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':msgfrom'=>$userid, ':uid'=>$userid, ':uid2'=>$userid, ':uid3'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[3]==1) {
			$prefix = _('Hidden: ');
		} else if ($row[2]==0) {
			$prefix = _('Inactive: ');
		} else {
			$prefix = '';
		}
		echo "<option value=\"".Sanitize::onlyInt($row[0])."\" ";
		if ($filtercid==$row[0]) {
			echo 'selected=1';
		}
		printf(" >%s</option>", Sanitize::encodeStringForDisplay($prefix . $row[1]));
	}
	echo "</select> ";
	echo '<label for="filteruid">By recipient</label>: <select id="filteruid" onchange="chgfilter()"><option value="0" ';
	if ($filteruid==0) {
		echo 'selected="selected" ';
	}
	echo '>All</option>';
	//DB $query = "SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users ";
	//DB $query .= "JOIN imas_msgs ON imas_msgs.msgto=imas_users.id WHERE imas_msgs.msgfrom='$userid'";
	//DB if ($filtercid>0) {
		//DB $query .= " AND imas_msgs.courseid='$filtercid'";
	//DB }
	//DB $query .= " ORDER BY imas_users.LastName, imas_users.FirstName";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query = "SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users ";
	$query .= "JOIN imas_msgs ON imas_msgs.msgto=imas_users.id WHERE imas_msgs.msgfrom=:msgfrom";
	if ($filtercid>0) {
		$query .= " AND imas_msgs.courseid=:courseid";
	}
	$query .= " ORDER BY imas_users.LastName, imas_users.FirstName";
	$stm = $DBH->prepare($query);
	if ($filtercid>0) {
		$stm->execute(array(':msgfrom'=>$userid, ':courseid'=>$filtercid));
	} else {
		$stm->execute(array(':msgfrom'=>$userid));
	}
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo "<option value=\"".Sanitize::onlyInt($row[0])."\" ";
		if ($filteruid==$row[0]) {
			echo 'selected=1';
		}
		printf(" >%s, %s</option>", Sanitize::encodeStringForDisplay($row[1]),
            Sanitize::encodeStringForDisplay($row[2]));
	}
	echo "</select></p>";
?>
	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
	With Selected: 	<input type=submit name="remove" value="Remove from Sent Message List">
	<?php
	if ($isteacher) {
		echo ' <input type=submit name="unsend" value="Unsend" onclick="return confirm(\'Are you sure? This will delete the question from the receiver\\\'s inbox and your send list.\');">';
	}
	?>

	<table class=gb>
	<thead>
	<tr><th></th><th>Message</th><th>To</th><th>Read</th><th>Sent</th></tr>
	</thead>
	<tbody>
<?php
	$offset = ($page-1)*$threadsperpage;
	//DB $query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.isread FROM imas_msgs,imas_users ";
	//DB $query .= "WHERE imas_users.id=imas_msgs.msgto AND imas_msgs.msgfrom='$userid' AND (imas_msgs.isread&4)=0 ";
	//DB if ($filtercid>0) {
	//DB 	$query .= "AND imas_msgs.courseid='$filtercid' ";
	//DB }
	//DB if ($filteruid>0) {
	//DB 	$query .= "AND imas_msgs.msgto='$filteruid' ";
	//DB }
	//DB $query .= " ORDER BY senddate DESC ";
	//DB $query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset";
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.isread FROM imas_msgs,imas_users ";
	$query .= "WHERE imas_users.id=imas_msgs.msgto AND imas_msgs.msgfrom=:msgfrom AND (imas_msgs.isread&4)=0 ";
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
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->prepare($query);
  $stm->execute($qarr);
	//DB if (mysql_num_rows($result)==0) {
	if ($stm->rowCount()==0) {
		echo "<tr><td></td><td>No messages</td><td></td><td></td></tr>";
	}
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
		printf("<td>%s, %s</td>", Sanitize::encodeStringForDisplay($line['LastName']),
            Sanitize::encodeStringForDisplay($line['FirstName']));
		if (($line['isread']&1)==1) {
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
