<?php
require("../init.php");
if ($myrights<100) { exit;}

if (isset($_POST['data'])) {
	$info = array();
	$lines = explode("\n",$_POST['data']);
	foreach ($lines as $line) {
		list($uid,$lastm,$extref) = explode('@',$line);
		$extref = str_replace(array("\r","\t"," "),'',$extref);
		$info[$uid] = array($lastm,$extref);
	}
	$add_extref_stm = $DBH->prepare("UPDATE imas_questionset SET extref=:extref WHERE id=:id");

	//DB $query = "SELECT id,uniqueid,lastmoddate,extref FROM imas_questionset WHERE 1";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_assoc($result)) {
	$stm = $DBH->query("SELECT id,uniqueid,lastmoddate,extref FROM imas_questionset WHERE 1");
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($info[$row['uniqueid']])) {continue;}
		if (trim($row['extref'])!=trim($info[$row['uniqueid']][1])) {  //if different
			if ($row['extref']=='') {
				echo "Found new extref.  Adding.<br/>";
				//add it
				//DB $query = "UPDATE imas_questionset SET extref='".$info[$row['uniqueid']][1]."' WHERE id=".$row['id'];
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
				$add_extref_stm->execute(array(':extref'=>$info[$row['uniqueid']][1], ':id'=>$row['id']));
			} else {
				if ($row['lastmoddate']>$info[$row['uniqueid']][0]) {
					echo 'Local more recent '.$row['id'].': '.$row['extref']. ' vs. '.$info[$row['uniqueid']][1].'.  Skipping.<br/>';
				} else {
					echo 'Import more recent '.$row['id'].': '.$row['extref']. ' vs. '.$info[$row['uniqueid']][1].'.   Updating.<br/>';
					//DB $query = "UPDATE imas_questionset SET extref='".$info[$row['uniqueid']][1]."' WHERE id=".$row['id'];
					//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
					$add_extref_stm->execute(array(':extref'=>$info[$row['uniqueid']][1], ':id'=>$row['id']));
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
