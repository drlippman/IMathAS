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

$query = "SELECT id,qtype,control,ownerid FROM imas_questionset WHERE id=9421";//ORDER BY ownerid,id LIMIT 0,1000";
$result = mysql_query($query) or die("Query failed : " . mysql_error());

//echo "<pre>";

$row[0] = 0;
$row[1] = "control";
$row[2] = '//$a,$b = $c[3][2^4]^2 + sin(3)^2;'."\n".
	  '//this is a test'."\n".
	  '$a = 5/-$b^2';
 

$otc = 0;
$ntc = 0;
$loopt = 0;
$loopm = 0;
$lastowner = -1;
$badids = array();
//while ($row = mysql_fetch_row($result)) {
	if ($row[3]!=$lastowner) {
		echo "Last owner: $lastowner.  Id's to look at: ";
		echo implode(',',$badids);
		echo "<h4>Owner: {$row[3]}</h4>";
		$lastowner = $row[3];
		$badids = array();
	}

	$start_time = microtime(true);
	$oldc = interpret('control',$row[1],$row[2]);
	$end_time = microtime(true);
	$otc += $end_time - $start_time;
	
	$start_time = microtime(true);
	$newc = interpret5('control',$row[1],$row[2]);
	$end_time = microtime(true);
	$ntc += $end_time - $start_time;
	
	echo $newc;
	
	$oldcs = preg_replace('/loadlibrary\(.*?\)/','',$oldc);
	$oldcs = preg_replace('/;\s*\/\/\s*;/','',$oldcs);
	$oldcs = preg_replace('/setseed\(.*?\)/','',$oldcs);
	$oldcs = preg_replace('/[\s\(\);]/','',$oldcs);
	$newcs = preg_replace('/loadlibrary\(.*?\)/','',$newc);
	$newcs = preg_replace('/setseed\(.*?\)/','',$newcs);
	$newcs = preg_replace('/[\s\(\);]/','',$newcs);
	if ($oldcs!=$newcs) {
		$badids[] = $row[0];
		if ($newc=='error') {
			echo "<br/>error in {$row[0]}<br/>";
		} else {
			echo '<br/>'.$row[0].'<br/>'.$row[2].'<br/><br/>'.$oldc.'<br/><br/>'.$newc.'<br/><br/><br/>';
		}
		//echo '<Br/>'.$row[0].'<br/>'.$oldc.'<br/><br/>'.$newc.'<br/>';
	}
//}
echo "<br/>Old time: $otc, New: $ntc, loopt: $loopt, loopm: $loopm<Br/>";

echo (1/-2) . '<br/>';
echo (1/-2*5) . '<br/>';

?>

