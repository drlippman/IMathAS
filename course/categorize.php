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
	$('select optgroup[label=Custom]').append('<option value="'+name+'">'+name+'</option>');
	document.getElementById("newcat").value='';
}
function quickpick() {
	$('select.qsel').each(function() {
		if ($(this).val()==0) {
			$(this).find('optgroup[label=Libraries] option:first').prop('selected',true);
		}
	});
}

function massassign() {
	var val = $('#masssel').val();
	$('input:checked').each(function() {
		var n = $(this).attr('id').substr(1);
		$('#'+n).val(val);
	});
}

function resetcat() {
	if (confirm("Are you SURE you want to reset all categories to Uncategorized/Default?")) {
		$('select.qsel').val(0);
	}
}
</script>
END;
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; <a href=\"addquestions.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> &gt; Categorize Questions</div>\n";
	
	$query = "SELECT id,name FROM imas_outcomes WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$outcomenames = array();
	while ($row = mysql_fetch_row($result)) {
		$outcomenames[$row[0]] = $row[1];
	}
	$query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	if ($row[0]=='') {
		$outcomearr = array();
	} else {
		$outcomearr = unserialize($row[0]);
	}
	
	$outcomes = array();
	function flattenarr($ar) {
		global $outcomes;
		foreach ($ar as $v) {
			if (is_array($v)) { //outcome group
				$outcomes[] = array($v['name'], 1);
				flattenarr($v['outcomes']);
			} else {
				$outcomes[] = array($v, 0);
			}
		}
	}
	flattenarr($outcomearr);
	
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
	
	$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	$itemarr = explode(',',$row[0]);
	foreach ($itemarr as $k=>$v) {
		if (($p=strpos($v,'~'))!==false) {
			$itemarr[$k] = substr($v,$p+1);
		}
	}
	$itemarr = implode(',',$itemarr);
	$itemarr = str_replace('~',',',$itemarr);
	$itemarr = explode(',', $itemarr);
	
	echo '<div id="headercategorize" class="pagetitle"><h2>Categorize Questions</h2></div>';
	echo "<form method=post action=\"categorize.php?aid=$aid&cid=$cid&record=true\">";
	echo 'Check: <a href="#" onclick="$(\'input[type=checkbox]\').prop(\'checked\',true);return false;">All</a> ';
	echo '<a href="#" onclick="$(\'input[type=checkbox]\').prop(\'checked\',false);return false;">None</a>';
	echo '<table class="gb"><thead><tr><th></th><th>Description</th><th>Category</th></tr></thead><tbody>';
	
	foreach($itemarr as $qid) {
		echo "<tr><td><input type=\"checkbox\" id=\"c$qid\"/></td>";
		echo "<td>{$descriptions[$qid]}</td><td>";
		echo "<select id=\"$qid\" name=\"$qid\" class=\"qsel\">";
		echo "<option value=\"0\" ";
		if ($category[$qid] == 0) { echo "selected=1";}
		echo ">Uncategorized or Default</option>\n";
		if (count($outcomes)>0) {
			echo '<optgroup label="Outcomes"></optgroup>';
		}
		$ingrp = false;
		$issel = false;
		foreach ($outcomes as $oc) {
			if ($oc[1]==1) {//is group
				if ($ingrp) { echo '</optgroup>';}
				echo '<optgroup label="'.htmlentities($oc[0]).'">';
				$ingrp = true;
			} else {
				echo '<option value="'.$oc[0].'" ';
				if ($category[$qid] == $oc[0]) { echo "selected=1"; $issel = true;}
				echo '>'.$outcomenames[$oc[0]].'</option>';
			}
		}
		if ($ingrp) { echo '</optgroup>';}
		echo '<optgroup label="Libraries">';
		foreach ($questionlibs[$qid] as $qlibid) {
			echo "<option value=\"{$libnames[$qlibid]}\" ";
			if ($category[$qid] == $libnames[$qlibid] && !$issel) { echo "selected=1"; $issel= true;}
			echo ">{$libnames[$qlibid]}</option>\n";
		}
		echo '</optgroup><optgroup label="Custom">';
		foreach ($extracats as $cat) {
			echo "<option value=\"$cat\" ";
			if ($category[$qid] == $cat && !$issel) { echo "selected=1";$issel = true;}
			echo ">$cat</option>\n";
		}
		echo '</optgroup>';
		echo "</select></td></tr>\n";
	}
	echo "</tbody></table>\n";
	if (count($outcomes)>0) {
		echo '<p>Apply outcome to selected: <select id="masssel">';
		$ingrp = false;
		$issel = false;
		foreach ($outcomes as $oc) {
			if ($oc[1]==1) {//is group
				if ($ingrp) { echo '</optgroup>';}
				echo '<optgroup label="'.htmlentities($oc[0]).'">';
				$ingrp = true;
			} else {
				echo '<option value="'.$oc[0].'">'.$outcomenames[$oc[0]].'</option>';
			}
		}
		if ($ingrp) { echo '</optgroup>';}	
		echo '</select> <input type="button" value="Assign" onclick="massassign()"/></p>';
		
	}
	echo "<p>Select first listed library for all uncategorized questions: <input type=button value=\"Quick Pick\" onclick=\"quickpick()\"></p>\n";
	
	echo "<p>Add new category to lists: <input type=type id=\"newcat\" size=40> ";
	echo "<input type=button value=\"Add Category\" onclick=\"addcategory()\"></p>\n";
	echo '<p><input type=submit value="Record Categorizations"> and return to the course page.  <input type="button" value="Reset" onclick="resetcat()"/></p>';
	echo "</form>\n";
	
	
				
?>
