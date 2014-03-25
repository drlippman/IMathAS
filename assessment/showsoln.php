<?php

require("../validate.php");

$id = intval($_GET['id']);
$sig = $_GET['sig'];
$t = intval($_GET['t']);
$sessiondata['coursetheme'] = $coursetheme;

$flexwidth = true;
require("header.php");
echo '<p><b style="font-size:110%">'._('Written Example').'</b> '._('of a similar problem').'</p>';
if ($sig != md5($id.$sessiondata['secsalt'])) {
	echo "invalid signature - not authorized to view the solution for this problem";
}

require("displayq2.php");
$txt = displayq(0,$id,0,false,false,0,2+$t);
echo filter($txt);
require("../footer.php");

?>
