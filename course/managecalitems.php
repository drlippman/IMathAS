<?php
//IMathAS:  Manage Calendar Items
//(c) 2008 David Lippman

/*** master php includes *******/
require("../init.php");

if (!isset($teacherid)) {
	echo _("You must be a teacher to access this page");
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
			$stm = $DBH->prepare("UPDATE imas_calitems SET date=:date,tag=:tag,title=:title WHERE id=:id");
			$stm->execute(array(':date'=>$date, ':tag'=>Sanitize::stripHtmlTags($tag),
				':title'=>Sanitize::stripHtmlTags($_POST['txt'][$id]), ':id'=>$id));
		}
	}

	//add new
	$newcnt = 0;
	while (isset($_POST['datenew-'.$newcnt])) {
		if (trim($_POST['tagnew-'.$newcnt])!='' && (trim($_POST['txtnew-'.$newcnt])!='' || $_POST['tagnew-'.$newcnt] != '!')) {
			$date = $_POST['datenew-'.$newcnt];
			preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dmatches);
            $datenew = mktime(12,0,0,$dmatches[1],$dmatches[2],$dmatches[3]);
            $dayofweek = date('w', $datenew);
            $tag = Sanitize::stripHtmlTags($_POST['tagnew-'.$newcnt]);
            $title = Sanitize::stripHtmlTags($_POST['txtnew-'.$newcnt]);
            $qarr = [$cid, $datenew, $tag, $title];
            if (!empty($_POST['repeat'.$newcnt])) {
                foreach ($_POST['repeat'.$newcnt] as $daytorepeat) {
                    $dayoffset = ($daytorepeat - $dayofweek + 7)%7;
                    for ($i=($dayoffset==0)?1:0;$i<$_POST['repeatN'.$newcnt];$i++) {
                        $date = strtotime("+$dayoffset days +$i weeks", $datenew);
                        array_push($qarr, $cid, $date, $tag, $title);
                    }
                }
            }
            $ph = Sanitize::generateQueryPlaceholdersGrouped($qarr, 4);
            $stm = $DBH->prepare("INSERT INTO imas_calitems (courseid,date,tag,title) VALUES $ph");
            $stm->execute($qarr);

		}
		$newcnt++;
	}
	if ($_POST['submit']=='Save') {
		if ($from=='cp') {
			$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid$btf" . "&r=" . Sanitize::randomQueryStringParam());
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/showcalendar.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		}
		exit;
	}
}


//HTML output
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<script type="text/javascript">
	$(document).on("submit","form",function() {
		window.onbeforeunload = null;
		return true;
	});
	var nextnewcnt = 0;
	function addnewevent() {
		var html = "<tr><td><input type=text size=10 id=\"datenew-"+nextnewcnt+"\" name=\"datenew-"+nextnewcnt+"\"> ";
		html += "<a href=\"#\" onClick=\"displayDatePicker(\'datenew-"+nextnewcnt+"\', this); return false\"><img src=\"'.$staticroot.'/img/cal.gif\" alt=\"Calendar\"/></a></td>";
		html += "<td><input name=\"tagnew-"+nextnewcnt+"\" id=\"tagnew-"+nextnewcnt+"\" type=text size=8 /></td>";
        html += "<td><input name=\"txtnew-"+nextnewcnt+"\" id=\"txtnew-"+nextnewcnt+"\" type=text size=80 />";
        html += "<button type=\"button\" onclick=\"$(\'#repeat"+nextnewcnt+"\').toggle()\">'._('Repeat').'</button>";
        html += "<span id=\"repeat"+nextnewcnt+"\" style=\"display:none\"><br/>'._('Repeat every').'"
          + "<label><input type=\"checkbox\" value=\"1\" name=\"repeat"+nextnewcnt+"[]\">'._('M').'</label> "
          + "<label><input type=\"checkbox\" value=\"2\" name=\"repeat"+nextnewcnt+"[]\">'._('T').'</label> "
          + "<label><input type=\"checkbox\" value=\"3\" name=\"repeat"+nextnewcnt+"[]\">'._('W').'</label> "
          + "<label><input type=\"checkbox\" value=\"4\" name=\"repeat"+nextnewcnt+"[]\">'._('Th').'</label> "
          + "<label><input type=\"checkbox\" value=\"5\" name=\"repeat"+nextnewcnt+"[]\">'._('F').'</label> "
          + "<label><input type=\"checkbox\" value=\"6\" name=\"repeat"+nextnewcnt+"[]\">'._('Sa').'</label> "
          + "<label><input type=\"checkbox\" value=\"0\" name=\"repeat"+nextnewcnt+"[]\">'._('Su').'</label> "
          + "'._('for').' <input size=2 name=\"repeatN"+nextnewcnt+"\" value=1> '._('weeks').'</span></td></tr>";
		$("#newEventsTable tbody").append(html);
		$("#datenew-"+nextnewcnt).val($("#datenew-"+(nextnewcnt-1)).val());
		$("#tagnew-"+nextnewcnt).val($("#tagnew-"+(nextnewcnt-1)).val());
		$("#txtnew-"+nextnewcnt).val($("#txtnew-"+(nextnewcnt-1)).val());
		nextnewcnt++;
		if (!haschanged) {
			haschanged = true;
			window.onbeforeunload = function() {return unsavedmsg;}
		}
	}
	var unsavedmsg = "'._("You have unrecorded changes.  Are you sure you want to abandon your changes?").'";
	var haschanged = false;
	function txtchg() {
		if (!haschanged) {
			haschanged = true;
			window.onbeforeunload = function() {return unsavedmsg;}
		}
	}
    </script>
    <style> td { white-space: nowrap;}</style>';
require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
if ($from=="cal") {
	echo "&gt; <a href=\"showcalendar.php?cid=$cid\">Calendar</a> ";
}
echo "&gt; "._("Manage Calendar Items")."</div>\n";
echo '<div id="headermanagecalitems" class="pagetitle"><h1>'._('Manage Calendar Items').'</h1></div>';
echo "<p>"._("This page allows you to add events to the calendar.  Course items automatically place themselves on the calendar.")."</p>";
$stm = $DBH->prepare("SELECT id,date,title,tag FROM imas_calitems WHERE courseid=:courseid ORDER BY date");
$stm->execute(array(':courseid'=>$cid));

?>
<form method=post action="managecalitems.php?cid=<?php echo $cid.'&amp;from='.$from;?>">
<h3>Manage Events</h3>
<table class="gb">
<thead>
<tr><th>Delete?</th><th>Date</th><th>Tag</th><th>Event Details</th></tr>
</thead>
<tbody>
<?php
$cnt = 0;
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	echo '<tr>';
	echo '<td><input type=checkbox name="del['.Sanitize::onlyInt($row[0]).']" /></td>';
	$date = tzdate("m/d/Y",$row[1]);
	echo "<td><input type=text size=10 id=\"date" . Sanitize::onlyInt($row[0]) . "\" name=\"date".Sanitize::onlyInt($row[0])."\" value=\"";
	echo Sanitize::encodeStringForDisplay($date) . "\" oninput=\"txtchg()\" /> ";
	echo "<a href=\"#\" onClick=\"displayDatePicker('date".Sanitize::onlyInt($row[0])."', this); return false\"><img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></a></td>";
	$cnt++;
	echo '<td><input name="tag['.Sanitize::onlyInt($row[0]).']" type=text size=8 value="'.Sanitize::encodeStringForDisplay($row[3]).'" oninput="txtchg()" /></td>';
	echo '<td><input name="txt['.Sanitize::onlyInt($row[0]).']" type=text size=80 value="'.Sanitize::encodeStringForDisplay($row[2]).'" oninput="txtchg()" /></td>';
	echo '<tr/>';
}
echo '</tbody></table>';
echo '<p><button type="submit" name="submit" value="Save">'._('Save Changes').'</button></p>';
echo '<h3>Add New Events</h3>';
echo '<table class="gb" id="newEventsTable">
<thead>
<tr><th>Date</th><th>Tag</th><th>'._('Event Details').'</th></tr>
</thead>
<tbody>';
$now = time();
/*echo '<tr>';
//echo '<td></td>';
if (isset($_GET['addto'])) {
	$date = tzdate("m/d/Y",$_GET['addto']);
} else if (isset($datenew)) {
	$date = tzdate("m/d/Y",$datenew);
} else  {
	$date = tzdate("m/d/Y",$now);
}
echo "<td><input type=text size=10 id=\"datenew-0\" name=\"datenew-0\" value=\"$date\" oninput=\"txtchg()\"/> ";
echo "<a href=\"#\" onClick=\"displayDatePicker('datenew-0', this); return false\"><img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></a></td>";
$cnt++;
echo '<td><input name="tagnew-0" id="tagnew-0" type=text size=8 value="!" oninput="txtchg()" /></td>';
echo '<td><input name="txtnew-0" id="txtnew-0" type=text size=80 value="" oninput="txtchg()" /></td>';
echo '<tr/>';
*/
?>
</thead>
</table>
<button type="button" onclick="addnewevent()"><?php echo _('Add Event') ?></button>
<button type="submit" name="submit" value="Save"><?php echo _('Save Changes') ?></button>
</form>

<?php
require("../footer.php");
?>
