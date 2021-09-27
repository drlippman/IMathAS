<?php
require_once("../includes/sanitize.php");

if ($_GET['cid']==="embedq") {
	$_SESSION = array();
	require("../init_without_validate.php");

	$cid = "embedq";
	$_SESSION['secsalt'] = "12345";
	$_SESSION['graphdisp'] = 1;
	$_SESSION['mathdisp'] = 1;
	if (isset($_GET['theme'])) {
		$coursetheme = 	$_GET['theme'];
	}
} else {
	require("../init.php");
}

$id = Sanitize::onlyInt($_GET['id']);
$sig = $_GET['sig'];
$t = Sanitize::onlyInt($_GET['t']);
$_SESSION['coursetheme'] = $coursetheme;

$flexwidth = true;
require("header.php");
echo '<p><b style="font-size:110%">'._('Written Example').'</b> '._('of a similar problem').'</p>';
if ($sig != md5($id.$_SESSION['secsalt'])) {
	echo "invalid signature - not authorized to view the solution for this problem";
	exit;
}

require("displayq2.php");
$txt = displayq(0,$id,100000,false,false,0,2+$t);
echo filter($txt);
require("../footer.php");

?>
