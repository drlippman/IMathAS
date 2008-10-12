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
$useeditor = "description";


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";

if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Forum</div>\n";
	$pagetitle = "Modify Forum";
} else {
	$curBreadcrumb .= "&gt; Add Forum</div>\n";
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
		}
		
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script');
		$_POST['description'] = addslashes(htmLawed(stripslashes($_POST['description']),$htmlawedconfig));
				
		if (isset($_GET['id'])) {  //already have id; update
		$query = "UPDATE imas_forums SET name='{$_POST['name']}',description='{$_POST['description']}',startdate=$startdate,enddate=$enddate,settings=$fsets,";
		$query .= "defdisplay='{$_POST['defdisplay']}',replyby=$replyby,postby=$postby,grpaid='{$_POST['grpaid']}',points='{$_POST['points']}',gbcategory='{$_POST['gbcat']}',avail='{$_POST['avail']}' ";
		$query .= "WHERE id='{$_GET['id']}';";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$newforumid = $_GET['id'];
		} else { //add new
		$query = "INSERT INTO imas_forums (courseid,name,description,startdate,enddate,settings,defdisplay,replyby,postby,grpaid,points,gbcategory,avail) VALUES ";
		$query .= "('$cid','{$_POST['name']}','{$_POST['description']}',$startdate,$enddate,$fsets,'{$_POST['defdisplay']}',$replyby,$postby,'{$_POST['grpaid']}','{$_POST['points']}','{$_POST['gbcat']}','{$_POST['avail']}');";
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
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
			
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
			//$allowdel = (($line['settings']&4)==4);
			$defdisplay = $line['defdisplay'];
			$replyby = $line['replyby'];
			$postby = $line['postby'];
			$grpaid = $line['grpaid'];
			$points = $line['points'];
			$gbcat = $line['gbcategory'];
		} else {  //ADD MODE
			//set defaults
			$line['name'] = "Enter Forum Name here";
			$line['description'] = "<p>Enter forum description here</p>";
			$line['avail'] = 1;
			$startdate = time();
			$enddate = time() + 7*24*60*60;
			$allowanon = false;
			$allowmod = true;
			$allowdel = false;
			$replyby = 2000000000;
			$postby = 2000000000;
			$hassubscrip = false;
			$grpaid = 0;
			$points = 0;
			$gbcat = 0;
		}   
		
		$page_formActionTag = "?block=$block&cid=$cid&folder=" . $_GET['folder'];
		$page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";
		$page_formActionTag .= "&tb=$totb";
		
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
		if ($replyby<2000000000 && $replyby>0) {
			$replybydate = tzdate("m/d/Y",$replyby);
			$replybytime = tzdate("g:i a",$replyby);	
		} else {
			$replybydate = tzdate("m/d/Y",time()+7*24*60*60);
			$replybytime = tzdate("g:i a",time()+7*24*60*60);
		}
		if ($postby<2000000000 && $postby>0) {
			$postbydate = tzdate("m/d/Y",$postby);
			$postbytime = tzdate("g:i a",$postby);	
		} else {
			$postbydate = tzdate("m/d/Y",time()+7*24*60*60);
			$postbytime = tzdate("g:i a",time()+7*24*60*60);
		}
		
		$query = "SELECT id,name FROM imas_assessments WHERE isgroup>0 AND courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$i=0;
		$page_groupSelect = array();
		while ($row = mysql_fetch_row($result)) {
			$page_groupSelect['val'][$i] = $row[0];
			$page_groupSelect['label'][$i] = "Use groups of $row[1]";
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
	}
}

//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY 	

?>
	
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<h2><?php echo $pagetitle ?><img src="<?php echo $imasroot ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=forumitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></h2>

	<form method=post action="addforum.php<?php echo $page_formActionTag ?>">
		<span class=form>Name: </span>
		<span class=formright><input type=text size=60 name=name value="<?php echo str_replace('"','&quot;',$line['name']);?>"></span>
		<BR class=form>
	
		Description:<BR>
		<div class=editor>
		<textarea cols=60 rows=20 id=description name=description style="width: 100%">
		<?php echo htmlentities($line['description']);?></textarea>
		</div>
		
		<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?>/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?>/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($line['avail'],2);?>/>Show Always<br/>
		</span><br class="form"/>
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
			<a href="#" onClick="displayDatePicker('edate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>
	
		<span class=form>Group linked forum?</span><span class=formright>
<?php
	writeHtmlSelect("grpaid",$page_groupSelect['val'],$page_groupSelect['label'],$grpaid,"Not group forum",0);
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
		
		<span class=form>Get email notify of new posts:</span>
		<span class=formright>
			<input type=checkbox name="subscribe" value="1" <?php if ($hassubscrip) { echo "checked=1";}?>/>
		</span><br class="form"/>
		
		<span class=form>Default display:</span>
		<span class=formright>
			<select name="defdisplay">
				<option value="0" <?php if ($defdisplay==0) {echo "selected=1";}?>>Expanded</option>
				<option value="1" <?php if ($defdisplay==1) {echo "selected=1";}?>>Collapsed</option>
				<option value="2" <?php if ($defdisplay==2) {echo "selected=1";}?>>Condensed</option>
			</select>
		</span><br class="form" />
		 
		<span class=form>Students can reply to posts:</span>
		<span class=formright>
			<input type=radio name="replyby" value="Always" <?php if ($replyby==2000000000) { echo "checked=1";}?>/>Always<br/>
			<input type=radio name="replyby" value="Never" <?php if ($replyby==0) { echo "checked=1";}?>/>Never<br/>
			<input type=radio name="replyby" value="Date" <?php if ($replyby<2000000000 && $replyby>0) { echo "checked=1";}?>/>Before: 
			<input type=text size=10 name="replybydate" value="<?php echo $replybydate;?>">
			<a href="#" onClick="displayDatePicker('replybydate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=replybytime value="<?php echo $replybytime;?>">
		</span><br class="form" />
		
		
		<span class=form>Students can create new threads:</span><span class=formright>
			<input type=radio name="postby" value="Always" <?php if ($postby==2000000000) { echo "checked=1";}?>/>Always<br/>
			<input type=radio name="postby" value="Never" <?php if ($postby==0) { echo "checked=1";}?>/>Never<br/>
			<input type=radio name="postby" value="Date" <?php if ($postby<2000000000 && $postby>0) { echo "checked=1";}?>/>Before: 
			<input type=text size=10 name="postbydate" value="<?php echo $postbydate;?>">
			<a href="#" onClick="displayDatePicker('postbydate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=postbytime value="<?php echo $postbytime;?>">
		</span><br class="form"/>
		
		<span class="form">Count in gradebook?</span>
		<span class="formright">
			<input type=radio name="cntingb" value="0" <?php if ($points==0) { echo 'checked=1';}?>/>No<br/>
			<input type=radio name="cntingb" value="1" <?php if ($points!=0) { echo 'checked=1';}?>/>Yes, <input type=text size=4 name="points" value="<?php echo $points;?>"/> points
		</span><br class="form"/>
		
		<span class=form>Gradebook Category:</span>
			<span class=formright>
		
<?php
	writeHtmlSelect("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],$gbcat,"Default",0);
?>
		</span><br class=form>
	
		<div class=submit><input type=submit value=Submit></div>
	</form>	

<?php
}

require("../footer.php");
?>
