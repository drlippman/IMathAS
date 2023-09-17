<?php
	require_once "../init.php";
	if ($myrights<100) {exit;}
	/*
	echo "<p>Raw Non-WebWork Questions anywhere on system </p>\n";
	$query = "SELECT COUNT(imas_questionset.id),imas_users.LastName FROM imas_questionset,imas_users WHERE imas_users.id=imas_questionset.ownerid AND imas_questionset.description NOT LIKE '%.pg%' GROUP BY imas_questionset.ownerid";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "$row[1]: $row[0]<br/>\n";
	}
	*/
	/*
	$startt = mktime(0,0,0,6,10,2007);
	$runtot = 0;
	//echo "<p>Questions anywhere on system created since 6/10/07</p>\n";
	$query = "SELECT COUNT(DISTINCT imas_questionset.id) AS qcnt,imas_users.LastName FROM imas_questionset,imas_users WHERE imas_users.id=imas_questionset.ownerid AND imas_questionset.adddate>$startt AND imas_questionset.description NOT LIKE '%.pg%' GROUP BY imas_questionset.ownerid ORDER BY qcnt DESC ";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		//echo "$row[1]: $row[0]<br/>\n";
		$qs[$row[1]] = $row[0];
		$runtot += $row[0];
	}
	//echo "Total: $runtot";

	$runtot = 0;
	//echo "<p>Questions in open-to-all libraries created since 6/10/07</p>\n";
	$query = "SELECT COUNT(DISTINCT imas_questionset.id),imas_users.LastName FROM imas_questionset,imas_users,imas_library_items,imas_libraries ";
	$query .= "WHERE imas_users.id=imas_questionset.ownerid AND ";
	$query .= "imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid=imas_libraries.id AND ";
	$query .= "imas_libraries.userights=8 AND imas_questionset.adddate>$startt AND imas_questionset.description NOT LIKE '%.pg%' GROUP BY imas_questionset.ownerid";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		//echo "$row[1]: $row[0]<br/>\n";
		$qsp[$row[1]] = $row[0];
		$runtot += $row[0];
	}
	//echo "Total: $runtot";

	echo "<p>Questions on system created since 6/10/07:  public / total</p>";
	foreach ($qs as $u=>$n) {
		echo "$u: {$qsp[$u]}/$n<br/>\n";
	}
	*/
	/*
	echo "<p>Raw WebWork Questions anywhere on system </p>\n";
	$query = "SELECT COUNT(id),ownerid FROM imas_questionset WHERE description LIKE '%.pg%' GROUP BY ownerid";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "$row[1]: $row[0]<br/>\n";
	}
	*/

	$startt = 0; //AND imas_questionset.adddate>$startt
	$runtot = 0;
	$info = array();
	//echo "<p>Questions anywhere on system created since 6/10/07</p>\n";
	$stm = $DBH->query("SELECT COUNT(DISTINCT imas_questionset.id) AS qcnt,imas_users.LastName,imas_users.id,imas_users.email,imas_users.FirstName FROM imas_questionset,imas_users WHERE imas_users.id=imas_questionset.ownerid GROUP BY imas_questionset.ownerid  ORDER BY qcnt DESC ");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		//echo "$row[1]: $row[0]<br/>\n";
		$qs[$row[2]] = $row[0];
		$info[$row[2]] = $row[1].', '.$row[4].': '.$row[3];
		$runtot += $row[0];
	}
	//echo "Total: $runtot";

	$runtot = 0;
	//echo "<p>Questions in open-to-all libraries created since 6/10/07</p>\n";
	$query = "SELECT COUNT(DISTINCT imas_questionset.id),imas_users.LastName,imas_users.id FROM imas_questionset,imas_users,imas_library_items,imas_libraries ";
	$query .= "WHERE imas_users.id=imas_questionset.ownerid AND ";
	$query .= "imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid=imas_libraries.id AND ";
	$query .= "imas_libraries.userights>0 AND imas_questionset.userights>0 AND imas_questionset.adddate>$startt GROUP BY imas_questionset.ownerid";
	$stm = $DBH->query($query);
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		//echo "$row[1]: $row[0]<br/>\n";
		$qsnp[$row[2]] = $row[0];
		$runtot += $row[0];
	}
	//echo "Total: $runtot";


	$runtot = 0;
	//echo "<p>Questions in open-to-all libraries created since 6/10/07</p>\n";
	$query = "SELECT COUNT(DISTINCT imas_questionset.id),imas_users.LastName,imas_users.id FROM imas_questionset,imas_users,imas_library_items,imas_libraries ";
	$query .= "WHERE imas_users.id=imas_questionset.ownerid AND ";
	$query .= "imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid=imas_libraries.id AND ";
	$query .= "imas_libraries.userights=8 AND imas_questionset.userights>0 AND imas_questionset.adddate>$startt GROUP BY imas_questionset.ownerid";
	$stm = $DBH->query($query);
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		//echo "$row[1]: $row[0]<br/>\n";
		$qsp[$row[2]] = $row[0];
		$runtot += $row[0];
	}
	//echo "Total: $runtot";

	echo "<p>Questions on system created anytime:  public / nonprivate / total</p>";
	foreach ($qs as $u=>$n) {
		echo Sanitize::encodeStringForDisplay($info[$u]).": " . Sanitize::onlyInt($u).": " .Sanitize::onlyInt($qsp[$u])."/". Sanitize::onlyInt($qsnp[$u]) ."/" .Sanitize::onlyInt($n) ."<br/>\n";
	}

?>
