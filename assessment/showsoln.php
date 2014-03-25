<?php

require("../validate.php");

$id = intval($_GET['id']);
$sig = $_GET['sig'];

$flexwidth = true;
require("header.php");

if ($sig != md5($id.$sessiondata['secsalt'])) {
	echo "invalid signature - not authorized to view the solution for this problem";
}

require("displayq2.php");
$txt = displayq(0,$id,0,false,false,0,2);
echo filter($txt);
require("../footer.php");

?>
