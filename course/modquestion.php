<?php
//IMathAS:  Modify a question's settings in an assessment
//(c) 2006 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	if ($_GET['process']== true) {
		if ($_POST['points']=="") {$points=9999;} else {$points = $_POST['points'];}
		if ($_POST['attempts']=="") {$attempts=9999;} else {$attempts = $_POST['attempts'];}
		if ($_POST['penalty']=="") {$penalty=9999;} else {$penalty = $_POST['penalty'];}
		if ($penalty!=9999) {
			if ($_POST['skippenalty']==10) {
				$penalty = 'L'.$penalty;
			} else if ($_POST['skippenalty']>0) {
				$penalty = 'S'.$_POST['skippenalty'].$penalty;
			}
		}
		if (isset($_GET['id'])) { //already have id - updating
			$query = "UPDATE imas_questions SET points='$points',attempts='$attempts',penalty='$penalty' ";
			$query .= "WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if ($_POST['copies']>0) {
				$query = "SELECT questionsetid FROM imas_questions WHERE id='{$_GET['id']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$_GET['qsetid'] = mysql_result($result,0,0);
			}
		} 
		if (isset($_GET['qsetid'])) { //new - adding
			$query = "SELECT itemorder FROM imas_assessments WHERE id='{$_GET['aid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemorder = mysql_result($result,0,0);
			for ($i=0;$i<$_POST['copies'];$i++) {
				$query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,questionsetid) ";
				$query .= "VALUES ('{$_GET['aid']}','$points','$attempts','$penalty','{$_GET['qsetid']}')";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$qid = mysql_insert_id();
				
				//add to itemorder
				if ($itemorder=='') {
					$itemorder = $qid;
				} else {
					$itemorder = $itemorder . ",$qid";	
				}
			}
			$query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id='{$_GET['aid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid={$_GET['cid']}&aid={$_GET['aid']}");
		exit;
	} else {
		$pagetitle = "Question Settings";
		require("../header.php");
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"addquestions.php?aid={$_GET['aid']}&cid={$_GET['cid']}\">Add/Remove Questions</a> &gt; ";
		
		echo "Modify Question Settings</div>\n";
		if (isset($_GET['id'])) {
			$query = "SELECT points,attempts,penalty FROM imas_questions WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
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
		}
	}
?>

<h2>Modify Question</h2> 
<form method=post action="modquestion.php?process=true&<?php echo "cid={$_GET['cid']}&aid={$_GET['aid']}"; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";} if (isset($_GET['qsetid'])) {echo "&qsetid={$_GET['qsetid']}";}?>">
Leave items blank or set to 9999 to use default values<BR>

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

<?php
if (isset($_GET['qsetid'])) { //adding new question
	echo "<span class=form>Number of copies of question to add:</span><span class=formright><input type=text size=4 name=copies value=\"1\"/></span><br class=form />";
} else {
	echo "<span class=form>Number, if any, of additional copies to add to assessment:</span><span class=formright><input type=text size=4 name=copies value=\"0\"/></span><br class=form />";
}
?>

<div class=submit><input type=submit value=Submit></div>

<p><b>Warning</b>: If this assessment has been taken, altering the points or penalty will not change the scores of students who already completed this question.</p> 
<?php
	require("../footer.php");
?>
