<?php

$DBH->beginTransaction();

// create new table
$query = 'CREATE TABLE imas_questionerrorlog (
	qsetid INT UNSIGNED NOT NULL, 
	seed MEDIUMINT UNSIGNED NOT NULL, 
	etime INT UNSIGNED NOT NULL, 
	ehash CHAR(32) NOT NULL,
	error TEXT,
	PRIMARY KEY (qsetid, ehash)
	)';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

// select into new one
$res = $DBH->query('INSERT INTO imas_questionerrorlog (qsetid,seed,etime,ehash,error) 
	SELECT qsetid,
	MIN(seed),
	MAX(etime),
	MD5(error),
	error 
	FROM imas_questionerrors GROUP BY qsetid,error');
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

// remove old table
/*$res = $DBH->query('DROP TABLE imas_questionerrors;');
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }*/

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ revamp imas_questionerrors to imas_questionerrorlog</p>';

return true;
