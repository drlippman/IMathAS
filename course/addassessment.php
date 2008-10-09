<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "summary,intro";
$pagetitle = "Assessment Settings";
$cid = $_GET['cid'];

if (isset($_GET['from'])) {
	$from = $_GET['from'];
} else {
	$from = 'cp';
}
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
}

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
if ($from=='gb') {
	$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> ";
} else if ($from=='mcd') {
	$curBreadcrumb .= "&gt; <a href=\"masschgdates.php?cid=$cid\">Mass Change Dates</a> ";
} 

if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Assessment\n";
} else {
	$curBreadcrumb .= "&gt; Add Assessment\n";
}


if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = $_GET['cid'];
	$block = $_GET['block'];

	
	if (isset($_GET['clearattempts'])) { //FORM POSTED WITH CLEAR ATTEMPTS FLAG
		if ($_GET['clearattempts']=="confirmed") {
			require_once('../includes/filehandler.php');
			deleteasidfilesbyquery(array('assessmentid'=>$_GET['id']));
			
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "UPDATE imas_questions SET withdrawn=0 WHERE assessmentid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}");
			exit;
		} else {
			$overwriteBody = 1;
			$body = "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			$body .= "&gt; <a href=\"addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}\">Modify Assessment</a> &gt; Clear Attempts</div>\n";
			$body .= "Are you SURE you want to delete all attempts (grades) for this assessment?";
			$body .= "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}&clearattempts=confirmed'\">\n";
			$body .= "<input type=button value=\"Nevermind\" onClick=\"window.location='addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}'\"></p>\n";
		}
	} elseif ($_POST['name']!= null) { //if the form has been submitted
		
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
		
		if (isset($_POST['allowlate'])) {
			$_POST['allowlate'] = 1;
		} else {
			$_POST['allowlate'] = 0;
		}
		
		$timelimit = $_POST['timelimit']*60;
		if (isset($_POST['timelimitkickout'])) {
			$timelimit = -1*$timelimit;
		}
		
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
			$query = "SELECT timelimit,displaymethod,defpoints,defattempts,defpenalty,deffeedback,shuffle,gbcategory,password,showcat,intro,startdate,enddate,reviewdate,isgroup,showhints,reqscore,reqscoreaid,noprint,allowlate FROM imas_assessments WHERE id='{$_POST['copyfrom']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			list($timelimit,$_POST['displaymethod'],$_POST['defpoints'],$_POST['defattempts'],$_POST['defpenalty'],$deffeedback,$shuffle,$_POST['gbcat'],$_POST['password'],$_POST['showqcat'],$cpintro,$cpstartdate,$cpenddate,$cpreviewdate,$isgroup,$showhints,$_POST['reqscore'],$_POST['reqscoreaid'],$_POST['noprint'],$_POST['allowlate']) = mysql_fetch_row($result);
			if (isset($_POST['copyinstr'])) {
				$_POST['intro'] = addslashes($cpintro);
			}
			if (isset($_POST['copydates'])) {
				$startdate = $cpstartdate;
				$enddate = $cpenddate;
				$reviewdate = $cpreviewdate;
			}
			if (isset($_POST['removeperq'])) {
				$query = "UPDATE imas_questions SET points=9999,attempts=9999,penalty=9999,regen=0,showans=0 WHERE assessmentid='{$_GET['id']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		if ($_POST['deffeedback']=="Practice") {
			$_POST['cntingb'] = $_POST['pcntingb'];
		}
		$_POST['ltisecret'] = trim($_POST['ltisecret']);
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script');
		$_POST['summary'] = addslashes(htmLawed(stripslashes($_POST['summary']),$htmlawedconfig));
		$_POST['intro'] = addslashes(htmLawed(stripslashes($_POST['intro']),$htmlawedconfig));
		if (isset($_GET['id'])) {  //already have id; update
			$query = "SELECT isgroup FROM imas_assessments WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0)>0 && $isgroup==0) {
				$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE assessmentid='{$_GET['id']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			if (isset($_POST['defpoints'])) {
				$query = "UPDATE imas_assessments SET name='{$_POST['name']}',summary='{$_POST['summary']}',intro='{$_POST['intro']}',startdate=$startdate,enddate=$enddate,reviewdate=$reviewdate,timelimit='$timelimit',minscore='{$_POST['minscore']}',isgroup='$isgroup',showhints='$showhints',";
				$query .= "displaymethod='{$_POST['displaymethod']}',defpoints='{$_POST['defpoints']}',defattempts='{$_POST['defattempts']}',defpenalty='{$_POST['defpenalty']}',deffeedback='$deffeedback',shuffle='$shuffle',gbcategory='{$_POST['gbcat']}',password='{$_POST['password']}',";
				$query .= "cntingb='{$_POST['cntingb']}',showcat='{$_POST['showqcat']}',reqscore='{$_POST['reqscore']}',reqscoreaid='{$_POST['reqscoreaid']}',noprint='{$_POST['noprint']}',avail='{$_POST['avail']}',groupmax='{$_POST['groupmax']}',allowlate='{$_POST['allowlate']}',exceptionpenalty='{$_POST['exceptionpenalty']}',ltisecret='{$_POST['ltisecret']}' ";
				$query .= "WHERE id='{$_GET['id']}';";
			} else { //has been taken - not updating "don't change" settings
				$query = "UPDATE imas_assessments SET name='{$_POST['name']}',summary='{$_POST['summary']}',intro='{$_POST['intro']}',startdate=$startdate,enddate=$enddate,reviewdate=$reviewdate,timelimit='$timelimit',minscore='{$_POST['minscore']}',isgroup='$isgroup',showhints='$showhints',";
				$query .= "displaymethod='{$_POST['displaymethod']}',defattempts='{$_POST['defattempts']}',deffeedback='$deffeedback',shuffle='$shuffle',gbcategory='{$_POST['gbcat']}',password='{$_POST['password']}',cntingb='{$_POST['cntingb']}',showcat='{$_POST['showqcat']}',";
				$query .= "reqscore='{$_POST['reqscore']}',reqscoreaid='{$_POST['reqscoreaid']}',noprint='{$_POST['noprint']}',avail='{$_POST['avail']}',groupmax='{$_POST['groupmax']}',allowlate='{$_POST['allowlate']}',exceptionpenalty='{$_POST['exceptionpenalty']}',ltisecret='{$_POST['ltisecret']}' ";
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
			$query .= "displaymethod,defpoints,defattempts,defpenalty,deffeedback,shuffle,gbcategory,password,cntingb,showcat,isgroup,groupmax,showhints,reqscore,reqscoreaid,noprint,avail,allowlate,exceptionpenalty,ltisecret) VALUES ";
			$query .= "('$cid','{$_POST['name']}','{$_POST['summary']}','{$_POST['intro']}',$startdate,$enddate,$reviewdate,'$timelimit','{$_POST['minscore']}',";
			$query .= "'{$_POST['displaymethod']}','{$_POST['defpoints']}','{$_POST['defattempts']}',";
			$query .= "'{$_POST['defpenalty']}','$deffeedback','$shuffle','{$_POST['gbcat']}','{$_POST['password']}','{$_POST['cntingb']}','{$_POST['showqcat']}',";
			$query .= "'$isgroup','{$_POST['groupmax']}','$showhints','{$_POST['reqscore']}','{$_POST['reqscoreaid']}','{$_POST['noprint']}','{$_POST['avail']}','{$_POST['allowlate']}','{$_POST['exceptionpenalty']}','{$_POST['ltisecret']}');";
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
			if ($totb=='b') {
				$sub[] = $itemid;
			} else if ($totb=='t') {
				array_unshift($sub,$itemid);
			}
				
			$itemorder = addslashes(serialize($items));
			
			$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid={$_GET['cid']}&aid=$newaid");
			exit;
		}
		
		
	} else { //INITIAL LOAD
		if (isset($_GET['id'])) {  //INITIAL LOAD IN MODIFY MODE
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
			if ($testtype=='Practice') {
				$pcntingb = $line['cntingb'];
				$cntingb = 1;
			} else {
				$cntingb = $line['cntingb'];
				$pcntingb = 3;
			}
			$showqcat = $line['showcat'];
			$timelimit = $line['timelimit']/60;
		} else {  //INITIAL LOAD IN ADD MODE
			//set defaults
			$line['name'] = "Enter assessment name";
			$line['summary'] = "<p>Enter summary here (shows on course page)</p>";
			$line['intro'] = "<p>Enter intro/instructions</p>";
			$startdate = time()+60*60;
			$enddate = time() + 7*24*60*60;
			$line['startdate'] = $startdate;
			$line['enddate'] = $enddate;
			$line['avail'] = 1;
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
			$line['noprint'] = 0;
			$line['groupmax'] = 6;
			$line['allowlate'] = 1;
			$line['exceptionpenalty'] = 0;
			$line['ltisecret'] = '';
			$gbcat = 0;
			$cntingb = 1;
			$pcntingb = 3;
			$showqcat = 0;
		}
		// ALL BELOW IS COMMON TO MODIFY OR ADD MODE
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
		if ($taken) {
			$page_isTakenMsg = "<p>This assessment has already been taken.  Modifying some settings will mess up those assessment attempts, and those inputs ";
			$page_isTakenMsg .=  "have been disabled.  If you want to change these settings, you should clear all existing assessment attempts</p>\n";
			$page_isTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addassessment.php?cid={$_GET['cid']}&id={$_GET['id']}&clearattempts=ask'\"></p>\n";
		} else {
			$page_isTakenMsg = "";
		}
		
		if (isset($_GET['id'])) {
			$formTitle = "<h2>Modify Assessment <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
		} else {
			$formTitle = "<h2>Add Assessment <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
		}

		$page_formActionTag = "addassessment.php?block=$block&cid=$cid";
		if (isset($_GET['id'])) {
			$page_formActionTag .= "&id=" . $_GET['id'];
		}
		$page_formActionTag .= "&folder=" . $_GET['folder'] . "&from=" . $_GET['from'];
		$page_formActionTag .= "&tb=$totb";
		
		$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$page_copyFromSelect = array();
		$i=0;
		if (mysql_num_rows($result)>0) {
			while ($row = mysql_fetch_row($result)) {
				$page_copyFromSelect['val'][$i] = $row[0];
				$page_copyFromSelect['label'][$i] = $row[1];
				$i++;
			}
		}	
		
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$page_gbcatSelect = array();
		$i=0;
		if (mysql_num_rows($result)>0) {
			while ($row = mysql_fetch_row($result)) {
				$page_gbcatSelect['val'][$i] = $row[0];
				$page_gbcatSelect['label'][$i] = $row[1];
				$i++;
			}
		}	
	} //END INITIAL LOAD BLOCK
	
}


//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY 	
	
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
	<script src="../javascript/CalendarPopup.js"></script>
	<SCRIPT LANGUAGE="JavaScript" ID="js1">
	var cal1 = new CalendarPopup();
	</SCRIPT>
	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<?php echo $formTitle ?>
	<?php echo $page_isTakenMsg ?>
	
	<form method=post action="<?php echo $page_formActionTag ?>">
		<span class=form>Assessment Name:</span>
		<span class=formright><input type=text size=30 name=name value="<?php echo str_replace('"','&quot;',$line['name']);?>"></span><BR class=form>
	
		Summary:<BR>
		<div class=editor>
			<textarea cols=50 rows=15 id=summary name=summary style="width: 100%"><?php echo htmlentities($line['summary']);?></textarea>
		</div><BR>
		Intro/Instructions:<BR>
		<div class=editor>
			<textarea cols=50 rows=20 id=intro name=intro style="width: 100%"><?php echo htmlentities($line['intro']);?></textarea>
		</div><BR>
	
		<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?>/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?>/>Show by Dates
		</span><br class="form"/>
		<span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,"0",0); ?>/> 
			Always until end date<br/>
			<input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,"0",1); ?>/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
			<A HREF="#" onClick="cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;" NAME="anchor1" ID="anchor1"><img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>">
		</span><BR class=form>
	
		<span class=form>Available Until:</span>
		<span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,"2000000000",0); ?>/>
			 Always after start date<br/>
			<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,"2000000000",1); ?>/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
			<A HREF="#" onClick="cal1.select(document.forms[0].edate,'anchor2','MM/dd/yyyy',(document.forms[0].sdate.value=='<?php echo $sdate;?>')?(document.forms[0].edate.value):(document.forms[0].sdate.value)); return false;" NAME="anchor2" ID="anchor2"><img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>
		<span class=form>Keep open as review:</span>
		<span class=formright>
			<input type=radio name="doreview" value="0" <?php writeHtmlChecked($line['reviewdate'],0,0); ?>> Never<br/>
			<input type=radio name="doreview" value="2000000000" <?php writeHtmlChecked($line['reviewdate'],2000000000,0); ?>> Always after due date<br/>
			<input type=radio name="doreview" value="rdate" <?php if ($line['reviewdate']>0 && $line['reviewdate']<2000000000) { echo "checked=1";} ?>> Until: 
			<input type=text size=10 name=rdate value="<?php echo $rdate;?>"> 
			<A HREF="#" onClick="cal1.select(document.forms[0].rdate,'anchor3','MM/dd/yyyy',(document.forms[0].rdate.value=='<?php echo $rdate;?>')?(document.forms[0].rdate.value):(document.forms[0].rdate.value)); return false;" NAME="anchor3" ID="anchor3"><img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=rtime value="<?php echo $rtime;?>">
		</span><BR class=form>
		
		<span class=form></span>
		<span class=formright>
			<input type=submit value="Submit Now"> or continue below for Assessment Options
		</span><br class=form>
	
		<fieldset><legend>Assessment Options</legend>
<?php
	if (count($page_copyFromSelect['val'])>0) {
?>	
		<span class=form>Copy Options from:</span>
		<span class=formright>

<?php	
		writeHtmlSelect ("copyfrom",$page_copyFromSelect['val'],$page_copyFromSelect['label'],0,"None - use settings below",0," onChange=\"chgcopyfrom()\"");
?>
		</span><br class=form>
<?php
	}
?>	

		<div id="copyfromoptions" class="hidden">
		<span class=form>Also copy:</span>
		<span class=formright>
			<input type=checkbox name="copyinstr" /> Instructions<br/>
			<input type=checkbox name="copydates" /> Dates
		</span><br class=form />
		<span class=form>Remove any existing per-question settings?</span>
		<span class=formright>
			<input type=checkbox name="removeperq" />
		</span><br class=form />

		</div>
		<div id="customoptions" class="show">
			<fieldset><legend>Core Options</legend>
			<span class=form>Require Password (blank for none):</span>
			<span class=formright><input type=text name=password value="<?php echo $line['password'];?>"></span><br class=form />
			<span class=form>Time Limit (minutes, 0 for no time limit): </span>
			<span class=formright><input type=text size=4 name=timelimit value="<?php echo abs($timelimit);?>">
				<input type="checkbox" name="timelimitkickout" <?php if ($timelimit<0) echo 'checked="checked"';?> /> Kick student out at timelimit</span><BR class=form>
			<span class=form>Display method: </span>
			<span class=formright>
				<select name="displaymethod">
					<option value="AllAtOnce" <?php writeHtmlSelected($line['displaymethod'],"AllAtOnce",0) ?>>Full test at once</option>
					<option value="OneByOne" <?php writeHtmlSelected($line['displaymethod'],"OneByOne",0) ?>>One question at a time</option>
					<option value="Seq" <?php writeHtmlSelected($line['displaymethod'],"Seq",0) ?>>Full test, submit one at time</option>
					<option value="SkipAround" <?php writeHtmlSelected($line['displaymethod'],"SkipAround",0) ?>>Skip Around</option>
				</select>
			</span><BR class=form>
	
			<span class=form>Default points per problem: </span>
			<span class=formright><input type=text size=4 name=defpoints value="<?php echo $line['defpoints'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>></span><BR class=form>
	
			<span class=form>Default attempts per problem (0 for unlimited): </span>
			<span class=formright>
				<input type=text size=4 name=defattempts value="<?php echo $line['defattempts'];?>" > 
	 			<input type=checkbox name="reattemptsdiffver" <?php writeHtmlChecked($line['shuffle']&8,8); ?> />
	 			Reattempts different versions</span><BR class=form>
	
			<span class=form>Default penalty:</span>
			<span class=formright>
				<input type=text size=4 name=defpenalty value="<?php echo $line['defpenalty'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>>% 
			   	<select name="skippenalty" <?php if ($taken) {echo 'disabled=disabled';}?>>
			    	<option value="0" <?php if ($skippenalty==0) {echo "selected=1";} ?>>per missed attempt</option>
					<option value="1" <?php if ($skippenalty==1) {echo "selected=1";} ?>>per missed attempt, after 1</option>
					<option value="2" <?php if ($skippenalty==2) {echo "selected=1";} ?>>per missed attempt, after 2</option>
					<option value="3" <?php if ($skippenalty==3) {echo "selected=1";} ?>>per missed attempt, after 3</option>
					<option value="4" <?php if ($skippenalty==4) {echo "selected=1";} ?>>per missed attempt, after 4</option>
					<option value="5" <?php if ($skippenalty==5) {echo "selected=1";} ?>>per missed attempt, after 5</option>
					<option value="6" <?php if ($skippenalty==6) {echo "selected=1";} ?>>per missed attempt, after 6</option>
					<option value="10" <?php if ($skippenalty==10) {echo "selected=1";} ?>>on last possible attempt only</option>
				</select>
			</span><BR class=form>
			
			<span class=form>Feedback method: </span>
			<span class=formright>
				<select id="deffeedback" name="deffeedback" onChange="chgfb()" >
					<option value="NoScores" <?php if ($testtype=="NoScores") {echo "SELECTED";} ?>>No scores shown (use with 1 attempt per problem)</option>
					<option value="EndScore" <?php if ($testtype=="EndScore") {echo "SELECTED";} ?>>Just show final score (total points & average) - only whole test can be reattemped</option>
					<option value="EachAtEnd" <?php if ($testtype=="EachAtEnd") {echo "SELECTED";} ?>>Show score on each question at the end of the test </option>
					<option value="AsGo" <?php if ($testtype=="AsGo") {echo "SELECTED";} ?>>Show score on each question as it's submitted (does not apply to Full test at once display)</option>
					<option value="Practice" <?php if ($testtype=="Practice") {echo "SELECTED";} ?>>Practice test: Show score on each question as it's submitted & can restart test; scores not saved</option>
					<option value="Homework" <?php if ($testtype=="Homework") {echo "SELECTED";} ?>>Homework: Show score on each question as it's submitted & allow similar question to replace missed question</option>
				</select>
			</span><BR class=form>
			
			<span class=form>Show Answers: </span>
			<span class=formright>
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
			<span class=form>Show hints when available?</span>
			<span class=formright>
				<input type="checkbox" name="showhints" <?php writeHtmlChecked($line['showhints'],1); ?>>
			</span><br class=form>
			
			<span class=form>Allow use of LatePasses?: </span>
			<span class=formright>
				<input type="checkbox" name="allowlate" <?php writeHtmlChecked($line['allowlate'],1); ?>>
			</span><BR class=form>
			
			<span class=form>Make hard to print?</span>
			<span class=formright>
				<input type="radio" value="0" name="noprint" <?php writeHtmlChecked($line['noprint'],0); ?>/> No <input type="radio" value="1" name="noprint" <?php writeHtmlChecked($line['noprint'],1); ?>/> Yes 
			</span><br class=form>

			
			<span class=form>Shuffle item order: </span>
			<span class=formright><input type="checkbox" name="shuffle" <?php writeHtmlChecked($line['shuffle']&1,1); ?>>
			</span><BR class=form>
			<span class=form>Gradebook Category:</span>
			<span class=formright>
			
<?php
	writeHtmlSelect("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],$gbcat,"Default",0);
?>
			</span><br class=form>
			<span class=form>Count: </span>
			<span <?php if ($testtype=="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="stdcntingb">
				<input type=radio name="cntingb" value="1" <?php writeHtmlChecked($cntingb,1,0); ?> /> Count in Gradebook<br/>
				<input type=radio name="cntingb" value="0" <?php writeHtmlChecked($cntingb,0,0); ?> /> Don't count in grade total and hide from students<br/>
				<input type=radio name="cntingb" value="3" <?php writeHtmlChecked($cntingb,3,0); ?> /> Don't count in grade total<br/>
				<input type=radio name="cntingb" value="2" <?php writeHtmlChecked($cntingb,2,0); ?> /> Count as Extra Credit
			</span>
			<span <?php if ($testtype!="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="praccntingb">
				<input type=radio name="pcntingb" value="0" <?php writeHtmlChecked($pcntingb,0,0); ?> /> Don't count in grade total and hide from students<br/>
				<input type=radio name="pcntingb" value="3" <?php writeHtmlChecked($pcntingb,3,0); ?> /> Don't count in grade total<br/>
			</span><br class=form />
			</fieldset>
			
			<fieldset><legend>Advanced Options</legend>
			<span class=form>Minimum score to receive credit: </span>
			<span class=formright>
				<input type=text size=4 name=minscore value="<?php echo $line['minscore'];?>">
			</span><BR class=form>
	
			<span class=form>Show based on another assessment: </span>
			<span class=formright>Show only after a score of 
				<input type=text size=4 name=reqscore value="<?php echo $line['reqscore'];?>">
		   		points is obtained on 
<?php 
	writeHtmlSelect ("reqscoreaid",$page_copyFromSelect['val'],$page_copyFromSelect['label'],$selectedVal=$line['reqscoreaid'],$defaultLabel="Dont Use",$defaultVal=0,$actions=null); 
?>				
			</span><br class=form>
			<span class=form>All items same random seed: </span>
			<span class=formright>
				<input type="checkbox" name="sameseed" <?php writeHtmlChecked($line['shuffle']&2,2); ?>>
			</span><BR class=form>
			<span class=form>All students same version of questions: </span>
			<span class=formright>
				<input type="checkbox" name="samever" <?php writeHtmlChecked($line['shuffle']&4,4); ?>>
			</span><BR class=form>
			
			<span class=form>Penalty for questions done while in exception/LatePass: </span>
			<span class=formright>
				<input type=text size=4 name="exceptionpenalty" value="<?php echo $line['exceptionpenalty'];?>">%
			</span><BR class=form>
			
			<span class=form>Group assessment: </span>
			<span class=formright>
				<input type="radio" name="isgroup" value="0" <?php writeHtmlChecked($line['isgroup'],0); ?> />Not a group assessment<br/>
				<input type="radio" name="isgroup" value="1" <?php writeHtmlChecked($line['isgroup'],1); ?> />Students can add members with login passwords<br/>
				<input type="radio" name="isgroup" value="2" <?php writeHtmlChecked($line['isgroup'],2); ?> />Students can add members without passwords<br/>
				<input type="radio" name="isgroup" value="3" <?php writeHtmlChecked($line['isgroup'],3); ?> />Students cannot add members
			</span><br class="form" />
			<span class=form>Max group members (if group assessment): </span>
			<span class=formright>
				<input type="text" name="groupmax" value="<?php echo $line['groupmax'];?>" />
			</span><br class="form" />
			<span class=form>Show question categories:</span>
			<span class=formright>
				<input name="showqcat" type="radio" value="0" <?php writeHtmlChecked($showqcat,"0"); ?>>No <br />
				<input name="showqcat" type="radio" value="1" <?php writeHtmlChecked($showqcat,"1"); ?>>In Points Possible bar <br />
				<input name="showqcat" type="radio" value="2" <?php writeHtmlChecked($showqcat,"2"); ?>>In navigation bar (Skip-Around only)
			</span><br class="form" />
			<span class="form">LTI access secret (max 10 chars; blank to not use)</span>
			<span class="formright">
				<input name="ltisecret" type="text" value="<?php echo $line['ltisecret'];?>" />
			</span><br class="form" />
			
			</fieldset>
		</div>
	</fieldset>
	<div class=submit><input type=submit value=Submit></div>

<?php
}
	require("../footer.php");
?>
