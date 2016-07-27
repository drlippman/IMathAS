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
$useeditor = "description,postinstr,replyinstr";


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";

if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Forum\n";
	$pagetitle = "Modify Forum";
} else {
	$curBreadcrumb .= "&gt; Add Forum\n";
	$pagetitle = "Add Forum";
} 
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
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
	
	if ($_POST['name']!= null) { //FORM SUBMITTED, DATA PROCESSING
		require_once("../includes/parsedatetime.php");
		if ($_POST['avail']==1) {
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
		} else {
			$startdate = 0;
			$enddate = 2000000000;
		}
		$fsets = 0;
		if (isset($_POST['allowanon']) && $_POST['allowanon']==1) {
			$fsets += 1;
		}
		if (isset($_POST['allowmod']) && $_POST['allowmod']==1) {
			$fsets += 2;
		}
		if (isset($_POST['allowdel']) && $_POST['allowdel']==1) {
			$fsets += 4;
		}
		if (isset($_POST['allowlikes']) && $_POST['allowlikes']==1) {
			$fsets += 8;
		}
		if (isset($_POST['viewafterpost']) && $_POST['viewafterpost']==1) {
			$fsets += 16;
		}
		if ($_POST['replyby']=="Always") {
			$replyby = 2000000000;
		} else if ($_POST['replyby']=="Never") {
			$replyby = 0;
		} else {
			$replyby = parsedatetime($_POST['replybydate'],$_POST['replybytime']);
		}
		if ($_POST['postby']=="Always") {
			$postby = 2000000000;
		} else if ($_POST['postby']=="Never") {
			$postby = 0;
		} else {
			$postby = parsedatetime($_POST['postbydate'],$_POST['postbytime']);
		}
		
		if ($_POST['cntingb']==0) {
			$_POST['points'] = 0;
			$tutoredit = 0;
			$_POST['gbcat'] = 0;
		} else {
			$tutoredit = intval($_POST['tutoredit']);
			if ($_POST['cntingb']==4) {
				$_POST['cntingb'] = 0;
			}
		}
		
		if (intval($_POST['points'])==0) {
			$_POST['cntingb'] = 0;
		} 
		
		$caltag = $_POST['caltagpost'].'--'.$_POST['caltagreply'];
		if (isset($_POST['usetags'])) {
			$taglist = trim($_POST['taglist']);
		} else {
			$taglist = '';
		}
		if (isset($_POST['rubric'])) {
			$rubric = intval($_POST['rubric']);
		} else {
			$rubric = 0;
		}
		$allowlate = 0;
		if ($_POST['allowlate']>0) {
			$allowlate = $_POST['allowlate'] + 10*$_POST['allowlateon'];
			if (isset($_POST['latepassafterdue'])) {
				$allowlate += 100;
			}
		}
		$outcomes = array();
		if (isset($_POST['outcomes'])) {
			foreach ($_POST['outcomes'] as $o) {
				if (is_numeric($o) && $o>0) {
					$outcomes[] = intval($o);
				}
			}
		}
		$outcomes = implode(',',$outcomes);
		
		$_POST['name'] = addslashes(htmlentities(stripslashes($_POST['name'])));
		
		require_once("../includes/htmLawed.php");
		if ($_POST['description']=='<p>Enter forum description here</p>') {
			$_POST['description'] = '';
		} else {
			$_POST['description'] = addslashes(myhtmLawed(stripslashes($_POST['description'])));
		}
		if (!isset($_POST['postinstr']) || trim($_POST['postinstr'])=='' || preg_match('/^\s*<p>(\s|&nbsp;)*<\/p>\s*$/',$_POST['postinstr'])) {
			$_POST['postinstr'] = '';
		} else {
			$_POST['postinstr'] = addslashes(myhtmLawed(stripslashes($_POST['postinstr'])));
		}
		if (!isset($_POST['replyinstr']) || trim($_POST['replyinstr'])=='' || preg_match('/^\s*<p>(\s|&nbsp;)*<\/p>\s*$/',$_POST['replyinstr'])) {
			$_POST['replyinstr'] = '';
		} else {
			$_POST['replyinstr'] = addslashes(myhtmLawed(stripslashes($_POST['replyinstr'])));
		}
		if (isset($_GET['id'])) {  //already have id; update
			$query = "SELECT groupsetid FROM imas_forums WHERE id='{$_GET['id']}';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$oldgroupsetid = mysql_result($result,0,0);
			if ($oldgroupsetid!=$_POST['groupsetid']) {
				//change of groupset; zero out stugroupid 
				$query = "UPDATE imas_forum_threads SET stugroupid=0 WHERE forumid='{$_GET['id']}';";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$query = "UPDATE imas_forums SET name='{$_POST['name']}',description='{$_POST['description']}',postinstr='{$_POST['postinstr']}',replyinstr='{$_POST['replyinstr']}',startdate=$startdate,enddate=$enddate,settings=$fsets,caltag='$caltag',";
			$query .= "defdisplay='{$_POST['defdisplay']}',replyby=$replyby,postby=$postby,groupsetid='{$_POST['groupsetid']}',points='{$_POST['points']}',cntingb='{$_POST['cntingb']}',tutoredit=$tutoredit,";
			$query .= "gbcategory='{$_POST['gbcat']}',avail='{$_POST['avail']}',sortby='{$_POST['sortby']}',forumtype='{$_POST['forumtype']}',taglist='$taglist',rubric=$rubric,outcomes='$outcomes',allowlate=$allowlate ";
			$query .= "WHERE id='{$_GET['id']}';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$newforumid = $_GET['id'];
			
		} else { //add new
			$query = "INSERT INTO imas_forums (courseid,name,description,postinstr,replyinstr,startdate,enddate,settings,defdisplay,replyby,postby,groupsetid,points,cntingb,tutoredit,gbcategory,avail,sortby,caltag,forumtype,taglist,rubric,outcomes,allowlate) VALUES ";
			$query .= "('$cid','{$_POST['name']}','{$_POST['description']}','{$_POST['postinstr']}','{$_POST['replyinstr']}',$startdate,$enddate,$fsets,'{$_POST['defdisplay']}',$replyby,$postby,'{$_POST['groupsetid']}','{$_POST['points']}','{$_POST['cntingb']}',$tutoredit,'{$_POST['gbcat']}','{$_POST['avail']}','{$_POST['sortby']}','$caltag','{$_POST['forumtype']}','$taglist',$rubric,'$outcomes',$allowlate);";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$newforumid = mysql_insert_id();
			
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "('$cid','Forum','$newforumid');";
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
			
		}
		$query = "SELECT id FROM imas_forum_subscriptions WHERE forumid='$newforumid' AND userid='$userid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			if (!isset($_POST['subscribe'])) {
				$query = "DELETE FROM imas_forum_subscriptions WHERE forumid='$newforumid' AND userid='$userid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}	
		} else if (isset($_POST['subscribe'])) {
			$query = "INSERT INTO imas_forum_subscriptions (forumid,userid) VALUES ('$newforumid','$userid')";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
			
		exit;
	} else { //INITIAL LOAD DATA PROCESS
		if (isset($_GET['id'])) { //MODIFY MODE
			$hassubscrip = false;
			$query = "SELECT id FROM imas_forum_subscriptions WHERE forumid='{$_GET['id']}' AND userid='$userid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$hassubscrip = true;
			}
			$query = "SELECT * FROM imas_forums WHERE id='{$_GET['id']}';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$startdate = $line['startdate'];
			$enddate = $line['enddate'];
			$allowanon = (($line['settings']&1)==1);
			$allowmod = (($line['settings']&2)==2);
			$allowdel = (($line['settings']&4)==4);
			$allowlikes = (($line['settings']&8)==8);
			$viewafterpost = (($line['settings']&16)==16);
			$sortby = $line['sortby'];
			$defdisplay = $line['defdisplay'];
			$replyby = $line['replyby'];
			$postby = $line['postby'];
			$groupsetid = $line['groupsetid'];
			if ($groupsetid>0) {
				$query = "SELECT * FROM imas_forum_threads WHERE forumid='{$_GET['id']}' AND stugroupid>0 LIMIT 1";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$hasgroupthreads = true;
				} else {
					$hasgroupthreads = false;
				}
			}
			$points = $line['points'];
			$cntingb = $line['cntingb'];
			$gbcat = $line['gbcategory'];
			if ($line['outcomes']!='') {
				$gradeoutcomes = explode(',',$line['outcomes']);
			} else {
				$gradeoutcomes = array();
			}
			if ($line['description']=='') {
				//$line['description'] = "<p>Enter forum description here</p>";
			}
			$savetitle = _("Save Changes");
		} else {  //ADD MODE
			//set defaults
			$line['name'] = "Enter Forum Name here";
			$line['description'] = "<p>Enter forum description here</p>";
			$line['avail'] = 1;
			$line['caltag'] = 'FP--FR';
			$line['forumtype'] = 0;
			$line['taglist'] = '';
			$line['rubric'] = 0;
			$line['postinstr'] = '';
			$line['replyinstr'] = '';
			$line['allowlate'] = 0;
			$gradeoutcomes = array();
			$startdate = time();
			$enddate = time() + 7*24*60*60;
			$allowanon = false;
			$allowmod = true;
			$allowdel = false;
			$allowlikes = false;
			$viewafterpost = false;
			$replyby = 2000000000;
			$postby = 2000000000;
			$hassubscrip = false;
			$groupsetid = 0;
			$points = 0;
			$gbcat = 0;
			$sortby = 0;
			$cntingb = 0;
			$line['tutoredit'] = 0;
			$savetitle = _("Create Forum");
		}   
		
		list($posttag,$replytag) = explode('--',$line['caltag']);
		
		$page_formActionTag = "?block=$block&cid=$cid&folder=" . $_GET['folder'];
		$page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";
		$page_formActionTag .= "&tb=$totb";
		
		$hr = floor($coursedeftime/60)%12;
		$min = $coursedeftime%60;
		$am = ($coursedeftime<12*60)?'am':'pm';
		$deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
		$hr = floor($coursedefstime/60)%12;
		$min = $coursedefstime%60;
		$am = ($coursedefstime<12*60)?'am':'pm';
		$defstime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
	
		if ($startdate!=0) {
			$sdate = tzdate("m/d/Y",$startdate);
			$stime = tzdate("g:i a",$startdate);
		} else {
			$sdate = tzdate("m/d/Y",time());
			$stime = $defstime; // tzdate("g:i a",time());
		}
		if ($enddate!=2000000000) {
			$edate = tzdate("m/d/Y",$enddate);
			$etime = tzdate("g:i a",$enddate);	
		} else {
			$edate = tzdate("m/d/Y",time()+7*24*60*60);
			$etime = $deftime; // tzdate("g:i a",time()+7*24*60*60);
		}  
		if ($replyby<2000000000 && $replyby>0) {
			$replybydate = tzdate("m/d/Y",$replyby);
			$replybytime = tzdate("g:i a",$replyby);	
		} else {
			$replybydate = tzdate("m/d/Y",time()+7*24*60*60);
			$replybytime = $deftime; // tzdate("g:i a",time()+7*24*60*60);
		}
		if ($postby<2000000000 && $postby>0) {
			$postbydate = tzdate("m/d/Y",$postby);
			$postbytime = tzdate("g:i a",$postby);	
		} else {
			$postbydate = tzdate("m/d/Y",time()+7*24*60*60);
			$postbytime = $deftime; // tzdate("g:i a",time()+7*24*60*60);
		}
		
		if (!isset($_GET['id'])) {
			$stime = $defstime;
			$etime = $deftime;
			$replybytime = $deftime;
			$postbytime = $deftime;
		}
		
		/*
		$query = "SELECT id,name FROM imas_assessments WHERE isgroup>0 AND courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$i=0;
		$page_groupSelect = array();
		while ($row = mysql_fetch_row($result)) {
			$page_groupSelect['val'][$i] = $row[0];
			$page_groupSelect['label'][$i] = "Use groups of $row[1]";
			$i++;
		}
		*/
		$query = "SELECT id,name FROM imas_stugroupset WHERE courseid='$cid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$i=0;
		$page_groupSelect = array();
		while ($row = mysql_fetch_row($result)) {
			$page_groupSelect['val'][$i] = $row[0];
			$page_groupSelect['label'][$i] = "Use group set: {$row[1]}";
			$i++;
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
		$rubric_vals = array(0);
		$rubric_names = array('None');
		$query = "SELECT id,name FROM imas_rubrics WHERE ownerid='$userid' OR groupid='$gropuid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$rubric_vals[] = $row[0];
			$rubric_names[] = $row[1];
		}
		$query = "SELECT id,name FROM imas_outcomes WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$outcomenames = array();
		while ($row = mysql_fetch_row($result)) {
			$outcomenames[$row[0]] = $row[1];
		}
		$query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($row[0]=='') {
			$outcomearr = array();
		} else {
			$outcomearr = unserialize($row[0]);
			if ($outcomearr==false) {
				$outcomearr = array();
			}
		}
		$outcomes = array();
		function flattenarr($ar) {
			global $outcomes;
			foreach ($ar as $v) {
				if (is_array($v)) { //outcome group
					$outcomes[] = array($v['name'], 1);
					flattenarr($v['outcomes']);
				} else {
					$outcomes[] = array($v, 0);
				}
			}
		}
		flattenarr($outcomearr);
		
		$page_tutorSelect['label'] = array("No access to scores","View Scores","View and Edit Scores");
		$page_tutorSelect['val'] = array(2,0,1);
		
		$page_allowlateSelect = array();
		$page_allowlateSelect['val'][0] = 0;
		$page_allowlateSelect['label'][0] = "None";
		$page_allowlateSelect['val'][1] = 1;
		$page_allowlateSelect['label'][1] = "Unlimited";
		for ($k=1;$k<9;$k++) {
			$page_allowlateSelect['val'][] = $k+1;
			$page_allowlateSelect['label'][] = "Up to $k";
		}
		$page_allowlateonSelect = array();
		$page_allowlateonSelect['val'][0] = 0;
		$page_allowlateonSelect['label'][0] = "Posts and Replies (1 LatePass for both)";
		//doesn't work yet
		//$page_allowlateonSelect['val'][1] = 1;
		//$page_allowlateonSelect['label'][1] = "Posts or Replies (1 LatePass each)";
		$page_allowlateonSelect['val'][1] = 2;
		$page_allowlateonSelect['label'][1] = "Posts only";
		$page_allowlateonSelect['val'][2] = 3;
		$page_allowlateonSelect['label'][2] = "Replies only";
	}
}

//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
 $placeinhead .= '<script type="text/javascript"> function toggleGBdetail(v) { document.getElementById("gbdetail").style.display = v?"block":"none";}</script>';
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY 	

?>
	
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headeraddforum" class="pagetitle"><h2><?php echo $pagetitle ?><img src="<?php echo $imasroot ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=forumitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></h2></div>

	<form method=post action="addforum.php<?php echo $page_formActionTag ?>">
		<span class=form>Name: </span>
		<span class=formright><input type=text size=60 name=name value="<?php echo str_replace('"','&quot;',$line['name']);?>"></span>
		<BR class=form>
	
		Description:<BR>
		<div class=editor>
		<textarea cols=60 rows=20 id=description name=description style="width: 100%">
		<?php echo htmlentities($line['description']);?></textarea>
		</div><br/>
		
		<?php if ($line['postinstr']=='' && $line['replyinstr']=='') {
			echo '<div><script type="text/javascript"> function showpostreply(el) { $("#postreplyinstr").show(); $(el).remove();}</script>';
			echo '<a href="#" onclick="showpostreply(this);return false">'._('Add Posting / Reply Instructions').'</a>';
			echo '<div id="postreplyinstr" style="display:none;">';
		}?>
		Posting Instructions: <em>Displays on Add New Thread</em><br/>
		<div class=editor>
		<textarea cols=60 rows=10 id="postinstr" name="postinstr" style="width: 100%">
		<?php echo htmlentities($line['postinstr']);?></textarea>
		</div><br/>
		Reply Instructions: <em>Displays on Add Reply</em><br/>
		<div class=editor>
		<textarea cols=60 rows=10 id="replyinstr" name="replyinstr" style="width: 100%">
		<?php echo htmlentities($line['replyinstr']);?></textarea>
		</div>
		<?php if ($line['postinstr']=='' && $line['replyinstr']=='') {
			echo '</div></div>';
		}?>
		<br class="form"/>
		<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?> onclick="document.getElementById('datediv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?> onclick="document.getElementById('datediv').style.display='block';"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($line['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';"/>Show Always<br/>
		</span><br class="form"/>
		
		<div id="datediv" style="display:<?php echo ($line['avail']==1)?"block":"none"; ?>">
		<span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/> 
			Always until end date<br/>
			<input type=radio name="sdatetype" value="sdate" <?php  writeHtmlChecked($startdate,'0',1) ?>/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>">
		</span><BR class=form>
		
		<span class=form>Available Until:</span>
		<span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/>
			 Always after start date<br/>
			<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>
		</div>
		<span class=form>Group forum?</span><span class=formright>
<?php
	writeHtmlSelect("groupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$groupsetid,"Not group forum",0);
	if ($groupsetid>0 && $hasgroupthreads) {
		echo '<br/>WARNING: <span style="font-size: 80%">Group threads exist.  Changing the group set will set all existing threads to be non-group-specific threads</span>';
	}
?>
		</span><br class="form"/>
		
		
		<span class=form>Allow anonymous posts:</span>
		<span class=formright>
			<input type=checkbox name="allowanon" value="1" <?php if ($allowanon) { echo "checked=1";}?>/>
		</span><br class="form"/>
		
		<span class=form>Allow students to modify posts:</span>
		<span class=formright>
			<input type=checkbox name="allowmod" value="1" <?php if ($allowmod) { echo "checked=1";}?>/>
		</span><br class="form"/>
		
		<span class=form>Allow students to delete own posts (if no replies):</span>
		<span class=formright>
			<input type=checkbox name="allowdel" value="1" <?php if ($allowdel) { echo "checked=1";}?>/>
		</span><br class="form"/>
		
		<span class=form>Turn on "liking" posts:</span>
		<span class=formright>
			<input type=checkbox name="allowlikes" value="1" <?php if ($allowlikes) { echo "checked=1";}?>/>
		</span><br class="form"/>
		
		<span class=form>Viewing before posting:</span>
		<span class=formright>
			<input type=checkbox name="viewafterpost" value="1" <?php if ($viewafterpost) { echo "checked=1";}?>/> Prevent students from viewing posts until they have created a thread.<br/><i>You will likely also want to disable modifying posts</i>
		</span><br class="form"/>
		
		<span class=form>Get email notify of new posts:</span>
		<span class=formright>
			<input type=checkbox name="subscribe" value="1" <?php if ($hassubscrip) { echo "checked=1";}?>/>
		</span><br class="form"/>
		
		<span class=form>Default display:</span>
		<span class=formright>
			<select name="defdisplay">
				<option value="0" <?php if ($defdisplay==0 || $defdisplay==1) {echo "selected=1";}?>>Expanded</option>
				<option value="2" <?php if ($defdisplay==2) {echo "selected=1";}?>>Condensed</option>
			</select>
		</span><br class="form" />
		
		<span class="form">Sort threads by: </span>
		<span class="formright">
			<input type="radio" name="sortby" value="0" <?php writeHtmlChecked($sortby,0);?>/> Thread start date<br/>
			<input type="radio" name="sortby" value="1" <?php writeHtmlChecked($sortby,1);?>/> Most recent reply date
		</span><br class="form" />
		
		<span class=form>Students can create new threads:</span><span class=formright>
			<input type=radio name="postby" value="Always" <?php if ($postby==2000000000) { echo "checked=1";}?>/>Always<br/>
			<input type=radio name="postby" value="Never" <?php if ($postby==0) { echo "checked=1";}?>/>Never<br/>
			<input type=radio name="postby" value="Date" <?php if ($postby<2000000000 && $postby>0) { echo "checked=1";}?>/>Before: 
			<input type=text size=10 name="postbydate" value="<?php echo $postbydate;?>">
			<a href="#" onClick="displayDatePicker('postbydate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=postbytime value="<?php echo $postbytime;?>">
		</span><br class="form"/>
		
		<span class=form>Students can reply to posts:</span>
		<span class=formright>
			<input type=radio name="replyby" value="Always" <?php if ($replyby==2000000000) { echo "checked=1";}?>/>Always<br/>
			<input type=radio name="replyby" value="Never" <?php if ($replyby==0) { echo "checked=1";}?>/>Never<br/>
			<input type=radio name="replyby" value="Date" <?php if ($replyby<2000000000 && $replyby>0) { echo "checked=1";}?>/>Before: 
			<input type=text size=10 name="replybydate" value="<?php echo $replybydate;?>">
			<a href="#" onClick="displayDatePicker('replybydate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=replybytime value="<?php echo $replybytime;?>">
		</span><br class="form" />
		
		<span class=form>Allow use of LatePasses?: </span>
			<span class=formright>
				<?php
				writeHtmlSelect("allowlate",$page_allowlateSelect['val'],$page_allowlateSelect['label'],$line['allowlate']%10);
				echo ' on ';
				writeHtmlSelect("allowlateon",$page_allowlateonSelect['val'],$page_allowlateonSelect['label'],floor($line['allowlate']/10)%10);
				?>
				<br/><label><input type="checkbox" name="latepassafterdue" <?php writeHtmlChecked($line['allowlate']>100,true); ?>> Allow LatePasses after due date, within 1 LatePass period</label>
			</span><BR class=form> 
		
		<span class="form">Calendar icon:</span>
		<span class="formright">
			New Threads: <input name="caltagpost" type=text size=4 value="<?php echo $posttag;?>"/>, 
			Replies: <input name="caltagreply" type=text size=4 value="<?php echo $replytag;?>"/>
		</span><br class="form" />
		
		
		<span class="form">Count in gradebook?</span>
		<span class="formright">
			<input type=radio name="cntingb" value="0" <?php if ($cntingb==0) { echo 'checked=1';}?> onclick="toggleGBdetail(false)"/>No<br/>
			<input type=radio name="cntingb" value="1" <?php if ($cntingb==1) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/>Yes<br/>
			<input type=radio name="cntingb" value="4" <?php if ($cntingb==0 && $points>0) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/>Yes, but hide from students for now<br/>
			<input type=radio name="cntingb" value="2" <?php if ($cntingb==2) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/>Yes, as extra credit<br/>
		</span><br class="form"/>
		<div id="gbdetail" <?php if ($cntingb==0 && $points==0) { echo 'style="display:none;"';}?>>
		<span class="form">Points:</span>
		<span class="formright">
			<input type=text size=4 name="points" value="<?php echo $points;?>"/> points
		</span><br class="form"/>
		<span class=form>Gradebook Category:</span>
			<span class=formright>
		
<?php
	writeHtmlSelect("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],$gbcat,"Default",0);
?>
		</span><br class=form>
		<span class="form">Tutor Access:</span>
			<span class="formright">
<?php
	writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$line['tutoredit']);
?>			
		</span><br class="form" />

		<span class=form>Use Scoring Rubric</span><span class=formright>
<?php 
    writeHtmlSelect('rubric',$rubric_vals,$rubric_names,$line['rubric']);
    echo " <a href=\"addrubric.php?cid=$cid&amp;id=new&amp;from=addf&amp;fid={$_GET['id']}\">Add new rubric</a> ";
    echo "| <a href=\"addrubric.php?cid=$cid&amp;from=addf&amp;fid={$_GET['id']}\">Edit rubrics</a> ";
?>
    		</span><br class="form"/>
<?php
	if (count($outcomes)>0) {
			echo '<span class="form">Associate Outcomes:</span></span class="formright">';
			writeHtmlMultiSelect('outcomes',$outcomes,$outcomenames,$gradeoutcomes,'Select an outcome...');
			echo '</span><br class="form"/>';
	}
	
?>
		</div>
		<span class="form">Forum type:</span>
		<span class="formright">
			<input type=radio name="forumtype" value="0" <?php if ($line['forumtype']==0) { echo 'checked=1';}?>/>Regular forum<br/>
			<input type=radio name="forumtype" value="1" <?php if ($line['forumtype']==1) { echo 'checked=1';}?>/>File sharing forum
		</span><br class="form"/>
		<span class="form">Categorize posts?</span>
		<span class="formright">
			<input type=checkbox name="usetags" value="1" <?php if ($line['taglist']!='') { echo "checked=1";}?> 
			  onclick="document.getElementById('tagholder').style.display=this.checked?'':'none';" />
			  <span id="tagholder" style="display:<?php echo ($line['taglist']=='')?"none":"inline"; ?>">
			  Enter in format CategoryDescription:category,category,category<br/>
			  <textarea rows="2" cols="60" name="taglist"><?php echo $line['taglist'];?></textarea>
			  </span>
		</span><br class="form"/>
		
		<div class=submit><input type=submit value="<?php echo $savetitle;?>"></div>
	</form>	
	<p>&nbsp;</p>
	<p>&nbsp;</p>
<?php
}

require("../footer.php");
?>
