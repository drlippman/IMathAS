<?php 
// Add/remove Teachers
// IMathAS (c) 2018 David Lippman

require_once "../init.php";

if ($myrights<40) {
	echo "Not authorized to view this page";
	exit;
}
$cid = Sanitize::onlyInt($_GET['id']);

if ($cid==0) {
	echo "Invalid course ID";
	exit;
}

$stm = $DBH->prepare("SELECT ic.name,ic.ownerid,iu.groupid FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ic.id=?");
$stm->execute(array($cid));
list($coursename, $courseownerid, $coursegroupid) = $stm->fetch(PDO::FETCH_NUM);

if (!($myrights==100 || ($myrights>=75 && $coursegroupid==$groupid) || $courseownerid==$userid)) {
	echo "Not authorized to transfer ownership of this course.";
	exit;
}

$from = 'admin2';
if (!empty($_GET['from'])) {
	if ($_GET['from']=='home') {
		$from = 'home';
		$backloc = '/index.php?r=' . Sanitize::randomQueryStringParam();
	} else if ($_GET['from']=='admin2') {
		$from = 'admin2';
		$backloc = '/admin/admin2.php?r=' . Sanitize::randomQueryStringParam();
	} else if (substr($_GET['from'],0,2)=='ud') {
		$userdetailsuid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'ud'.$userdetailsuid;
		$backloc = '/admin/userdetails.php?id='.Sanitize::encodeUrlParam($userdetailsuid) .'&r=' . Sanitize::randomQueryStringParam();
	} else if (substr($_GET['from'],0,2)=='gd') {
		$groupdetailsgid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'gd'.$groupdetailsgid;
		$backloc = '/admin/admin2.php?groupdetails='.Sanitize::encodeUrlParam($groupdetailsgid).'&r=' . Sanitize::randomQueryStringParam();
	}
}

//process transfer
if (!empty($_POST['newowner'])) {
    $ownerid = Sanitize::onlyInt($_POST['newowner']);
	$stm = $DBH->prepare("UPDATE imas_courses SET ownerid=:ownerid WHERE id=:id");
	$stm->execute(array(':ownerid'=>$ownerid, ':id'=>$cid));
	if ($stm->rowCount()>0) {
		$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':courseid'=>$cid, ':userid'=>$ownerid));
		if ($stm->rowCount()==0) {
			$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
			$stm->execute(array(':userid'=>$ownerid, ':courseid'=>$cid));
		}
		if (isset($_POST['removeasteacher'])) {
			$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
			$stm->execute(array(':courseid'=>$cid, ':userid'=>$courseownerid));
		}
	}
	header('Location: ' . $GLOBALS['basesiteurl'] . $backloc );
	exit;
}

$pagetitle = _('Transfer Course Ownership');

require_once "../header.php";

echo "<div class=breadcrumb>$breadcrumbbase ";
if ($from == 'admin') {
	echo "<a href=\"admin2.php\">Admin</a> &gt; ";
} else if ($from == 'admin2') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='ud') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$imasroot.$backloc.'">'._('User Details').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='gd') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$imasroot.$backloc.'">'._('Group Details').'</a> &gt; ';
}
echo "$pagetitle</div>\n";
echo '<div class="pagetitle"><h1>'.$pagetitle.' - '.Sanitize::encodeStringForDisplay($coursename).'</h1></div>';
?>
<form method="post">

<p>List your group members or search for a teacher to transfer your course ownership to.</p>
<p><label><input type=checkbox name=removeasteacher checked> Remove me as a teacher after transferring the course.</label></p>
<?php
require_once '../includes/userlookupform.php';
generateUserLookupForm(_('Instructor to transfer to:'), 'newowner', $defaultresults = '');
echo '<p><button class=primary type=submit disabled=true id=transferbtn>'._('Transfer').'</button>';
echo '<button type=button class="secondarybtn" onclick="window.location=\'../index.php\'">'._('Nevermind').'</button></p>';
echo '<script>
function userlookupcallback() {
	let el = document.getElementById("newowner");
	if (el && el.value > 0) {
		document.getElementById("transferbtn").disabled = false;
	}
}
</script>';
require_once "../footer.php";
