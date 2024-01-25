<?php
require_once "../init.php";
if ($myrights<100) { exit;}

function doquery($vals) {  //provide presanitized values
	global $DBH;
	$query_placeholders = Sanitize::generateQueryPlaceholdersGrouped($vals, 2);
	$query = "UPDATE imas_library_items AS ili
	  JOIN imas_questionset AS iqs ON iqs.id=ili.qsetid
	  JOIN imas_libraries AS il ON ili.libid=il.id
	  SET ili.junkflag = 1 WHERE (iqs.uniqueid, il.uniqueid) IN ($query_placeholders)";
	 $stm = $DBH->prepare($query);
	 $stm->execute($vals);
	 return $stm->rowCount();
}

if (isset($_POST['data'])) {
	$info = array();
	$lines = explode("\n",$_POST['data']);
	$valarray = array();
	$tot = 0;
	foreach ($lines as $line) {
		$line = str_replace(array("\r","\t"," "),'',$line);
		list($uqid,$ulibid) = explode('@',$line);
		if (!ctype_digit($uqid) || !ctype_digit($ulibid)) {continue;} //only use numeric values
		array_push($valarray, $uqid, $ulibid);
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
