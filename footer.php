<div class="clear"></div>
</div>
<div class="footerwrapper"><?php
	$curdir = rtrim(dirname(__FILE__), '/\\');
	if (file_exists("footercontent.php")) {
		require("footercontent.php");
	}
?></div>
</div>
<?php
if (isset($useeditor) && $sessiondata['useed']==1) {
	//echo "<script type=\"text/javascript\">initEditor();</script>\n";
}
if (isset($useeqnhelper) && $useeqnhelper==true) {
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require("$curdir/assessment/eqnhelper.html");
}
?>
</body>
</html>
<?php
if (isset($link)) {
	mysql_close($link);
}
/*
$end_time = microtime(true);
$exectime = $end_time - $start_time;
echo "Executed in $exectime sec";
*/
?>
