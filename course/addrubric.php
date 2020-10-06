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
	$curBreadcrumb .= "&gt; <a href=\"addrubric.php?cid=$cid$fromstr\">Manage Rubrics</a> ";
	if ($_GET['id']=='new') {
		$curBreadcrumb .= "&gt; Add Rubric\n";
		$pagetitle = "Add Rubric";
	} else if ($_GET['do'] === 'delete') {
		$curBreadcrumb .= "&gt; Delete Rubric\n";
		$pagetitle = "Delete Rubric";
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
	if (!empty($_POST['delete'])) { // delete form submitted
		$rubid = intval($_GET['id']);
		$stm = $DBH->prepare("DELETE FROM imas_rubrics WHERE id=? and ownerid=?");
		$stm->execute(array($rubid, $userid));
		if ($stm->rowCount() > 0) {
			// this doesn't clear rubric usage outside of directly owned courses,
			// but that's better than nothing
			$query = "UPDATE imas_questions AS iq JOIN imas_assessments AS ia ";
			$query .= "ON iq.assessmentid=ia.id JOIN imas_courses AS ic ";
			$query .= "ON ia.courseid=ic.id AND ic.ownerid=? SET rubric=0 WHERE rubric=?";
			$stm = $DBH->prepare($query);
			$stm->execute($userid, $rubid);
			$query = "UPDATE imas_gbitems AS ig JOIN imas_courses AS ic ";
			$query .= "ON ig.courseid=ic.id AND ic.ownerid=? SET rubric=0 WHERE rubric=?";
			$stm = $DBH->prepare($query);
			$stm->execute($userid, $rubid);
			$query = "UPDATE imas_forums AS if JOIN imas_courses AS ic ";
			$query .= "ON if.courseid=ic.id AND ic.ownerid=? SET rubric=0 WHERE rubric=?";
			$stm = $DBH->prepare($query);
			$stm->execute($userid, $rubid);
			// This is too inefficient to run
			/*$stm = $DBH->prepare("UPDATE imas_questions SET rubric=0 WHERE rubric=?");
			$stm->execute(array($rubid));
			$stm = $DBH->prepare("UPDATE imas_gbitems SET rubric=0 WHERE rubric=?");
			$stm->execute(array($rubid));
			$stm = $DBH->prepare("UPDATE imas_forums SET rubric=0 WHERE rubric=?");
			$stm->execute(array($rubid));
			*/
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addrubric.php?cid=$cid$fromstr&r=" .Sanitize::randomQueryStringParam());
		exit;
	} else if (isset($_POST['rubname'])) { //FORM SUBMITTED, DATA PROCESSING
		if (isset($_POST['rubisgroup'])) {
			$rubgrp = $groupid;
		} else {
			$rubgrp = -1;
		}
		$rubric = array();
		for ($i=0;$i<15; $i++) {
			if (!empty($_POST['rubitem'.$i])) {
				$rubric[] = array($_POST['rubitem'.$i], $_POST['rubnote'.$i], floatval($_POST['rubscore'.$i]));
			}
		}
		$rubricstring = serialize($rubric);
		if ($_GET['id']!='new') { //MODIFY
			$stm = $DBH->prepare("UPDATE imas_rubrics SET name=:name,rubrictype=:rubrictype,groupid=:groupid,rubric=:rubric WHERE id=:id");
			$stm->execute(array(':name'=>$_POST['rubname'], ':rubrictype'=>$_POST['rubtype'], ':groupid'=>$rubgrp, ':rubric'=>$rubricstring, ':id'=>$_GET['id']));
		} else {
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
				if (isset($_GET['copy'])) {
					$stm = $DBH->prepare("SELECT name,groupid,rubrictype,rubric FROM imas_rubrics WHERE id=:id");
					$stm->execute(array(':id'=>intval($_GET['copy'])));
					list($rubname,$rubgrp,$rubtype,$rubric) = $stm->fetch(PDO::FETCH_NUM);
					$rubric = unserialize($rubric);
					$rubname = 'Copy of: '.$rubname;
				} else {
					$rubname = "New Rubric";
					$rubric = array();
					$rubgrp = -1;
					$rubtype = 1;
				}
				$savetitle = _('Create Rubric');
			} else {
				$rubid = Sanitize::onlyInt($_GET['id']);
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
$placeinhead = '<script type="text/javascript" src="'.$staticroot.'/javascript/rubric.js?v=113016"></script>';
$placeinhead .= '<script type="text/javascript">$(function() {
  var html = \'<span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="'.$staticroot.'/img/gears.png" alt="Options"/></a>\';
  html += \'<ul role="menu" class="dropdown-menu">\';
	var fromstr = "'.Sanitize::encodeStringForJavascript($fromstr).'";
  $("img[data-rid]").each(function (i,el) {
  	var rid = $(el).attr("data-rid");
		var thishtml = html + \' <li><a href="addrubric.php?cid=\'+cid+\'&id=\'+rid+fromstr+\'">'._('Edit').'</a></li>\';
		thishtml += \' <li><a href="addrubric.php?cid=\'+cid+\'&id=new&copy=\'+rid+fromstr+\'">'._('Copy').'</a></li>\';
		if ($(el).attr("data-mine") == "true") {
			thishtml += \' <li><a href="addrubric.php?cid=\'+cid+\'&do=delete&id=\'+rid+fromstr+\'">'._('Delete').'</a></li>\';
		}
		thishtml += \'</ul></span> \';
		$(el).replaceWith(thishtml);
	  });
	  $(".dropdown-toggle").dropdown();
	});
  </script>';
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY

?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headeraddforum" class="pagetitle"><h1><?php echo $pagetitle ?></h1></div>
<?php
if (!isset($_GET['id'])) {//displaying "Manage Rubrics" page
	echo '<p>'._('You can attach a rubric to an offline grade item on the Add Offline Grade page.').' ';
	echo _('You can attach a rubric to an assessment questions from the Add/Remove Questions page by choosing Change Settings for the question.').'</p>';
	echo '<p>Select a rubric to edit or <a href="addrubric.php?cid='.$cid.'&amp;id=new">Add a new rubric</a></p><p>';
	$stm = $DBH->prepare("SELECT id, name, ownerid FROM imas_rubrics WHERE ownerid=:ownerid OR groupid=:groupid ORDER BY name");
	$stm->execute(array(':ownerid'=>$userid, ':groupid'=>$groupid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo '<img data-rid="' . Sanitize::onlyInt($row[0]) . '" ';
		echo 'data-mine="' . ($row[2] == $userid ? "true" : "false") .'" ';
		echo 'src="'.$staticroot.'/img/gears.png"/>';
		echo Sanitize::encodeStringForDisplay($row[1]) . '<br/>';

	}
	echo '</p>';
} else if ($_GET['do'] === 'delete') { // deleting
	echo "<form method=\"post\" action=\"addrubric.php?cid=$cid&amp;id=" . Sanitize::encodeUrlParam($_GET['id']) . $fromstr . "\">";
	echo '<p>Are you SURE you want to delete rubric <b>'.Sanitize::encodeStringForDisplay($rubname).'</b>?</p>';
	echo '<input type="hidden" name="delete" value="1" />';
	echo '<p><button type=submit>Yes, Delete</button> ';
	echo '<button type=button onclick="location.href=\'addrubric.php?cid='.$cid.$fromstr.'\'">Nevermind</button>';
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
	echo '<p>Name:  <input type="text" size="70" name="rubname" value="'.Sanitize::encodeStringForDisplay($rubname).'"/></p>';

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
