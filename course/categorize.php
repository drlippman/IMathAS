<?php
//IMathAS:  Categorize questions used in an assessment
//(c) 2006 David Lippman

	require("../validate.php");
	$aid = $_GET['aid'];
	$cid = $_GET['cid'];
	
	if (isset($_GET['record'])) {
	
		$query = "SELECT id,category FROM imas_questions WHERE assessmentid='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			if ($row[1] != $_POST[$row[0]]) {
				$query = "UPDATE imas_questions SET category='{$_POST[$row[0]]}' WHERE id='{$row[0]}'";
				
				mysql_query($query) or die("Query failed : " . mysql_error());
			} 
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
			
		exit;	
	}
	
	$pagetitle = "Categorize Questions";
	require("../header.php");
	echo <<<END
<script>
function addcategory() {
	var name = document.getElementById("newcat").value;
	var sels = document.getElementsByTagName("select");
	for (var i=0; i<sels.length; i++) {
		sels[i].options[sels[i].options.length] = new Option(name,name);
	}
	document.getElementById("newcat").value='';
}
function quickpick() {
	var sels = document.getElementsByTagName("select");
	for (var i=0; i<sels.length; i++) {
		if (sels[i].selectedIndex == 0) {
			sels[i].selectedIndex = 1;
		}
	}
}
</script>
END;
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; <a href=\"addquestions.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> &gt; Categorize Questions</div>\n";
	
	$query = "SELECT imas_questions.id,imas_libraries.id,imas_libraries.name FROM imas_questions,imas_library_items,imas_libraries ";
	$query .= "WHERE imas_questions.assessmentid='$aid' AND imas_questions.questionsetid=imas_library_items.qsetid AND ";
	$query .= "imas_library_items.libid=imas_libraries.id ORDER BY imas_questions.id";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$libnames = array();
	$questionlibs = array();
	while ($row = mysql_fetch_row($result)) {
		$questionlibs[$row[0]][] = $row[1];
		$libnames[$row[1]] = $row[2];
	}
	$query = "SELECT iq.id,iq.category,iqs.description FROM imas_questions AS iq,imas_questionset as iqs";
	$query .= " WHERE iq.questionsetid=iqs.id AND iq.assessmentid='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$descriptions = array();
	$category = array();
	$extracats = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$descriptions[$line['id']] = $line['description'];
		$category[$line['id']] = $line['category'];
		if (!is_numeric($line['category']) && trim($line['category'])!='' && !in_array($line['category'],$extracats)) {
			$extracats[] = $line['category'];
		}
	}
	echo '<div id="headercategorize" class="pagetitle"><h2>Categorize Questions</h2></div>';
	echo "<form method=post action=\"categorize.php?aid=$aid&cid=$cid&record=true\">";
	echo "<table><thead><tr><th>Description</th><th>Category</th></tr></thead><tbody>";
	
	foreach(array_keys($category) as $qid) {
		echo "<tr><td>{$descriptions[$qid]}</td><td>";
		echo "<select id=\"$qid\" name=\"$qid\">";
		echo "<option value=\"0\" ";
		if ($category[$qid] == 0) { echo "selected=1";}
		echo ">Uncategorized</option>\n";
		foreach ($questionlibs[$qid] as $qlibid) {
			echo "<option value=\"$qlibid\" ";
			if ($category[$qid] == $qlibid) { echo "selected=1";}
			echo ">{$libnames[$qlibid]}</option>\n";
		}
		foreach ($extracats as $cat) {
			echo "<option value=\"$cat\" ";
			if ($category[$qid] == $cat) { echo "selected=1";}
			echo ">$cat</option>\n";
		}
		echo "</select></td></tr>\n";
	}
	echo "</tbody></table>\n";
	echo "<p><input type=button value=\"Quick Pick\" onclick=\"quickpick()\"> : Select first listed library for all uncategorized questions.</p>\n";
	
	echo "<p>Add new category to lists: <input type=type id=\"newcat\" size=40> ";
	echo "<input type=button value=\"Add Category\" onclick=\"addcategory()\"></p>\n";
	echo "<p><input type=submit value=\"Record Categorizations\"> and return to the course page</p>\n";
	echo "</form>\n";
	
	
				
?>
