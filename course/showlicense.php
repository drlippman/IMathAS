<?php

require("../init_without_validate.php");
require("../header.php");
echo '<h3>Question License</h3>';

if (empty($_GET['id'])) {
	echo "No IDs specified";
	exit;
}

function getquestionlicense($row) {
	global $CFG, $sendfrom;
	$license = 'This question was written by '.$row['author'];
	if ($row['authorancestors']!='') {
		$license .= ', derived from work by '.$row['authorancestors'];
	}
	if ($row['license']==0) {
		$license .= '. This work is copyrighted, or contains copyright material. ';
	} else if ($row['license']==1) {
		$license .= '. This work is licensed under the <a href="http://www.imathas.com/communitylicense.html">IMathAS Community License (GPL + CC-BY)</a>.<br/>';
		$license .= 'The code that generated this question can be obtained by instructors by ';
		if (isset($CFG['GEN']['meanstogetcode'])) {
			$license .= $CFG['GEN']['meanstogetcode'];
		} else {
			global $sendfrom;
			$license .= 'emailing '.$sendfrom;
		}
		$license .= '. ';
	} else if ($row['license']==2) {
		$license .= '. This work has been placed in the <a href="https://creativecommons.org/publicdomain/zero/1.0/">Public Domain</a>. ';
	} else if ($row['license']==3) {
		$license .= '. This work, both code and generated output, is licensed under the <a href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike</a> license. ';
	} else if ($row['license']==4) {
		$license .= '. This work, both code and generated output, is licensed under the <a href="http://creativecommons.org/licenses/by-sa/4.0/">Creative Commons Attribution-ShareAlike</a> license. ';
	}
	if ($row['otherattribution']!='') {
		$license .= '<br/>Other Attribution: '.$row['otherattribution'];
	}
	return $license;
}

$ids = explode('-',$_GET['id']);
$idlist = implode(',',array_map('intval', $ids));

//DB $query = "SELECT id,uniqueid,author,ancestorauthors,license,otherattribution FROM imas_questionset WHERE id IN ($idlist)";
//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
//DB while ($row = mysql_fetch_assoc($result)) {
$stm = $DBH->query("SELECT id,uniqueid,author,ancestorauthors,license,otherattribution FROM imas_questionset WHERE id IN ($idlist)");
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	echo "<p>Question ID ".$row['id'].' (Universal ID '.$row['uniqueid'].')</p>';
	echo '<p style="margin-left:20px">';
	echo getquestionlicense($row);
	echo '</p>';
}
require('../footer.php');
?>
