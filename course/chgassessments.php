<?php
//IMathAS:  Mass change assessment settings
//(c) 2006 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	$cid = $_GET['cid'];
	
	if (isset($_POST['checked'])) { //if the form has been submitted
		$checked = $_POST['checked'];
		$checkedlist = "'".implode("','",$checked)."'";
		/*
		if (isset($_POST['shuffle'])) { $shuffle = 1;} else {$shuffle = 0;}
		if (isset($_POST['sameseed'])) { $shuffle += 2;}
		if (isset($_POST['samever'])) { $shuffle += 4;}
		if (isset($_POST['reattemptsdiffver'])) { $shuffle += 8;}
		*/
		$turnonshuffle = 0;
		$turnoffshuffle = 0;
		if (isset($_POST['chgshuffle'])) {
			if (isset($_POST['shuffle'])) {
				$turnonshuffle += 1;
			} else {
				$turnoffshuffle +=1;
			}
		}
		if (isset($_POST['chgsameseed'])) {
			if (isset($_POST['sameseed'])) {
				$turnonshuffle += 2;
			} else {
				$turnoffshuffle +=2;
			}
		}
		if (isset($_POST['chgsamever'])) {
			if (isset($_POST['samever'])) {
				$turnonshuffle += 4;
			} else {
				$turnoffshuffle +=4;
			}
		}
		if (isset($_POST['chgdefattempts'])) {
			if (isset($_POST['reattemptsdiffver'])) {
				$turnonshuffle += 8;
			} else {
				$turnoffshuffle +=8;
			}
		}
		if ($_POST['lastpenalty']==1) {
			$_POST['defpenalty'] = 'L'.$_POST['defpenalty'];
		}
		if ($_POST['deffeedback']=="Practice" || $_POST['deffeedback']=="Homework") {
			$deffeedback = $_POST['deffeedback'].'-'.$_POST['showansprac'];
		} else {
			$deffeedback = $_POST['deffeedback'].'-'.$_POST['showans'];
		}
		/*
		if ($_POST['copyfrom']!=0) {
			$query = "SELECT timelimit,displaymethod,defpoints,defattempts,defpenalty,deffeedback,shuffle FROM imas_assessments WHERE id='{$_POST['copyfrom']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			list($_POST['timelimit'],$_POST['displaymethod'],$_POST['defpoints'],$_POST['defattempts'],$_POST['defpenalty'],$deffeedback,$shuffle) = mysql_fetch_row($result);
		}*/
		$sets = array();
		if (isset($_POST['chgtimelimit'])) {
			$sets[] = "timelimit='{$_POST['timelimit']}'";
		}
		if (isset($_POST['chgdisplaymethod'])) {
			$sets[] = "displaymethod='{$_POST['displaymethod']}'";
		}
		if (isset($_POST['chgdefpoints'])) {
			$sets[] = "defpoints='{$_POST['defpoints']}'";
		}
		if (isset($_POST['chgdefattempts'])) {
			$sets[] = "defattempts='{$_POST['defattempts']}'";
		}
		if (isset($_POST['chgdefpenalty'])) {
			$sets[] = "defpenalty='{$_POST['defpenalty']}'";
		}
		if (isset($_POST['chgfeedback'])) {
			$sets[] = "deffeedback='{$_POST['deffeedback']}'";
		}
		if (isset($_POST['chggbcat'])) {
			$sets[] = "gbcategory='{$_POST['gbcat']}'";
		}
		if ($turnonshuffle!=0 || $turnoffshuffle!=0) {
			$shuff = "shuffle = ((shuffle";
			if ($turnoffshuffle>0) {
				$shuff .= " & ~$turnoffshuffle)";
			} else {
				$shuff .= ")";
			}
			if ($turnonshuffle>0) {
				$shuff .= " | $turnonshuffle";
			}
			$shuff .= ")";
			$sets[] = $shuff;
			
		}
		if (count($sets)>0) {
			$setslist = implode(',',$sets);
			$query = "UPDATE imas_assessments SET $setslist WHERE id IN ($checkedlist);";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		}
		/*
		$query = "UPDATE imas_assessments SET timelimit='{$_POST['timelimit']}',";
		$query .= "displaymethod='{$_POST['displaymethod']}',defpoints='{$_POST['defpoints']}',defattempts='{$_POST['defattempts']}',defpenalty='{$_POST['defpenalty']}',deffeedback='$deffeedback',shuffle='$shuffle' ";
		$query .= "WHERE id IN ($checkedlist);";
		*/
		
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		exit;
		 
	} else {
		$line['timelimit'] = 0;
		$line['displaymethod']= "SkipAround";
		$line['defpoints'] = 10;
		$line['defattempts'] = 1;
		//$line['deffeedback'] = "AsGo";
		$testtype = "AsGo";
		$showans = "A";
		$lastpenalty = false;
		$line['defpenalty'] = 10;
		$line['shuffle'] = 0;
	}
	
	$pagetitle = "Mass Change Assessment Settings";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";	
	echo "&gt; Mass Change Assessment Settings</div>\n";
	echo "<h2>Mass Change Assessment Settings <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	
?>
<p>This form will allow you to change the assessment settings for several or all assessments at once.  <b>Beware</b> that changing default points or 
penalty after an assessment has been taken will not change the scores of students who have already completed the assessment.</p>
<style type="text/css">
span.hidden {
	display: none;
}
span.show {
	display: inline;
}
</style>
<script type="text/javascript">
function chgfb() {
	if (document.getElementById("deffeedback").value=="Practice" || document.getElementById("deffeedback").value=="Homework") {
		document.getElementById("showanspracspan").className = "show";
		document.getElementById("showansspan").className = "hidden";
	} else {
		document.getElementById("showanspracspan").className = "hidden";
		document.getElementById("showansspan").className = "show";
	}
}

function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}

</script>
<form method=post action="chgassessments.php?cid=<?php echo $cid; ?>">
<h3>Assessments to Change</h3>

Check/Uncheck All: <input type="checkbox" name="ca" value="1" onClick="chkAll(this.form, 'checked[]', this.checked)" checked=checked>
<ul class=nomark>
<?php
	$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo "<li>No Assessments to change</li>";
	}
	while ($row = mysql_fetch_row($result)) {
		echo "<li><input type=checkbox name='checked[]' value='{$row[0]}' checked=checked>{$row[1]}</li>\n";
	}		
?>
</ul>

<fieldset><legend>Assessment Options</legend>
<table class=gb><thead><tr><th>Change?</th><th>Option</th><th>Setting</th></tr></thead><tbody>
<tr><td><input type="checkbox" name="chgtimelimit"/></td><td class="r">Time Limit (minutes, 0 for no time limit): </td><td><input type=text size=4 name=timelimit value="<?php echo $line['timelimit'];?>"></td></tr>

<tr><td><input type="checkbox" name="chgdisplaymethod"/></td><td class="r">Display method: </td><td><select name="displaymethod">
	<option value="AllAtOnce" <?php if ($line['displaymethod']=="AllAtOnce") {echo "SELECTED";} ?>>Full test at once</option>
	<option value="OneByOne" <?php if ($line['displaymethod']=="OneByOne") {echo "SELECTED";} ?>>One question at a time</option>
	<option value="SkipAround" <?php if ($line['displaymethod']=="SkipAround") {echo "SELECTED";} ?>>Skip Around</option>
</select></td></tr>

<tr><td><input type="checkbox" name="chgdefpoints"/></td><td class="r">Default points per problem: </td><td><input type=text size=4 name=defpoints value="<?php echo $line['defpoints'];?>" ></td></tr>

<tr><td><input type="checkbox" name="chgdefattempts"/></td><td class="r">Default attempts per problem (0 for unlimited): </td><td><input type=text size=4 name=defattempts value="<?php echo $line['defattempts'];?>" >
 <input type=checkbox name="reattemptsdiffver" <?php if ($line['shuffle']&8) {echo "CHECKED";} ?> />Reattempts different versions</td></tr>


<tr><td><input type="checkbox" name="chgdefpenalty"/></td><td class="r">Default penalty:</td><td><input type=text size=4 name=defpenalty value="<?php echo $line['defpenalty'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>>% 
   <select name="lastpenalty" <?php if ($taken) {echo 'disabled=disabled';}?>>
     <option value="0" <?php if (!$lastpenalty) {echo "selected=1";} ?>>per missed attempt</option>
     <option value="1" <?php if ($lastpenalty) {echo "selected=1";} ?>>on last possible attempt only</option>
     </select></td></tr>


<tr><td><input type="checkbox" name="chgfeedback"/></td><td class="r">Feedback method: </td><td><select id="deffeedback" name="deffeedback" onChange="chgfb()" >
	<option value="NoScores" <?php if ($testtype=="NoScores") {echo "SELECTED";} ?>>No scores shown (use with 1 attempt per problem)</option>
	<option value="EndScore" <?php if ($testtype=="EndScore") {echo "SELECTED";} ?>>Just show final score (total points & average) - only whole test can be reattemped</option>
	<option value="EachAtEnd" <?php if ($testtype=="EachAtEnd") {echo "SELECTED";} ?>>Show score on each question at the end of the test </option>
	<option value="AsGo" <?php if ($testtype=="AsGo") {echo "SELECTED";} ?>>Show score on each question as it's submitted (does not apply to Full test at once display)</option>
	<option value="Practice" <?php if ($testtype=="Practice") {echo "SELECTED";} ?>>Practice test: Show score on each question as it's submitted & can restart test; scores not saved</option>
	<option value="Homework" <?php if ($testtype=="Homework") {echo "SELECTED";} ?>>Homework: Show score on each question as it's submitted & allow similar question to replace missed question</option>
</select></td></tr>

<tr><td></td><td class="r">and Show Answers: </td><td>
<span id="showanspracspan" class="<?php if ($testtype=="Practice" || $testtype=="Homework") {echo "show";} else {echo "hidden";} ?>">
<select name="showansprac">
	<option value="N" <?php if ($showans=="N") {echo "SELECTED";} ?>>Never</option>
	<option value="0" <?php if ($showans=="0") {echo "SELECTED";} ?>>Always</option>
	<option value="1" <?php if ($showans=="1") {echo "SELECTED";} ?>>After 1 attempt</option>
	<option value="2" <?php if ($showans=="2") {echo "SELECTED";} ?>>After 2 attempts</option>
	<option value="3" <?php if ($showans=="3") {echo "SELECTED";} ?>>After 3 attempts</option>
	<option value="4" <?php if ($showans=="4") {echo "SELECTED";} ?>>After 4 attempts</option>
	<option value="5" <?php if ($showans=="5") {echo "SELECTED";} ?>>After 5 attempts</option>
</select>
</span>
<span id="showansspan" class="<?php if ($testtype!="Practice" && $testtype!="Homework") {echo "show";} else {echo "hidden";} ?>">
<select name="showans">
	<option value="N" <?php if ($showans=="N") {echo "SELECTED";} ?>>Never</option>
	<option value="I" <?php if ($showans=="I") {echo "SELECTED";} ?>>Immediately (in gradebook) - don't use if allowing multiple attempts per problem</option>
	<option value="A" <?php if ($showans=="A") {echo "SELECTED";} ?>>After due date (in gradebook)</option>
</select>
</span>
</td></tr>
<tr><td><input type="checkbox" name="chgshuffle"/></td><td class="r">Shuffle item order: </td><td><input type="checkbox" name="shuffle" <?php if ($line['shuffle']&1) {echo "CHECKED";} ?>></td></tr>
<tr><td><input type="checkbox" name="chgsameseed"/></td><td class="r">All items same random seed: </td><td><input type="checkbox" name="sameseed" <?php if ($line['shuffle']&2) {echo "CHECKED";} ?>></td></tr>
<tr><td><input type="checkbox" name="chgsamever"/></td><td class="r">All students same version of questions: </td><td><input type="checkbox" name="samever" <?php if ($line['shuffle']&4) {echo "CHECKED";} ?>></td></tr>
<tr><td><input type="checkbox" name="chggbcat"/></td><td class="r">Gradebook category: </td><td>
<?php
	$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	echo "<select name=gbcat id=gbcat>\n";
	echo "<option value=\"0\">Default</option>\n";
	if (mysql_num_rows($result)>0) {
		while ($row = mysql_fetch_row($result)) {
			echo "<option value=\"{$row[0]}\">{$row[1]}</option>\n";
		}
	}	
	echo "</select>\n";
?>
</td></tr>
</tbody></table>
</fieldset>
<div class=submit><input type=submit value=Submit></div>

<?php

	require("../footer.php");
?>
