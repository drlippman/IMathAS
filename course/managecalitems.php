<?php
//IMathAS:  Manage Calendar Items
//(c) 2008 David Lippman

/*** master php includes *******/
require("../init.php");

if (!isset($teacherid)) {
	echo "You must be a teacher to access this page";
	exit;
}

$cid = Sanitize::courseId($_GET['cid']);
if (isset($_GET['from']) && $_GET['from']=='cal') {
	$from = 'cal';
} else {
	$from = 'cp';
}

//form processing
if (isset($_POST['submit'])) {
	//delete any marked for deletion
	if (isset($_POST['del']) && count($_POST['del'])>0) {
		foreach ($_POST['del'] as $id=>$val) {
			//DB $query = "DELETE FROM imas_calitems WHERE id='$id' AND courseid='$cid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_calitems WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$id, ':courseid'=>$cid));
		}
	}

	//update the rest
	if (isset($_POST['tag']) && count($_POST['tag'])>0) {
		foreach ($_POST['tag'] as $id=>$tag) {
			$date = $_POST['date'.$id];
			preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dmatches);
			$date = mktime(12,0,0,$dmatches[1],$dmatches[2],$dmatches[3]);
			//DB $query = "UPDATE imas_calitems SET date='$date',tag='$tag',title='{$_POST['txt'][$id]}' WHERE id='$id'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_calitems SET date=:date,tag=:tag,title=:title WHERE id=:id");
			$stm->execute(array(':date'=>$date, ':tag'=>$tag, ':title'=>$_POST['txt'][$id], ':id'=>$id));
		}
	}

	//add new
	if (trim($_POST['txtnew'])!='' || $_POST['tagnew'] != '!') {
		$date = $_POST['datenew'];
		preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dmatches);
		$datenew = mktime(12,0,0,$dmatches[1],$dmatches[2],$dmatches[3]);
		//DB $query = "INSERT INTO imas_calitems (courseid,date,tag,title) VALUES ('$cid','$datenew','{$_POST['tagnew']}','{$_POST['txtnew']}')";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("INSERT INTO imas_calitems (courseid,date,tag,title) VALUES (:courseid, :date, :tag, :title)");
		$stm->execute(array(':courseid'=>$cid, ':date'=>$datenew, ':tag'=>$_POST['tagnew'], ':title'=>$_POST['txtnew']));
	}
	if ($_POST['submit']=='Save') {
		if ($from=='cp') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/showcalendar.php?cid=$cid");
		}
		exit;
	}
}


//HTML output
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
if ($from=="cal") {
	echo "&gt; <a href=\"showcalendar.php?cid=$cid\">Calendar</a> ";
}
echo "&gt; Manage Calendar Items</div>\n";
echo '<div id="headermanagecalitems" class="pagetitle"><h2>Manage Calendar Items</h2></div>';
echo "<p>This page allows you to add events to the calendar.  Course items automatically place themselves on the calendar.</p>";

//DB $query = "SELECT id,date,title,tag FROM imas_calitems WHERE courseid='$cid' ORDER BY date";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
$stm = $DBH->prepare("SELECT id,date,title,tag FROM imas_calitems WHERE courseid=:courseid ORDER BY date");
$stm->execute(array(':courseid'=>$cid));

?>
<form method=post action="managecalitems.php?cid=<?php echo $cid.'&amp;from='.$from;?>">
<h4>Manage Events</h4>
<table class="gb">
<thead>
<tr><th>Delete?</th><th>Date</th><th>Tag</th><th>Event Details</th></tr>
</thead>
<tbody>
<?php
$cnt = 0;
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	echo '<tr>';
	echo '<td><input type=checkbox name="del['.$row[0].']" /></td>';
	$date = tzdate("m/d/Y",$row[1]);
	echo "<td><input type=text size=10 id=\"date{$row[0]}\" name=\"date{$row[0]}\" value=\"$date\"/> ";
	echo "<a href=\"#\" onClick=\"displayDatePicker('date{$row[0]}', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a></td>";
	$cnt++;
	echo '<td><input name="tag['.$row[0].']" type=text size=8 value="'.$row[3].'" /></td>';
	echo '<td><input name="txt['.$row[0].']" type=text size=80 value="'.str_replace('"','&quot;',$row[2]).'" /></td>';
	echo '<tr/>';
}
echo '</tbody></table>';
echo '<p><button type="submit" name="submit" value="Save">'._('Save Changes').'</button></p>';
echo '<h4>Add New Events</h4>';
echo '<table class="gb">
<thead>
<tr><th>Date</th><th>Tag</th><th>Event Details</th></tr>
</thead>
<tbody>';
$now = time();
echo '<tr>';
//echo '<td></td>';
if (isset($_GET['addto'])) {
	$date = tzdate("m/d/Y",$_GET['addto']);
} else if (isset($datenew)) {
	echo "datenew";
	$date = tzdate("m/d/Y",$datenew);
} else  {
	$date = tzdate("m/d/Y",$now);
}
echo "<td><input type=text size=10 id=\"datenew\" name=\"datenew\" value=\"$date\"/> ";
echo "<a href=\"#\" onClick=\"displayDatePicker('datenew', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a></td>";
$cnt++;
echo '<td><input name="tagnew" type=text size=8 value="!" /></td>';
echo '<td><input name="txtnew" type=text size=80 value="" /></td>';
echo '<tr/>';

?>
</thead>
</table>

<button type="submit" name="submit" value="SaveAndAdd"><?php echo _('Save and Add another') ?></button>
<button type="submit" name="submit" value="Save"><?php echo _('Save Changes') ?></button>
</form>

<?php
require("../footer.php");
?>
