<?php

require("../validate.php");

if ($myrights<100 || empty($_GET['from']) || empty($_GET['to'])) {
	exit;
}

$from = $_GET['from'];
$to = $_GET['to'];

$ids = array();
$query = "SELECT assessmentid FROM imas_assessment_sessions WHERE userid='$to'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$ids[] = $row[0];
}
$idlist = implode(',',$ids);

$query = "UPDATE imas_assessment_sessions SET userid='$to' WHERE userid='$from' AND ";
$query .= "assessmentid IN (SELECT id FROM imas_assessments WHERE courseid='$cid') ";
if (count($ids)>0) {
	$query .= "AND assessmentid NOT IN ($idlist)";
}
mysql_query($query) or die("Query failed : " . mysql_error());
echo mysql_affected_rows().' assessment sessions moved<br/><br/>';


$ids = array();
$query = "SELECT gradetypeid FROM imas_grades WHERE userid='$to' AND gradetype='offline' AND score IS NOT NULL";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$ids[] = $row[0];
}
$idlist = implode(',',$ids);

$query = "UPDATE imas_grades SET userid='$to' WHERE userid='$from' AND ";
$query .= "gradetype='offline' AND gradetypeid IN (SELECT id FROM imas_gbitems WHERE courseid='$cid') ";
if (count($ids)>0) {
	$query .= "AND gradetypeid NOT IN ($idlist)";
}
mysql_query($query) or die("Query failed : " . mysql_error());
echo mysql_affected_rows().' offline grades moved<br/><br/>';

?>





