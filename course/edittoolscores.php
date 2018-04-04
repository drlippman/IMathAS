<?php
//IMathAS:  Add/modify external tool scores
//(c) 2014 David Lippman

	require("../init.php");
	require("../includes/htmlutil.php");


	$istutor = false;
	$isteacher = false;
	if (isset($tutorid)) { $istutor = true;}
	if (isset($teacherid)) { $isteacher = true;}

	$lid = intval($_GET['lid']);

	//DB $query = "SELECT title,text,points FROM imas_linkedtext WHERE id='$lid' AND courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)==0) {
	$stm = $DBH->prepare("SELECT title,text,points FROM imas_linkedtext WHERE id=:id AND courseid=:courseid");
	$stm->execute(array(':id'=>$lid, ':courseid'=>$cid));
	if ($stm->rowCount()==0) {
		echo "invalid item";
		exit;
	}
	//DB list($name,$text,$points) = mysql_fetch_row($result);
	list($name,$text,$points) = $stm->fetch(PDO::FETCH_NUM);
	$toolparts = explode('~~',substr($text,8));
	if (isset($toolparts[3])) {
		$gbcat = $toolparts[3];
		$cntingb = $toolparts[4];
		$tutoredit = $toolparts[5];
	} else {
		echo "invalid paramters";
		exit;
	}

	if ($istutor) {
		$isok = ($tutoredit==1);
		if (!$isok) {
			require("../header.php");
			echo "You don't have authority for this action";
			require("../footer.php");
			exit;
		}
	} else if (!$isteacher) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}


	/*Not called from anywhere?
	if (isset($_REQUEST['clear']) && $isteacher) {
		if (isset($_POST['confirm'])) {
			//DB $query = "DELETE FROM imas_grades WHERE gradetype='exttool' AND gradetypeid='$lid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='exttool' AND gradetypeid=:gradetypeid");
			$stm->execute(array(':gradetypeid'=>$lid));
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=".Sanitize::courseId($_GET['cid']));
			exit;
		} else {
			require("../header.php");
			echo "<p>Are you SURE you want to clear all associated grades on this item from the gradebook?</p>";
			
			$querystring = http_build_query(array('stu'=>$_GET['stu'], 'cid'=>$cid, 'lid'=> $lid));
			echo '<form method="POST" action="edittoolscores.php?'.$querystring.'">';
			echo '<p><button type=submit name="confirm" value="true">'._('Clear Scores').'</button>';
			
			$querystring2 = http_build_query(array('stu'=>$_GET['stu'], 'cid'=>$cid));
			echo " <a href=\"gradebook.php?$querystring2\">Nevermind</a></p>";
			echo '</form>';
			
			require("../footer.php");
			exit;
		}
	}
	*/

	//check for grades marked as newscore that aren't really new
	//shouldn't happen, but could happen if two browser windows open
	if (isset($_POST['newscore'])) {
		$keys = array_keys($_POST['newscore']);
		foreach ($keys as $k=>$v) {
			if (trim($v)=='') {unset($keys[$k]);}
		}
		if (count($keys)>0) {
			$kl = implode(',', array_map('intval',$keys));
			//DB $query = "SELECT userid FROM imas_grades WHERE gradetype='exttool' AND gradetypeid='$lid' AND userid IN ($kl)";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT userid FROM imas_grades WHERE gradetype='exttool' AND gradetypeid=:gradetypeid AND userid IN ($kl)");
			$stm->execute(array(':gradetypeid'=>$lid));
			while($row = $stm->fetch(PDO::FETCH_NUM)) {
				$_POST['score'][$row[0]] = $_POST['newscore'][$row[0]];
				unset($_POST['newscore'][$row[0]]);
			}
		}
	}


	///regular submit
	if (isset($_POST['score'])) {
		foreach($_POST['score'] as $k=>$sc) {
			if (trim($k)=='') { continue;}
			$sc = trim($sc);
			if ($sc!='') {
				//DB $query = "UPDATE imas_grades SET score='$sc',feedback='{$_POST['feedback'][$k]}' WHERE userid='$k' AND gradetype='exttool' AND gradetypeid='$lid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_grades SET score=:score,feedback=:feedback WHERE userid=:userid AND gradetype='exttool' AND gradetypeid=:gradetypeid");
				$stm->execute(array(':score'=>$sc, ':feedback'=>$_POST['feedback'][$k], ':userid'=>$k, ':gradetypeid'=>$lid));
			} else {
				//DB $query = "UPDATE imas_grades SET score=NULL,feedback='{$_POST['feedback'][$k]}' WHERE userid='$k' AND gradetype='exttool' AND gradetypeid='$lid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_grades SET score=NULL,feedback=:feedback WHERE userid=:userid AND gradetype='exttool' AND gradetypeid=:gradetypeid");
				$stm->execute(array(':feedback'=>$_POST['feedback'][$k], ':userid'=>$k, ':gradetypeid'=>$lid));
				//$query = "DELETE FROM imas_grades WHERE gbitemid='$lid' AND userid='$k'";

			}
		}
	}

	if (isset($_POST['newscore'])) {
		foreach($_POST['newscore'] as $k=>$sc) {
			if (trim($k)=='') {continue;}
			if ($sc!='') {
				//DB $query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
				//DB $query .= "('exttool','$lid','$k','$sc','{$_POST['feedback'][$k]}')";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
				$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':gradetype'=>'exttool', ':gradetypeid'=>$lid, ':userid'=>$k, ':score'=>$sc, ':feedback'=>$_POST['feedback'][$k]));
			} else if (trim($_POST['feedback'][$k])!='') {
				//DB $query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
				//DB $query .= "('exttool','$lid','$k',NULL,'{$_POST['feedback'][$k]}')";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
				$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':gradetype'=>'exttool', ':gradetypeid'=>$lid, ':userid'=>$k, ':score'=>NULL, ':feedback'=>$_POST['feedback'][$k]));
			}
		}
	}

	if (isset($_POST['score']) || isset($_POST['newscore']) || isset($_POST['name'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?stu=" . Sanitize::encodeUrlParam($_GET['stu']) . "&gbmode=" . Sanitize::encodeUrlParam($_GET['gbmode']) . "&cid=".Sanitize::courseId($_GET['cid']) . "&r=".Sanitize::randomQueryStringParam());
		exit;
	}

	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/addgrades.js?v=121213\"></script>";


	require("../header.php");
    printf('<div class=breadcrumb>%s <a href="course.php?cid=%s">%s</a> ', $breadcrumbbase,
        Sanitize::courseId($_GET['cid']), Sanitize::encodeStringForDisplay($coursename));
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	if ($_GET['stu']>0) {
		echo "&gt; <a href=\"gradebook.php?stu=" . Sanitize::onlyInt($_GET['stu']) . "&cid=$cid\">Student Detail</a> ";
	} else if ($_GET['stu']==-1) {
		echo "&gt; <a href=\"gradebook.php?stu=" . Sanitize::onlyInt($_GET['stu']) . "&cid=$cid\">Averages</a> ";
	}
	echo "&gt; External Tool Grades</div>";

	echo "<div id=\"headerexttoolgrades\" class=\"pagetitle\"><h2>Modify External Tool Grades</h2></div>";
	echo '<h3>' . Sanitize::encodeStringForDisplay($name) . '</h3>';

	echo "<form id=\"mainform\" method=post action=\"edittoolscores.php?stu=" . Sanitize::onlyInt($_GET['stu']) . "&gbmode=" . Sanitize::encodeUrlParam($_GET['gbmode']) . "&cid=$cid&lid=$lid&uid=" . Sanitize::encodeUrlParam($_GET['uid']) . "\">";


		//DB $query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
		//DB $query .= "AND imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_result($result,0,0)>0) {
		$query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
		$query .= "AND imas_students.courseid=:courseid AND imas_students.section IS NOT NULL";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid));
		if ($stm->fetchColumn(0)>0) {
			$hassection = true;
		} else {
			$hassection = false;
		}

		if ($hassection) {
			//DB $query = "SELECT usersort FROM imas_gbscheme WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_result($result,0,0)==0) {
			$stm = $DBH->prepare("SELECT usersort FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			if ($stm->fetchColumn(0)==0) {
				$sortorder = "sec";
			} else {
				$sortorder = "name";
			}
		} else {
			$sortorder = "name";
		}


		echo '<div id="gradeboxes">';
		echo '<input type=button value="Expand Feedback Boxes" onClick="togglefeedback(this)"/> ';
		if ($hassection) {
			echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		}
		if ($_GET['uid']=='all') {
			echo "<br/><span class=form>Add/Replace to all grades:</span><span class=formright><input type=text size=3 id=\"toallgrade\" onblur=\"this.value = doonblur(this.value);\"/>";
			echo ' <input type=button value="Add" onClick="sendtoall(0,0);"/> <input type=button value="Multiply" onclick="sendtoall(0,1)"/> <input type=button value="Replace" onclick="sendtoall(0,2)"/></span><br class="form"/>';
			echo "<span class=form>Add/Replace to all feedback:</span><span class=formright><input type=text size=40 id=\"toallfeedback\"/>";
			echo ' <input type=button value="Append" onClick="sendtoall(1,0);"/> <input type=button value="Prepend" onclick="sendtoall(1,1)"/> <input type=button value="Replace" onclick="sendtoall(1,2)"/></span><br class="form"/>';
		}
		echo '<div class="clear"></div>';
		echo "<table id=myTable><thead><tr><th>Name</th>";
		if ($hassection) {
			echo '<th>Section</th>';
		}
		echo "<th>Grade</th><th>Feedback</th></tr></thead><tbody>";


		if ($_GET['uid']!='all') {
			//DB $query = "SELECT userid,score,feedback FROM imas_grades WHERE gradetype='exttool' AND gradetypeid='$lid' ";
			//DB $query .= "AND userid='{$_GET['uid']}' ";
			$query = "SELECT userid,score,feedback FROM imas_grades WHERE gradetype='exttool' AND gradetypeid=:gradetypeid ";
			$query .= "AND userid=:userid ";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':gradetypeid'=>$lid, ':userid'=>$_GET['uid']));
		} else {
			//DB $query = "SELECT userid,score,feedback FROM imas_grades WHERE gradetype='exttool' AND gradetypeid='$lid' ";
			$stm = $DBH->prepare("SELECT userid,score,feedback FROM imas_grades WHERE gradetype='exttool' AND gradetypeid=:gradetypeid ");
			$stm->execute(array(':gradetypeid'=>$lid));
		}
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($row[1]!=null) {
				$score[$row[0]] = $row[1];
			} else {
				$score[$row[0]] = '';
			}
			$feedback[$row[0]] = $row[2];
		}

		//DB $query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked FROM imas_users,imas_students ";
		//DB if ($_GET['uid']!='all') {
			//DB $query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id='{$_GET['uid']}' AND imas_students.courseid='$cid'";
		//DB } else {
			//DB $query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid='$cid'";
		//DB }
		//DB if ($istutor && isset($tutorsection) && $tutorsection!='') {
			//DB $query .= " AND imas_students.section='$tutorsection' ";
		//DB }
		//DB if ($hassection && $sortorder=="sec") {
			 //DB $query .= " ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		//DB } else {
			 //DB $query .= " ORDER BY imas_users.LastName,imas_users.FirstName";
		//DB }
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked FROM imas_users,imas_students ";
		if ($_GET['uid']!='all') {
			$query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id=:id AND imas_students.courseid=:courseid";
			$qarr = array(':id'=>$_GET['uid'], ':courseid'=>$cid);
		} else {
			$query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid=:courseid";
			$qarr = array(':courseid'=>$cid);
		}
		if ($istutor && isset($tutorsection) && $tutorsection!='') {
			$query .= " AND imas_students.section=:section ";
			$qarr[':section'] = $tutorsection;
		}
		if ($hassection && $sortorder=="sec") {
			$query .= " ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			$query .= " ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);

		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($row[4]>0) {
				echo '<tr><td style="text-decoration: line-through;">';
			} else {
				echo '<tr><td>';
			}
			echo Sanitize::encodeStringForDisplay($row[1]) . ", " . Sanitize::encodeStringForDisplay($row[2]);
			echo '</td>';
			if ($hassection) {
				echo "<td>" . Sanitize::encodeStringForDisplay($row[3]) . "</td>";
			}
			if (isset($score[$row[0]])) {
				echo "<td><input type=\"text\" size=\"3\" autocomplete=\"off\" name=\"score[" . Sanitize::encodeStringForDisplay($row[0]) . "]\" id=\"score" . Sanitize::encodeStringForDisplay($row[0]) . "\" value=\"";
				echo Sanitize::encodeStringForDisplay($score[$row[0]]);
			} else {
				echo "<td><input type=\"text\" size=\"3\" autocomplete=\"off\" name=\"newscore[" . Sanitize::encodeStringForDisplay($row[0]) . "]\" id=\"score" . Sanitize::encodeStringForDisplay($row[0]) . "\" value=\"";
			}
			echo "\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" />";

			echo "</td>";
			echo "<td><textarea cols=60 rows=1 id=\"feedback" . Sanitize::encodeStringForDisplay($row[0]) . "\" name=\"feedback[" . Sanitize::encodeStringForDisplay($row[0]) . "]\">" . Sanitize::encodeStringForDisplay($feedback[$row[0]]) . "</textarea></td>";
			echo "</tr>";
		}

		echo "</tbody></table>";
		if ($hassection) {
			echo "<script> initSortTable('myTable',Array('S','S',false,false),false);</script>";
		}


?>
<div class=submit><input type=submit value="Submit"></div></div>
</form>

<?php
	require("../footer.php");
?>
