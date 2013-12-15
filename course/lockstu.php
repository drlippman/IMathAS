<?php
//IMathAS:  Lock students students; called from List Users or Gradebook
//(c) 2013 David Lippman
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
			$tolock = explode(",",$_POST['tolock']);
		} else if ($_GET['uid']=="all") {
			$query = "SELECT userid FROM imas_students WHERE courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$tolock[] = $row[0];
			}
		} else {
			$tolock[] = $_GET['uid'];
		}
		$locklist = implode("','",$tolock);
		$now = time();
		$query = "UPDATE imas_students SET locked='$now' WHERE courseid='$cid' AND userid IN ('$locklist')";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		if ($calledfrom=='lu') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else if ($calledfrom == 'gb') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}");
			exit;		
		}
	} else { //get confirm
		if ((isset($_POST['submit']) && $_POST['submit']=="Lock") || (isset($_POST['posted']) && $_POST['posted']=="Lock")) {
			$_GET['uid'] = 'selected';
		}
		
		if ($_GET['uid']=="selected") {
			if (count($_POST['checked'])>0) {
				$ulist = "'".implode("','",$_POST['checked'])."'";
				$query = "SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)";
				$resultUserList = mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
			}
		} else {
			$query = "SELECT FirstName,LastName,SID FROM imas_users WHERE id='{$_GET['uid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$lockConfirm =  "Are you SURE you want to lock {$row[0]} {$row[1]} ($row[2]) out of the course?";
		}
		
		/**** confirmation page body *****/
		require("../header.php");
		echo  "<div class=breadcrumb>$curBreadcrumb</div>";
		if ($calledfrom=='lu') {
			echo "<form method=post action=\"listusers.php?cid=$cid&action=lock&uid={$_GET['uid']}&confirmed=true\">";
		} else if ($calledfrom=='gb') {
			echo "<form method=post action=\"gradebook.php?cid=$cid&action=lock&uid={$_GET['uid']}&confirmed=true\">";
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
					while ($row = mysql_fetch_row($resultUserList)) {
						echo "			<li>{$row[0]}, {$row[1]} ({$row[2]})</li>";
					}
?>					
		</ul>
		<input type=hidden name="tolock" value="<?php echo implode(",",$_POST['checked']) ?>">
<?php
				}
			} else {
				echo $lockConfirm;
			}
?>

		<p>
			<input type=submit value="Yes, Lock Out Student">
<?php
			if ($calledfrom=='lu') {
				echo "<input type=button value=\"Nevermind\" onclick=\"window.location='listusers.php?cid=$cid'\">";
			} else if ($calledfrom=='gb') {
				echo "<input type=button value=\"Nevermind\" onclick=\"window.location='gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}'\">";
			}
?>
		</p>
	</form>

<?php		
	}		
?>
