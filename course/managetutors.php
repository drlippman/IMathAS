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
			//DB $toremove = "'".implode("','",$_POST['remove'])."'";
			$toremove = implode(',', array_map('intval', $_POST['remove']));
			//DB $query = "DELETE FROM imas_tutors WHERE id IN ($toremove) AND courseid='$cid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_tutors WHERE id IN ($toremove) AND courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
		}
		//update sections
		if (count($_POST['section'])>0) {
			foreach ($_POST['section'] as $id=>$val) {
				//DB $query = "UPDATE imas_tutors SET section='$val' WHERE id='$id' AND courseid='$cid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_tutors SET section=:section WHERE id=:id AND courseid=:courseid");
				$stm->execute(array(':section'=>$val, ':id'=>$id, ':courseid'=>$cid));
			}
		}
		//add new tutors
		if (trim($_POST['newtutors'])!='') {
			//gotta check if they're already a tutor
			$existingsids = array();
			//DB $query = "SELECT u.SID FROM imas_tutors as tut JOIN imas_users as u ON tut.userid=u.id WHERE tut.courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT u.SID FROM imas_tutors as tut JOIN imas_users as u ON tut.userid=u.id WHERE tut.courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$existingsids[] = $row[0];
			}
			//also don't want students enrolled as tutors
			//DB $query = "SELECT u.SID FROM imas_students as stu JOIN imas_users as u ON stu.userid=u.id WHERE stu.courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT u.SID FROM imas_students as stu JOIN imas_users as u ON stu.userid=u.id WHERE stu.courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$existingsids[] = $row[0];
			}
			$sids = explode(',',$_POST['newtutors']);
			for ($i=0;$i<count($sids);$i++) {
				$sids[$i] = trim($sids[$i]);
			}
			$sidstouse = array_diff($sids,$existingsids);
			if (count($sidstouse)>0) {
				//check if SID exists
				//DB $tutsids = "'".implode("','",$sidstouse)."'";
				//DB $query = "SELECT id,SID FROM imas_users WHERE SID in ($tutsids)";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
					//DB while ($row = mysql_fetch_row($result)) {
				$query_placeholders = Sanitize::generateQueryPlaceholders($sidstouse);
				$stm = $DBH->prepare("SELECT id,SID FROM imas_users WHERE SID IN ($query_placeholders)");
				$stm->execute($sidstouse);
				$insvals = array();
				if ($stm->rowCount()>0) {
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						//DB $inspt[] = "('{$row[0]}','$cid','')";
						$inspt[] = "(?,?,'')";
						array_push($insvals, $row[0], $cid);
						$foundsid[] = $row[1];
					}
					$ins = implode(',',$inspt);
					//insert them
					//DB $query = "INSERT INTO imas_tutors (userid,courseid,section) VALUES $ins";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_tutors (userid,courseid,section) VALUES $ins");
					$stm->execute($insvals);
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
	//DB $query = "SELECT sel2name,sel2list FROM imas_diags WHERE cid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
	$stm = $DBH->prepare("SELECT sel2name,sel2list FROM imas_diags WHERE cid=:cid");
	$stm->execute(array(':cid'=>$cid));
	if ($stm->rowCount()>0) {
		$isdiag = true;
		//DB $limitname = mysql_result($result,0,0);
		//DB $sel2list = mysql_result($result,0,1);
		list($limitname,$sel2list) = $stm->fetch(PDO::FETCH_NUM);
		$sel2list = str_replace('~',';',$sel2list);
		$sections = array_unique(explode(';',$sel2list));
	}

	//if not diagnostic, we'll work off the sections
	if (!$isdiag) {
		//DB $query = "SELECT DISTINCT section FROM imas_students WHERE courseid='$cid' AND section IS NOT NULL";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE courseid=:courseid AND section IS NOT NULL");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$sections[] = $row[0];
		}
		$limitname = "Section";

	}
	sort($sections);

	//get tutorlist
	$tutorlist = array();
	$i = 0;
	//DB $query = "SELECT tut.id,u.LastName,u.FirstName,tut.section FROM imas_tutors as tut JOIN imas_users as u ON tut.userid=u.id WHERE tut.courseid='$cid' ORDER BY u.LastName,u.FirstName";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT tut.id,u.LastName,u.FirstName,tut.section FROM imas_tutors as tut JOIN imas_users as u ON tut.userid=u.id WHERE tut.courseid=:courseid ORDER BY u.LastName,u.FirstName");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$tutorlist[$i]['id'] = $row[0];
		$tutorlist[$i]['name'] = $row[1].', '.$row[2];
		$tutorlist[$i]['section'] = $row[3];
		$i++;
	}

	//*** DISPLAY ***
	$pagetitle = "Manage Tutors";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
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
