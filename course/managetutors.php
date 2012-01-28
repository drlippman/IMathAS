<?php 
//IMathAS:  Add/remove class tutors
//(c) 2009 David Lippman
	require("../validate.php");
	require("../includes/htmlutil.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	if (isset($CFG['GEN']['allowinstraddtutors']) &&  $CFG['GEN']['allowinstraddtutors']==false) {
		echo "Adding tutors is not allowed";
		exit;
	}
	$cid = $_GET['cid'];
	
	//*** PROCESSING ***
	$err = '';
	if (isset($_POST['submit'])) {
		//remove any selected tutors
		if (count($_POST['remove'])>0) {
			$toremove = "'".implode("','",$_POST['remove'])."'";
			$query = "DELETE FROM imas_tutors WHERE id IN ($toremove) AND courseid='$cid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		//update sections
		if (count($_POST['section'])>0) {
			foreach ($_POST['section'] as $id=>$val) {
				$query = "UPDATE imas_tutors SET section='$val' WHERE id='$id' AND courseid='$cid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		//add new tutors
		if (trim($_POST['newtutors'])!='') {
			//gotta check if they're already a tutor
			$existingsids = array();
			$query = "SELECT u.SID FROM imas_tutors as tut JOIN imas_users as u ON tut.userid=u.id WHERE tut.courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$existingsids[] = $row[0];
			}
			//also don't want students enrolled as tutors
			$query = "SELECT u.SID FROM imas_students as stu JOIN imas_users as u ON stu.userid=u.id WHERE stu.courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$existingsids[] = $row[0];
			}
			$sids = explode(',',$_POST['newtutors']);
			for ($i=0;$i<count($sids);$i++) {
				$sids[$i] = trim($sids[$i]);
			}
			$sidstouse = array_diff($sids,$existingsids);
			if (count($sidstouse)>0) {
				$tutsids = "'".implode("','",$sidstouse)."'";
				//check if SID exists
				$query = "SELECT id,SID FROM imas_users WHERE SID in ($tutsids)";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					while ($row = mysql_fetch_row($result)) {
						$inspt[] = "('{$row[0]}','$cid','')";
						$foundsid[] = $row[1];
					}
					$ins = implode(',',$inspt);
					//insert them
					$query = "INSERT INTO imas_tutors (userid,courseid,section) VALUES $ins";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$notfound = array_diff($sids,$foundsid);
					if (count($notfound)>0) {
						$err .= "<p>Some usernames not found:<br/>";
						foreach ($notfound as $nf) {
							$err .= "$nf<br/>";
						}
						$err .= '</p>';
					}
				} else {
					$err .= "<p>No usernames provided were found</p>";
				}
			}
		} else {
			//if not adding new, redirect back to listusers
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		}
	}
	
	//*** PRE-DISPLAY 
	$isdiag = false;
	$sections = array();
	//if diagnostic, then we'll use level-2 selectors in place of sections.  level-2 selector is recorded into the
	//imas_students.section field, so filter will act the same.
	$query = "SELECT sel2name,sel2list FROM imas_diags WHERE cid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$isdiag = true;
		$limitname = mysql_result($result,0,0);
		$sel2list = mysql_result($result,0,1);
		$sel2list = str_replace('~',';',$sel2list);
		$sections = array_unique(explode(';',$sel2list));
	}
	
	//if not diagnostic, we'll work off the sections
	if (!$isdiag) {
		$query = "SELECT DISTINCT section FROM imas_students WHERE courseid='$cid' AND section IS NOT NULL";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$sections[] = $row[0];
		}
		$limitname = "Section";
		
	}
	sort($sections);
	
	//get tutorlist
	$tutorlist = array();
	$i = 0;
	$query = "SELECT tut.id,u.LastName,u.FirstName,tut.section FROM imas_tutors as tut JOIN imas_users as u ON tut.userid=u.id WHERE tut.courseid='$cid' ORDER BY u.LastName,u.FirstName";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$tutorlist[$i]['id'] = $row[0];
		$tutorlist[$i]['name'] = $row[1].', '.$row[2];
		$tutorlist[$i]['section'] = $row[3];
		$i++;
	}
	
	//*** DISPLAY ***
	$pagetitle = "Manage Tutors";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"listusers.php?cid=$cid\">List Users</a> &gt; Manage Tutors</div>";

?>

	<div id="headermanagetutors" class="pagetitle"><h2>Manage Tutors</h2></div>
<?php
	echo $err;
?>
	<form id="curform" method=post action="managetutors.php?cid=<?php echo $cid ?>">	
	
	<table class="gb">
	<thead>
		<tr>
			<th>Tutor name</th>
			<th>Limit to <?php echo $limitname; ?></th>
			
			<th>Remove? 
			Check: <a href="#" onclick="return chkAllNone('curform','remove[]',true)">All</a> <a href="#" onclick="return chkAllNone('curform','remove[]',false)">None</a></th>
			
		</tr>
	</thead>
	<tbody>
<?php
if (count($tutorlist)==0) {
	echo '<tr><td colspan="3">No tutors have been designated for this course. You can add tutors below</td></tr>';
}
foreach ($tutorlist as $tutor) {
	echo '<tr>';
	echo '<td>'.$tutor['name'].'</td>';
	echo '<td>';
	//section
	echo '<select name="section['.$tutor['id'].']">';
	echo '<option value="" '.getHtmlSelected($tutor['section'],"").'>All</option>';
	foreach ($sections as $sec) {
		echo '<option value="'.$sec.'" '.getHtmlSelected($tutor['section'],$sec).'>'.$sec.'</option>';
	}
	if (!in_array($tutor['section'],$sections) && $tutor['section']!='') {
		echo '<option value="invalid" selected="selected">Invalid - reselect</option>';
	}
	echo '</select>';
	echo '</td>';
	echo '<td>';
	echo '<input type="checkbox" name="remove[]" value="'.$tutor['id'].'" />';
	echo '</td>';
	echo '</tr>';
}

?>
	
	</tbody>
	</table>
	<hr/>
	<p>
	<b>Add new tutors.</b>  Provide a list of usernames below, separated by commas, to add as tutors.
	</p>
	<p>
	<textarea name="newtutors" rows="3" cols="60"></textarea>
	</p>
	<input type="submit" name="submit" value="Update" />
	</form>

<?php
	require("../footer.php");
?>
