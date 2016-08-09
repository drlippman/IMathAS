<?php
//IMathAS:  Unenroll students; called from List Users or Gradebook
//(c) 2007 David Lippman
@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");

	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	if (isset($_GET['confirmed'])) { //do unenroll
		if ($_GET['uid']=="selected") {
			$tounenroll = explode(",",$_POST['tounenroll']);
		} else if ($_GET['uid']=="all") {
			//DB $query = "SELECT userid FROM imas_students WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$tounenroll[] = $row[0];
			}
		} else {
			$tounenroll[] = $_GET['uid'];
		}

		if (!isset($_POST['delwikirev'])) {
			$delwikirev = intval($_POST['delwikirev']);
		} else {
			$delwikirev = 0;
		}
		require_once("../includes/unenroll.php");
		if (isset($_POST['removewithdrawn'])) {
			$withwithdraw = 'remove';
		} else if ($_GET['uid']=="all") {
			$withwithdraw = 'unwithdraw';
		} else {
			$withwithdraw = false;
		}
		//DB mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
		$DBH->beginTransaction();
		unenrollstu($cid,$tounenroll,($_GET['uid']=="all" || isset($_POST['delforumposts'])),($_GET['uid']=="all" && isset($_POST['removeoffline'])),$withwithdraw,$delwikirev, isset($_POST['usereplaceby']));
		//DB mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
		$DBH->commit();


		if ($calledfrom=='lu') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else if ($calledfrom == 'gb') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}");
			exit;
		}
	} else { //get confirm
		if ((isset($_POST['submit']) && $_POST['submit']=="Unenroll") || (isset($_POST['posted']) && $_POST['posted']=="Unenroll")) {
			/*if (isset($_POST['ca']) && $secfilter==-1) {
				//if "check all" and not section limited, mark as all to deliver "all students" message
				$_GET['uid'] = "all";
			} else {
				$_GET['uid'] = "selected";
			}
			if ($_GET['uid']=="all") {
				//not quite sure why we're doing this check... makes sure all students were actually selected..
				//if not, convert to selected type
				$query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (count($_POST['checked']) < mysql_result($result,0,0)) {
					$_GET['uid'] = 'selected';
				}
			}*/
			//DB $query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (count($_POST['checked']) == mysql_result($result,0,0)) {
			$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_students WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$_GET['cid']));
			if (count($_POST['checked']) == $stm->fetchColumn(0)) {
				$_GET['uid'] = 'all';
			} else {
				$_GET['uid'] = 'selected';
			}
		}

		if ($_GET['uid']=="all") {
			//DB $query = "SELECT iu.LastName,iu.FirstName,iu.SID FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$cid'";
			//DB $resultUserList = mysql_query($query) or die("Query failed : " . mysql_error());
			$resultUserList = $DBH->prepare("SELECT iu.LastName,iu.FirstName,iu.SID FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid=:courseid");
			$resultUserList->execute(array(':courseid'=>$cid));
		} else if ($_GET['uid']=="selected") {
			if (count($_POST['checked'])>0) {
				//DB $ulist = "'".implode("','",$_POST['checked'])."'";
				$ulist = implode(',', array_map('intval', $_POST['checked']));
				//DB $query = "SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)";
				//DB $resultUserList = mysql_query($query) or die("Query failed : " . mysql_error());
				$resultUserList = $DBH->query("SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)");
				//DB $query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (count($_POST['checked']) > floor(mysql_result($result,0,0)/2)) {
				$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_students WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$_GET['cid']));
				if (count($_POST['checked']) > floor($stm->fetchColumn(0)/2)) {
					$delForumMsg = "<p>Also delete <b style=\"color:red;\">ALL</b> forum posts by ALL students (not just the selected ones)? <input type=checkbox name=\"delforumposts\"/></p>";
					$delWikiMsg = "<p>Also delete <b style=\"color:red;\">ALL</b> wiki revisions: ";
					$delWikiMsg .= '<input type="radio" name="delwikirev" value="0" checked="checked" />No,  ';
					$delWikiMsg .= '<input type="radio" name="delwikirev" value="1" />Yes, from all wikis, ';
					$delWikiMsg .= '<input type="radio" name="delwikirev" value="2" />Yes, from group wikis only</p>';
				} else {
					$delForumMsg = "";
					$delWikiMsg = '';
				}
			}
		} else {
			//DB $query = "SELECT FirstName,LastName,SID FROM imas_users WHERE id='{$_GET['uid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT FirstName,LastName,SID FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['uid']));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$unenrollConfirm =  "Are you SURE you want to unenroll {$row[0]} {$row[1]} ($row[2])?";
		}

		/**** confirmation page body *****/
		require("../header.php");
		echo  "<div class=breadcrumb>$curBreadcrumb</div>";
		if ($calledfrom=='lu') {
			echo "<form method=post action=\"listusers.php?cid=$cid&action={$_GET['action']}&uid={$_GET['uid']}&confirmed=true\">";
		} else if ($calledfrom=='gb') {
			echo "<form method=post action=\"gradebook.php?cid=$cid&action=unenroll&uid={$_GET['uid']}&confirmed=true\">";
		}


			if ($_GET['uid']=="all") {
?>
			<p><b style="color:red">Warning!</b>: This will delete ALL course data about these students.  This action <b>cannot be undone</b>.
			If you have a student who isn't attending but may return, use the Lock Out of course option instead of unenrolling them.</p>
			<p>Are you SURE you want to unenroll ALL students?</p>
			<ul>
<?php
					//DB while ($row = mysql_fetch_row($resultUserList)) {
					while ($row = $resultUserList->fetch(PDO::FETCH_NUM)) {
						echo "			<li>{$row[0]}, {$row[1]} ({$row[2]})</li>";
					}
?>
		</ul>
			<p>This will also clear all regular posts from all class forums</p>
			<p><input type=checkbox name="removeoffline" value="1" /> Also remove all offline grade items from gradebook?

			</p>
			<p><input type=checkbox name="removewithdrawn" value="1" checked="checked"/> Also remove any withdrawn questions?

			</p>
			<p><input type=checkbox name="usereplaceby" value="1" checked="checked"/> Also use any suggested replacements for old questions?

			</p>
			<p>Also remove wiki revisions: <input type="radio" name="delwikirev" value="1" />All wikis,
				<input  type="radio" name="delwikirev" value="2" checked="checked" />Group wikis only
			</p>

<?php
			//<p>Also remove any withdrawn questions? <input type="checkbox" name="removewithdrawn" value="1" checked="checked"/></p>
			//<p>Use any suggested replacements for old questions? <input type="checkbox" name="usereplaceby" value="1" checked="checked"/></p>

			//<p>Also remove any withdrawn questions from assessments?
			//	<input type=checkbox name="removewithdrawn" value="1" />
			//</p>
			} else if ($_GET['uid']=="selected") {
				if (count($_POST['checked'])==0) {
					if ($calledfrom=='lu') {
						echo "No users selected.  <a href=\"listusers.php?cid=$cid\">Try again</a></form>";
					}

				} else {
?>
		<p><b style="color:red">Warning!</b>: This will delete ALL course data about these students.  This action <b>cannot be undone</b>.
		If you have a student who isn't attending but may return, use the Lock Out of course option instead of unenrolling them.</p>
		<p>Are you SURE you want to unenroll the selected students?</p>
		<ul>
<?php
					//DB while ($row = mysql_fetch_row($resultUserList)) {
					while ($row = $resultUserList->fetch(PDO::FETCH_NUM)) {
						echo "			<li>{$row[0]}, {$row[1]} ({$row[2]})</li>";
					}
?>
		</ul>
		<?php echo $delForumMsg; ?>
		<?php echo $delWikiMsg; ?>
		<input type=hidden name="tounenroll" value="<?php echo implode(",",$_POST['checked']) ?>">
<?php
				}
			} else {
				echo $unenrollConfirm;
			}
?>

		<p>
			<input type=submit class="secondarybtn" value="Unenroll">
			<input type=submit name="lockinstead" value="Lock Students Out Instead">
<?php
			if ($calledfrom=='lu') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='listusers.php?cid=$cid'\">";
			} else if ($calledfrom=='gb') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}'\">";
			}
?>
		</p>
	</form>

<?php
	}
?>
