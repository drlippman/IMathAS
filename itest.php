<?php
//IMathAS:  Core of the testing engine.  Displays and grades questions
//(c) 2006 David Lippman
require("validate.php");
$mathfuncs = array("sin","cos","tan","sinh","cosh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
$allowedmacros = $mathfuncs;
require_once("assessment/mathphp.php");
require("assessment/interpret.php");
require("assessment/interpret2.php");
require("assessment/macros.php");

$query = "SELECT id,qtype,control FROM imas_questionset LIMIT 8000,1000";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$otc = 0;
$ntc = 0;
$loopt = 0;
$loopm = 0;
while ($row = mysql_fetch_row($result)) {

	$start_time = microtime(true);
	$oldc = interpret('control',$row[1],$row[2]);
	$end_time = microtime(true);
	$otc += $end_time - $start_time;
	
	$start_time = microtime(true);
	$newc = interpret2('control',$row[1],$row[2]);
	$end_time = microtime(true);
	$ntc += $end_time - $start_time;
	
	$oldcs = preg_replace('/loadlibrary\(.*?\)/','',$oldc);
	$oldcs = preg_replace('/setseed\(.*?\)/','',$oldcs);
	$oldcs = preg_replace('/[\s\(\);]/','',$oldcs);
	$newcs = preg_replace('/loadlibrary\(.*?\)/','',$newc);
	$newcs = preg_replace('/setseed\(.*?\)/','',$newcs);
	$newcs = preg_replace('/[\s\(\);]/','',$newcs);
	if ($oldcs!=$newcs) {
		echo $row[0].'<br/>'.$row[2].'<br/><br/>'.$oldcs.'<br/><br/>'.$newcs.'<br/>';
	}
}
echo "<br/>Old time: $otc, New: $ntc, loopt: $loopt, loopm: $loopm";

?>
