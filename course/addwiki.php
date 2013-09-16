<?php
//IMathAS:  add/modify wiki
//(c) 2010 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "description";

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";

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
	
	if (isset($_GET['clearattempts'])) {
		if ($_GET['clearattempts']=='true') {
			$id = $_GET['id'];
			$query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id'";	
			mysql_query($query) or die("Query failed : " . mysql_error());
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addwiki.php?cid=$cid&id=$id");	
			exit;
		} else {
			$curBreadcrumb .= " &gt; <a href=\"addwiki.php?cid=$cid&id=$id\">Modify Wiki</a>";
			$curBreadcrumb .= " &gt; Clear all Wiki Revisions\n";	
			$pagetitle = "Confirm Page Contents Delete";
		}
	} else if ($_POST['name']!= null) { //FORM SUBMITTED, DATA PROCESSING
		if ($_POST['avail']==1) {
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
		} else {
			$startdate = 0;
			$enddate = 2000000000;
		}
		if ($_POST['rdatetype']=='Always') {
			$revisedate = 2000000000;
		} else if ($_POST['rdatetype']=='Never') {
			$revisedate = 0;
		} else {
			$revisedate = parsedatetime($_POST['rdate'],$_POST['rtime']);
		}
		$settings = intval($_POST['settings']);
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script');
		if ($_POST['description']=='<p>Enter Wiki description here</p>') {
			$_POST['description'] = '';
		} else {
			$_POST['description'] = addslashes(htmLawed(stripslashes($_POST['description']),$htmlawedconfig));
		}
		if (isset($_GET['id'])) {  //already have id - update
			$query = "UPDATE imas_wikis SET name='{$_POST['name']}',description='{$_POST['description']}',startdate=$startdate,enddate=$enddate,";
			$query .= "editbydate=$revisedate,avail='{$_POST['avail']}',groupsetid='{$_POST['groupsetid']}',settings=$settings ";
			$query .= "WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$newwikiid = $_GET['id'];	
		} else { //add new
			$query = "INSERT INTO imas_wikis (courseid,name,description,startdate,enddate,editbydate,avail,settings,groupsetid) VALUES ";
			$query .= "('$cid','{$_POST['name']}','{$_POST['description']}',$startdate,$enddate,$revisedate,'{$_POST['avail']}',$settings,'{$_POST['groupsetid']}');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$newwikiid = mysql_insert_id();
			
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "('$cid','Wiki','$newwikiid');";
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
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
			
		exit;
	} else { //INITIAL LOAD DATA PROCESS
		
		if (isset($_GET['id'])) {
			$curBreadcrumb .= "&gt; Modify Wiki\n";
			$pagetitle = "Modify Wiki";
		} else {
			$curBreadcrumb .= "&gt; Add Wiki\n";
			$pagetitle = "Add Wiki";
		} 
		

		if (isset($_GET['id'])) { //MODIFY MODE
			$query = "SELECT * FROM imas_wikis WHERE id='{$_GET['id']}';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$startdate = $line['startdate'];
			$enddate = $line['enddate'];
			$revisedate = $line['editbydate'];
			$settings = $line['settings'];
			$query = "SELECT id FROM imas_wiki_revisions WHERE wikiid='{$_GET['id']}';";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$started = true;
			} else {
				$started = false;
			}
			if ($line['description']=='') {
				//$line['description'] = "<p>Enter Wiki description here</p>";
			}
		} else {
			$line['name'] = "Enter Wiki Name here";
			$line['description'] = "<p>Enter Wiki description here</p>";
			$line['avail'] = 1;
			$line['groupsetid'] = 0;
			$startdate = time();
			$enddate = time() + 7*24*60*60;
			$revisedate  = 2000000000;
			$settings = 0;
			$started = false;
		}
		
		$page_formActionTag = "?block=$block&cid=$cid&folder=" . $_GET['folder'];
		$page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";
		$page_formActionTag .= "&tb=$totb";
		
		$hr = floor($coursedeftime/60)%12;
		$min = $coursedeftime%60;
		$am = ($coursedeftime<12*60)?'am':'pm';
		$deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
	
		if ($startdate!=0) {
			$sdate = tzdate("m/d/Y",$startdate);
			$stime = tzdate("g:i a",$startdate);
		} else {
			$sdate = tzdate("m/d/Y",time());
			$stime = $deftime; //tzdate("g:i a",time());
		}
		if ($enddate!=2000000000) {
			$edate = tzdate("m/d/Y",$enddate);
			$etime = tzdate("g:i a",$enddate);	
		} else {
			$edate = tzdate("m/d/Y",time()+7*24*60*60);
			$etime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
		}  
		if ($revisedate<2000000000 && $revisedate>0) {
			$rdate = tzdate("m/d/Y",$revisedate);
			$rtime = tzdate("g:i a",$revisedate);	
		} else {
			$rdate = tzdate("m/d/Y",time()+7*24*60*60);
			$rtime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
		}
		
		if (!isset($_GET['id'])) {
			$stime = $deftime;
			$etime = $deftime;
			$rtime = $deftime;
		}
		
		$query = "SELECT id,name FROM imas_stugroupset WHERE courseid='$cid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$i=0;
		$page_groupSelect = array();
		while ($row = mysql_fetch_row($result)) {
			$page_groupSelect['val'][$i] = $row[0];
			$page_groupSelect['label'][$i] = "Use group set: {$row[1]}";
			$i++;
		}
	}
}

//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //DISPLAY 	

?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headeraddwiki" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php
if (isset($_GET['clearattempts'])) {
	$id = $_GET['id'];
	echo '<p>Are you SURE you want to delete all contents and history for this Wiki page? ';
	echo 'This will clear contents for all groups if you are using groups.</p>';
	echo "<p><a href=\"addwiki.php?cid=$cid&id=$id&clearattempts=true\">Yes, I'm Sure</a> | ";
	echo "<a href=\"addwiki.php?cid=$cid&id=$id\">Nevermind</a></p>";

} else { //default display

if ($started) {
	echo '<p>Revisions have already been made on this wiki.  Changing group settings has been disabled.  If you want to change the ';
	echo 'group settings, you should clear all existing wiki content.</p>';
	echo '<p><input type="button" value="Clear All Wiki Content"  onclick="window.location=\'addwiki.php?cid='.$cid.'&id='.$_GET['id'].'&clearattempts=ask\'" /></p>';
}

?>

	<form method=post action="addwiki.php<?php echo $page_formActionTag ?>">
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
		<span class=form>Group wiki?</span><span class=formright>
<?php
if ($started) {
	writeHtmlSelect("ignoregroupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0,$started?'disabled="disabled"':'');
	echo '<input type="hidden" name="groupsetid" value="'.$line['groupsetid'].'" />';
} else {
	writeHtmlSelect("groupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0);
}
?>
		</span><br class="form"/>
		
		<span class=form>Students can edit:</span>
		<span class=formright>
			<input type=radio name="rdatetype" value="Always" <?php if ($revisedate==2000000000) { echo "checked=1";}?>/>Always<br/>
			<input type=radio name="rdatetype" value="Never" <?php if ($revisedate==0) { echo "checked=1";}?>/>Never<br/>
			<input type=radio name="rdatetype" value="Date" <?php if ($revisedate<2000000000 && $revisedate>0) { echo "checked=1";}?>/>Before: 
			<input type=text size=10 name="rdate" value="<?php echo $rdate;?>">
			<a href="#" onClick="displayDatePicker('rdate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=rtime value="<?php echo $rtime;?>">
		</span><br class="form" />
		
		<div class=submit><input type=submit value=Submit></div>
	</form>	

<?php
}//default display
} 

require("../footer.php");
?>


