<?php
require("../validate.php");
if ($myrights<100) { exit;}

if (isset($_POST['data'])) {
	$info = array();
	$lines = explode("\n",$_POST['data']);
	$replace = array(); $names = array();
	foreach ($lines as $line) {
		$parts = explode('@',$line);
		$replace[$parts[0]] = $parts[1];
		$names[$parts[1]] = $parts[3];
	}
	$destlist = "'".implode("','",array_values($replace))."'";
	$replacelist = "'".implode("','",array_keys($replace))."'";

	$query = "SELECT id,uniqueid,replaceby FROM imas_questionset WHERE uniqueid IN ($destlist)";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$ref = array();
	while ($row = mysql_fetch_row($result)) {
		$ref[$row[1]] = $row[0];
	}

	$query = "SELECT id,uniqueid,replaceby FROM imas_questionset WHERE uniqueid IN ($replacelist)";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		if ($row[2]==0) { //no existing replaceby
			if (isset($ref[$replace[$row[1]]])) { //if the replaceby exists on this system
				$query = "UPDATE imas_questionset SET replaceby=".intval($ref[$replace[$row[1]]])." where id=".$row[0];
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				echo "Updated question ID {$row[0]}<br/>";
			} else {
				echo "Skipped question ID {$row[0]}; replaceby not found (".$names[$replace[$row[1]]]."<br/>";
			}
		} else {
			echo "Skipped question ID {$row[0]}; already has replaceby<br/>";
		}
	}
	
} else {
	echo '<html><body><b>Do NOT use this unless you know what you are doing.</b>';
	echo '<form method="post"><textarea name="data" rows="30" cols="80"></textarea>';
	echo '<input type="submit"></form></body></html>';
}
?>
