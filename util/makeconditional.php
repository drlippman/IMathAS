<?php
require("../init.php");

if (!isset($teacherid) || !isset($cid)) {
	echo "You are not authorized to view this page";
	exit;
}
$cid = intval($cid);
if (isset($_POST['aidorder'])) {
	$aidorder = explode(',',$_POST['aidorder']);
	$aidorder = array_map('intval', $aidorder);
	$aidlist = implode(',', $aidorder);

	//DB $query = "SELECT id,points FROM imas_questions WHERE assessmentid IN ($aidlist) AND points<9999";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->query("SELECT id,points FROM imas_questions WHERE assessmentid IN ($aidlist) AND points<9999");
	$qpoints = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$qpoints[$row[0]] = $row[1];
	}

	//DB $query = "SELECT id,defpoints,itemorder FROM imas_assessments WHERE id IN ($aidlist) AND courseid='$cid'";
	$stm = $DBH->query("SELECT id,defpoints,itemorder FROM imas_assessments WHERE id IN ($aidlist) AND courseid=$cid"); //presanitized
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$possible = array();
	//DB while ($line = mysql_fetch_assoc($result)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
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

	$stm = $DBH->prepare("UPDATE imas_assessments SET reqscoreaid=:reqscoreaid,reqscore=:reqscore WHERE id=:id AND courseid=:courseid");
	foreach ($_POST['checked'] as $tochgaid) {
		if (!isset($possible[$tochgaid])) { continue;}
		$reqval = intval($_POST['req'.$tochgaid]);
		$pos = $aidpos[$tochgaid];
		if ($pos<1) {continue;}  //can't set prereq on first item
		$prereq = $aidorder[$pos-1]; //identify prereq assignment

		$score = ceil($reqval/100*$possible[$prereq] - .000000000001);
		//DB $query = "UPDATE imas_assessments SET reqscoreaid='$prereq',reqscore=$score WHERE id='$tochgaid' AND courseid='$cid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm->execute(array(':reqscoreaid'=>$prereq, ':reqscore'=>$score, ':id'=>intval($tochgaid), ':courseid'=>$cid));
	}

	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
	exit;

} else {
	require("../includes/copyiteminc.php");
	//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->query("SELECT itemorder FROM imas_courses WHERE id=$cid");

	//DB $items = unserialize(mysql_result($result,0,0));
	$items = unserialize($stm->fetchColumn(0));
	$gitypeids = array();
	$ids = array();
	$types = array();
	$names = array();
	$sums = array();
	$parents = array();
	$agbcats = array();
	getsubinfo($items,'0','','Assessment');

	require("../header.php");

	echo '<h1>Quick-setup conditional release</h1>';

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
