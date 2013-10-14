<?php
require("../validate.php");

if (!isset($teacherid) || !isset($cid)) {
	echo "You are not authorized to view this page";
	exit;
}
if (isset($_POST['aidorder'])) {
	$aidorder = explode(',',$_POST['aidorder']);
	foreach ($aidorder as $k=>$v) {
		$aidorder[$k] = intval($v);
	}
	$aidlist = implode(',', $aidorder);
	
	$query = "SELECT id,points FROM imas_questions WHERE assessmentid IN ($aidlist) AND points<9999";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$qpoints = array();
	while ($row = mysql_fetch_row($result)) {
		$qpoints[$row[0]] = $row[1];
	}
	
	$query = "SELECT id,defpoints,itemorder FROM imas_assessments WHERE id IN ($aidlist) AND courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$possible = array();
	while ($line = mysql_fetch_assoc($result)) {
		$pos = 0;
		$aitems = explode(',',$line['itemorder']);
		$defpoints = $line['defpoints'];
		foreach ($aitems as $k=>$v) {
			if (strpos($v,'~')!==FALSE) {
				$sub = explode('~',$v);
				if (strpos($sub[0],'|')===false) { //backwards compat
					$pos += (isset($qpoints[$sub[0]]))?$qpoints[$sub[0]]:$defpoints;
				} else {
					$grpparts = explode('|',$sub[0]);
					$pos += $grpparts[0]*((isset($qpoints[$sub[1]]))?$qpoints[$sub[1]]:$defpoints);
				}
			} else {
				$pos += (isset($qpoints[$v]))?$qpoints[$v]:$defpoints;
			}
		}
		$possible[$line['id']] = $pos;
	}

	$aidpos = array_flip($aidorder);
	
	foreach ($_POST['checked'] as $tochgaid) {
		if (!isset($possible[$tochgaid])) { continue;}
		$reqval = intval($_POST['req'.$tochgaid]);
		$pos = $aidpos[$tochgaid];
		if ($pos<1) {continue;}  //can't set prereq on first item
		$prereq = $aidorder[$pos-1]; //identify prereq assignment
		
		$score = ceil($reqval/100*$possible[$prereq] - .000000000001);
		$query = "UPDATE imas_assessments SET reqscoreaid='$prereq',reqscore=$score WHERE id='$tochgaid' AND courseid='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
	exit;	
	
} else {
	require("../includes/copyiteminc.php");
	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());

	$items = unserialize(mysql_result($result,0,0));
	$gitypeids = array();
	$ids = array();
	$types = array();
	$names = array();
	$sums = array();
	$parents = array();
	$agbcats = array();
	getsubinfo($items,'0','','Assessment');
	
	require("../header.php");

	echo '<h2>Quick-setup conditional release</h2>';
	
	echo '<p>If an item is checked, the item prior in the course order will be set as the prereq assignment</p>';
	
	echo '<form method="post" action="makeconditional.php?cid='.$cid.'">';
	echo '<p>Req <input id="reqdef" value=""/> % <input type="button" onclick="$(\'.req\').val($(\'#reqdef\').val())" value="Copy to all"/></p>';
	echo '<table><tbody>';
	$aids = array();
	for ($i = 0 ; $i<(count($ids)); $i++) {
		if (strpos($types[$i],'Block')!==false) {
			echo '<tr><td colspan="2">'.$names[$i].'</td></tr>';
		} else {
			echo '<tr><td><input type="checkbox" name="checked[]" value="'.$gitypeids[$i].'"/> ';
			echo $names[$i].'</td>';
			echo '<td>Req: <input type="text" name="req'.$gitypeids[$i].'" class="req" size="3"/> %</td></tr>';
			$aids[] = $gitypeids[$i];
		}
	}
	echo '</tbody></table>';
	echo '<input type="hidden" name="aidorder" value="'.implode(',',$aids).'"/>';
	echo '<input type="submit"/>';
	echo '</form>';
	
	require("../footer.php");
}


?>
