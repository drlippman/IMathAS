<?php
require("../validate.php");
if ($myrights<100) { exit;}

if (isset($_POST['data'])) {
	$info = array();
	$lines = explode("\n",$_POST['data']);
	foreach ($lines as $line) {
		list($uid,$lastm,$extref) = explode('@',$line);
		$info[$uid] = array($lastm,$extref);
	}
	$query = "SELECT id,uniqueid,lastmoddate,extref FROM imas_questionset WHERE 1";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_assoc($result)) {
		if (!isset($info[$row['uniqueid']])) {continue;}
		if (trim($row['extref'])!=trim($info[$row['uniqueid']][1])) {  //if different 
			if ($row['extref']=='') {
				echo "Found new extref.  Adding.<br/>";
				//add it
				$query = "UPDATE imas_questionset SET extref='".$info[$row['uniqueid']][1]."' WHERE id=".$row['id'];
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			} else {
				if ($row['lastmoddate']>$info[$row['uniqueid']][0]) {
					echo 'Local more recent '.$row['id'].': '.$row['extref']. ' vs. '.$info[$row['uniqueid']][1].'.  Skipping.<br/>';
				} else {
					echo 'Import more recent '.$row['id'].': '.$row['extref']. ' vs. '.$info[$row['uniqueid']][1].'.   Updating.<br/>';	
					$query = "UPDATE imas_questionset SET extref='".$info[$row['uniqueid']][1]."' WHERE id=".$row['id'];
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
		}
	}
} else {
	echo '<html><body><b>Do NOT use this unless you know what you are doing.</b>';
	echo '<form method="post"><textarea name="data" rows="30" cols="80"></textarea>';
	echo '<input type="submit"></form></body></html>';
}
?>
