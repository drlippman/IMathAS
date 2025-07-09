<?php
//IMathAS:  Unenroll students; called from List Users or Gradebook
//Included from listusers or gradebook - not called directly
//(c) 2007 David Lippman


ini_set("max_execution_time", "600");

	if (!(isset($teacherid))) {
		require_once "../header.php";
		echo "You need to log in as a teacher to access this page";
		require_once "../footer.php";
		exit;
	}
	$get_uid = Sanitize::simpleString($_GET['uid'] ?? '');

	if (isset($_POST['dounenroll'])) { //do unenroll - postback
		if ($get_uid=="selected") {
			$tounenroll = explode(",",$_POST['tounenroll']);
		} else if ($get_uid=="all") {
			$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$tounenroll[] = $row[0];
			}
		} else {
			$tounenroll[] = $get_uid;
		}

		if (isset($_POST['delwikirev'])) {
			$delwikirev = intval($_POST['delwikirev']);
		} else {
			$delwikirev = 0;
		}
		require_once "../includes/unenroll.php";
		if (isset($_POST['removewithdrawn'])) {
			$withwithdraw = 'remove';
		} else if ($get_uid=="all") {
			$withwithdraw = 'unwithdraw';
		} else {
			$withwithdraw = false;
		}
		$DBH->beginTransaction();
		unenrollstu($cid,$tounenroll,($get_uid=="all" || isset($_POST['delforumposts'])),($get_uid=="all" && isset($_POST['removeoffline'])),$withwithdraw,$delwikirev, isset($_POST['usereplaceby']), isset($_POST['upgradeassessver']));
		$DBH->commit();

		if ($get_uid=="all") {
			$updcrs = $DBH->prepare("UPDATE imas_courses SET cleanupdate=0 WHERE id=?");
			$updcrs->execute(array($cid));
		}

		if ($calledfrom=='lu') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=".Sanitize::courseId($cid) . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else if ($calledfrom == 'gb') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=".Sanitize::courseId($cid)."&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'] ?? '')."&r=".Sanitize::randomQueryStringParam());
			exit;
		}
	} else { //get confirm
        if (empty($_POST['checked'])) {
            $_POST['checked'] = [];
        }
		if (is_numeric($get_uid)) {
			$_POST['checked'] = [$get_uid];
			$get_uid = "selected";
		}
		if ((isset($_POST['submit']) && $_POST['submit']=="Unenroll") || (isset($_POST['posted']) && $_POST['posted']=="Unenroll")) {
			/*if (isset($_POST['ca']) && $secfilter==-1) {
				//if "check all" and not section limited, mark as all to deliver "all students" message
				$get_uid = "all";
			} else {
				$get_uid = "selected";
			}
			if ($get_uid=="all") {
				//not quite sure why we're doing this check... makes sure all students were actually selected..
				//if not, convert to selected type
				$query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (count($_POST['checked']) < mysql_result($result,0,0)) {
					$get_uid = 'selected';
				}
			}*/
			$stm = $DBH->prepare("SELECT COUNT(imas_students.id) FROM imas_students,imas_users WHERE imas_students.userid=imas_users.id AND imas_students.courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
            $stuInClass = $stm->fetchColumn(0);

			if ($stuInClass == 0 || count($_POST['checked']) == $stuInClass)  {
				$get_uid = 'all';
			} else {
				$get_uid = 'selected';
			}
		}

		if ($get_uid=="all") {
			$resultUserList = $DBH->prepare("SELECT iu.LastName,iu.FirstName,iu.SID FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid=:courseid");
			$resultUserList->execute(array(':courseid'=>$cid));
		} else if ($get_uid=="selected") {
			if (!empty($_POST['checked'])) {
				$ulist = implode(',', array_map('intval', $_POST['checked']));
				$resultUserList = $DBH->query("SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)");
				$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_students WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$cid));
				if (count($_POST['checked']) > floor($stm->fetchColumn(0)/2)) {
					$delForumMsg = "<p><label><input type=checkbox name=\"delforumposts\"/> Also delete <b class=noticetext>ALL</b> forum posts by ALL students (not just the selected ones)?</label></p>";
					$delWikiMsg = '<p><label for="delwikirev">Also remove <b class=noticetext>ALL</b> wiki revisions:</label> 
						<select name=delwikirev id=delwikirev>
							<option value=0 selected>None</option>
							<option value=1>All wikis</option>
							<option value=2">Group wikis only</option>
						</select></p>';
				} else {
					$delForumMsg = "";
					$delWikiMsg = '';
				}
			}
		}

		/**** confirmation page body *****/
		$pagetitle = _('Unenroll Confirmation');
		require_once "../header.php";
		echo  "<div class=breadcrumb>$curBreadcrumb</div>";
		echo '<h1>'.$pagetitle.'</h1>';
		if ($calledfrom=='lu') {
			echo "<form method=post action=\"listusers.php?cid=".Sanitize::courseId($cid)."&action=".Sanitize::encodeUrlParam($_GET['action'])."&uid=".Sanitize::encodeUrlParam($get_uid)."\">";
		} else if ($calledfrom=='gb') {
			echo "<form method=post action=\"gradebook.php?cid=".Sanitize::courseId($cid)."&action=unenroll&uid=".Sanitize::encodeUrlParam($get_uid)."\">";
		}

		echo '<p><b class=noticetext>Warning!</b>: This will delete ALL course data about these students.  This action <b>cannot be undone</b>.
		If you have a student who isn\'t attending but may return, use the Lock Out of course option instead of unenrolling them.</p>';

			if ($get_uid=="all") {
?>
			<p>Are you SURE you want to unenroll ALL students?</p>
			<ul>
<?php
					while ($row = $resultUserList->fetch(PDO::FETCH_NUM)) {
						printf("			<li>%s %s (%s)</li>",
                            Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]),
                            Sanitize::encodeStringForDisplay($row[2]));
					}
?>
		</ul>
			<p>This will also clear all regular posts from all class forums</p>
			<p><label><input type=checkbox name="removeoffline" value="1" /> Also remove all offline grade items from gradebook?</label>

			</p>
			<p><label><input type=checkbox name="removewithdrawn" value="1" checked="checked"/> Also remove any withdrawn questions?</label>

			</p>
			<p><label><input type=checkbox name="usereplaceby" value="1" checked="checked"/> Also use any suggested replacements for old questions?</label>

			</p>
			<p><label for="delwikirev">Also remove wiki revisions:</label> 
				<select name=delwikirev id=delwikirev>
					<option value=0 selected>None</option>
					<option value=1>All wikis</option>
					<option value=2">Group wikis only</option>
				</select>
			</p>
			<?php if ($courseUIver == 1) { ?>
			<p><label><input type=checkbox name="upgradeassessver" value="1" checked />
				Upgrade course to use new assessment interface.</label>
			</p>
			<?php } ?>

<?php
			//<p>Also remove any withdrawn questions? <input type="checkbox" name="removewithdrawn" value="1" checked="checked"/></p>
			//<p>Use any suggested replacements for old questions? <input type="checkbox" name="usereplaceby" value="1" checked="checked"/></p>

			//<p>Also remove any withdrawn questions from assessments?
			//	<input type=checkbox name="removewithdrawn" value="1" />
			//</p>
			} else if ($get_uid=="selected") {
				if (empty($_POST['checked'])) {
					if ($calledfrom=='lu') {
						echo "No users selected.  <a href=\"listusers.php?cid=".Sanitize::courseId($cid)."\">Try again</a></form>";
					}

				} else {
?>
		<p>Are you SURE you want to unenroll the selected students?</p>
		<ul>
<?php
					while ($row = $resultUserList->fetch(PDO::FETCH_NUM)) {
						printf("			<li><span class='pii-full-name'>%s %s</span> (<span class='pii-username'>%s</span>)</li>",
							Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]),
							Sanitize::encodeStringForDisplay($row[2]));
					}
?>
		</ul>
		<?php echo $delForumMsg; ?>
		<?php echo $delWikiMsg; ?>
		<input type=hidden name="tounenroll" value="<?php echo Sanitize::encodeStringForDisplay(implode(",",$_POST['checked'])); ?>">
<?php
				}
			} 
?>

		<p>
			<input type=submit name="dounenroll" class="secondarybtn" value="Unenroll">
			<input type=submit name="lockinstead" value="Lock Students Out Instead">
<?php
			if ($calledfrom=='lu') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='listusers.php?cid=".Sanitize::courseId($cid)."'\">";
			} else if ($calledfrom=='gb') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gradebook.php?cid=".Sanitize::courseId($cid)."&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'] ?? '')."'\">";
			}
?>
		</p>
	</form>

<?php
	}
?>
