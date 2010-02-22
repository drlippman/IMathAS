<?php
//IMathAS:  Unenroll students; called from List Users or Gradebook
//(c) 2007 David Lippman
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
			$query = "SELECT userid FROM imas_students WHERE courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$tounenroll[] = $row[0];
			}
		} else {
			$tounenroll[] = $_GET['uid'];
		}
		
		require_once("../includes/unenroll.php");
		unenrollstu($cid,$tounenroll,($_GET['uid']=="all" || isset($_POST['delforumposts'])),($_GET['uid']=="all" && isset($_POST['removeoffline'])),($_GET['uid']=="all"));
		
		
		if ($calledfrom=='lu') {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else if ($calledfrom == 'gb') {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}");
			exit;		
		}
	} else { //get confirm
		if (isset($_POST['submit']) && $_POST['submit']=="Unenroll") {
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
			$query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (count($_POST['checked']) == mysql_result($result,0,0)) {
				$_GET['uid'] = 'all';
			} else {
				$_GET['uid'] = 'selected';
			}
		}
		
		if ($_GET['uid']=="all") {
			$query = "SELECT iu.LastName,iu.FirstName,iu.SID FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$cid'";
			$resultUserList = mysql_query($query) or die("Query failed : " . mysql_error());
		} else if ($_GET['uid']=="selected") {

			$ulist = "'".implode("','",$_POST['checked'])."'";
			$query = "SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)";
			$resultUserList = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (count($_POST['checked']) > floor(mysql_result($result,0,0)/2)) {
				$delForumMsg = "<p>Also delete <b style=\"color:red;\">ALL</b> forum posts by ALL students (not just the selected ones)? <input type=checkbox name=\"delforumposts\"/></p>";
			} else {
				$delForumMsg = "";
			}
		} else {
			$query = "SELECT FirstName,LastName,SID FROM imas_users WHERE id='{$_GET['uid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
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
			<p>Are you SURE you want to unenroll ALL students?</p>
			<ul>
<?php
					while ($row = mysql_fetch_row($resultUserList)) {
						echo "			<li>{$row[0]}, {$row[1]} ({$row[2]})</li>";
					}
?>					
		</ul>
			<p>This will also clear all regular posts from all class forums</p>
			<p>Also remove all offline grade items from gradebook? 
				<input type=checkbox name="removeoffline" value="1" />
			</p>
<?php
			} else if ($_GET['uid']=="selected") {
				if (count($_POST['checked'])==0) {
					if ($calledfrom=='lu') {
						echo "No users selected.  <a href=\"listusers.php?cid=$cid\">Try again</a></form>";
					}

				} else {
?>				
		Are you SURE you want to unenroll the selected students?
		<ul>
<?php
					while ($row = mysql_fetch_row($resultUserList)) {
						echo "			<li>{$row[0]}, {$row[1]} ({$row[2]})</li>";
					}
?>					
		</ul>
		<?php echo $delForumMsg; ?>
		<input type=hidden name="tounenroll" value="<?php echo implode(",",$_POST['checked']) ?>">
<?php
				}
			} else {
				echo $unenrollConfirm;
			}
?>

		<p>
			<input type=submit value="Yes, I'm Sure">
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
