<?php
//IMathAS:  Add/remove class tutors
//(c) 2009 David Lippman
	require_once "../init.php";
	require_once "../includes/htmlutil.php";
    require_once '../includes/TeacherAuditLog.php';

	if (!(isset($teacherid))) {
		require_once "../header.php";
		echo "You need to log in as a teacher to access this page";
		require_once "../footer.php";
		exit;
	}
	if (isset($CFG['GEN']['allowinstraddtutors']) &&  $CFG['GEN']['allowinstraddtutors']==false) {
		echo "Adding tutors is not allowed";
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);

	//*** PROCESSING ***
	$err = '';
	if (isset($_POST['submit'])) {
		//remove any selected tutors
		if (!empty($_POST['remove'])) {
			$toremove = implode(',', array_map('intval', $_POST['remove']));
			$stm = $DBH->prepare("DELETE FROM imas_tutors WHERE id IN ($toremove) AND courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
            TeacherAuditLog::addTracking(
				$cid,
				"Roster Action",
				null,
				array(
					'action' => 'Remove Tutors',
					'ids' => array_map('intval', $_POST['remove'])
				)
			);
		}
		//update sections
		if (isset($_POST['section']) && count($_POST['section'])>0) {
			foreach ($_POST['section'] as $id=>$val) {
				$stm = $DBH->prepare("UPDATE imas_tutors SET section=:section WHERE id=:id AND courseid=:courseid");
				$stm->execute(array(':section'=>$val, ':id'=>$id, ':courseid'=>$cid));
			}
		}
		//add new tutors
		if (isset($_POST['promotetotutor'])) {
			require_once "../includes/unenroll.php";
			$ph = Sanitize::generateQueryPlaceholders($_POST['promotetotutor']);
			$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=? AND userid IN ($ph)");
			$stm->execute(array_merge(array($cid), $_POST['promotetotutor']));
			$topromote = [];
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$topromote[] = $row[0];
			}
			if (count($topromote) > 0) {
				// unenroll
				unenrollstu($cid, $topromote);

				// add as tutor
				$insvals = [];
				foreach ($topromote as $uid) {
					$inspt[] = "(?,?,?)";
					array_push($insvals, $uid, $cid, $_POST['section'][$uid] ?? '');
				}
				$ins = implode(',',$inspt);
				//insert them
				$stm = $DBH->prepare("INSERT INTO imas_tutors (userid,courseid,section) VALUES $ins");
				$stm->execute($insvals);
				TeacherAuditLog::addTracking(
					$cid,
					"Roster Action",
					null,
					array(
						'action' => 'Add Tutors',
						'IDs' => $topromote
					)
				);
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managetutors.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else {
			//if not adding new, redirect back to listusers
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		}
	}

	//*** PRE-DISPLAY
	$isdiag = false;
	$sections = array();
	//if diagnostic, then we'll use level-2 selectors in place of sections.  level-2 selector is recorded into the
	//imas_students.section field, so filter will act the same.
	$stm = $DBH->prepare("SELECT sel2name,sel2list FROM imas_diags WHERE cid=:cid");
	$stm->execute(array(':cid'=>$cid));
	if ($stm->rowCount()>0) {
		$isdiag = true;
		list($limitname,$sel2list) = $stm->fetch(PDO::FETCH_NUM);
		$sel2list = str_replace('~',';',$sel2list);
		$sections = array_unique(explode(';',$sel2list));
	}

	//if not diagnostic, we'll work off the sections
	if (!$isdiag) {
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
	require_once "../header.php";
	$cid = Sanitize::courseId($_GET['cid']);
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
	echo "<a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Manage Tutors</div>";

?>

	<div id="headermanagetutors" class="pagetitle"><h1>Manage Tutors</h1></div>
	
<?php
	echo $err;
?>
	<form id="curform" method=post action="managetutors.php?cid=<?php echo $cid ?>">
	<p>Check: <a href="#" onclick="return chkAllNone('curform','remove[]',true)">All</a> <a href="#" onclick="return chkAllNone('curform','remove[]',false)">None</a></p>
	<table class="gb">
    <caption class="sr-only">Tutors</caption>
	<thead>
		<tr>
			<th>Tutor name</th>
			<th>Limit to <?php echo Sanitize::encodeStringForDisplay($limitname); ?></th>

			<th>Remove?</th>

		</tr>
	</thead>
	<tbody>
<?php
if (count($tutorlist)==0) {
	echo '<tr><td colspan="3">No tutors have been designated for this course.</td></tr>';
}
$i = 0;
foreach ($tutorlist as $tutor) {
	$i++;
	echo '<tr>';
	echo '<td><span class="pii-full-name" id="u'.$i.'">'.Sanitize::encodeStringForDisplay($tutor['name']).'</span></td>';
	echo '<td>';
	//section
	echo '<select name="section['.Sanitize::encodeStringForDisplay($tutor['id']).']" aria-labelledby="u'.$i.'">';
	echo '<option value="" '.getHtmlSelected($tutor['section'],"").'>All</option>';
	foreach ($sections as $sec) {
		echo '<option value="'.Sanitize::encodeStringForDisplay($sec).'" '.getHtmlSelected($tutor['section'],$sec).'>'.Sanitize::encodeStringForDisplay($sec).'</option>';
	}
	if (!in_array($tutor['section'],$sections) && $tutor['section']!='') {
		echo '<option value="invalid" selected="selected">Invalid - reselect</option>';
	}
	echo '</select>';
	echo '</td>';
	echo '<td>';
	echo '<input type="checkbox" name="remove[]" value="'.Sanitize::encodeStringForDisplay($tutor['id']).'" aria-labelledby="u'.$i.'" />';
	echo '</td>';
	echo '</tr>';
}

?>

	</tbody>
	</table>
	
	<input type="submit" name="submit" value="Update" />
	</form>

<?php
	echo '<p>'._('You can add additional tutors by having them enroll as students, then in the roster select them and use the "With Selected: Add as Tutors" option.').'</p>';
	require_once "../footer.php";
?>
