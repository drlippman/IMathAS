<?php
//IMathAS:  Mass change offline grade items
//(c) 2010 David Lippman

/*** master php includes *******/
require_once "../init.php";
require_once "../includes/htmlutil.php";
require_once "../includes/TeacherAuditLog.php";

if (!isset($teacherid)) {
	echo "You need to log in as a teacher to access this page";
	exit;
}
$cid = Sanitize::courseId($_GET['cid']);

if (isset($_POST['checked'])) { //form submitted
	if (!is_array($_POST['checked'])) {
		$_POST['checked'] = explode(',',$_POST['checked']);
	}
	$checked = array_map('Sanitize::onlyInt', $_POST['checked']);
	$ph = Sanitize::generateQueryPlaceholders($checked);
	if ($_POST['submit']=="Delete") {
		if (isset($_POST['confirm'])) {
			$gbitems = array();
			$stm = $DBH->prepare("SELECT id,name,points FROM imas_gbitems WHERE id IN ($ph)");
			$stm->execute($checked);
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$gbitems[$row['id']] = array('name'=>$row['name'], 'points'=>$row['points']);
			}
			$grades = array();
			$stm = $DBH->prepare("SELECT userid,gradetypeid,score FROM imas_grades WHERE gradetype='offline' AND gradetypeid IN ($ph)");
			$stm->execute($checked);
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$grades[$row['gradetypeid']][$row['userid']] = $row['score'];
			}
			TeacherAuditLog::addTracking(
				$cid,
				"Delete Item",
				null,
				array(
					'type'=>'Delete Offline',
					'items'=>$gbitems,
					'grades'=>$grades
				)
			);

			$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid IN ($ph)");
			$stm->execute($checked);

			$stm = $DBH->prepare("DELETE FROM imas_gbitems WHERE id IN ($ph)");
			$stm->execute($checked);

            $stm = $DBH->prepare("DELETE FROM imas_excused WHERE type='O' AND typeid IN ($ph)");
            $stm->execute($checked);

		} else {
			$checkedlist = implode(',', array_map('intval', $checked));

			require_once "../header.php";
            echo "<div class=breadcrumb>$breadcrumbbase ";
            if (empty($_COOKIE['fromltimenu'])) {
                echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
            }
            echo " <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
			echo "&gt; <a href=\"chgoffline.php?cid=$cid\">Manage Offline Grades</a> &gt; Confirm Delete</div>";
			echo "<form id=\"mainform\" method=post action=\"chgoffline.php?cid=$cid\">";
			echo '<input type="hidden" name="submit" value="Delete" />';
			echo '<input type="hidden" name="checked" value="'.Sanitize::encodeStringForDisplay($checkedlist).'"/>';
			echo '<p>Are you <b>SURE</b> you want to delete these offline grade items ';
			echo 'and the associated student grades?<br/>If you haven\'t already, you might want to back up the gradebook first.</p><p>';
			$stm = $DBH->prepare("SELECT name FROM imas_gbitems WHERE id IN ($ph)");
			$stm->execute($checked);
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				echo Sanitize::encodeStringForDisplay($row[0]) . '<br/>';
			}
			//echo '</p><p><input type="submit" value="Yes, Delete"/>';
			echo '</p><p><button type=submit name=confirm value=true>'._('Yes, Delete').'</button> ';
			echo '<input type=button value="Nevermind" class="secondarybtn" onClick="window.location=\'gradebook.php?cid='.$cid.'\'"></p>';
			echo '</form>';
			require_once "../footer.php";
			exit;
		}
	} else {
		require_once "../includes/parsedatetime.php";

		$sets = array();
		$qarr = array();
		if (isset($_POST['chgshowafter'])) {
			if ($_POST['sdatetype']=='0') {
				$showdate = 0;
			} else {
				$showdate = parsedatetime($_POST['sdate'],$_POST['stime'],0);
			}
			$sets[] = "showdate=?";
			$qarr[] = $showdate;
		}
		if (isset($_POST['chgcount'])) {
			$sets[] = "cntingb=?";
			$qarr[] = $_POST['cntingb'];
		}
		if (isset($_POST['chgtutoredit'])) {
			$sets[] = "tutoredit=?";
			$qarr[] = $_POST['tutoredit'];
		}
		if (isset($_POST['chggbcat'])) {
			$sets[] = "gbcategory=?";
			$qarr[] = $_POST['gbcat'];
		}
		if (count($sets)>0) {
			$setslist = implode(',',$sets);
			$stm = $DBH->prepare("UPDATE imas_gbitems SET $setslist WHERE id IN ($ph)");
			$stm->execute(array_merge($qarr, $checked));
		}
	}

	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
	exit;
}

//Prep for output
$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
$i=0;
$page_gbcatSelect = array('val'=>[], 'label'=>[]);
if ($stm->rowCount()>0) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$page_gbcatSelect['val'][$i] = $row[0];
		$page_gbcatSelect['label'][$i] = $row[1];
		$i++;
	}
}

$sdate = tzdate("m/d/Y",time());
$stime = tzdate("g:i a",time());
$tutoreditdef = isset($CFG['AMS']['tutoredit'])?$CFG['AMS']['tutoredit']:0;

//HTML output
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<script type="text/javascript">
 $(function() {
   $("#options td:first-child input[type=checkbox").on("change", function() {
	$(this).parents("tr").toggleClass("odd");
   });
 });
 </script>';
require_once "../header.php";

echo "<div class=breadcrumb>$breadcrumbbase ";
if (empty($_COOKIE['fromltimenu'])) {
    echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
echo " <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
echo "&gt; Manage Offline Grades</div>";
echo '<div id="headerchgoffline" class="pagetitle"><h1>Manage Offline Grades</h1></div>';

echo "<form id=\"mainform\" method=post action=\"chgoffline.php?cid=$cid\">";

$gbitems = array();
$stm = $DBH->prepare("SELECT id,name FROM imas_gbitems WHERE courseid=:courseid ORDER BY name");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$gbitems[$row[0]] = $row[1];
}
if (count($gbitems)==0) {
	echo "<p>No offline grades.  <a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">Add one</a> or ";
	echo '<a href="uploadmultgrades.php?cid='.$cid.'">Upload multiple offline grades</a></p>';
	require_once "../footer.php";
	exit;
} else {
	echo '<div class="cpmid"><a href="uploadmultgrades.php?cid='.$cid.'">Upload multiple offline grades</a></div>';
}
?>
Check: <a href="#" onclick="return chkAllNone('mainform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('mainform','checked[]',false)">None</a>

<ul class=nomark>

<?php

foreach($gbitems as $id=>$name) {
	echo '<li><label><input type="checkbox" name="checked[]" value="' . Sanitize::encodeStringForDisplay($id) . '" /> ' . Sanitize::encodeStringForDisplay($name) . '</label> ';
	echo '<a class="small" href="addgrades.php?cid=' . Sanitize::encodeUrlParam($cid) . '&grades=all&gbitem=' . Sanitize::encodeUrlParam($id) . '" target="_blank">Edit ' . Sanitize::encodeStringForDisplay($name) . '</a></li>';
}
?>
</ul>
<p>With selected, <input type="submit" name="submit" value="Delete"/> or make changes below
<fieldset>
<legend>Offline Grade Options</legend>
<table class=gb id=options>
<caption class="sr-only">Settings</caption>
<thead>
<tr><th>Change?</th><th>Option</th><th>Setting</th></tr>
</thead>
<tbody>
<tr>
	<td><input type="checkbox" name="chgshowafter" aria-labelledby="opshowafter"/></td>
	<td class="r"><span id="opshowafter">Show after:</span></td>
	<td role=group aria-labelledby="opshowafter">
		<label><input type=radio name="sdatetype" value="0"/> Always</label><br/>
		<input type=radio name="sdatetype" value="sdate" checked="checked" aria-label="Date"/>
		<input type=text size=10 name=sdate value="<?php echo $sdate;?>" aria-label="show after date">
		<a href="#" onClick="displayDatePicker('sdate', this); return false">
		<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
		at <input type=text size=10 name=stime value="<?php echo $stime;?>" aria-label="show after time">
	</td>
</tr>
<tr>
	<td><input type="checkbox" name="chgcount" aria-labelledby="opcount"/></td>
	<td class="r"><span id="opcount">Count:</label></td>
	<td>
	<select name=cntingb id=cntingb aria-labelledby="opcount">
		<option value=1 selected><?php echo _('Count in Gradebook');?></option>
		<option value=0><?php echo _('Don\'t count in grade total and hide from students');?></option>
		<option value=3><?php echo _('Don\'t count in grade total');?></option>
		<option value=2><?php echo _('Count as Extra Credit');?></option>
	</select>
	</td>
</tr>
<tr>
	<td><input type="checkbox" name="chggbcat" aria-labelledby="opgbcat"/></td>
	<td class="r"><label for="gbcat" id="opgbcat">Gradebook category</label>: </td>
	<td>
<?php
writeHtmlSelect ("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],null,"Default",0," id=gbcat");
?>

	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgtutoredit" aria-labelledby="optutoredit"/></td>
	<td class="r"><label for="tutoredit" id="optutoredit">Tutor Access:</label></td>
	<td>
<?php
$page_tutorSelect['label'] = array("No access","View Scores","View and Edit Scores");
$page_tutorSelect['val'] = array(2,0,1);
writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$tutoreditdef);

?>
	</td>
</tr>
</tbody>
</table>
</fieldset>
<div class="submit"><input type="submit" name="submit" value="<?php echo _('Apply Changes')?>" /></div>
</form>
<?php
require_once "../footer.php";
?>
