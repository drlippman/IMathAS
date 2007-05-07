<?php
//IMathAS:  Add an assessment/ change settings
//(c) 2006 David Lippman
	require("../validate.php");
	
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	$cid = $_GET['cid'];
	$block = $_GET['block'];
	if (isset($_GET['from'])) {
		$from = $_GET['from'];
	} else {
		$from = 'cp';
	}
	
	if (isset($_GET['remove'])) {
		if ($_GET['remove']=="really") {
			$aid = $_GET['id'];
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_questions WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT id FROM imas_items WHERE typeid='$aid' AND itemtype='Assessment'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemid = mysql_result($result,0,0);
			$query = "DELETE FROM imas_items WHERE id='$itemid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_assessments WHERE id='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$items = unserialize(mysql_result($result,0,0));
			
			$blocktree = explode('-',$block);
			$sub =& $items;
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
			$key = array_search($itemid,$sub);
			array_splice($sub,$key,1);
			$itemorder = addslashes(serialize($items));
			$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
			
			exit;
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; Modify Assessment</div>\n";
			echo "Are you SURE you want to delete this assessment and all associated student attempts?";
			echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='addassessment.php?cid={$_GET['cid']}&block=$block&id={$_GET['id']}&remove=really'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='course.php?cid={$_GET['cid']}'\"></p>\n";
			require("../footer.php");
			exit;
		}
	} else if (isset($_GET['clearattempts'])) {
		if ($_GET['clearattempts']=="confirmed") {
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; <a href=\"addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}\">Modify Assessment</a> &gt; Clear Attempts</div>\n";
			echo "Are you SURE you want to delete all attempts (grades) for this assessment?";
			echo "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}&clearattempts=confirmed'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	
	if ($_POST['name']!= null) { //if the form has been submitted
		
		require_once("parsedatetime.php");
		if ($_POST['sdatetype']=='0') {
			$startdate = 0;
		} else {
			$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		}
		if ($_POST['edatetype']=='2000000000') {
			$enddate = 2000000000;
		} else {
			$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
		}
	
		if ($_POST['doreview']=='0') {
			$reviewdate = 0;
		} else if ($_POST['doreview']=='2000000000') {
			$reviewdate = 2000000000;
		} else {
			$reviewdate = parsedatetime($_POST['rdate'],$_POST['rtime']);	
		} 
		
		if (isset($_POST['shuffle'])) { $shuffle = 1;} else {$shuffle = 0;}
		if (isset($_POST['sameseed'])) { $shuffle += 2;}
		if (isset($_POST['samever'])) { $shuffle += 4;}
		if (isset($_POST['reattemptsdiffver'])) { $shuffle += 8;}
		
		$isgroup = $_POST['isgroup'];
		
		if (isset($_POST['showhints'])) {
			$showhints = 1;
		} else {
			$showhints = 0;
		}
		
		$timelimit = $_POST['timelimit']*60;
		
		if ($_POST['deffeedback']=="Practice" || $_POST['deffeedback']=="Homework") {
			$deffeedback = $_POST['deffeedback'].'-'.$_POST['showansprac'];
		} else {
			$deffeedback = $_POST['deffeedback'].'-'.$_POST['showans'];
		}
		
		if ($_POST['skippenalty']==10) {
			$_POST['defpenalty'] = 'L'.$_POST['defpenalty'];
		} else if ($_POST['skippenalty']>0) {
			$_POST['defpenalty'] = 'S'.$_POST['skippenalty'].$_POST['defpenalty'];
		}
		if ($_POST['copyfrom']!=0) {
			$query = "SELECT timelimit,displaymethod,defpoints,defattempts,defpenalty,deffeedback,shuffle,gbcategory,password,showcat,intro,startdate,enddate,reviewdate,isgroup,showhints,reqscore,reqscoreaid FROM imas_assessments WHERE id='{$_POST['copyfrom']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			list($timelimit,$_POST['displaymethod'],$_POST['defpoints'],$_POST['defattempts'],$_POST['defpenalty'],$deffeedback,$shuffle,$_POST['gbcat'],$_POST['password'],$_POST['showqcat'],$cpintro,$cpstartdate,$cpenddate,$cpreviewdate,$isgroup,$showhints,$_POST['reqscore'],$_POST['reqscoreaid']) = mysql_fetch_row($result);
			if (isset($_POST['copyinstr'])) {
				$_POST['intro'] = addslashes($cpintro);
			}
			if (isset($_POST['copydates'])) {
				$startdate = $cpstartdate;
				$enddate = $cpenddate;
				$reviewdate = $cpreviewdate;
			}
		}
		if ($_POST['deffeedback']=="Practice") {
			$_POST['cntingb'] = 1;
		}
		if (isset($_GET['id'])) {  //already have id; update
			$query = "SELECT isgroup FROM imas_assessments WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0)>0 && $isgroup==0) {
				$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE assessmentid='{$_GET['id']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			if (isset($_POST['defpoints'])) {
				$query = "UPDATE imas_assessments SET name='{$_POST['name']}',summary='{$_POST['summary']}',intro='{$_POST['intro']}',startdate=$startdate,enddate=$enddate,reviewdate=$reviewdate,timelimit='$timelimit',minscore='{$_POST['minscore']}',isgroup='$isgroup',showhints='$showhints',";
				$query .= "displaymethod='{$_POST['displaymethod']}',defpoints='{$_POST['defpoints']}',defattempts='{$_POST['defattempts']}',defpenalty='{$_POST['defpenalty']}',deffeedback='$deffeedback',shuffle='$shuffle',gbcategory='{$_POST['gbcat']}',password='{$_POST['password']}',cntingb='{$_POST['cntingb']}',showcat='{$_POST['showqcat']}',reqscore='{$_POST['reqscore']}',reqscoreaid='{$_POST['reqscoreaid']}' ";
				$query .= "WHERE id='{$_GET['id']}';";
			} else { //has been taken - not updating "don't change" settings
				$query = "UPDATE imas_assessments SET name='{$_POST['name']}',summary='{$_POST['summary']}',intro='{$_POST['intro']}',startdate=$startdate,enddate=$enddate,reviewdate=$reviewdate,timelimit='$timelimit',minscore='{$_POST['minscore']}',isgroup='$isgroup',showhints='$showhints',";
				$query .= "displaymethod='{$_POST['displaymethod']}',defattempts='{$_POST['defattempts']}',deffeedback='$deffeedback',shuffle='$shuffle',gbcategory='{$_POST['gbcat']}',password='{$_POST['password']}',cntingb='{$_POST['cntingb']}',showcat='{$_POST['showqcat']}',reqscore='{$_POST['reqscore']}',reqscoreaid='{$_POST['reqscoreaid']}' ";
				$query .= "WHERE id='{$_GET['id']}';";
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if ($from=='gb') {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid={$_GET['cid']}");
			} else if ($from=='mcd') {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/masschgdates.php?cid={$_GET['cid']}");
			} else {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
			}
			exit;
		} else { //add new
									
			$query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,";
			$query .= "displaymethod,defpoints,defattempts,defpenalty,deffeedback,shuffle,gbcategory,password,cntingb,showcat,isgroup,showhints,reqscore,reqscoreaid) VALUES ";
			$query .= "('$cid','{$_POST['name']}','{$_POST['summary']}','{$_POST['intro']}',$startdate,$enddate,$reviewdate,'$timelimit','{$_POST['minscore']}',";
			$query .= "'{$_POST['displaymethod']}','{$_POST['defpoints']}','{$_POST['defattempts']}',";
			$query .= "'{$_POST['defpenalty']}','$deffeedback','$shuffle','{$_POST['gbcat']}','{$_POST['password']}','{$_POST['cntingb']}','{$_POST['showqcat']}','$isgroup','$showhints','{$_POST['reqscore']}','{$_POST['reqscoreaid']}');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$newaid = mysql_insert_id();
			
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "('$cid','Assessment','$newaid');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$itemid = mysql_insert_id();
						
			$query = "SELECT itemorder FROM imas_courses WHERE id='$cid';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$items = unserialize($line['itemorder']);
			
			$blocktree = explode('-',$block);
			$sub =& $items;
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
			$sub[] = $itemid;
			$itemorder = addslashes(serialize($items));
			
			$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid={$_GET['cid']}&aid=$newaid");
			exit;
		}
		
		
	} else {
		if (isset($_GET['id'])) {
			$query = "SELECT COUNT(ias.id) FROM imas_assessment_sessions AS ias,imas_students WHERE ";
			$query .= "ias.assessmentid='{$_GET['id']}' AND ias.userid=imas_students.userid AND imas_students.courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : $query; " . mysql_error());
			$taken = (mysql_result($result,0,0)>0);
			$query = "SELECT * FROM imas_assessments WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			list($testtype,$showans) = explode('-',$line['deffeedback']);
			$startdate = $line['startdate'];
			$enddate = $line['enddate'];
			$gbcat = $line['gbcategory'];
			$cntingb = $line['cntingb'];
			$showqcat = $line['showcat'];
			$timelimit = $line['timelimit']/60;
		} else {
			//set defaults
			$line['name'] = "Enter assessment name";
			$line['summary'] = "<p>Enter summary here (shows on course page)</p>";
			$line['intro'] = "<p>Enter intro/instructions</p>";
			$startdate = time()+60*60;
			$enddate = time() + 7*24*60*60;
			$line['reviewdate'] = 0;
			$timelimit = 0;
			$line['displaymethod']= "SkipAround";
			$line['defpoints'] = 10;
			$line['defattempts'] = 1;
			$line['password'] = '';
			//$line['deffeedback'] = "AsGo";
			$testtype = "AsGo";
			$showans = "A";
			$line['defpenalty'] = 10;
			$line['shuffle'] = 0;
			$line['minscore'] = 0;
			$line['isgroup'] = 0;
			$line['showhints']=1;
			$line['reqscore'] = 0;
			$line['reqscoreaid'] = 0;
			$gbcat = 0;
			$cntingb = 1;
			$showqcat = 0;
		}
		if ($startdate!=0) {
			$sdate = tzdate("m/d/Y",$startdate);
			$stime = tzdate("g:i a",$startdate);
		} else {
			$sdate = tzdate("m/d/Y",time());
			$stime = tzdate("g:i a",time());
		}
		if ($enddate!=2000000000) {
			$edate = tzdate("m/d/Y",$enddate);
			$etime = tzdate("g:i a",$enddate);	
		} else {
			$edate = tzdate("m/d/Y",time()+7*24*60*60);
			$etime = tzdate("g:i a",time()+7*24*60*60);
		}
		
		if ($line['reviewdate'] > 0) {
			if ($line['reviewdate']=='2000000000') {
				$rdate = tzdate("m/d/Y",$line['enddate']+7*24*60*60);
				$rtime = tzdate("g:i a",$line['enddate']+7*24*60*60);
			} else {
				$rdate = tzdate("m/d/Y",$line['reviewdate']);
				$rtime = tzdate("g:i a",$line['reviewdate']);
			}
		} else {
			$rdate = tzdate("m/d/Y",$line['enddate']+7*24*60*60);
			$rtime = tzdate("g:i a",$line['enddate']+7*24*60*60);
		}
		if ($line['defpenalty']{0}==='L') {
			$line['defpenalty'] = substr($line['defpenalty'],1);
			$skippenalty=10;
		} else if ($line['defpenalty']{0}==='S') {
			$skippenalty = $line['defpenalty']{1};
			$line['defpenalty'] = substr($line['defpenalty'],2);
		} else {
			$skippenalty = 0;
		}
	}
	$useeditor = "summary,intro";
	$pagetitle = "Assessment Settings";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	if ($from=='gb') {
		echo "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> ";
	} else if ($from=='mcd') {
		echo "&gt; <a href=\"masschgdates.php?cid=$cid\">Mass Change Dates</a> ";
	} 
	
	if (isset($_GET['id'])) {
		echo "&gt; Modify Assessment</div>\n";
		echo "<h2>Modify Assessment <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	} else {
		echo "&gt; Add Assessment</div>\n";
		echo "<h2>Add Assessment <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	}
	
	if ($taken) {
		echo "<p>This assessment has already been taken.  Modifying some settings will mess up those assessment attempts, and those inputs ";
		echo "have been disabled.  If you want to change these settings, you should clear all existing assessment attempts</p>\n";
		echo "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}&clearattempts=ask'\"></p>\n";
	}
?>
<style type="text/css">
span.hidden {
	display: none;
}
span.show {
	display: inline;
}
</style>
<script>
function chgfb() {
	if (document.getElementById("deffeedback").value=="Practice" || document.getElementById("deffeedback").value=="Homework") {
		document.getElementById("showanspracspan").className = "show";
		document.getElementById("showansspan").className = "hidden";
	} else {
		document.getElementById("showanspracspan").className = "hidden";
		document.getElementById("showansspan").className = "show";
	}
	if (document.getElementById("deffeedback").value=="Practice") {
		document.getElementById("stdcntingb").className = "hidden";
		document.getElementById("praccntingb").className = "formright";
	} else {
		document.getElementById("stdcntingb").className = "formright";
		document.getElementById("praccntingb").className = "hidden";
	}
}
function chgcopyfrom() {
	if (document.getElementById('copyfrom').value==0) {
		document.getElementById('customoptions').className="show";
		document.getElementById('copyfromoptions').className="hidden";
	} else {
		document.getElementById('customoptions').className="hidden";
		document.getElementById('copyfromoptions').className="show";
	}
}
</script>
<form method=post action="addassessment.php?block=<?php echo $block;?>&cid=<?php echo $cid; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";}?>&folder=<?php echo $_GET['folder'];?>&from=<?php echo $_GET['from'];?>">
<span class=form>Assessment Name:</span><span class=formright><input type=text size=30 name=name value="<?php echo $line['name'];?>"></span><BR class=form>

Summary:<BR>
<div class=editor><textarea cols=50 rows=15 id=summary name=summary style="width: 100%"><?php echo $line['summary'];?></textarea></div>
<BR>
Intro/Instructions:<BR>
<div class=editor><textarea cols=50 rows=20 id=intro name=intro style="width: 100%"><?php echo $line['intro'];?></textarea></div>
<BR>

<script src="../javascript/CalendarPopup.js"></script>
<SCRIPT LANGUAGE="JavaScript" ID="js1">
var cal1 = new CalendarPopup();
</SCRIPT>

<span class=form>Available After:</span><span class=formright><input type=radio name="sdatetype" value="0" <?php if ($startdate=='0') {echo "checked=1";}?>/> Always until end date<br/>
<input type=radio name="sdatetype" value="sdate" <?php if ($startdate!='0') {echo "checked=1";}?>/><input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
<A HREF="#" onClick="cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;" NAME="anchor1" ID="anchor1"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=stime value="<?php echo $stime;?>"></span><BR class=form>

<span class=form>Available Until:</span><span class=formright>
<input type=radio name="edatetype" value="2000000000" <?php if ($enddate=='2000000000') {echo "checked=1";}?>/> Always after start date<br/>
<input type=radio name="edatetype" value="edate"  <?php if ($enddate!='2000000000') {echo "checked=1";}?>/>
<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
<A HREF="#" onClick="cal1.select(document.forms[0].edate,'anchor2','MM/dd/yyyy',(document.forms[0].sdate.value=='<?php echo $sdate;?>')?(document.forms[0].edate.value):(document.forms[0].sdate.value)); return false;" NAME="anchor2" ID="anchor2"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=etime value="<?php echo $etime;?>"></span><BR class=form>

<span class=form>Keep open as review:</span><span class=formright>
<input type=radio name="doreview" value="0" <?php if ($line['reviewdate']==0) { echo "checked=1";} ?>> Never<br/>
<input type=radio name="doreview" value="2000000000" <?php if ($line['reviewdate']==2000000000) { echo "checked=1";} ?>> Always after due date<br/>
<input type=radio name="doreview" value="rdate" <?php if ($line['reviewdate']>0 && $line['reviewdate']<2000000000) { echo "checked=1";} ?>> Until: 
<input type=text size=10 name=rdate value="<?php echo $rdate;?>"> 
<A HREF="#" onClick="cal1.select(document.forms[0].rdate,'anchor3','MM/dd/yyyy',(document.forms[0].rdate.value=='<?php echo $rdate;?>')?(document.forms[0].rdate.value):(document.forms[0].rdate.value)); return false;" NAME="anchor3" ID="anchor3"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=rtime value="<?php echo $rtime;?>"></span><BR class=form>



<fieldset><legend>Assessment Options</legend>
<?php
	$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$aidarr = array();
	if (mysql_num_rows($result)>0) {
		echo "<span class=form>Copy Options from:</span><span class=formright><select name=copyfrom id=copyfrom onChange=\"chgcopyfrom()\">\n";
		echo "<option value=\"0\">None - use settings below</option>\n";
		while ($row = mysql_fetch_row($result)) {
			echo "<option value=\"{$row[0]}\">{$row[1]}</option>\n";
			$aidarr[$row[0]] = $row[1];
		}
		echo "</select></span><br class=form>\n";
		
	}	
?>
<div id="copyfromoptions" class="hidden">
<span class=form>Also copy:</span><span class=formright><input type=checkbox name="copyinstr" /> Instructions<br/>
      <input type=checkbox name="copydates" /> Dates</span><br class=form />
</div>
<div id="customoptions" class="show">

<fieldset><legend>Core Options</legend>
<span class=form>Require Password (blank for none):</span><span class=formright><input type=text name=password value="<?php echo $line['password'];?>"></span><br class=form />

<span class=form>Time Limit (minutes, 0 for no time limit): </span><span class=formright><input type=text size=4 name=timelimit value="<?php echo $timelimit;?>"></span><BR class=form>

<span class=form>Display method: </span><span class=formright><select name="displaymethod">
	<option value="AllAtOnce" <?php if ($line['displaymethod']=="AllAtOnce") {echo "SELECTED";} ?>>Full test at once</option>
	<option value="OneByOne" <?php if ($line['displaymethod']=="OneByOne") {echo "SELECTED";} ?>>One question at a time</option>
	<option value="Seq" <?php if ($line['displaymethod']=="Seq") {echo "SELECTED";} ?>>Full test, submit one at time</option>
	<option value="SkipAround" <?php if ($line['displaymethod']=="SkipAround") {echo "SELECTED";} ?>>Skip Around</option>
</select></span><BR class=form>

<span class=form>Default points per problem: </span><span class=formright><input type=text size=4 name=defpoints value="<?php echo $line['defpoints'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>></span><BR class=form>

<span class=form>Default attempts per problem (0 for unlimited): </span><span class=formright><input type=text size=4 name=defattempts value="<?php echo $line['defattempts'];?>" > 
 <input type=checkbox name="reattemptsdiffver" <?php if ($line['shuffle']&8) {echo "CHECKED";} ?> />Reattempts different versions</span><BR class=form>

<span class=form>Default penalty:</span><span class=formright><input type=text size=4 name=defpenalty value="<?php echo $line['defpenalty'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>>% 
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

<span class=form>Feedback method: </span><span class=formright><select id="deffeedback" name="deffeedback" onChange="chgfb()" >
	<option value="NoScores" <?php if ($testtype=="NoScores") {echo "SELECTED";} ?>>No scores shown (use with 1 attempt per problem)</option>
	<option value="EndScore" <?php if ($testtype=="EndScore") {echo "SELECTED";} ?>>Just show final score (total points & average) - only whole test can be reattemped</option>
	<option value="EachAtEnd" <?php if ($testtype=="EachAtEnd") {echo "SELECTED";} ?>>Show score on each question at the end of the test </option>
	<option value="AsGo" <?php if ($testtype=="AsGo") {echo "SELECTED";} ?>>Show score on each question as it's submitted (does not apply to Full test at once display)</option>
	<option value="Practice" <?php if ($testtype=="Practice") {echo "SELECTED";} ?>>Practice test: Show score on each question as it's submitted & can restart test; scores not saved</option>
	<option value="Homework" <?php if ($testtype=="Homework") {echo "SELECTED";} ?>>Homework: Show score on each question as it's submitted & allow similar question to replace missed question</option>
</select></span><BR class=form>

<span class=form>Show Answers: </span><span class=formright>
<span id="showanspracspan" class="<?php if ($testtype=="Practice" || $testtype=="Homework") {echo "show";} else {echo "hidden";} ?>">
<select name="showansprac">
	<option value="N" <?php if ($showans=="N") {echo "SELECTED";} ?>>Never</option>
	<option value="F" <?php if ($showans=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
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
	<option value="F" <?php if ($showans=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
	<option value="A" <?php if ($showans=="A") {echo "SELECTED";} ?>>After due date (in gradebook)</option>
</select>
</span>
</span><br class=form>
<span class=form>Show hints when available?</span><span class=formright><input type="checkbox" name="showhints" <?php if ($line['showhints']==1) { echo "CHECKED";} ?>></span><br class=form>
<span class=form>Shuffle item order: </span><span class=formright><input type="checkbox" name="shuffle" <?php if ($line['shuffle']&1) {echo "CHECKED";} ?>></span><BR class=form>
<?php
	$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	echo "<span class=form>Gradebook Category:</span><span class=formright><select name=gbcat id=gbcat>\n";
	echo "<option value=\"0\" ";
	if ($gbcat==0) {
		echo "selected=1 ";
	}
	echo ">Default</option>\n";
	if (mysql_num_rows($result)>0) {
		
		while ($row = mysql_fetch_row($result)) {
			echo "<option value=\"{$row[0]}\" ";
			if ($gbcat==$row[0]) {
				echo "selected=1 ";
			}
			echo ">{$row[1]}</option>\n";
		}
		
	}	
	echo "</select></span><br class=form>\n";
?>
<span class=form>Count: </span><span <?php if ($testtype=="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="stdcntingb">
<input type=radio name="cntingb" value="1" <?php if ($cntingb==1) { echo "checked=1";} ?> /> Count in Gradebook<br/>
<input type=radio name="cntingb" value="0" <?php if ($cntingb==0) { echo "checked=1";} ?> /> Don't count in grade total<br/>
<input type=radio name="cntingb" value="2" <?php if ($cntingb==2) {echo "checked=1";} ?> /> Count as Extra Credit</span>
<span <?php if ($testtype!="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="praccntingb">
Practice tests aren't counted in grade total</span>
<br class=form />

</fieldset>
<fieldset><legend>Advanced Options</legend>
<span class=form>Minimum score to receive credit: </span><span class=formright><input type=text size=4 name=minscore value="<?php echo $line['minscore'];?>"></span><BR class=form>

<span class=form>Show based on another assessment: </span><span class=formright>Show only after a score of <input type=text size=4 name=reqscore value="<?php echo $line['reqscore'];?>">
   points is obtained on <select name=reqscoreaid>
<?php
	echo '<option value=0 ';
	if ($line['reqscoreaid']==0) {echo 'selected="1"';}
	echo '>Don\'t Use</option>';
	if (count($aidarr)>0) {
		foreach($aidarr as $id=>$name) {
			echo "<option value=\"$id\" ";
			if ($line['reqscoreaid']==$id) {
				echo 'selected="1"';
			}
			echo ">$name</option>";
		}
	}
?>
</select></span><br class=form>
<span class=form>All items same random seed: </span><span class=formright><input type="checkbox" name="sameseed" <?php if ($line['shuffle']&2) {echo "CHECKED";} ?>></span><BR class=form>
<span class=form>All students same version of questions: </span><span class=formright><input type="checkbox" name="samever" <?php if ($line['shuffle']&4) {echo "CHECKED";} ?>></span><BR class=form>
<span class=form>Group assessment: </span><span class=formright>
	<input type="radio" name="isgroup" value="0" <?php if ($line['isgroup']==0) { echo 'checked="1"';} ?> />Not a group assessment<br/>
	<input type="radio" name="isgroup" value="1" <?php if ($line['isgroup']==1) { echo 'checked="1"';} ?> />Students can add members with login passwords<br/>
	<input type="radio" name="isgroup" value="2" <?php if ($line['isgroup']==2) { echo 'checked="1"';} ?> />Students can add members without passwords<br/>
	<input type="radio" name="isgroup" value="3" <?php if ($line['isgroup']==3) { echo 'checked="1"';} ?> />Students cannot add members
	</span><br class="form" />
<span class=form>Show question categories:</span><span class=formright>
	<input name="showqcat" type="radio" value="0" <?php if ($showqcat=="0") {echo 'checked="1"';} ?>>No <br />
	<input name="showqcat" type="radio" value="1" <?php if ($showqcat=="1") {echo 'checked="1"';} ?>>In Points Possible bar <br />
	<input name="showqcat" type="radio" value="2" <?php if ($showqcat=="2") {echo 'checked="1"';} ?>>In navigation bar (Skip-Around only)</span><br class="form" />

</fieldset>

</div>
</fieldset>
<div class=submit><input type=submit value=Submit></div>

<?php

	require("../footer.php");
?>
