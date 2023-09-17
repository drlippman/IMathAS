<?php
require_once "../init.php";
if ($myrights<100) { exit;}
if (isset($_POST['data'])) {
	$info = array();
	$lines = explode("\n",$_POST['data']);
	$replace = array(); $names = array();
	foreach ($lines as $line) {
		$parts = explode('@',$line);
		//parts[0] and parts[1] are bigint uniqueid values
		if (!ctype_digit($parts[0]) || !ctype_digit($parts[1])) {continue;} //only use numeric values
		$replace[$parts[0]] = Sanitize::onlyInt($parts[1]);
		$names[$parts[1]] = $parts[3];
	}
	$destlist = implode(',',array_values($replace));
	$replacelist = implode(',',array_keys($replace));

	$query = "SELECT id,uniqueid,replaceby FROM imas_questionset WHERE uniqueid IN ($destlist)";
	$stm = $DBH->query($query); //we know values are all numeric
	$ref = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$ref[$row[1]] = $row[0];
	}

	$query = "SELECT id,uniqueid,replaceby FROM imas_questionset WHERE uniqueid IN ($replacelist)";
	$stm = $DBH->query($query); //we know values are all numeric
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[2]==0) { //no existing replaceby
			if (isset($ref[$replace[$row[1]]])) { //if the replaceby exists on this system
				$query = 'UPDATE imas_questionset SET replaceby=:replaceby where id=:id';
				$stm = $DBH->prepare($query); //we know values are all numeric
				$stm->execute(array(':replaceby'=>intval($ref[$replace[$row[1]]]), ':id'=>$row[0]));
				echo "Updated question ID ".Sanitize::onlyInt($row[0])."<br/>";
			} else {
				echo "Skipped question ID ".Sanitize::onlyInt($row[0])."; replaceby not found (".Sanitize::encodeStringForDisplay($names[$replace[$row[1]]]).")<br/>";
			}
		} else {
			echo "Skipped question ID ".Sanitize::onlyInt($row[0])."; already has replaceby<br/>";
		}
	}

} else {
	echo '<html><body><b>Do NOT use this unless you know what you are doing.</b>';
	echo '<form method="post"><textarea name="data" rows="30" cols="80"></textarea>';
	echo '<input type="submit"></form></body></html>';
}
?>
