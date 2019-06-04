<?php
//IMathAS: Question settings page, new assess format
//(c) 2019 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");


 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Question Settings";


	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);
	$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	$curBreadcrumb .= "&gt; <a href=\"addquestions.php?aid=$aid&cid=$cid\">Add/Remove Questions</a> &gt; ";
	$curBreadcrumb .= "Modify Question Settings";

	if ($_GET['process']== true) {
		if (isset($_GET['usedef'])) {
			$points = 9999;
			$attempts=9999;
			$penalty=9999;
			$regen = 0;
			$showans = 0;
			$rubric = 0;
			$showhints = -1;
			$fixedseeds = null;
			$_POST['copies'] = 1;
		} else {
			if (trim($_POST['points'])=="") {$points=9999;} else {$points = intval($_POST['points']);}
			if (trim($_POST['attempts'])=="") {$attempts=9999;} else {$attempts = intval($_POST['attempts']);}
			if (trim($_POST['penalty'])=="") {$penalty=9999;} else {$penalty = intval($_POST['penalty']);}
			if (trim($_POST['fixedseeds'])=="") {$fixedseeds=null;} else {$fixedseeds = trim($_POST['fixedseeds']);}
			if ($penalty!=9999) {
        $penalty_aftern = Sanitize::onlyInt($_POST['penaltyaftern']);
				if ($penalty_aftern > 1 && $attempts > 1) {
          $penalty = 'S' . $penalty_aftern . $penalty;
        }
			}

			$regen = $_POST['allowregen'];
			$showans = $_POST['showans'];
			$rubric = intval($_POST['rubric']);
			$showhints = intval($_POST['showhints']);
		}
		if (isset($_GET['id'])) { //already have id - updating
			if (isset($_POST['replacementid']) && $_POST['replacementid']!='' && intval($_POST['replacementid'])!=0) {
				$query = "UPDATE imas_questions SET points=:points,attempts=:attempts,penalty=:penalty,regen=:regen,showans=:showans,rubric=:rubric,showhints=:showhints,fixedseeds=:fixedseeds";
				$query .= ',questionsetid=:questionsetid WHERE id=:id';
				$stm = $DBH->prepare($query);
				$stm->execute(array(':points'=>$points, ':attempts'=>$attempts, ':penalty'=>$penalty, ':regen'=>$regen, ':showans'=>$showans, ':rubric'=>$rubric,
					':showhints'=>$showhints,  ':fixedseeds'=>$fixedseeds, ':questionsetid'=>$_POST['replacementid'], ':id'=>$_GET['id']));
			} else {
				$query = "UPDATE imas_questions SET points=:points,attempts=:attempts,penalty=:penalty,regen=:regen,showans=:showans,rubric=:rubric,showhints=:showhints,fixedseeds=:fixedseeds";
				$query .= " WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':points'=>$points, ':attempts'=>$attempts, ':penalty'=>$penalty, ':regen'=>$regen, ':showans'=>$showans,
					':rubric'=>$rubric, ':showhints'=>$showhints, ':fixedseeds'=>$fixedseeds, ':id'=>$_GET['id']));
			}
			if (isset($_POST['copies']) && $_POST['copies']>0) {
				$stm = $DBH->prepare("SELECT questionsetid FROM imas_questions WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['id']));
				$_GET['qsetid'] = $stm->fetchColumn(0);
			}
		}
		require_once("../includes/updateptsposs.php");
		if (isset($_GET['qsetid'])) { //new - adding
			$stm = $DBH->prepare("SELECT itemorder,defpoints FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			list($itemorder,$defpoints) = $stm->fetch(PDO::FETCH_NUM);
			for ($i=0;$i<$_POST['copies'];$i++) {
				$query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,regen,showans,questionsetid,rubric,showhints,fixedseeds) ";
				$query .= "VALUES (:assessmentid, :points, :attempts, :penalty, :regen, :showans, :questionsetid, :rubric, :showhints, :fixedseeds)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$aid, ':points'=>$points, ':attempts'=>$attempts, ':penalty'=>$penalty, ':regen'=>$regen,
					':showans'=>$showans, ':questionsetid'=>$_GET['qsetid'], ':rubric'=>$rubric, ':showhints'=>$showhints, ':fixedseeds'=>$fixedseeds));
				$qid = $DBH->lastInsertId();

				//add to itemorder
				if (isset($_GET['id'])) { //am adding copies of existing
					$itemarr = explode(',',$itemorder);
					$key = array_search($_GET['id'],$itemarr);
					array_splice($itemarr,$key+1,0,$qid);
					$itemorder = implode(',',$itemarr);
				} else {
					if ($itemorder=='') {
						$itemorder = $qid;
					} else {
						$itemorder = $itemorder . ",$qid";
					}
				}
			}
			$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$aid));

			updatePointsPossible($aid, $itemorder, $defpoints);
		} else {
			updatePointsPossible($aid);
		}

    // Delete any teacher or tutor attempts on this assessment
    $query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
      imas_teachers AS usr ON usr.userid=iar.userid AND usr.courseid=?
      WHERE iar.assessmentid=?';
    $stm = $DBH->prepare($query);
    $stm->execute(array($cid, $aid));
    $query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
      imas_tutors AS usr ON usr.userid=iar.userid AND usr.courseid=?
      WHERE iar.assessmentid=?';
    $stm = $DBH->prepare($query);
    $stm->execute(array($cid, $aid));

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions.php?cid=$cid&aid=$aid&r=" . Sanitize::randomQueryStringParam());
		exit;
	} else { //DEFAULT DATA MANIPULATION

		if (isset($_GET['id'])) {
			$stm = $DBH->prepare("SELECT points,attempts,penalty,regen,showans,rubric,showhints,questionsetid,fixedseeds FROM imas_questions WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			if ($line['penalty']{0}==='S') {
				$penalty_aftern = $line['penalty']{1};
				$line['penalty'] = substr($line['penalty'],2);
			} else {
				$penalty_aftern = 1;
			}

			if ($line['points']==9999) {$line['points']='';}
			if ($line['attempts']==9999) {$line['attempts']='';}
			if ($line['penalty']==9999) {$line['penalty']=''; }
			if ($line['fixedseeds']===null) {$line['fixedseeds'] = '';}
			$qsetid = $line['questionsetid'];
		} else {
			//set defaults
			$line['points']="";
			$line['attempts']="";
			$line['penalty']="";
			$line['fixedseeds'] = '';
		  $penalty_aftern = 1;
			$line['regen']=0;
			$line['showans']='0';
			$line['rubric']=0;
			$line['showhints']=-1;
			$qsetid = $_GET['qsetid'];
		}

		$stm = $DBH->prepare("SELECT description FROM imas_questionset WHERE id=:id");
		$stm->execute(array(':id'=>$qsetid));
		$qdescrip = $stm->fetchColumn(0);
		if (isset($_GET['loc'])) {
			$qdescrip = $_GET['loc'].': '.$qdescrip;
		}

		$rubric_vals = array(0);
		$rubric_names = array('None');
		$stm = $DBH->prepare("SELECT id,name FROM imas_rubrics WHERE ownerid=:ownerid OR groupid=:groupid ORDER BY name");
		$stm->execute(array(':ownerid'=>$userid, ':groupid'=>$gropuid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$rubric_vals[] = $row[0];
			$rubric_names[] = $row[1];
		}
		$query = "SELECT iar.userid FROM imas_assessment_records AS iar,imas_students WHERE ";
		$query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
		if ($stm->rowCount() > 0) {
			$page_beenTakenMsg = "<h2>Warning</h2>\n";
			$page_beenTakenMsg .= "<p>This assessment has already been taken.  Altering the points or penalty will not change the scores of students who already completed this question. ";
			$page_beenTakenMsg .= "If you want to make these changes, or add additional copies of this question, you should clear all existing assessment attempts</p> ";
			$page_beenTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearattempts=ask'\"></p>\n";
			$beentaken = true;
		} else {
			$beentaken = false;
		}

		//get defaults
		$query = "SELECT defpoints,defattempts,defpenalty,defregens,";
    $query .= "showans,submitby,showhints,shuffle FROM imas_assessments ";
		$query .= "WHERE id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$aid));
		$defaults = $stm->fetch(PDO::FETCH_ASSOC);

		if ($defaults['defpenalty']{0}==='S') {
			$defaults['penalty'] = sprintf(_('%d%% after %d full-credit tries'),
        substr($defaults['defpenalty'],2), $defaults['defpenalty']{1});
		} else {
			$defaults['penalty'] = $defaults['defpenalty'] . '%';
		}

		if ($defaults['showans']=='after_lastattempt') {
			$defaults['showans'] = _('After last attempt on a version');
		} else if ($defaults['showans']=='after_take') {
			$defaults['showans'] = _('After assessment version is submitted');
		} else if ($defaults['showans'] == 'never') {
			$defaults['showans'] = _('Never during assessment');
		} else if (substr($defaults['showans'],0,5)=='after') {
			$defaults['showans'] = sprintf(_('After %d tries'), substr($defaults['showans'], 6));
		}
    if ($defaults['showhints'] == 0) {
      $defaults['showhints'] = _('No');
    } else if ($defaults['showhints'] == 1) {
      $defaults['showhints'] = _('Hints');
    } else if ($defaults['showhints'] == 2) {
      $defaults['showhints'] = _('Video/text buttons');
    } else if ($defaults['showhints'] == 3) {
      $defaults['showhints'] = _('Hints and Video/text buttons');
    }
	}
}

/******* begin html output ********/
$placeinhead = '<script type="text/javascript">
function previewq(qn) {
  previewpop = window.open(imasroot+"/course/testquestion.php?fixedseeds=1&cid="+cid+"&qsetid="+qn,"Testing","width="+(.4*screen.width)+",height="+(.8*screen.height)+",scrollbars=1,resizable=1,status=1,top=20,left="+(.6*screen.width-20));
  previewpop.focus();
}
</script>';
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<div class="breadcrumb"><?php echo $curBreadcrumb; ?></div>
	<?php echo $page_beenTakenMsg; ?>

<div id="headermodquestion" class="pagetitle"><h1>
<?php
if (isset($_GET['id'])) {
	echo 'Modify Question Settings';
} else {
	echo 'New Question Settings';
}
?>
</h1></div>
<p><?php
	echo '<b>'.Sanitize::encodeStringForDisplay($qdescrip).'</b> ';
	echo '<button type="button" onclick="previewq('.Sanitize::encodeStringForJavascript($qsetid).')">'._('Preview').'</button>';
?>
</p>
<form method=post action="modquestion.php?process=true&<?php echo "cid=$cid&aid=" . Sanitize::encodeUrlParam($aid); if (isset($_GET['id'])) {echo "&id=" . Sanitize::encodeUrlParam($_GET['id']);} if (isset($_GET['qsetid'])) {echo "&qsetid=" . Sanitize::encodeUrlParam($_GET['qsetid']);}?>">
<p>Leave items blank to use the assessment's default values.
<input type="submit" value="<?php echo ('Save Settings');?>"></p>

<?php
if (!isset($_GET['id'])) {
?>
<span class=form>Points for this problem:</span>
<span class=formright> <input type=text size=4 name=points value="<?php echo Sanitize::encodeStringForDisplay($line['points']);?>"><br/><i class="grey">Default: <?php echo Sanitize::encodeStringForDisplay($defaults['defpoints']);?></i></span><BR class=form>
<?php
}
?>
<span class=form>Tries allowed on each version of this problem (0 for unlimited):</span>
<span class=formright> <input type=text size=3 name=attempts value="<?php echo Sanitize::encodeStringForDisplay($line['attempts']);?>">
  <br/><i class="grey">Default: <?php echo Sanitize::encodeStringForDisplay($defaults['defattempts']);?></i>
</span><BR class=form>

<span class=form>Penalty on Tries:</span>
<span class=formright><input type=text size=2 name=penalty value="<?php echo Sanitize::encodeStringForDisplay($line['penalty']);?>">%
   per try after
   <input type=text size=1 name="penalty_aftern" value="<?php echo Sanitize::encodeStringForDisplay($penalty_aftern);?>">
   full-credit tries
   <br/><i class="grey">Default: <?php echo Sanitize::encodeStringForDisplay($defaults['penalty']);?></i>
</span><BR class=form>
<?php
// TODO: Add regen penalty stuff.  Do we really want to?
if ($defaults['submitby'] == 'by_question' && $defaults['defregens'] > 1) {
?>
<span class="form">Allow &quot;Try similar problem&quot;?</span>
<span class=formright>
    <select name="allowregen">
     <option value="0" <?php if ($line['regen']==0) { echo 'selected="1"';}?>>Use Default</option>
     <option value="1" <?php if ($line['regen']>0) { echo 'selected="1"';}?>>No</option>
</select><br/><i class="grey">Default: <?php echo $defaults['defregens'];?> versions</i></span><br class="form"/>
<?php
}
?>
<span class=form>Show Answers during Assessment</span><span class=formright>
    <select name="showans">
     <option value="0" <?php if ($line['showans']=='0') { echo 'selected="1"';}?>>Use Default</option>
     <option value="N" <?php if ($line['showans']=='N') { echo 'selected="1"';}?>>Never during assessment</option>
    </select><br/><i class="grey">Default: <?php echo Sanitize::encodeStringForDisplay($defaults['showans']);?></i></span><br class="form"/>

<span class=form>Show hints and video/text buttons?</span><span class=formright>
    <select name="showhints">
     <option value="-1" <?php if ($line['showhints']==-1) { echo 'selected="1"';}?>>Use Default</option>
     <option value="0" <?php if ($line['showhints']==0) { echo 'selected="1"';}?>>No</option>
     <option value="1" <?php if ($line['showhints']==1) { echo 'selected="1"';}?>>Hints</option>
     <option value="2" <?php if ($line['showhints']==2) { echo 'selected="1"';}?>>Video/text buttons</option>
     <option value="3" <?php if ($line['showhints']==3) { echo 'selected="1"';}?>>Hints and Video/text buttons</option>
    </select><br/><i class="grey">Default: <?php echo $defaults['showhints'];?></i></span><br class="form"/>

<span class=form>Use Scoring Rubric</span><span class=formright>
<?php
    writeHtmlSelect('rubric',$rubric_vals,$rubric_names,$line['rubric']);
    echo " <a href=\"addrubric.php?cid=$cid&amp;id=new&amp;from=modq&amp;aid=" . Sanitize::encodeUrlParam($aid) . "&amp;qid=" . Sanitize::encodeUrlParam($_GET['id']) . "\">Add new rubric</a> ";
    echo "| <a href=\"addrubric.php?cid=$cid&amp;from=modq&amp;aid=" . Sanitize::encodeUrlParam($aid) . "&amp;qid=" . Sanitize::encodeUrlParam($_GET['id']) . "\">Edit rubrics</a> ";
?>
    </span><br class="form"/>
<?php
	if (isset($_GET['qsetid'])) { //adding new question
		echo "<span class=form>Number of copies of question to add:</span><span class=formright><input type=text size=4 name=copies value=\"1\"/></span><br class=form />";
	} else if (!$beentaken) {
		echo "<span class=form>Number, if any, of additional copies to add to assessment:</span><span class=formright><input type=text size=4 name=copies value=\"0\"/></span><br class=form />";
	}
	if ($line['fixedseeds']=='') {
		echo '<span class="form"><a href="#" onclick="$(this).hide();$(\'.advanced\').show();return false">Advanced</a></span><br class="form"/>';
	}
	echo '<div class="advanced" id="fixedseedwrap" ';
	if ($line['fixedseeds']=='') {
		echo 'style="display:none;"';
	}
	echo '>';
	echo '<span class="form">Restricted question seed list:</span>';
	echo '<span class="formright"><input size=30 name="fixedseeds" id="fixedseeds" value="'.$line['fixedseeds'].'"/></span><br class="form"/>';
	echo '</div>';
	if ($line['fixedseeds']!='' && isset($_GET['id'])) {
		echo '<span class="form"><a href="#" onclick="$(this).hide();$(\'.advanced\').show();return false">Advanced</a></span><br class="form"/>';
	}
	if (isset($_GET['id'])) {
		echo '<div class="advanced" style="display:none">';
		echo '<span class="form">Replace this question with question ID: ';
		if ($beentaken) {
			echo '<br/><span class=noticetext>WARNING: This is NOT recommended. It will mess up the question for any student who has already attempted it, and any work they have done may look garbled when you view it</span>';
		}
		echo '</span><span class="formright"><input size="7" name="replacementid"/></span><br class="form"/>';
		echo '</div>';
	}
	if ($beentaken) {
		echo '<div class="advanced" style="display:none">';
		echo '<span class=form>Points for this problem: <br/>';
		echo '<span class=noticetext>WARNING: you generally should not change point values after students have started the assessment, as the points already earned by students will not be re-calculated.</span></span>';
		echo '<span class=formright> <input type=text size=4 name=points value="'.Sanitize::encodeStringForDisplay($line['points']).'"> (blank for default)</span><BR class=form>';
		echo '</div>';
	} else if (isset($_GET['id'])) {
		echo '<input type=hidden name=points value="'.Sanitize::encodeStringForDisplay($line['points']).'" />';
	}
	echo '<div class="submit"><input type="submit" value="'._('Save Settings').'"></div>';
	echo '</form>';
}

require("../footer.php");
?>
