<div class="clear"></div>
</div>
<div class="footerwrapper"><?php
	$curdir = rtrim(dirname(__FILE__), '/\\');
	if (file_exists("$curdir/footercontent.php")) {
		require("$curdir/footercontent.php");
	}
?>
</div>
</div>
<?php
if (isset($CFG['GEN']['translatewidgetID'])) {
	echo '<div id="google_translate_element" style="visibility:hidden"></div><script type="text/javascript">
	function googleTranslateElementInit() {
	  new google.translate.TranslateElement({pageLanguage: "en", layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL}, "google_translate_element");
	}
	</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>';
}
if (isset($useeditor) && $sessiondata['useed']==1) {
	//echo "<script type=\"text/javascript\">initEditor();</script>\n";
}
if (isset($useeqnhelper) && ($useeqnhelper==1 || $useeqnhelper==2)) {
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require("$curdir/assessment/eqnhelper.html");
} else if (isset($useeqnhelper) && ($useeqnhelper==3 || $useeqnhelper==4)) {
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require("$curdir/assessment/mathquilled.html");
	require("$curdir/assessment/eqnhelperbasic.html");
}

if ((isset($testsettings) && $testsettings['showtips']==2) || (isset($showtips) && $showtips==2)) {
	echo '<div id="ehdd" class="ehdd"><span id="ehddtext"></span> <span onclick="showeh(curehdd);" style="cursor:pointer;">' . _('[more..]') . '</span></div>';
	echo '<div id="eh" class="eh"></div>';

}
if (isset($placeinfooter)) {
	echo $placeinfooter;
}
?>
</body>
</html>
<?php
//DB if (isset($link)) {
//DB 	mysql_close($link);
//DB }
if (isset($DBH)) {
	$DBH = null;
}
/*
$end_time = microtime(true);
$exectime = $end_time - $start_time;
echo "Executed in $exectime sec";
*/
?>
