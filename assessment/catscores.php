<?php
//IMathAS:  Function used to show category breakdown of scores
//Called from showtest and gradebook
//(c) 2006 David Lippman
function catscores($quests,$scores,$defptsposs) {
	$qlist = "'" . implode("','",$quests) . "'";
	$query = "SELECT id,category,points FROM imas_questions WHERE id IN ($qlist)";
 	$result = mysql_query($query) or die("Query failed : $query; " . mysql_error());
	$cat = array();
	$pospts = array();
	$tolookup = array();
	while ($row = mysql_fetch_row($result)) {
		$cat[$row[0]] = $row[1];
		if (is_numeric($row[1]) && $row[1]>0) {
			$tolookup[] = $row[1];
		}
		if ($row[2] == 9999) {
			$pospts[$row[0]] = $defptsposs;
		} else {
			$pospts[$row[0]] = $row[2];
		}
	}
	
	$libnames = array();
	$libnames[0] = "Uncategorized";
	if (count($tolookup)>0) {
		$lulist = "'".implode("','",$tolookup)."'";
		$query = "SELECT id,name FROM imas_libraries WHERE id IN ($lulist)";
		$result = mysql_query($query) or die("Query failed : $query; " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$libnames[$row[0]] = $row[1];
		}
	}
	
	$catscore = array();
	$catposs = array();
	for ($i=0; $i<count($quests); $i++) {
		$pts = getpts($scores[$i]);
		if ($pts<0) { $pts = 0;}
		$catscore[$cat[$quests[$i]]] += $pts;
		$catposs[$cat[$quests[$i]]] += $pospts[$quests[$i]];
	}
	echo "<h4>Categorized Score Breakdown</h4>\n";
	echo "<table cellpadding=5 class=gb><thead><tr><th>Category</th><th>Points Earned / Possible (Percent)</th></tr></thead><tbody>\n";
	$alt = 0;
	foreach (array_keys($catscore) as $category) {
		if (is_numeric($category)) {
			$catname = $libnames[$category];
		} else {
			$catname = $category;
		}
		if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
		$pc = round(100*$catscore[$category]/$catposs[$category],1);
		echo "<td>$catname</td><td>{$catscore[$category]} / $catposs[$category] ($pc %)</td></tr>\n";
	}
	echo "</tbody></table>\n";
	
}

?>
