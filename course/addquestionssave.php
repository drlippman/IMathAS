<?php
//IMathAS:  Save changes to addquestions submitted through AHAH
//(c) 2007 IMathAS/WAMAP Project
	require("../validate.php");
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	if (!isset($teacherid)) {
		echo "error: validation";
	}
	$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error()); 
	$itemorder = mysql_result($result,0,0);
	$itemorder = str_replace('~',',',$itemorder);
	$curitems = array();
	foreach (explode(',',$itemorder) as $qid) {
		if (strpos($qid,'|')===false) {
			$curitems[] = $qid;
		}
	}
	
	$submitted = $_GET['order'];
	$submitted = str_replace('~',',',$submitted);
	$newitems = array();
	foreach (explode(',',$submitted) as $qid) {
		if (strpos($qid,'|')===false) {
			$newitems[] = $qid;
		}
	}
	$toremove = array_diff($curitems,$newitems);
	
	//delete any removed questions
	$query = "DELETE FROM imas_questions WHERE id IN ('".implode("','",$toremove)."')";
	mysql_query($query) or die("Query failed : " . mysql_error()); 
	
	//store new itemorder
	$query = "UPDATE imas_assessments SET itemorder='{$_GET['order']}' WHERE id='$aid'";
	mysql_query($query) or die("Query failed : " . mysql_error()); 
	
	if (mysql_affected_rows()>0) {
		echo "OK";
	} else {
		echo "error: not saved";
	}
	
?>
