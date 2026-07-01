<?php
require_once "../init.php";

if (!isset($teacherid) || !isset($cid)) {
	echo "You are not authorized to view this page";
	exit;
}
$cid = intval($cid);
if (isset($_POST['aidorder'])) {
	$aidorder = explode(',',$_POST['aidorder']);
	$aidorder = array_map('intval', $aidorder);
	$aidlist = implode(',', $aidorder);
	$aidpos = array_flip($aidorder);

	$stm = $DBH->prepare("UPDATE imas_assessments SET reqscorejson=:reqscorejson WHERE id=:id AND courseid=:courseid");
	foreach ($_POST['checked'] as $tochgaid) {
		if (!isset($possible[$tochgaid])) { continue;}
		$reqval = intval($_POST['req'.$tochgaid]);
		$pos = $aidpos[$tochgaid];
		if ($pos<1) {continue;}  //can't set prereq on first item
		$prereq = $aidorder[$pos-1]; //identify prereq assignment
		$json = json_encode([$prereq,$reqval,1]);
		$stm->execute(array(':reqscorejson'=>$json, ':id'=>intval($tochgaid), ':courseid'=>$cid));
	}

	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
	exit;

} else {
	require_once "../includes/copyiteminc.php";
	$stm = $DBH->query("SELECT itemorder FROM imas_courses WHERE id=$cid");
	$items = unserialize($stm->fetchColumn(0));
	$gitypeids = array();
	$ids = array();
	$types = array();
	$names = array();
	$sums = array();
	$parents = array();
	$agbcats = array();
	getsubinfo($items,'0','','Assessment');

	$pagetitle = _('Quick-setup conditional release');
	require_once "../header.php";

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

	require_once "../footer.php";
}


?>
