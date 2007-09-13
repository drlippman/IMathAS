<?php
//IMathAS:  Modify a question's settings in an assessment: grid for multiple.  Included in addquestions.php
//(c) 2006 David Lippman
	if (!(isset($teacherid))) {
		echo "This page cannot be accessed directly";
		exit;
	}
	
	if ($_GET['process']== true) {
		if (isset($_POST['add'])) { //adding new questions
			$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemorder = mysql_result($result,0,0);
			$newitemorder = '';
			if (isset($_POST['addasgroup'])) {
				$newitemorder = '1|0';
			}
			foreach (explode(',',$_POST['qsetids']) as $qsetid) {
				for ($i=0; $i<$_POST['copies'.$qsetid];$i++) {
					$points = $_POST['points'.$qsetid];
					$attempts = $_POST['attempts'.$qsetid];
					if ($points=='') { $points = 9999;}
					if ($attempts=='') {$attempts = 9999;}
					$query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,regen,showans,questionsetid) ";
					$query .= "VALUES ('$aid','$points','$attempts',9999,0,0,'$qsetid')";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					$qid = mysql_insert_id();
					if ($newitemorder=='') {
						$newitemorder = $qid;
					} else {
						if (isset($_POST['addasgroup'])) {
							$newitemorder = $newitemorder . "~$qid";
						} else {
							$newitemorder = $newitemorder . ",$qid";
						}
					}
				}
			}
			if ($itemorder == '') {
				$itemorder = $newitemorder;
			} else {
				$itemorder .= ','.$newitemorder;
			}
			
			$query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		} else if (isset($_POST['mod'])) { //modifying existing
			foreach(explode(',',$_POST['qids']) as $qid) {
				$points = $_POST['points'.$qid];
				$attempts = $_POST['attempts'.$qid];
				if ($points=='') { $points = 9999;}
				if ($attempts=='') {$attempts = 9999;}
				$query = "UPDATE imas_questions SET points='$points',attempts='$attempts' WHERE id='$qid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		
	} else {
		$pagetitle = "Question Settings";
		require("../header.php");
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
		echo "&gt; <a href=\"addquestions.php?aid=$aid&cid=$cid\">Add/Remove Questions</a> &gt; ";
		
		echo "Question Settings</div>\n";
	
?>
<h2>Question Settings</h2>
<p>For more advanced settings, modify the settings for individual questions after adding.
<?php
if (isset($_POST['checked'])) { //modifying existing
	echo "<form method=post action=\"addquestions.php?modqs=true&process=true&cid=$cid&aid=$aid\">";
} else {
	echo "<form method=post action=\"addquestions.php?addset=true&process=true&cid=$cid&aid=$aid\">";
}
?>
Leave items blank or set to 9999 to use default values<BR>
<table class=gb>
<thead><tr>
<?php
		if (isset($_POST['checked'])) { //modifying existing questions
			echo "<th>Description><th>Points</th><th>Attempts (0 for unlimited)</th></tr></thead>";
			echo "<tbody>";

			$qids = array();
			foreach ($_POST['checked'] as $k=>$v) {
				$v = explode(':',$v);
				$qids[] = $v[1];
			}
			$query = "SELECT imas_questions.id,imas_questionset.description,imas_questions.points,imas_questions.attempts ";
			$query .= "FROM imas_questions,imas_questionset WHERE imas_questionset.id=imas_questions.questionsetid AND ";
			$query .= "imas_questions.id IN ('".implode("','",$qids)."')";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if ($row[2]==9999) {
					$row[2] = '';
				} 
				if ($row[3]==9999) {
					$row[3] = '';
				}
				echo '<tr><td>'.$row[1].'</td>';
				echo "<td><input type=text size=4 name=\"points{$row[0]}\" value=\"{$row[2]}\" /></td>";
				echo "<td><input type=text size=4 name=\"attempts{$row[0]}\" value=\"{$row[3]}\" /></td>";
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<input type=hidden name="qids" value="'.implode(',',$qids).'" />';
			echo '<input type=hidden name="mod" value="true" />';
			
			echo '<div class=submit><input type=submit value=Submit></div>';
			
		} else { //adding new questions
			echo "<th>Description><th>Points</th><th>Attempts (0 for unlimited)</th><th>Number of Copies to Add</th></tr></thead>";
			echo "<tbody>";
			
			$query = "SELECT id,description FROM imas_questionset WHERE id IN ('".implode("','",$_POST['nchecked'])."')";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				echo '<tr><td>'.$row[1].'</td>';
				echo "<td><input type=text size=4 name=\"points{$row[0]}\" value=\"\" /></td>";
				echo "<td><input type=text size=4 name=\"attempts{$row[0]}\" value=\"\" /></td>";
				echo "<td><input type=text size=4 name=\"copies{$row[0]}\" value=\"1\" /></td>";
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<input type=hidden name="qsetids" value="'.implode(',',$_POST['nchecked']).'" />';
			echo '<input type=hidden name="add" value="true" />';
			
			echo '<p><input type=checkbox name="addasgroup" value="1" /> Add as group?</p>';
			echo '<div class=submit><input type=submit value=Submit></div>';
		}
		echo '</form>';
		require("../footer.php");
		exit;
	}
?>
