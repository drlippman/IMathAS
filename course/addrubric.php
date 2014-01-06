<?php
//IMathAS:  Add/edit rubrics
//(c) 2011 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$cid = $_GET['cid'];
$from = $_GET['from'];

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
if ($from=='modq') {
	$fromstr = '&amp;from=modq&amp;aid='.$_GET['aid'].'&amp;qid='.$_GET['qid'];
	$returnstr = 'modquestion.php?cid='.$cid.'&amp;aid='.$_GET['aid'].'&amp;id='.$_GET['qid'];
	$curBreadcrumb .= "&gt; <a href=\"$returnstr\">Modify Question Settings</a> ";
} else if ($from=='addg') {
	$fromstr = '&amp;from=addg&amp;gbitem='.$_GET['gbitem'];
	$returnstr = 'addgrades.php?cid='.$cid.'&amp;gbitem='.$_GET['gbitem'].'&amp;grades=all';
	$curBreadcrumb .= "&gt; <a href=\"$returnstr\">Offline Grades</a> ";
} else if ($from=='addf') {
	$fromstr = '&amp;from=addf&amp;fid='.$_GET['fid'];
	$returnstr = 'addforum.php?cid='.$cid.'&amp;id='.$_GET['fid'];
	$curBreadcrumb .= "&gt; <a href=\"$returnstr\">Modify Forum</a> ";
}


if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; <a href=\"addrubric.php?cid=$cid\">Manage Rubrics</a> ";
	if ($_GET['id']=='new') {
		$curBreadcrumb .= "&gt; Add Rubric\n";
		$pagetitle = "Add Rubric";
	} else {
		$curBreadcrumb .= "&gt; Edit Rubric\n";
		$pagetitle = "Edit Rubric";
	}
} else {
	$curBreadcrumb .= "&gt; Manage Rubrics\n";
	$pagetitle = "Manage Rubrics";
} 

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING	
	if (isset($_POST['rubname'])) { //FORM SUBMITTED, DATA PROCESSING
		if (isset($_POST['rubisgroup'])) {
			$rubgrp = $groupid;
		} else {
			$rubgrp = -1;
		}
		$rubric = array();
		for ($i=0;$i<15; $i++) {
			if (!empty($_POST['rubitem'.$i])) {
				$rubric[] = array(stripslashes($_POST['rubitem'.$i]), stripslashes($_POST['rubnote'.$i]), floatval($_POST['rubscore'.$i]));
			}
		}
		
		$rubricstring = addslashes(serialize($rubric));
		if ($_GET['id']!='new') { //MODIFY
			$query = "UPDATE imas_rubrics SET name='{$_POST['rubname']}',rubrictype='{$_POST['rubtype']}',groupid=$rubgrp,rubric='$rubricstring' WHERE id='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			$query = "INSERT INTO imas_rubrics (ownerid,name,rubrictype,groupid,rubric) VALUES ";
			$query .= "($userid,'{$_POST['rubname']}','{$_POST['rubtype']}',$rubgrp,'$rubricstring')";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		$fromstr = str_replace('&amp;','&',$fromstr);
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addrubric.php?cid=$cid$fromstr");
		
		
		
	} else { //INITIAL LOAD DATA PROCESS
		if (isset($_GET['id'])) { //MODIFY
			if ($_GET['id']=='new') {//NEW
				$rubric = array();
				$rubname = "New Rubric";
				$rubgrp = -1;
				$rubtype = 1;
				$savetitle = _('Create Rubric');
			} else {
				$rubid = intval($_GET['id']);
				$query = "SELECT name,groupid,rubrictype,rubric FROM imas_rubrics WHERE id=$rubid";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				list($rubname,$rubgrp,$rubtype,$rubric) = mysql_fetch_row($result);
				$rubric = unserialize($rubric);
				$savetitle = _('Save Changes');
			}
		} 
	}
}

//BEGIN DISPLAY BLOCK

/******* begin html output ********/
$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js"></script>';
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  //ONLY INITIAL LOAD HAS DISPLAY 	

?>

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headeraddforum" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php
if (!isset($_GET['id'])) {//displaying "Manage Rubrics" page
	echo '<p>Select a rubric to edit or <a href="addrubric.php?cid='.$cid.'&amp;id=new">Add a new rubric</a></p><p>';
	$query = "SELECT id, name FROM imas_rubrics WHERE ownerid='$userid' OR groupid='$groupid' ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "{$row[1]} <a href=\"addrubric.php?cid=$cid&amp;id={$row[0]}$fromstr\">Edit</a><br/>";
	}
	echo '</p>';
} else {  //adding/editing a rubric
	/*  Rubric Types
	*   1: score breakdown (record score and feedback)
	*   0: score breakdown (record score)
	*   3: score total (record score and feedback)
	*   4: score total (record score only)
	*   2: record feedback only (checkboxes)
	*/
	$rubtypeval = array(1,0,3,4,2);
	$rubtypelabel = array('Score breakdown, record score and feedback','Score breakdown, record score only','Score total, record score and feedback','Score total, record score only','Feedback only');
	echo "<form method=\"post\" action=\"addrubric.php?cid=$cid&amp;id={$_GET['id']}$fromstr\">";
	echo '<p>Name:  <input type="text" size="70" name="rubname" value="'.str_replace('"','\\"',$rubname).'"/></p>';
	
	echo '<p>Rubric Type: ';
	writeHtmlSelect('rubtype',$rubtypeval,$rubtypelabel,$rubtype,null,null,'onchange="imasrubric_chgtype()"');
	echo '</p>';
	
	echo '<p>Share with Group: <input type="checkbox" name="rubisgroup" '.getHtmlChecked($rubgrp,-1,1).' /></p>';
	echo '<table><thead><tr><th>Rubric Item<br/>Shows in feedback</th><th>Instructor Note<br/>Not in feedback</th><th><span id="pointsheader" ';
	if ($rubtype==2) {echo 'style="display:none;" ';}
	if ($rubtype==3 || $rubtype==4) {
		echo '>Percentage of score</span>';
	} else {
		echo '>Percentage of score<br/>Should add to 100</span>';
	}
	echo '</th></tr></thead><tbody>';
	for ($i=0;$i<15; $i++) {
		echo '<tr><td><input type="text" size="40" name="rubitem'.$i.'" value="';
		if (isset($rubric[$i]) && isset($rubric[$i][0])) { echo str_replace('"','&quot;',$rubric[$i][0]);}
		echo '"/></td>';
		echo '<td><input type="text" size="40" name="rubnote'.$i.'" value="';
		if (isset($rubric[$i]) && isset($rubric[$i][1])) { echo str_replace('"','&quot;',$rubric[$i][1]);}
		echo '"/></td>';
		echo '<td><input type="text" size="4" class="rubricpoints" ';
		if ($rubtype==2) {echo 'style="display:none;" ';}
		echo 'name="rubscore'.$i.'" value="';
		if (isset($rubric[$i]) && isset($rubric[$i][2])) { echo str_replace('"','&quot;',$rubric[$i][2]);} else {echo 0;}
		echo '"/></td></tr>';
	}
	echo '</table>';
	echo '<input type="submit" value="'.$savetitle.'"/>';
	echo '</form>';
	

}
}
require("../footer.php");
?>
