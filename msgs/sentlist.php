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
	
	$threadsperpage = 20;
	
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

	*/
	if (isset($_POST['remove'])) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist) AND isread>1";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+4 WHERE id IN ($checklist) AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	if (isset($_GET['removeid'])) {
		$query = "DELETE FROM imas_msgs WHERE id='{$_GET['removeid']}' AND isread>1";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+4 WHERE id='{$_GET['removeid']}' AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	
	$pagetitle = "Messages";
	require("../header.php");
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> ";
	if ($cid>0) {
		echo "&gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
	}
	echo "&gt; Sent Message List</div>";
	echo "<h3>Sent Messages</h3>";		
	
	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom='$userid' AND isread<4";
	if ($filtercid>0) {
		$query .= " AND courseid='$filtercid'";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
	
	if ($numpages > 1) {
		echo "<div style=\"padding: 5px;\">Page: ";
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
			echo "<a href=\"sentlist.php?page=1&cid=$cid\">1</a> ";
		}
		if ($min!=2) { echo " ... ";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				echo "<b>$i</b> ";
			} else {
				echo "<a href=\"sentlist.php?page=$i&cid=$cid\">$i</a> ";
			}
		}
		if ($max!=$numpages-1) { echo " ... ";}
		if ($page == $numpages) {
			echo "<b>$numpages</b> ";
		} else {
			echo "<a href=\"sentlist.php?page=$numpages&cid=$cid\">$numpages</a> ";
		}
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		if ($page>1) {
			echo "<a href=\"sentlist.php?page=".($page-1)."&cid=$cid\">Previous</a> ";
		}
		if ($page < $numpages) {
			echo "<a href=\"sentlist.php?page=".($page+1)."&cid=$cid\">Next</a> ";
		}
		echo "</div>\n";
	}
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/sentlist.php?cid=$cid&filtercid=";
	
	
?>
<script type="text/javascript">
function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}
function chgfilter() {
	var filtercid = document.getElementById("filtercid").value;
	window.location = "<?php echo $address;?>"+filtercid;
}
</script>	
	<form method=post action="sentlist.php?page=<?php echo $page;?>&cid=<?php echo $cid;?>">
	<p>Filter by course: <select id="filtercid" onchange="chgfilter()">
<?php
	echo "<option value=\"0\" ";
	if ($filtercid==0) {
		echo "selected=1 ";
	}
	echo ">All courses</option>";
	$query = "SELECT DISTINCT imas_courses.id,imas_courses.name FROM imas_courses,imas_msgs WHERE imas_courses.id=imas_msgs.courseid AND imas_msgs.msgto='$userid'";
	$query .= " ORDER BY imas_courses.name";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<option value=\"{$row[0]}\" ";
		if ($filtercid==$row[0]) {
			echo 'selected=1';
		}
		echo " >{$row[1]}</option>";
	}
	echo "</select></p>";
?>
	Check/Uncheck All: <input type="checkbox" name="ca2" value="1" onClick="chkAll(this.form, 'checked[]', this.checked)">	
	With Selected: 	<input type=submit name="remove" value="Remove from Sent Message List">
			
	<table class=gb>
	<thead>
	<tr><th></th><th>Message</th><th>To</th><th>Read</th><th>Sent</th></tr>
	</thead>
	<tbody>
<?php
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.isread FROM imas_msgs,imas_users ";
	$query .= "WHERE imas_users.id=imas_msgs.msgto AND imas_msgs.msgfrom='$userid' AND imas_msgs.isread<4 ";
	if ($filtercid>0) {
		$query .= "AND imas_msgs.courseid='$filtercid' ";
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
		echo "<tr><td><input type=checkbox name=\"checked[]\" value=\"{$line['id']}\"/></td><td>";
		echo "<a href=\"viewmsg.php?page$page&cid=$cid&filtercid=$filtercid&type=sent&msgid={$line['id']}\">";
		echo $line['title'];
		echo "</a></td>";
		echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
		if ($line['isread']==1 || $line['isread']==3) {
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
	echo "<p><a href=\"msglist.php?cid=$cid\">Back to Messages</a></p>";
	
	require("../footer.php");
?>
		
	
