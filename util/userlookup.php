<?php
// lookup users
// IMathAS (c) 2024 David Lippman
// Called via AJAX

require_once "../init.php";

if ($myrights<40) {
	echo "Not authorized to view this page";
	exit;
}

if (!empty($_GET['cid'])) {
    $stm = $DBH->prepare("SELECT ic.name,ic.ownerid,iu.groupid FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ic.id=?");
    $stm->execute(array($cid));
    list($coursename, $courseownerid, $coursegroupid) = $stm->fetch(PDO::FETCH_NUM);
} else {
    $courseownerid = $userid;
    $coursegroupid = $groupid;
}

if (isset($_POST['loadgroup'])) {
	$stm = $DBH->prepare("SELECT id,LastName,FirstName,rights FROM imas_users WHERE id<>? AND groupid=? AND rights>11 ORDER BY LastName,FirstName");
	$stm->execute(array($courseownerid, $coursegroupid));
	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($row['rights']==76 || $row['rights']==77) {continue;}
		$out[] = array("id"=>$row['id'], "name"=>$row['LastName'].', '.$row['FirstName']);
	}
    if (isset($_POST['format']) && $_POST['format'] == 'select' && isset($_POST['name'])) {
        $outstr = '<select name="' . Sanitize::encodeStringForDisplay($_POST['name']).'" id="' . Sanitize::encodeStringForDisplay($_POST['name']).'">';
        foreach ($out as $user) {
            $outstr .= '<option value="' . Sanitize::encodeStringForDisplay($user['id']).'">';
            $outstr .= Sanitize::encodeStringForDisplay($user['name']) . '</option>';
        }
        $outstr .= '</select>';
        echo $outstr;
    } else {
        echo json_encode($out, JSON_HEX_TAG);
    }
	exit;
} else if (isset($_POST['search'])) {
	require_once "../includes/userutils.php";
	$search = (string) trim($_POST['search']);
	$possible_teachers = searchForUser($search, true, true);
	$out = array();
	foreach ($possible_teachers as $row) {
		if ($row['id']==$courseownerid) { continue; }
		$out[] = array("id"=>$row['id'], "name"=>$row['LastName'].', '.$row['FirstName'].' ('.$row['name'].')');
	}
	if (isset($_POST['format']) && $_POST['format'] == 'select' && isset($_POST['name'])) {
        $outstr = '<select name="' . Sanitize::encodeStringForDisplay($_POST['name']).'" id="' . Sanitize::encodeStringForDisplay($_POST['name']).'">';
        foreach ($out as $user) {
            $outstr .= '<option value="' . Sanitize::encodeStringForDisplay($user['id']).'">';
            $outstr .= Sanitize::encodeStringForDisplay($user['name']) . '</option>';
        }
        $outstr .= '</select>';
        echo $outstr;
    } else {
        echo json_encode($out, JSON_HEX_TAG);
    }
	exit;
}
