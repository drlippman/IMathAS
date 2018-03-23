<?php
//IMathAS:  Add/edit rubrics
//(c) 2011 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$cid = Sanitize::courseId($_GET['cid']);
$from = $_GET['from'];

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
if ($from=='modq') {
	$fromstr = '&amp;' . Sanitize::generateQueryStringFromMap(array('from' => 'modq', 'aid' => $_GET['aid'],
			'qid' => $_GET['qid']));
	$returnstr = 'modquestion.php?' . Sanitize::generateQueryStringFromMap(array('cid' => $cid,
			'aid' => $_GET['aid'], 'id' => $_GET['qid']));
	$curBreadcrumb .= "&gt; <a href=\"$returnstr\">Modify Question Settings</a> ";
} else if ($from=='addg') {
	$fromstr = '&amp;' . Sanitize::generateQueryStringFromMap(array('from' => 'addg', 'gbitem' => $_GET['gbitem']));
	$returnstr = 'addgrades.php?'. Sanitize::generateQueryStringFromMap(array('cid' => $cid,
			'gbitem' => $_GET['gbitem'], 'grades' => 'all'));
	$curBreadcrumb .= "&gt; <a href=\"$returnstr\">Offline Grades</a> ";
} else if ($from=='addf') {
	$fromstr = '&amp;' . Sanitize::generateQueryStringFromMap(array('from' => 'addf', 'fid' => $_GET['fid']));
	$returnstr = 'addforum.php?' . Sanitize::generateQueryStringFromMap(array('cid' => $cid, 'id' => $_GET['fid']));
	$curBreadcrumb .= "&gt; <a href=\"$returnstr\">Modify Forum</a> ";
}


if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; <a href=\"addrubric.php?cid=$cid\">Manage Rubrics</a> ";
	if ($_GET['id']=='new') {
		$curBreadcrumb .= "&gt; Add Rubric\n";
		$pagetitle = "Add Rubric";
	} else {
		$curBreadcrumb .= "&gt; Edit Rubric\n";
		$pagetitle = "Edit Rubric";
	}
} else {
	$curBreadcrumb .= "&gt; Manage Rubrics\n";
	$pagetitle = "Manage Rubrics";
}

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	if (isset($_POST['rubname'])) { //FORM SUBMITTED, DATA PROCESSING
		if (isset($_POST['rubisgroup'])) {
			$rubgrp = $groupid;
		} else {
			$rubgrp = -1;
		}
		$rubric = array();
		for ($i=0;$i<15; $i++) {
			if (!empty($_POST['rubitem'.$i])) {
				//DB $rubric[] = array(stripslashes($_POST['rubitem'.$i]), stripslashes($_POST['rubnote'.$i]), floatval($_POST['rubscore'.$i]));
				$rubric[] = array($_POST['rubitem'.$i], $_POST['rubnote'.$i], floatval($_POST['rubscore'.$i]));
			}
		}

		//DB $rubricstring = addslashes(serialize($rubric));
		$rubricstring = serialize($rubric);
		if ($_GET['id']!='new') { //MODIFY
			//DB $query = "UPDATE imas_rubrics SET name='{$_POST['rubname']}',rubrictype='{$_POST['rubtype']}',groupid=$rubgrp,rubric='$rubricstring' WHERE id='{$_GET['id']}'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_rubrics SET name=:name,rubrictype=:rubrictype,groupid=:groupid,rubric=:rubric WHERE id=:id");
			$stm->execute(array(':name'=>$_POST['rubname'], ':rubrictype'=>$_POST['rubtype'], ':groupid'=>$rubgrp, ':rubric'=>$rubricstring, ':id'=>$_GET['id']));
		} else {
			//DB $query = "INSERT INTO imas_rubrics (ownerid,name,rubrictype,groupid,rubric) VALUES ";
			//DB $query .= "($userid,'{$_POST['rubname']}','{$_POST['rubtype']}',$rubgrp,'$rubricstring')";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "INSERT INTO imas_rubrics (ownerid,name,rubrictype,groupid,rubric) VALUES ";
			$query .= "(:ownerid, :name, :rubrictype, :groupid, :rubric)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':ownerid'=>$userid, ':name'=>$_POST['rubname'], ':rubrictype'=>$_POST['rubtype'], ':groupid'=>$rubgrp, ':rubric'=>$rubricstring));
		}
		$fromstr = str_replace('&amp;','&',$fromstr);
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addrubric.php?cid=$cid$fromstr&r=" .Sanitize::randomQueryStringParam());



	} else { //INITIAL LOAD DATA PROCESS
		if (isset($_GET['id'])) { //MODIFY
			if ($_GET['id']=='new') {//NEW
				$rubric = array();
				$rubname = "New Rubric";
				$rubgrp = -1;
				$rubtype = 1;
				$savetitle = _('Create Rubric');
			} else {
				$rubid = Sanitize::onlyInt($_GET['id']);
				//DB $query = "SELECT name,groupid,rubrictype,rubric FROM imas_rubrics WHERE id=$rubid";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB list($rubname,$rubgrp,$rubtype,$rubric) = mysql_fetch_row($result);
				$stm = $DBH->prepare("SELECT name,groupid,rubrictype,rubric FROM imas_rubrics WHERE id=:id");
				$stm->execute(array(':id'=>$rubid));
				list($rubname,$rubgrp,$rubtype,$rubric) = $stm->fetch(PDO::FETCH_NUM);
				$rubric = unserialize($rubric);
				$savetitle = _('Save Changes');
			}
		}
	}
}

//BEGIN DISPLAY BLOCK

/******* begin html output ********/
$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=113016"></script>';
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY

?>

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headeraddforum" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php
if (!isset($_GET['id'])) {//displaying "Manage Rubrics" page
	echo '<p>Select a rubric to edit or <a href="addrubric.php?cid='.$cid.'&amp;id=new">Add a new rubric</a></p><p>';
	//DB $query = "SELECT id, name FROM imas_rubrics WHERE ownerid='$userid' OR groupid='$groupid' ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT id, name FROM imas_rubrics WHERE ownerid=:ownerid OR groupid=:groupid ORDER BY name");
	$stm->execute(array(':ownerid'=>$userid, ':groupid'=>$groupid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo Sanitize::encodeStringForDisplay($row[1]) . " <a href=\"addrubric.php?cid=$cid&amp;id=" . Sanitize::onlyInt($row[0]) . $fromstr . "\">Edit</a><br/>";
	}
	echo '</p>';
} else {  //adding/editing a rubric
	/*  Rubric Types
	*   1: score breakdown (record score and feedback)
	*   0: score breakdown (record score)
	*   3: score total (record score and feedback)
	*   4: score total (record score only)
	*   2: record feedback only (checkboxes)
	*/
	$rubtypeval = array(1,0,3,4,2);
	$rubtypelabel = array('Score breakdown, record score and feedback','Score breakdown, record score only','Score total, record score and feedback','Score total, record score only','Feedback only');
    echo "<form method=\"post\" action=\"addrubric.php?cid=$cid&amp;id=" . Sanitize::encodeUrlParam($_GET['id']) . $fromstr . "\">";
    echo '<p>Name:  <input type="text" size="70" name="rubname" value="'.str_replace('"','&quot;',$rubname).'"/></p>';

	echo '<p>Rubric Type: ';
	writeHtmlSelect('rubtype',$rubtypeval,$rubtypelabel,$rubtype,null,null,'onchange="imasrubric_chgtype()"');
	echo '</p>';
	if ($rubtype==0 || $rubtype==1) {
		echo '<p id="breakdowninstr">';
	} else {
		echo '<p style="display:none;" id="breakdowninstr">';
	}
	echo 'With this type of rubric, the total score is divided up between the rubric items. The "portion of score" column should
		add to 100(%) or the expected point total for the question.';
	echo '</p>';
	if ($rubtype==3 || $rubtype==4) {
		echo '<p id="scoretotalinstr">';
	} else {
		echo '<p style="display:none;" id="scoretotalinstr">';
	}
	echo 'With this type of rubric, one rubric item will be selected, and "portion of score" associated with that item will be the
		total final score for the question.  Make sure one item is 100(%) or the expected point total for the question.';
	echo '</p>';

	echo '<p>Share with Group: <input type="checkbox" name="rubisgroup" '.getHtmlChecked($rubgrp,-1,1).' /></p>';
	echo '<table><thead><tr><th>Rubric Item<br/>Shows in feedback</th><th>Instructor Note<br/>Not in feedback</th><th><span id="pointsheader" ';
	if ($rubtype==2) {echo 'style="display:none;" ';}
	echo '>Portion of Score</span>';

	echo '</th></tr></thead><tbody>';
	for ($i=0;$i<15; $i++) {
		echo '<tr><td><input type="text" size="40" name="rubitem'.$i.'" value="';
		if (isset($rubric[$i]) && isset($rubric[$i][0])) { echo str_replace('"','&quot;',$rubric[$i][0]);}
		echo '"/></td>';
		echo '<td><input type="text" size="40" name="rubnote'.$i.'" value="';
		if (isset($rubric[$i]) && isset($rubric[$i][1])) { echo str_replace('"','&quot;',$rubric[$i][1]);}
		echo '"/></td>';
		echo '<td><input type="text" size="4" class="rubricpoints" ';
		if ($rubtype==2) {echo 'style="display:none;" ';}
		echo 'name="rubscore'.$i.'" value="';
		if (isset($rubric[$i]) && isset($rubric[$i][2])) { echo str_replace('"','&quot;',$rubric[$i][2]);} else {echo 0;}
		echo '"/></td></tr>';
	}
	echo '</table>';
	echo '<input type="submit" value="'.$savetitle.'"/>';
	echo '</form>';


}
}
require("../footer.php");
?>
