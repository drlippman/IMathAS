<?php
//IMathAS:  Lock students students; called from List Users or Gradebook
//  This file is always included from listusers.php or gradebook.php
//  The isset($teacherid) check blocks access if accessed directly
//(c) 2013 David Lippman


ini_set("max_execution_time", "600");

	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	$get_uid = Sanitize::simpleString($_GET['uid'] ?? 'selected');
	
	if (isset($_POST['dolockstu']) || isset($_POST['lockinstead'])) { //do lockout - postback
		if ($get_uid=="selected") {
			$tolock = explode(",",$_POST['tolock']);
		} else if ($get_uid=="all") {
			$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$tolock[] = $row[0];
			}
		} else {
			$tolock[] = $get_uid;
		}

		$locklist = implode(',', array_map('intval',$tolock));
		$now = time();
		$stm = $DBH->prepare("UPDATE imas_students SET locked=:locked WHERE courseid=:courseid AND userid IN ($locklist)");
		$stm->execute(array(':locked'=>$now, ':courseid'=>$cid));

		if ($calledfrom=='lu') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else if ($calledfrom == 'gb') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=$cid&gbmode=" . Sanitize::encodeUrlParam($_GET['gbmode'] ?? '') . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		}
	} else { //get confirm
		if ((isset($_POST['submit']) && $_POST['submit']=="Lock") || (isset($_POST['posted']) && $_POST['posted']=="Lock")) {
			$get_uid = 'selected';
		}

		if ($get_uid=="selected") {
			if (!empty($_POST['checked'])) {
				$ulist = implode(',', array_map('intval', $_POST['checked']));
				$resultUserList = $DBH->query("SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)");
				$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_students WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$cid));
			}
		} else {
			$stm = $DBH->prepare("SELECT FirstName,LastName,SID FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$get_uid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$lockConfirm =  "Are you SURE you want to lock <span class='pii-full-name'>{$row[0]} {$row[1]}</span> (<span class='pii-username'>$row[2]</span>) out of the course?";
		}

		/**** confirmation page body *****/
		require("../header.php");
		echo  "<div class=breadcrumb>$curBreadcrumb</div>";
		if ($calledfrom=='lu') {
			echo "<form method=post action=\"listusers.php?cid=$cid&action=lock&uid=" . Sanitize::simpleString($get_uid) . "&confirmed=true\">";
		} else if ($calledfrom=='gb') {
			echo "<form method=post action=\"gradebook.php?cid=$cid&action=lock&uid=" . Sanitize::simpleString($get_uid) . "&confirmed=true\">";
		}


		if ($get_uid=="selected") {
				if (empty($_POST['checked'])) {
					if ($calledfrom=='lu') {
						echo "No users selected.  <a href=\"listusers.php?cid=$cid\">Try again</a></form>";
					}

				} else {
?>
		Are you SURE you want to lock the selected students out of the course?
		<ul>
<?php
					while ($row = $resultUserList->fetch(PDO::FETCH_NUM)) {
						printf("			<li><span class='pii-full-name'>%s, %s</span> (<span class='pii-username'>%s</span>)</li>",
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
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gradebook.php?cid=$cid&gbmode=" . Sanitize::encodeUrlParam($_GET['gbmode'] ?? '') . "'\">";
			}
?>
		</p>
	</form>

<?php
	}
?>
