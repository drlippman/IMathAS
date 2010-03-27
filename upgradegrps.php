<?php
require("validate.php");

$query = "SELECT courseid,id,name FROM imas_assessments WHERE isgroup>0";
$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
$assessgrpset = array();
while ($row = mysql_fetch_row($result)) {
	$query = "INSERT INTO imas_stugroupset (courseid,name) VALUES ('{$row[0]}','Group set for {$row[2]}')";
	$res = mysql_query($query) or die("Query failed : $query:" . mysql_error());
	$assessgrpset[$row[1]] = mysql_insert_id();
}

$query = "SELECT userid,id,agroupid,assessmentid FROM imas_assessment_sessions WHERE agroupid>0";
$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
$agroupusers = array();
$agroupaids = array();
while ($row = mysql_fetch_row($result)) {
	if (!isset($assessgrpset[$row[3]])) { //why would agroupid>0 and not isgroup>0?
		continue;
	}
	if (!isset($agroupusers[$row[2]])) {
		$agroupusers[$row[2]] = array();
	}
	$agroupusers[$row[2]][] = $row[0];
	$agroupaids[$row[2]] = $row[3];
}

//update files.  Need to update before changinge agroupids so we will know the curs3asid
$query = "SELECT id,agroupid,lastanswers,bestlastanswers,reviewlastanswers,assessmentid FROM imas_assessment_sessions ";
$query .= "WHERE lastanswers LIKE '%@FILE:%' OR bestlastanswers LIKE '%@FILE:%' OR reviewlastanswers LIKE '%@FILE:%'";
$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
require("./includes/filehandler.php");
$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
$doneagroups = array();
while ($row = mysql_fetch_row($result)) {
	//set path to aid/asid/  or aid/agroupid/  - won't interefere with random values, and easier to do.
	if ($row[1]==0) {
		$path = $row[5].'/'.$row[0];
		$curs3asid = $row[0];
	} else {
		$path = $row[5].'/'.$row[1];
		$curs3asid = $row[1];
	}
	if ($row[1]==0 || !in_array($row[1],$doneagroups)) {
		preg_match_all('/@FILE:(.*?)@/',$row[2].$row[3].$row[4],$matches);
		$tomove = array_unique($matches[1]);
		foreach ($tomove as $file) {
			if (@$s3->copyObject($GLOBALS['AWSbucket'],"adata/$curs3asid/$file",$GLOBALS['AWSbucket'],"adata/$path/$file")) {
				@$s3->deleteObject($GLOBALS['AWSbucket'],"adata/$curs3asid/$file");
			}
		}
		if ($row[1]>0) {
			$doneagroups[] = $row[1];
		}
	}
	
	$la = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[2]));
	$bla = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[3]));
	$rla = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[4]));
	$query = "UPDATE imas_assessment_sessions SET lastanswers='$la',bestlastanswers='$bla',reviewlastanswers='$rla' WHERE id={$row[0]}";
	$res = mysql_query($query) or die("Query failed : $query:" . mysql_error());
}

$agroups = array_keys($agroupaids);
$agroupstugrp = array();
foreach($agroups as $agroup) {
	$query = "INSERT INTO imas_stugroups (groupsetid,name) VALUES (".$assessgrpset[$agroupaids[$agroup]].",'Unnamed group')";
	$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
	$stugrp = mysql_insert_id();
	if (count($agroupusers[$agroup])>0) {
		foreach ($agroupusers[$agroup] as $k=>$v) {
			$agroupusers[$agroup][$k] = "($stugrp,$v)";
		}
		$query = "INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES ".implode(',',$agroupusers[$agroup]);
		$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
	}
	$query = "UPDATE imas_assessment_sessions SET agroupid='$stugrp' WHERE agroupid='$agroup'";
	$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
}



?>
