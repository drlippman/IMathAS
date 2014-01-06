<?php
	//Displays Message list
	//(c) 2006 David Lippman
	
	require("../validate.php");
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
	
	$threadsperpage = $listperpage;
	
	$cid = $_GET['cid'];
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = $_GET['page'];
	}
	if (isset($_GET['filtercid'])) {
		$filtercid = $_GET['filtercid'];
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
	if (isset($_POST['remove'])) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist) AND (isread&2)=2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=(isread|4) WHERE id IN ($checklist)";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	if (isset($_POST['unsend'])) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist)";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	if (isset($_GET['removeid'])) {
		$query = "DELETE FROM imas_msgs WHERE id='{$_GET['removeid']}' AND (isread&2)=2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=(isread|4) WHERE id='{$_GET['removeid']}'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	
	$pagetitle = "Messages";
	require("../header.php");
	
	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
		echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
	}
	echo " Sent Message List</div>";
	echo '<div id="headersentlist" class="pagetitle"><h2>Sent Messages</h2></div>';
	
	echo "<div class=\"cpmid\"><a href=\"msglist.php?cid=$cid\">Received Messages</a></div>";

	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom='$userid' AND (isread&4)=0";
	if ($filtercid>0) {
		$query .= " AND courseid='$filtercid'";
	}
	if ($filteruid>0) {
		$query .= " AND msgto='$filteruid'";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
	if ($numpages==0 && $filteruid>0) {
		//might have changed filtercid w/o changing user.
		//we'll open up to all users then
		$filteruid = 0;	
		$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom='$userid' AND (isread&4)=0";
		if ($filtercid>0) {
			$query .= " AND courseid='$filtercid'";
		}
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
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
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/sentlist.php?cid=$cid&filtercid=";
	
	
?>
<script type="text/javascript">
function chgfilter() {
	var filtercid = document.getElementById("filtercid").value;
	var filteruid = document.getElementById("filteruid").value;
	window.location = "<?php echo $address;?>"+filtercid+"&filteruid="+filteruid;
}
</script>	
	<form id="qform" method="post" action="sentlist.php?page=<?php echo $page;?>&cid=<?php echo $cid;?>">
	<p>Filter by course: <select id="filtercid" onchange="chgfilter()">
<?php
	echo "<option value=\"0\" ";
	if ($filtercid==0) {
		echo "selected=1 ";
	}
	echo ">All courses</option>";
	$query = "SELECT DISTINCT imas_courses.id,imas_courses.name FROM imas_courses,imas_msgs WHERE imas_courses.id=imas_msgs.courseid AND imas_msgs.msgfrom='$userid'";
	$query .= " ORDER BY imas_courses.name";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<option value=\"{$row[0]}\" ";
		if ($filtercid==$row[0]) {
			echo 'selected=1';
		}
		echo " >{$row[1]}</option>";
	}
	echo "</select> ";
	echo 'By recipient: <select id="filteruid" onchange="chgfilter()"><option value="0" ';
	if ($filteruid==0) {
		echo 'selected="selected" ';
	}
	echo '>All</option>';
	$query = "SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users ";
	$query .= "JOIN imas_msgs ON imas_msgs.msgto=imas_users.id WHERE imas_msgs.msgfrom='$userid'";
	if ($filtercid>0) {
		$query .= " AND imas_msgs.courseid='$filtercid'";
	}
	$query .= " ORDER BY imas_users.LastName, imas_users.FirstName";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<option value=\"{$row[0]}\" ";
		if ($filteruid==$row[0]) {
			echo 'selected=1';
		}
		echo " >{$row[1]}, {$row[2]}</option>";
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
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.isread FROM imas_msgs,imas_users ";
	$query .= "WHERE imas_users.id=imas_msgs.msgto AND imas_msgs.msgfrom='$userid' AND (imas_msgs.isread&4)=0 ";
	if ($filtercid>0) {
		$query .= "AND imas_msgs.courseid='$filtercid' ";
	}
	if ($filteruid>0) {
		$query .= "AND imas_msgs.msgto='$filteruid' ";
	} 
	$query .= " ORDER BY senddate DESC ";
	$offset = ($page-1)*$threadsperpage;
	$query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset"; 
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo "<tr><td></td><td>No messages</td><td></td><td></td></tr>";
	}
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
		echo "<tr><td><input type=checkbox name=\"checked[]\" value=\"{$line['id']}\"/></td><td>";
		echo "<a href=\"viewmsg.php?page$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&type=sent&msgid={$line['id']}\">";
		echo $line['title'];
		echo "</a></td>";
		echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
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
		
	
