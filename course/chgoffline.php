<?php
//IMathAS:  Mass change offline grade items
//(c) 2010 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require_once("../includes/TeacherAuditLog.php");

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

		} else {
			$checkedlist = implode(',', $checked);

			require("../header.php");
            echo "<div class=breadcrumb>$breadcrumbbase ";
            if (empty($_COOKIE['fromltimenu'])) {
                echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
            }
            echo " <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
			echo "&gt; <a href=\"chgoffline.php?cid=$cid\">Manage Offline Grades</a> &gt; Confirm Delete</div>";
			echo "<form id=\"mainform\" method=post action=\"chgoffline.php?cid=$cid\">";
			echo '<input type="hidden" name="submit" value="Delete" />';
			echo '<input type="hidden" name="checked" value="'.$checkedlist.'"/>';
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
			require("../footer.php");
			exit;
		}
	} else {
		require_once("../includes/parsedatetime.php");

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
$page_gbcatSelect = array();
if ($stm->rowCount()>0) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$page_gbcatSelect['val'][$i] = $row[0];
		$page_gbcatSelect['label'][$i] = $row[1];
		$i++;
	}
}

$sdate = tzdate("m/d/Y",time());
$stime = tzdate("g:i a",time());
$line['tutoredit'] = isset($CFG['AMS']['tutoredit'])?$CFG['AMS']['tutoredit']:0;

//HTML output
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<script type="text/javascript">
 $(function() {
   $("#options td:first-child input[type=checkbox").on("change", function() {
	$(this).parents("tr").toggleClass("odd");
   });
 });
 </script>';
require("../header.php");

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
	require("../footer.php");
	exit;
} else {
	echo '<div class="cpmid"><a href="uploadmultgrades.php?cid='.$cid.'">Upload multiple offline grades</a></div>';
}
?>
Check: <a href="#" onclick="return chkAllNone('mainform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('mainform','checked[]',false)">None</a>

<ul class=nomark>

<?php

foreach($gbitems as $id=>$name) {
	echo '<li><input type="checkbox" name="checked[]" value="' . Sanitize::encodeStringForDisplay($id) . '" /> ' . Sanitize::encodeStringForDisplay($name) . ' <a class="small" href="addgrades.php?cid=' . Sanitize::encodeUrlParam($cid) . '&grades=all&gbitem=' . Sanitize::encodeUrlParam($id) . '" target="_blank">Edit</a></li>';
}
?>
</ul>
<p>With selected, <input type="submit" name="submit" value="Delete"/> or make changes below
<fieldset>
<legend>Offline Grade Options</legend>
<table class=gb id=options>
<thead>
<tr><th>Change?</th><th>Option</th><th>Setting</th></tr>
</thead>
<tbody>
<tr>
	<td><input type="checkbox" name="chgshowafter" /></td>
	<td class="r">Show after:</td>
	<td>
<input type=radio name="sdatetype" value="0" /> Always<br/>
<input type=radio name="sdatetype" value="sdate" checked="checked"/><input type=text size=10 name=sdate value="<?php echo $sdate;?>">
<a href="#" onClick="displayDatePicker('sdate', this); return false">
<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
at <input type=text size=10 name=stime value="<?php echo $stime;?>">
	</td>
</tr>
<tr>
	<td><input type="checkbox" name="chgcount" /></td>
	<td class="r">Count:</td>
	<td>
	<input type="radio" name="cntingb" value="1" checked="checked" />Count in Gradebook<br/>
	<input type="radio" name="cntingb" value="0" />Don't count in grade total and hide from students<br/>
	<input type="radio" name="cntingb" value="3" />Don't count in grade total<br/>
	<input type="radio" name="cntingb" value="2" />Count as Extra Credit
	</td>
</tr>
<tr>
	<td><input type="checkbox" name="chggbcat" /></td>
	<td class="r">Gradebook category: </td>
	<td>
<?php
writeHtmlSelect ("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],null,"Default",0," id=gbcat");
?>

	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgtutoredit"/></td>
	<td class="r">Tutor Access: </td>
	<td>
<?php
$page_tutorSelect['label'] = array("No access","View Scores","View and Edit Scores");
$page_tutorSelect['val'] = array(2,0,1);
writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$line['tutoredit']);

?>
	</td>
</tr>
</tbody>
</table>
</fieldset>
<div class="submit"><input type="submit" name="submit" value="<?php echo _('Apply Changes')?>" /></div>
</form>
<?php
require("../footer.php");
?>
