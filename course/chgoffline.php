<?php
//IMathAS:  Mass change offline grade items
//(c) 2010 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

if (!isset($teacherid)) {
	echo "You need to log in as a teacher to access this page";
	exit;
}
$cid = $_GET['cid'];

if (isset($_POST['checked'])) { //form submitted
	$checked = $_POST['checked'];
	if ($_POST['submit']=="Delete") {
		if (isset($_GET['confirm'])) {
			$checked = explode(',',$checked);
			foreach ($checked as $k=>$gbi) {
				$gbi = intval($gbi);
				$checked[$k] = $gbi;
				$query = "DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid='$gbi'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$checkedlist = "'".implode("','",$checked)."'";
			$query = "DELETE FROM imas_gbitems WHERE id IN ($checkedlist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			$checkedlist = implode(',',$checked);
			require("../header.php");
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
			echo "&gt; <a href=\"chgoffline.php?cid=$cid\">Manage Offline Grades</a> &gt; Confirm Delete</div>";
			echo "<form id=\"mainform\" method=post action=\"chgoffline.php?cid=$cid&confirm=true\">";
			echo '<input type="hidden" name="submit" value="Delete" />';
			echo '<input type="hidden" name="checked" value="'.$checkedlist.'"/>';
			echo '<p>Are you <b>SURE</b> you want to delete these offline grade items ';
			echo 'and the associated student grades?<br/>If you haven\'t already, you might want to back up the gradebook first.</p><p>';
			$checkedlist = "'".implode("','",$checked)."'";
			$query = "SELECT name FROM imas_gbitems WHERE id IN ($checkedlist)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				echo $row[0].'<br/>';
			}
			echo '<p><p><input type="submit" value="Yes, Delete"/>';
			echo '<input type=button value="Nevermind" onClick="window.location=\'gradebook.php?cid='.$cid.'\'"></p>';
			echo '</form>';
			require("../footer.php");
			exit;
		}		
	} else {
		require_once("../includes/parsedatetime.php");
		$checkedlist = "'".implode("','",$checked)."'";
		$sets = array();
		if (isset($_POST['chgshowafter'])) {
			if ($_POST['sdatetype']=='0') {
				$showdate = 0;
			} else {
				$showdate = parsedatetime($_POST['sdate'],$_POST['stime']);
			}
			$sets[] = "showdate='$showdate'";
		}
		if (isset($_POST['chgcount'])) {
			$sets[] = "cntingb='{$_POST['cntingb']}'";	
		}
		if (isset($_POST['chgtutoredit'])) {
			$sets[] = "tutoredit='{$_POST['tutoredit']}'";
		}
		if (isset($_POST['chggbcat'])) {
			$sets[] = "gbcategory='{$_POST['gbcat']}'";	
		}
		if (count($sets)>0) {
			$setslist = implode(',',$sets);
			$query = "UPDATE imas_gbitems SET $setslist WHERE id IN ($checkedlist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	}
	
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid=$cid");
	exit;	
}

//Prep for output
$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$i=0;
$page_gbcatSelect = array();
if (mysql_num_rows($result)>0) {
	while ($row = mysql_fetch_row($result)) {
		$page_gbcatSelect['val'][$i] = $row[0];
		$page_gbcatSelect['label'][$i] = $row[1];
		$i++;
	}
}	

$sdate = tzdate("m/d/Y",time());
$stime = tzdate("g:i a",time());
$line['tutoredit'] = isset($CFG['AMS']['tutoredit'])?$CFG['AMS']['tutoredit']:0;

//HTML output
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
echo "&gt; Manage Offline Grades</div>";
echo '<div id="headerchgoffline" class="pagetitle"><h2>Manage Offline Grades</h2></div>';

echo "<form id=\"mainform\" method=post action=\"chgoffline.php?cid=$cid\">";

$gbitems = array();
$query = "SELECT id,name FROM imas_gbitems WHERE courseid='$cid' ORDER BY name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
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
	echo '<li><input type="checkbox" name="checked[]" value="'.$id.'" /> '.$name.'</li>';		
}
?>
</ul>
<p>With selected, <input type="submit" name="submit" value="Delete"/> or make changes below
<fieldset>
<legend>Offline Grade Options</legend>
<table class=gb>
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
<img src="../img/cal.gif" alt="Calendar"/></a>
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
