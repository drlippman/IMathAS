<?php
//IMathAS:  Add/modify forum items
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
	
	if (isset($_GET['remove'])) {
		if ($_GET['remove']=="really") {
			$forumid = $_GET['id'];
			
			$query = "SELECT id FROM imas_items WHERE typeid='$forumid' AND itemtype='Forum'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemid = mysql_result($result,0,0);
			
			$query = "DELETE FROM imas_items WHERE id='$itemid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "DELETE FROM imas_forums WHERE id='$forumid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "DELETE FROM imas_forum_subscriptions WHERE forumid='$forumid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "DELETE FROM imas_forum_posts WHERE forumid='$forumid'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			
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
			echo "&gt; Remove Forum</div>\n";
			echo "Are you SURE you want to delete this forum and all associated postings?";
			echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='addforum.php?cid={$_GET['cid']}&block=$block&id={$_GET['id']}&remove=really'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='course.php?cid={$_GET['cid']}'\"></p>\n";
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
			
		
		if (isset($_GET['id'])) {  //already have id; update
		$query = "UPDATE imas_forums SET name='{$_POST['name']}',description='{$_POST['description']}',startdate=$startdate,enddate=$enddate,settings=$fsets,";
		$query .= "defdisplay='{$_POST['defdisplay']}',replyby=$replyby,postby=$postby ";
		$query .= "WHERE id='{$_GET['id']}';";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$newforumid = $_GET['id'];
		} else { //add new
		$query = "INSERT INTO imas_forums (courseid,name,description,startdate,enddate,settings,defdisplay,replyby,postby) VALUES ";
		$query .= "('$cid','{$_POST['name']}','{$_POST['description']}',$startdate,$enddate,$fsets,'{$_POST['defdisplay']}',$replyby,$postby);";
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
		$sub[] = $itemid;
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
	} else {
		if (isset($_GET['id'])) {
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
		} else {
			//set defaults
			$line['name'] = "Enter Forum Name here";
			$line['description'] = "<p>Enter forum description here</p>";
			$startdate = time();
			$enddate = time() + 7*24*60*60;
			$allowanon = false;
			$allowmod = true;
			$allowdel = false;
			$replyby = 2000000000;
			$postby = 2000000000;
			$hassubscrip = false;
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
	}
	$useeditor = "description";
	$pagetitle = "Forum Settings";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	if (isset($_GET['id'])) {
		echo "&gt; Modify Forum</div>\n";
		echo "<h2>Modify Forum <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=forumitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	} else {
		echo "&gt; Add Forum</div>\n";
		echo "<h2>Add Forum <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=forumitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	}
?>

<form method=post action="addforum.php?block=<?php echo $block;?>&cid=<?php echo $cid; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";}?>&folder=<?php echo $_GET['folder'];?>">
<span class=form>Name: </span><span class=formright><input type=text size=60 name=name value="<?php echo $line['name'];?>"></span><BR class=form>

Description:<BR>
<div class=editor>
<textarea cols=60 rows=20 id=description name=description style="width: 100%"><?php echo $line['description'];?></textarea>
</div>

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

<span class=form>Allow anonymous posts:</span><span class=formright>
<input type=checkbox name="allowanon" value="1" <?php if ($allowanon) { echo "checked=1";}?>/></span><br class="form"/>

<span class=form>Allow students to modify posts:</span><span class=formright>
<input type=checkbox name="allowmod" value="1" <?php if ($allowmod) { echo "checked=1";}?>/></span><br class="form"/>

<span class=form>Get email notify of new posts:</span><span class=formright>
<input type=checkbox name="subscribe" value="1" <?php if ($hassubscrip) { echo "checked=1";}?>/></span><br class="form"/>
<?php /*
<span class=form>Allow students to delete posts:</span><span class=formright>
<input type=checkbox name="allowdel" value="1" <?php if ($allowdel) { echo "checked=1";}?>/></span><br class="form"/>
*/ ?>

<span class=form>Default display:</span><span class=formright>
 <select name="defdisplay">
 <option value="0" <?php if ($defdisplay==0) {echo "selected=1";}?>>Expanded</option>
 <option value="1" <?php if ($defdisplay==1) {echo "selected=1";}?>>Collapsed</option>
 <option value="2" <?php if ($defdisplay==2) {echo "selected=1";}?>>Condensed</option>
 </select></span><br class="form" />
 
<span class=form>Students can reply to posts:</span><span class=formright>
<input type=radio name="replyby" value="Always" <?php if ($replyby==2000000000) { echo "checked=1";}?>/>Always<br/>
<input type=radio name="replyby" value="Never" <?php if ($replyby==0) { echo "checked=1";}?>/>Never<br/>
<input type=radio name="replyby" value="Date" <?php if ($replyby<2000000000 && $replyby>0) { echo "checked=1";}?>/>Before: 
<input type=text size=10 name=replybydate value="<?php echo $replybydate;?>">
<A HREF="#" onClick="cal1.select(document.forms[0].replybydate,'anchor3','MM/dd/yyyy',(document.forms[0].replybydate.value=='<?php echo $replybydate;?>')?(document.forms[0].replyby.value):(document.forms[0].replyby.value)); return false;" NAME="anchor3" ID="anchor3"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=replybytime value="<?php echo $replybytime;?>"></span><br class="form" />


<span class=form>Students can create new threads:</span><span class=formright>
<input type=radio name="postby" value="Always" <?php if ($postby==2000000000) { echo "checked=1";}?>/>Always<br/>
<input type=radio name="postby" value="Never" <?php if ($postby==0) { echo "checked=1";}?>/>Never<br/>
<input type=radio name="postby" value="Date" <?php if ($postby<2000000000 && $postby>0) { echo "checked=1";}?>/>Before: 
<input type=text size=10 name=postbydate value="<?php echo $postbydate;?>">
<A HREF="#" onClick="cal1.select(document.forms[0].postbydate,'anchor4','MM/dd/yyyy',(document.forms[0].postbydate.value=='<?php echo $postbydate;?>')?(document.forms[0].postby.value):(document.forms[0].postby.value)); return false;" NAME="anchor4" ID="anchor4"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=postbytime value="<?php echo $postbytime;?>"></span><br class="form"/>

<div class=submit><input type=submit value=Submit></div>

<?php
	require("../footer.php");
?>
