<?php
//IMathAS:  add/modify wiki
//(c) 2010 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "description";

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=". Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";

if (isset($_GET['tb'])) {
	$totb = Sanitize::encodeStringForDisplay($_GET['tb']);
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
	$cid = Sanitize::courseId($_GET['cid']);
	$block = $_GET['block'];

	if (isset($_REQUEST['clearattempts'])) {
		if (isset($_POST['clearattempts']) && $_POST['clearattempts']=="true") {
			$id = Sanitize::onlyInt($_GET['id']);
			//DB $query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_wiki_revisions WHERE wikiid=:wikiid");
			$stm->execute(array(':wikiid'=>$id));
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addwiki.php?cid=$cid&id=$id&r=" .Sanitize::randomQueryStringParam());
			exit;
		} else {
			$curBreadcrumb .= " &gt; <a href=\"addwiki.php?cid=$cid&id=$id\">Modify Wiki</a>";
			$curBreadcrumb .= " &gt; Clear all Wiki Revisions\n";
			$pagetitle = "Confirm Page Contents Delete";
		}
	} else if ($_POST['name']!= null) { //FORM SUBMITTED, DATA PROCESSING
		if ($_POST['avail']==1) {
			require_once("../includes/parsedatetime.php");
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

		//DB $_POST['name'] = addslashes(htmlentities(stripslashes($_POST['name'])));
		$_POST['name'] = htmlentities($_POST['name']);

		require_once("../includes/htmLawed.php");
		if ($_POST['description']=='<p>Enter Wiki description here</p>') {
			$_POST['description'] = '';
		} else {
			//DB $_POST['description'] = addslashes(myhtmLawed(stripslashes($_POST['description'])));
			$_POST['description'] = myhtmLawed($_POST['description']);
		}
		if (isset($_GET['id'])) {  //already have id - update
			//DB $query = "UPDATE imas_wikis SET name='{$_POST['name']}',description='{$_POST['description']}',startdate=$startdate,enddate=$enddate,";
			//DB $query .= "editbydate=$revisedate,avail='{$_POST['avail']}',groupsetid='{$_POST['groupsetid']}',settings=$settings ";
			//DB $query .= "WHERE id='{$_GET['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "UPDATE imas_wikis SET name=:name,description=:description,startdate=:startdate,enddate=:enddate,";
			$query .= "editbydate=:editbydate,avail=:avail,groupsetid=:groupsetid,settings=:settings ";
			$query .= "WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':name'=>$_POST['name'], ':description'=>$_POST['description'], ':startdate'=>$startdate, ':enddate'=>$enddate,
				':editbydate'=>$revisedate, ':avail'=>$_POST['avail'], ':groupsetid'=>$_POST['groupsetid'], ':settings'=>$settings, ':id'=>$_GET['id']));
			$newwikiid = $_GET['id'];
		} else { //add new
			//DB $query = "INSERT INTO imas_wikis (courseid,name,description,startdate,enddate,editbydate,avail,settings,groupsetid) VALUES ";
			//DB $query .= "('$cid','{$_POST['name']}','{$_POST['description']}',$startdate,$enddate,$revisedate,'{$_POST['avail']}',$settings,'{$_POST['groupsetid']}');";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $newwikiid = mysql_insert_id();
			$query = "INSERT INTO imas_wikis (courseid,name,description,startdate,enddate,editbydate,avail,settings,groupsetid) VALUES ";
			$query .= "(:courseid, :name, :description, :startdate, :enddate, :editbydate, :avail, :settings, :groupsetid);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':name'=>$_POST['name'], ':description'=>$_POST['description'], ':startdate'=>$startdate,
				':enddate'=>$enddate, ':editbydate'=>$revisedate, ':avail'=>$_POST['avail'], ':settings'=>$settings, ':groupsetid'=>$_POST['groupsetid']));
			$newwikiid = $DBH->lastInsertId();

			//DB $query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			//DB $query .= "('$cid','Wiki','$newwikiid');";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $itemid = mysql_insert_id();
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "(:courseid, 'Wiki', :typeid);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':typeid'=>$newwikiid));
			$itemid = $DBH->lastInsertId();

			//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid';";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$cid));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
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
			//DB $itemorder = addslashes(serialize($items));
			$itemorder = serialize($items);
			//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid';";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));


		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']). "&r=" .Sanitize::randomQueryStringParam());

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
			//DB $query = "SELECT * FROM imas_wikis WHERE id='{$_GET['id']}';";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT * FROM imas_wikis WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			$startdate = $line['startdate'];
			$enddate = $line['enddate'];
			$revisedate = $line['editbydate'];
			$settings = $line['settings'];
			//DB $query = "SELECT id FROM imas_wiki_revisions WHERE wikiid='{$_GET['id']}';";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->prepare("SELECT id FROM imas_wiki_revisions WHERE wikiid=:wikiid");
			$stm->execute(array(':wikiid'=>$_GET['id']));
			if ($stm->rowCount()>0) {
				$started = true;
			} else {
				$started = false;
			}
			if ($line['description']=='') {
				//$line['description'] = "<p>Enter Wiki description here</p>";
			}
			$savetitle = _("Save Changes");
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
			$savetitle = _("Create Wiki");
		}

		$page_formActionTag = "?block=".Sanitize::encodeUrlParam($block)."&cid=$cid&folder=" . Sanitize::encodeUrlParam($_GET['folder']);
		$page_formActionTag .= (isset($_GET['id'])) ? "&id=" . Sanitize::encodeUrlParam($_GET['id']) : "";
		$page_formActionTag .= "&tb=".Sanitize::encodeUrlParam($totb);

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
			$stime = $defstime; //tzdate("g:i a",time());
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
			$stime = $defstime;
			$etime = $deftime;
			$rtime = $deftime;
		}

		//DB $query = "SELECT id,name FROM imas_stugroupset WHERE courseid='$cid' ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT id,name FROM imas_stugroupset WHERE courseid=:courseid ORDER BY name");
		$stm->execute(array(':courseid'=>$cid));
		$i=0;
		$page_groupSelect = array();
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
	<div id="headeraddwiki" class="pagetitle"><h1><?php echo $pagetitle ?></h1></div>
<?php
if (isset($_GET['clearattempts'])) {
	$id = Sanitize::onlyInt($_GET['id']);
	echo '<p>Are you SURE you want to delete all contents and history for this Wiki page? ';
	echo 'This will clear contents for all groups if you are using groups.</p>';

	echo '<form method="POST" action="'.sprintf('addwiki.php?cid=%d&id=%d', $cid, $id) .'">';
	echo '<p><button type=submit name="clearattempts" value="true">'._("Yes, I'm Sure").'</button>';
	echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='".sprintf('addwiki.php?cid=%d&id=%d', $cid, $id)."'\"></p>\n";
	echo '</form>';

} else { //default display

if ($started) {
	echo '<p>Revisions have already been made on this wiki.  Changing group settings has been disabled.  If you want to change the ';
	echo 'group settings, you should clear all existing wiki content.</p>';
	echo '<p><input type="button" value="Clear All Wiki Content"  onclick="window.location=\'addwiki.php?cid='.$cid.'&id=' . Sanitize::onlyInt($_GET['id']) . '&clearattempts=ask\'" /></p>';
}

?>

	<form method=post action="addwiki.php<?php echo $page_formActionTag; ?>">
		<span class=form>Name: </span>
		<span class=formright><input type=text size=60 name=name value="<?php echo str_replace('"','&quot;',$line['name']);?>"></span>
		<BR class=form>

		Description:<BR>
		<div class=editor>
		<textarea cols=60 rows=20 id=description name=description style="width: 100%">
		<?php echo Sanitize::encodeStringForDisplay($line['description']);?></textarea>
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

		<div class=submit><input type=submit value="<?php echo $savetitle;?>"></div>
	</form>

<?php
}//default display
}

require("../footer.php");
?>
