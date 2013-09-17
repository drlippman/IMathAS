<?php
require("../validate.php");
if ($myrights<100) { exit;}

function doquery($vals) {
	$query = "UPDATE imas_library_items AS ili
	  JOIN imas_questionset AS iqs ON iqs.id=ili.qsetid 
	  JOIN imas_libraries AS il ON ili.libid=il.id 
	  SET ili.junkflag = 1 WHERE (iqs.uniqueid, il.uniqueid) IN (".implode(',',$vals).")";
	 $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	 return mysql_affected_rows();
}

if (isset($_POST['data'])) {
	$info = array();
	$lines = explode("\n",$_POST['data']);
	$valarray = array();
	$tot = 0;
	foreach ($lines as $line) {
		list($uqid,$ulibid) = explode('@',$line);
		$valarray[] = "('$uqid','$ulibid')";
		if (count($valarray)==500) {
			$tot += doquery($valarray);
			$valarray = array();
		}
	}
	if (count($valarray)>0) {
		$tot += doquery($valarray);
	}
	echo "$tot records newly marked as wrong library";
} else {
	echo '<html><body><form method="post"><textarea name="data" rows="30" cols="80"></textarea>';
	echo '<input type="submit"></form></body></html>';
}
?>
