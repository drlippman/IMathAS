<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
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

	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
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
			$showhints = 0;
			$_POST['copies'] = 1;
		} else {
			if (trim($_POST['points'])=="") {$points=9999;} else {$points = intval($_POST['points']);}
			if (trim($_POST['attempts'])=="") {$attempts=9999;} else {$attempts = intval($_POST['attempts']);}
			if (trim($_POST['penalty'])=="") {$penalty=9999;} else {$penalty = intval($_POST['penalty']);}
			if ($penalty!=9999) {
				if ($_POST['skippenalty']==10) {
					$penalty = 'L'.$penalty;
				} else if ($_POST['skippenalty']>0) {
					$penalty = 'S'.$_POST['skippenalty'].$penalty;
				}
			}
			$regen = $_POST['regen'] + 3*$_POST['allowregen'];
			$showans = $_POST['showans'];
			$rubric = intval($_POST['rubric']);
			$showhints = intval($_POST['showhints']);
		}
		if (isset($_GET['id'])) { //already have id - updating
			if (isset($_POST['replacementid']) && $_POST['replacementid']!='' && intval($_POST['replacementid'])!=0) {
				//DB $query = "UPDATE imas_questions SET points='$points',attempts='$attempts',penalty='$penalty',regen='$regen',showans='$showans',rubric=$rubric,showhints=$showhints";
				//DB $query .= ',questionsetid='.intval($_POST['replacementid'])." WHERE id='{$_GET['id']}'";
				$query = "UPDATE imas_questions SET points=:points,attempts=:attempts,penalty=:penalty,regen=:regen,showans=:showans,rubric=:rubric,showhints=:showhints";
				$query .= ',questionsetid=:questionsetid WHERE id=:id';
				$stm = $DBH->prepare($query);
				$stm->execute(array(':points'=>$points, ':attempts'=>$attempts, ':penalty'=>$penalty, ':regen'=>$regen, ':showans'=>$showans, ':rubric'=>$rubric,
					':showhints'=>$showhints, ':questionsetid'=>$_POST['replacementid'], ':id'=>$_GET['id']));
			} else {
				//DB $query = "UPDATE imas_questions SET points='$points',attempts='$attempts',penalty='$penalty',regen='$regen',showans='$showans',rubric=$rubric,showhints=$showhints";
				//DB $query .= " WHERE id='{$_GET['id']}'";
				$query = "UPDATE imas_questions SET points=:points,attempts=:attempts,penalty=:penalty,regen=:regen,showans=:showans,rubric=:rubric,showhints=:showhints";
				$query .= " WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':points'=>$points, ':attempts'=>$attempts, ':penalty'=>$penalty, ':regen'=>$regen, ':showans'=>$showans,
					':rubric'=>$rubric, ':showhints'=>$showhints, ':id'=>$_GET['id']));
			}

			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (isset($_POST['copies']) && $_POST['copies']>0) {
				//DB $query = "SELECT questionsetid FROM imas_questions WHERE id='{$_GET['id']}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $_GET['qsetid'] = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT questionsetid FROM imas_questions WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['id']));
				$_GET['qsetid'] = $stm->fetchColumn(0);
			}
		}
		if (isset($_GET['qsetid'])) { //new - adding
			//DB $query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $itemorder = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT itemorder FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			$itemorder = $stm->fetchColumn(0);
			for ($i=0;$i<$_POST['copies'];$i++) {
				//DB $query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,regen,showans,questionsetid,rubric,showhints) ";
				//DB $query .= "VALUES ('$aid','$points','$attempts','$penalty','$regen','$showans','{$_GET['qsetid']}',$rubric,$showhints)";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $qid = mysql_insert_id();
				$query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,regen,showans,questionsetid,rubric,showhints) ";
				$query .= "VALUES (:assessmentid, :points, :attempts, :penalty, :regen, :showans, :questionsetid, :rubric, :showhints)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$aid, ':points'=>$points, ':attempts'=>$attempts, ':penalty'=>$penalty, ':regen'=>$regen,
					':showans'=>$showans, ':questionsetid'=>$_GET['qsetid'], ':rubric'=>$rubric, ':showhints'=>$showhints));
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
			//DB $query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id='$aid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$aid));
		}

		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
		exit;
	} else { //DEFAULT DATA MANIPULATION

		if (isset($_GET['id'])) {
			//DB $query = "SELECT points,attempts,penalty,regen,showans,rubric,showhints FROM imas_questions WHERE id='{$_GET['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT points,attempts,penalty,regen,showans,rubric,showhints FROM imas_questions WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			if ($line['penalty']{0}==='L') {
				$line['penalty'] = substr($line['penalty'],1);
				$skippenalty==10;
			} else if ($line['penalty']{0}==='S') {
				$skippenalty = $line['penalty']{1};
				$line['penalty'] = substr($line['penalty'],2);
			} else {
				$skippenalty = 0;
			}

			if ($line['points']==9999) {$line['points']='';}
			if ($line['attempts']==9999) {$line['attempts']='';}
			if ($line['penalty']==9999) {$line['penalty']='';}
		} else {
			//set defaults
			$line['points']="";
			$line['attempts']="";
			$line['penalty']="";
			$skippenalty = 0;
			$line['regen']=0;
			$line['showans']='0';
			$line['rubric']=0;
			$line['showhints']=0;
		}

		$rubric_vals = array(0);
		$rubric_names = array('None');
		//DB $query = "SELECT id,name FROM imas_rubrics WHERE ownerid='$userid' OR groupid='$gropuid' ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,name FROM imas_rubrics WHERE ownerid=:ownerid OR groupid=:groupid ORDER BY name");
		$stm->execute(array(':ownerid'=>$userid, ':groupid'=>$gropuid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$rubric_vals[] = $row[0];
			$rubric_names[] = $row[1];
		}

		//DB $query = "SELECT ias.id FROM imas_assessment_sessions AS ias,imas_students WHERE ";
		//DB $query .= "ias.assessmentid='$aid' AND ias.userid=imas_students.userid AND imas_students.courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result) > 0) {
		$query = "SELECT ias.id FROM imas_assessment_sessions AS ias,imas_students WHERE ";
		$query .= "ias.assessmentid=:assessmentid AND ias.userid=imas_students.userid AND imas_students.courseid=:courseid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
		if ($stm->rowCount() > 0) {
			$page_beenTakenMsg = "<h3>Warning</h3>\n";
			$page_beenTakenMsg .= "<p>This assessment has already been taken.  Altering the points or penalty will not change the scores of students who already completed this question. ";
			$page_beenTakenMsg .= "If you want to make these changes, or add additional copies of this question, you should clear all existing assessment attempts</p> ";
			$page_beenTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearattempts=ask'\"></p>\n";
			$beentaken = true;
		} else {
			$beentaken = false;
		}

	}
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<div class="breadcrumb"><?php echo $curBreadcrumb; ?></div>
	<?php echo $page_beenTakenMsg; ?>


<div id="headermodquestion" class="pagetitle"><h2>Modify Question Settings</h2></div>
<form method=post action="modquestion.php?process=true&<?php echo "cid=$cid&aid=$aid"; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";} if (isset($_GET['qsetid'])) {echo "&qsetid={$_GET['qsetid']}";}?>">
Leave items blank to use the assessment's default values<br/>

<span class=form>Points for this problem:</span><span class=formright> <input type=text size=4 name=points value="<?php echo $line['points'];?>"></span><BR class=form>

<span class=form>Attempts allowed for this problem (0 for unlimited):</span><span class=formright> <input type=text size=4 name=attempts value="<?php echo $line['attempts'];?>"></span><BR class=form>

<span class=form>Default penalty:</span><span class=formright><input type=text size=4 name=penalty value="<?php echo $line['penalty'];?>">%
   <select name="skippenalty" <?php if ($taken) {echo 'disabled=disabled';}?>>
     <option value="0" <?php if ($skippenalty==0) {echo "selected=1";} ?>>per missed attempt</option>
     <option value="1" <?php if ($skippenalty==1) {echo "selected=1";} ?>>per missed attempt, after 1</option>
     <option value="2" <?php if ($skippenalty==2) {echo "selected=1";} ?>>per missed attempt, after 2</option>
     <option value="3" <?php if ($skippenalty==3) {echo "selected=1";} ?>>per missed attempt, after 3</option>
     <option value="4" <?php if ($skippenalty==4) {echo "selected=1";} ?>>per missed attempt, after 4</option>
     <option value="5" <?php if ($skippenalty==5) {echo "selected=1";} ?>>per missed attempt, after 5</option>
     <option value="6" <?php if ($skippenalty==6) {echo "selected=1";} ?>>per missed attempt, after 6</option>
     <option value="10" <?php if ($skippenalty==10) {echo "selected=1";} ?>>on last possible attempt only</option>
     </select></span><BR class=form>

<span class=form>New version on reattempt?</span><span class=formright>
    <select name="regen">
     <option value="0" <?php if (($line['regen']%3)==0) { echo 'selected="1"';}?>>Use Default</option>
     <option value="1" <?php if (($line['regen']%3)==1) { echo 'selected="1"';}?>>Yes, new version on reattempt</option>
     <option value="2" <?php if (($line['regen']%3)==2) { echo 'selected="1"';}?>>No, same version on reattempt</option>
    </select></span><br class="form"/>

<span class="form">Allow &quot;Try similar problem&quot;?</span>
<span class=formright>
    <select name="allowregen">
     <option value="0" <?php if ($line['regen']<3) { echo 'selected="1"';}?>>Use Default</option>
     <option value="1" <?php if ($line['regen']>=3) { echo 'selected="1"';}?>>No</option>
</select></span><br class="form"/>

<span class=form>Show Answers</span><span class=formright>
    <select name="showans">
     <option value="0" <?php if ($line['showans']=='0') { echo 'selected="1"';}?>>Use Default</option>
     <option value="N" <?php if ($line['showans']=='N') { echo 'selected="1"';}?>>Never during assessment</option>
     <option value="F" <?php if ($line['showans']=='F') { echo 'selected="1"';}?>>Show answer after last attempt</option>
     <option value="1" <?php if ($line['showans']=="1") {echo "SELECTED";} ?>>After 1 attempt</option>
     <option value="2" <?php if ($line['showans']=="2") {echo "SELECTED";} ?>>After 2 attempts</option>
     <option value="3" <?php if ($line['showans']=="3") {echo "SELECTED";} ?>>After 3 attempts</option>
     <option value="4" <?php if ($line['showans']=="4") {echo "SELECTED";} ?>>After 4 attempts</option>
     <option value="5" <?php if ($line['showans']=="5") {echo "SELECTED";} ?>>After 5 attempts</option>
     <option value="6" <?php if ($line['showans']=="6") {echo "SELECTED";} ?>>After 6 attempts</option>
     <option value="7" <?php if ($line['showans']=="7") {echo "SELECTED";} ?>>After 7 attempts</option>

    </select></span><br class="form"/>

<span class=form>Show hints and video/text buttons?</span><span class=formright>
    <select name="showhints">
     <option value="0" <?php if ($line['showhints']==0) { echo 'selected="1"';}?>>Use Default</option>
     <option value="1" <?php if ($line['showhints']==1) { echo 'selected="1"';}?>>No</option>
     <option value="2" <?php if ($line['showhints']==2) { echo 'selected="1"';}?>>Yes</option>
    </select></span><br class="form"/>

<span class=form>Use Scoring Rubric</span><span class=formright>
<?php
    writeHtmlSelect('rubric',$rubric_vals,$rubric_names,$line['rubric']);
    echo " <a href=\"addrubric.php?cid=$cid&amp;id=new&amp;from=modq&amp;aid=$aid&amp;qid={$_GET['id']}\">Add new rubric</a> ";
    echo "| <a href=\"addrubric.php?cid=$cid&amp;from=modq&amp;aid=$aid&amp;qid={$_GET['id']}\">Edit rubrics</a> ";
?>
    </span><br class="form"/>
<?php
	if (isset($_GET['qsetid'])) { //adding new question
		echo "<span class=form>Number of copies of question to add:</span><span class=formright><input type=text size=4 name=copies value=\"1\"/></span><br class=form />";
	} else if (!$beentaken) {
		echo "<span class=form>Number, if any, of additional copies to add to assessment:</span><span class=formright><input type=text size=4 name=copies value=\"0\"/></span><br class=form />";
	}

	if ($beentaken) {
		echo '<span class="form"><a href="#" onclick="$(this).hide();$(\'#advanced\').show();return false">Advanced</a></span><br class="form"/>';
		echo '<div id="advanced" style="display:none;">';
		echo '<span class="form">Replace this question with question ID: <br/>';
		echo '<span style="color:red">WARNING: This is NOT recommended. It will mess up the question for any student who has already attempted it, and any work they have done may look garbled when you view it</span></span>';
		echo '<span class="formright"><input size="7" name="replacementid"/></span><br class="form"/>';
		echo '</div>';
	}

	echo '<div class="submit"><input type="submit" value="'._('Save Settings').'"></div>';
}

require("../footer.php");
?>
