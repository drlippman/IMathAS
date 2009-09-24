<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Mass Change Assessment Settings";

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> &gt; Mass Change Assessment Settings";

	// SECURITY CHECK DATA PROCESSING
if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {
	$cid = $_GET['cid'];
	
	if (isset($_POST['checked'])) { //if the form has been submitted
		$checked = $_POST['checked'];
		$checkedlist = "'".implode("','",$checked)."'";

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
		if (isset($_POST['chgallowlate'])) {
			if (isset($_POST['allowlate'])) {
				$allowlate = 1;
			} else {
				$allowlate = 0;
			}
		}
		if (isset($_POST['chghints'])) {
			if (isset($_POST['showhints'])) {
				$showhints = 1;
			} else {
				$showhints = 0;
			}
		}
		if ($_POST['skippenalty']==10) {
			$_POST['defpenalty'] = 'L'.$_POST['defpenalty'];
		} else if ($_POST['skippenalty']>0) {
			$_POST['defpenalty'] = 'S'.$_POST['skippenalty'].$_POST['defpenalty'];
		}
		if ($_POST['deffeedback']=="Practice" || $_POST['deffeedback']=="Homework") {
			$deffeedback = $_POST['deffeedback'].'-'.$_POST['showansprac'];
		} else {
			$deffeedback = $_POST['deffeedback'].'-'.$_POST['showans'];
		}

		$sets = array();
		if (isset($_POST['chgtimelimit'])) {
			$timelimit = $_POST['timelimit']*60;
			if (isset($_POST['timelimitkickout'])) {
				$timelimit = -1*$timelimit;
			}
			$sets[] = "timelimit='$timelimit'";
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
			$sets[] = "deffeedback='$deffeedback'";
		}
		if (isset($_POST['chggbcat'])) {
			$sets[] = "gbcategory='{$_POST['gbcat']}'";
		}
		if (isset($_POST['chgallowlate'])) {
			$sets[] = "allowlate='$allowlate'";
		}
		if (isset($_POST['chgexcpen'])) {
			$sets[] = "exceptionpenalty='{$_POST['exceptionpenalty']}'";
		}
		if (isset($_POST['chgpassword'])) {
			$sets[] = "password='{$_POST['password']}'";
		}
		if (isset($_POST['chghints'])) {
			$sets[] = "showhints='$showhints'";
		}
		if (isset($_POST['chgisgroup'])) {
			$sets[] = "isgroup='{$_POST['isgroup']}'";
		}
		if (isset($_POST['chggroupmax'])) {
			$sets[] = "groupmax='{$_POST['groupmax']}'";
		}
		if (isset($_POST['chgcntingb'])) {
			$sets[] = "cntingb='{$_POST['cntingb']}'";
		}
		if (isset($_POST['chgminscore'])) {
			$sets[] = "minscore='{$_POST['minscore']}'";
		}
		if (isset($_POST['chgshowqcat'])) {
			$sets[] = "showcat='{$_POST['showqcat']}'";
		}
		
		if (isset($_POST['chgintro'])) {
			$query = "SELECT intro FROM imas_assessments WHERE id='{$_POST['intro']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$sets[] = "intro='".addslashes(mysql_result($result,0,0))."'";
		}
		if (isset($_POST['chgdates'])) {
			$query = "SELECT startdate,enddate,reviewdate FROM imas_assessments WHERE id='{$_POST['dates']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$sets[] = "startdate='{$row[0]}',enddate='{$row[1]}',reviewdate='{$row[2]}'";
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
		
		if (isset($_POST['removeperq'])) {
			$query = "UPDATE imas_questions SET points=9999,attempts=9999,penalty=9999,regen=0,showans=0 WHERE assessmentid IN ($checkedlist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}

		if (isset($_POST['chgendmsg'])) {
			include("assessendmsg.php");
		} else {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		}
		exit;
		 
	} else { //DATA MANIPULATION FOR INITIAL LOAD
		$line['timelimit'] = 0;
		$line['displaymethod']= "SkipAround";
		$line['defpoints'] = 10;
		$line['defattempts'] = 1;
		//$line['deffeedback'] = "AsGo";
		$testtype = "AsGo";
		$showans = "A";
		$skippenalty = 0;
		$line['defpenalty'] = 10;
		$line['shuffle'] = 0;
		
		$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)==0) {
			$page_assessListMsg = "<li>No Assessments to change</li>\n";
		} else {
			$page_assessListMsg = "";
			$i=0;
			$page_assessSelect = array();
			while ($row = mysql_fetch_row($result)) {
				$page_assessSelect['val'][$i] = $row[0];
				$page_assessSelect['label'][$i] = $row[1];
				$i++;
			}
		}	
		
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$i=0;
		$page_gbcatSelect = array();
		if (mysql_num_rows($result)>0) {
			while ($row = mysql_fetch_row($result)) {
				$page_gbcatSelect['val'][$i] = $row[0];
				$page_gbcatSelect['label'][$i] = $row[1];
				$i++;
			}
		}	

		
	}
}
	
/******* begin html output ********/
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
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

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<h2>Mass Change Assessment Settings 
		<img src="<?php echo $imasroot ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/>
	</h2>

	<p>This form will allow you to change the assessment settings for several or all assessments at once.
	 <b>Beware</b> that changing default points or penalty after an assessment has been 
	 taken will not change the scores of students who have already completed the assessment.</p>

	<form method=post action="chgassessments.php?cid=<?php echo $cid; ?>">
		<h3>Assessments to Change</h3>

		Check/Uncheck All: 
		<input type="checkbox" name="ca" value="1" onClick="chkAll(this.form, 'checked[]', this.checked)" checked=checked>
		<ul class=nomark>
<?php
	echo $page_assessListMsg;
	for ($i=0;$i<count($page_assessSelect['val']);$i++) {
?>
			<li><input type=checkbox name='checked[]' value='<?php echo $page_assessSelect['val'][$i] ?>' checked=checked><?php echo $page_assessSelect['label'][$i] ?></li>
<?php
	}
?>
		</ul>

		<fieldset>
		<legend>Assessment Options</legend>
		<table class=gb>
			<thead>
			<tr><th>Change?</th><th>Option</th><th>Setting</th></tr>
			</thead>
			<tbody>
			<tr>
				<td><input type="checkbox" name="chgintro"/></td>
				<td class="r">Instructions:</td>
				<td>Copy from: 
<?php
	writeHtmlSelect("intro",$page_assessSelect['val'],$page_assessSelect['label']);
?>

				</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgdates"/></td>
				<td class="r">Dates:</td>
				<td>Copy from:
<?php
	writeHtmlSelect("dates",$page_assessSelect['val'],$page_assessSelect['label']);
?>
				</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgpassword"/></td>
				<td class="r">Require Password (blank for none):</td>
				<td><input type=text name=password value=""></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgtimelimit"/></td>
				<td class="r">Time Limit (minutes, 0 for no time limit): </td>
				<td><input type=text size=4 name="timelimit" value="0" />
				   <input type="checkbox" name="timelimitkickout" /> Kick student out at timelimit
				   </td>
			</tr>

			<tr>
				<td><input type="checkbox" name="chgdisplaymethod"/></td>
				<td class="r">Display method: </td>
				<td>
				<select name="displaymethod">
					<option value="AllAtOnce">Full test at once</option>
					<option value="OneByOne">One question at a time</option>
					<option value="Seq">Full test, submit one at time</option>
					<option value="SkipAround" SELECTED>Skip Around</option>
				</select>
				</td>
			</tr>

			<tr>
				<td><input type="checkbox" name="chgdefpoints"/></td>
				<td class="r">Default points per problem: </td>
				<td><input type=text size=4 name=defpoints value="<?php echo $line['defpoints'];?>" ></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgdefattempts"/></td>
				<td class="r">Default attempts per problem (0 for unlimited): </td>
				<td>
					<input type=text size=4 name=defattempts value="<?php echo $line['defattempts'];?>" >
 					<input type=checkbox name="reattemptsdiffver" />
						Reattempts different versions
				</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgdefpenalty"/></td>
				<td class="r">Default penalty:</td>
				<td><input type=text size=4 name=defpenalty value="<?php echo $line['defpenalty'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>>% 
   					<select name="skippenalty" <?php if ($taken) {echo 'disabled=disabled';}?>>
						<option value="0">per missed attempt</option>
						<option value="1">per missed attempt, after 1</option>
					    <option value="2">per missed attempt, after 2</option>
					    <option value="3">per missed attempt, after 3</option>
					    <option value="4">per missed attempt, after 4</option>
					    <option value="5">per missed attempt, after 5</option>
					    <option value="6">per missed attempt, after 6</option>
					    <option value="10">on last possible attempt only</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgfeedback"/></td>
				<td class="r">Feedback method: </td>
				<td>
					<select id="deffeedback" name="deffeedback" onChange="chgfb()" >
						<option value="NoScores">No scores shown (use with 1 attempt per problem)</option>
						<option value="EndScore" >Just show final score (total points & average) - only whole test can be reattemped</option>
						<option value="EachAtEnd">Show score on each question at the end of the test </option>
						<option value="AsGo" SELECTED >Show score on each question as it's submitted (does not apply to Full test at once display)</option>
						<option value="Practice">Practice test: Show score on each question as it's submitted & can restart test; scores not saved</option>
						<option value="Homework">Homework: Show score on each question as it's submitted & allow similar question to replace missed question</option>
					</select>
				</td>
			</tr>
			<tr>
				<td></td>
				<td class="r">and Show Answers: </td>
				<td>
					<span id="showanspracspan" class="<?php echo ($testtype=="Practice" || $testtype=="Homework") ? "show" : "hidden"; ?>">
					<select name="showansprac">
						<option value="V">Never, but allow students to review their own answers</option>
						<option value="N" >Never, and don't allow students to review their own answers</option>
						<option value="F">After last attempt (Skip Around only)</option>
						<option value="J">After last attempt or Jump to Ans button (Skip Around only)</option>
						<option value="0" >Always</option>
						<option value="1" >After 1 attempt</option>
						<option value="2" >After 2 attempts</option>
						<option value="3" >After 3 attempts</option>
						<option value="4" >After 4 attempts</option>
						<option value="5" >After 5 attempts</option>
					</select>
					</span>
					<span id="showansspan" class="<?php echo ($testtype!="Practice" && $testtype!="Homework") ? "show" : "hidden"; ?>">
					<select name="showans">
						<option value="V" >Never, but allow students to review their own answers</option>
						<option value="N" >Never, and don't allow students to review their own answers</option>
						<option value="I">Immediately (in gradebook) - don't use if allowing multiple attempts per problem</option>
						<option value="F" >After last attempt (Skip Around only)</option>
						<option value="J">After last attempt or Jump to Ans button (Skip Around only)</option>
						<option value="A" SELECTED>After due date (in gradebook)</option>
					</select>
					</span>
				</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chghints"/></td>
				<td class="r">Show hints when available? </td>
				<td><input type="checkbox" name="showhints" checked="checked"></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgallowlate"/></td>
				<td class="r">Allow use of LatePasses?: </td>
				<td><input type="checkbox" name="allowlate" checked="1"></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgshuffle"/></td>
				<td class="r">Shuffle item order: </td>
				<td><input type="checkbox" name="shuffle"></td>
			</tr>
			
			
			<tr>
				<td><input type="checkbox" name="chggbcat"/></td>
				<td class="r">Gradebook category: </td>
				<td>
<?php 
writeHtmlSelect ("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],null,"Default",0," id=gbcat");
?>

				</td>
			</tr>
			<tr>
				<td style="border-bottom: 1px solid #000"><input type="checkbox" name="chgcntingb"/></td>
				<td class="r" style="border-bottom: 1px solid #000">Count: </td>
				<td style="border-bottom: 1px solid #000"><input name="cntingb" value="1" checked="checked" type="radio"> Count in Gradebook<br>
				<input name="cntingb" value="0" type="radio"> Don't count in grade total and hide from students<br>
				<input name="cntingb" value="3" type="radio"> Don't count in grade total<br>
				<input name="cntingb" value="2" type="radio"> Count as Extra Credit
				</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgminscore"/></td>
				<td class="r">Minimum score to receive credit: </td>
				<td><input type="text" name="minscore" size=4 value="0"> % </td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgsameseed"/></td>
				<td class="r">All items same random seed: </td>
				<td><input type="checkbox" name="sameseed"></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="chgsamever"/></td>
				<td class="r">All students same version of questions: </td>
				<td><input type="checkbox" name="samever"></td>
			</tr>
			
			<tr>
				<td><input type="checkbox" name="chgexcpen"/></td>
				<td class="r">Penalty for questions done while in exception/LatePass: </td>
				<td><input type="text" name="exceptionpenalty" size=4 value="0"> % </td>
			</tr>
			
			<tr>
				<td><input type="checkbox" name="chgisgroup"/></td>
				<td class="r">Group assessment: </td>
				<td><input type="radio" name="isgroup" value="0" checked="checked" />Not a group assessment<br/>
				<input type="radio" name="isgroup" value="1"  />Students can add members with login passwords<br/>
				<input type="radio" name="isgroup" value="2"  />Students can add members without passwords<br/>
				<input type="radio" name="isgroup" value="3"  />Students cannot add members</td>
			</tr>
			
			<tr>
				<td><input type="checkbox" name="chggroupmax"/></td>
				<td class="r">Max group members (if group assessment):</td>
				<td><input type=text name=groupmax value="6"></td>
			</tr>
			<tr>
				<td style="border-bottom: 1px solid #000"><input type="checkbox" name="chgshowqcat"/></td>
				<td class="r" style="border-bottom: 1px solid #000">Show question categories: </td>
				<td style="border-bottom: 1px solid #000"><input name="showqcat" value="0" checked="checked" type="radio">No <br/>
				<input name="showqcat" value="1" type="radio">In Points Possible bar <br/>
				<input name="showqcat" value="2" type="radio">In navigation bar (Skip-Around only)
				</td>
			</tr>
			<tr>	
				<td></td>
				<td class="r">Define end of assessment messages?</td>
				<td><input type="checkbox" name="chgendmsg" /> You will be taken to a page to change these after you hit submit</td>
			</tr>
			<tr>
				<td></td>
				<td class="r">Remove per-question settings (points, attempts, etc.) for all questions in these assessments?</td>
				<td><input type="checkbox" name="removeperq" /></td>
			</tr>
		</tbody>
		</table>
	</fieldset>
	<div class=submit><input type=submit value=Submit></div>

<?php
}
require("../footer.php");
?>
