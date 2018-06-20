<?php

require("../init.php");

if ($myrights<100) {
	exit;
}

if (empty($_POST['from']) || empty($_POST['to'])) {
	//DB $query = "SELECT id, LastName, FirstName, SID, lastaccess FROM imas_users WHERE rights>11 ORDER BY LastName, FirstName";
	$stm = $DBH->query("SELECT id, LastName, FirstName, SID, lastaccess FROM imas_users WHERE rights>11 ORDER BY LastName, FirstName");
	$ops = '';
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$ops .= '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).', '.Sanitize::encodeStringForDisplay($row[2]).' ('.Sanitize::encodeStringForDisplay($row[3]).') '.Sanitize::encodeStringForDisplay(tzdate('n/j/y',$row[4])).'</option>';
	}
	require("../header.php");
	echo "<h1>Merge Teacher Accounts</h1>";
	echo '<form method="post">';
	echo 'Move everything from <select name="from">'.$ops.'</select><br/>';
	echo 'to <select name="to">'.$ops.'</select><br/>';
	echo '<input type="submit" value="Go"/>';
	echo '</form>';
	require("../footer.php");

} else {
	$from = intval($_POST['from']);
	$to = intval($_POST['to']);
	if ($from==0 || $to==0) {exit;}

	$query = "UPDATE imas_courses SET ownerid=$to WHERE ownerid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_questionset SET ownerid=$to WHERE ownerid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_libraries SET ownerid=$to WHERE ownerid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_library_items SET ownerid=$to WHERE ownerid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_rubrics SET ownerid=$to WHERE ownerid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_diags SET ownerid=$to WHERE ownerid=$from";
	$DBH->query($query);

	$query = "UPDATE imas_teachers SET userid=$to WHERE userid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_tutors SET userid=$to WHERE userid=$from";
	$DBH->query($query);

	$query = "UPDATE imas_forum_posts SET userid=$to WHERE userid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_forum_views SET userid=$to WHERE userid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_forum_likes SET userid=$to WHERE userid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_forum_subscriptions SET userid=$to WHERE userid=$from";
	$DBH->query($query);

	$query = "UPDATE imas_wiki_revisions SET userid=$to WHERE userid=$from";
	$DBH->query($query);
	$query = "UPDATE imas_wiki_views SET userid=$to WHERE userid=$from";
	$DBH->query($query);

	$query = "UPDATE imas_ltiusers SET userid=$to WHERE userid=$from";
	$DBH->query($query);

	$query = "UPDATE imas_login_log SET userid=$to WHERE userid=$from";
	$DBH->query($query);

	$query = "DELETE FROM imas_users WHERE id=$from";
	$DBH->query($query);

	echo "Done";
}
