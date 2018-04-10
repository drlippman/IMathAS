<?php
//IMathAS:  Lock students students; called from List Users or Gradebook
//(c) 2013 David Lippman
@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");

require_once(__DIR__ . "/../includes/sanitize.php");

	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	if (isset($_POST['dolockstu']) || isset($_POST['lockinstead'])) { //do lockout - postback
		if ($_GET['uid']=="selected") {
			$tolock = explode(",",$_POST['tolock']);
		} else if ($_GET['uid']=="all") {
			//DB $query = "SELECT userid FROM imas_students WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$tolock[] = $row[0];
			}
		} else {
			$tolock[] = $_GET['uid'];
		}

		$locklist = implode(',', array_map('intval',$tolock));
		$now = time();
		//DB $query = "UPDATE imas_students SET locked='$now' WHERE courseid='$cid' AND userid IN ($locklist)";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_students SET locked=:locked WHERE courseid=:courseid AND userid IN ($locklist)");
		$stm->execute(array(':locked'=>$now, ':courseid'=>$cid));

		if ($calledfrom=='lu') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else if ($calledfrom == 'gb') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=$cid&gbmode=" . Sanitize::encodeUrlParam($_GET['gbmode']) . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		}
	} else { //get confirm
		if ((isset($_POST['submit']) && $_POST['submit']=="Lock") || (isset($_POST['posted']) && $_POST['posted']=="Lock")) {
			$_GET['uid'] = 'selected';
		}

		if ($_GET['uid']=="selected") {
			if (count($_POST['checked'])>0) {
				//DB $ulist = "'".implode("','",$_POST['checked'])."'";
				$ulist = implode(',', array_map('intval', $_POST['checked']));
				//DB $query = "SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)";
				//DB $resultUserList = mysql_query($query) or die("Query failed : " . mysql_error());
				$resultUserList = $DBH->query("SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)");
				//DB $query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_students WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$_GET['cid']));
			}
		} else {
			//DB $query = "SELECT FirstName,LastName,SID FROM imas_users WHERE id='{$_GET['uid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT FirstName,LastName,SID FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['uid']));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$lockConfirm =  "Are you SURE you want to lock {$row[0]} {$row[1]} ($row[2]) out of the course?";
		}

		/**** confirmation page body *****/
		require("../header.php");
		echo  "<div class=breadcrumb>$curBreadcrumb</div>";
		if ($calledfrom=='lu') {
			echo "<form method=post action=\"listusers.php?cid=$cid&action=lock&uid=" . Sanitize::simpleString($_GET['uid']) . "&confirmed=true\">";
		} else if ($calledfrom=='gb') {
			echo "<form method=post action=\"gradebook.php?cid=$cid&action=lock&uid=" . Sanitize::simpleString($_GET['uid']) . "&confirmed=true\">";
		}


		if ($_GET['uid']=="selected") {
				if (count($_POST['checked'])==0) {
					if ($calledfrom=='lu') {
						echo "No users selected.  <a href=\"listusers.php?cid=$cid\">Try again</a></form>";
					}

				} else {
?>
		Are you SURE you want to lock the selected students out of the course?
		<ul>
<?php
					//DB while ($row = mysql_fetch_row($resultUserList)) {
					while ($row = $resultUserList->fetch(PDO::FETCH_NUM)) {
						printf("			<li>%s, %s (%s)</li>",
                            Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]),
                            Sanitize::encodeStringForDisplay($row[2]));
					}
?>
		</ul>
		<input type=hidden name="tolock" value="<?php echo Sanitize::encodeStringForDisplay(implode(",",$_POST['checked'])); ?>">
<?php
				}
			} else {
				echo Sanitize::encodeStringForDisplay($lockConfirm);
			}
?>

		<p>
			<input type=submit name="dolockstu" value="Yes, Lock Out Student">
<?php
			if ($calledfrom=='lu') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='listusers.php?cid=$cid'\">";
			} else if ($calledfrom=='gb') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gradebook.php?cid=$cid&gbmode=" . Sanitize::encodeUrlParam($_GET['gbmode']) . "'\">";
			}
?>
		</p>
	</form>

<?php
	}
?>
