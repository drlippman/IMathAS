</div>
<?php
if (isset($useeditor) && $sessiondata['useed']==1) {
	//echo "<script type=\"text/javascript\">initEditor();</script>\n";
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
