<?php
//IMathAS:  Core of the testing engine.  Displays and grades questions
//(c) 2006 David Lippman
require("validate.php");
$mathfuncs = array("sin","cos","tan","sinh","cosh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
$allowedmacros = $mathfuncs;
require_once("assessment/mathphp.php");
require("assessment/interpret.php");
require("assessment/interpret5.php");
require("assessment/macros.php");

$query = "SELECT id,qtype,control FROM imas_questionset ";
$result = mysql_query($query) or die("Query failed : " . mysql_error());

//echo "<pre>";
/*
$row[0] = 0;
$row[1] = "control";
$row[2] = '$a,$b = diffrands(-2,2)'."\n".
	  '$a = 3 if $b^2/3<=3 //this sucks'."\n".
	  '$a,$b = diffrands(-2,2) where ($b>0) if ($c==0)'."\n".
	  '$c = 5-2^(2-33) + 5e^-2 - 2pi(6)';
*/
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
	$newc = interpret5('control',$row[1],$row[2]);
	$end_time = microtime(true);
	$ntc += $end_time - $start_time;
	
	//echo $newc;
	
	$oldcs = preg_replace('/loadlibrary\(.*?\)/','',$oldc);
	$oldcs = preg_replace('/;\s*\/\/\s*;/','',$oldcs);
	$oldcs = preg_replace('/setseed\(.*?\)/','',$oldcs);
	$oldcs = preg_replace('/[\s\(\);]/','',$oldcs);
	$newcs = preg_replace('/loadlibrary\(.*?\)/','',$newc);
	$newcs = preg_replace('/setseed\(.*?\)/','',$newcs);
	$newcs = preg_replace('/[\s\(\);]/','',$newcs);
	if ($oldcs!=$newcs) {
		if ($newc=='error') {
			echo "error in {$row[0]}<br/>";
		} else {
			echo $row[0].'<br/>'.$row[2].'<br/><br/>'.$oldc.'<br/><br/>'.$newc.'<br/>';
		}
		//echo '<Br/>'.$row[0].'<br/>'.$oldc.'<br/><br/>'.$newc.'<br/>';
	}
}
echo "<br/>Old time: $otc, New: $ntc, loopt: $loopt, loopm: $loopm";

?>

